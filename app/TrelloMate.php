<?php

namespace GeeksAreForLife\TrelloMate;

use cli\Table;
use cli\Colors;
use Trello\Client;
use cli\Arguments;

class TrelloMate
{
    private $debug = false;

    private $defaultConfig = __DIR__.'/defaultConfig.json';
    private $localConfig = __DIR__.'/config.json';

    private $config;

    private $moduleDir = __DIR__.'/modules';

    private $commands = [
        'help'        => [
                'short'        => 'Display the help for a command',
                'long'         => "Displays the help for a command.\n\n  Usage:\n  php trellomate [--debug] help <command>",
                'module'       => '',
            ],
    ];

    private $outputColors = [
        'eol'       => '%n',
        'info'      => '',
        'warning'   => '%Y',
        'error'     => '%R',
        'debug'     => '',
    ];

    const MSG_INFO = 0;
    const MSG_WARN = 1;
    const MSG_ERR = 2;
    const MSG_DEBUG = 3;

    public function __construct($debug = false)
    {
        $this->outputColors['debug'] = Colors::color(['color' => 'white', 'style' => 'bright', 'background' => 'blue']);

        $arguments = new Arguments();
        $arguments->addFlag(['debug', 'd'], 'Turn on debug output');

        $arguments->parse();

        if ($arguments['debug']) {
            $this->debug = $debug;
        }

        $this->loadConfig();
        $this->loadModules();

        // are we actually setup?
        $this->debug('Checking Setup');
        if ($this->checkSetup()) {
            // setup had to run, give option of continuing command
            if (!$this->yesno('Would you like to contine with your command')) {
                die;
            }
        }

        $commands = $arguments->getInvalidArguments();

        if (count($commands) == 1) {
            $this->process($commands[0]);
        } elseif (count($commands) == 2 && $commands[0] == 'help') {
            $this->showHelp($commands[1]);
        } else {
            $this->showHelp();
        }
    }

    public function debug($msg)
    {
        if ($this->debug) {
            $this->msg($msg, self::MSG_DEBUG);
        }
    }

    public function msg($msg, $type = self::MSG_INFO)
    {
        $output = '';

        if ($type == self::MSG_INFO) {
            $output .= $this->outputColors['info'];
        } elseif ($type == self::MSG_WARN) {
            $output .= $this->outputColors['warning'];
        } elseif ($type == self::MSG_ERR) {
            $output .= $this->outputColors['error'];
        } elseif ($type == self::MSG_DEBUG) {
            $output .= $this->outputColors['debug'];
        }

        $output .= $msg;

        $output .= $this->outputColors['eol'];

        \cli\line($output);
    }

    public function question($question, $default = false)
    {
        return \cli\prompt($question, $default);
    }

    public function yesno($question, $default = 'y')
    {
        $choice = \cli\choose($question, 'yn', $default);
        if ($choice == 'y') {
            return true;
        } else {
            return false;
        }
    }

    private function process($command)
    {
        $this->debug('Processing '.$command);

        $command = strtolower($command);

        // should we deal with this internally?
        if ($command == 'help') {
            $this->showHelp();
        } else {
            // lookup module
            if (!isset($this->commands[$command])) {
                $this->msg('Invalid command', self::MSG_ERR);
            } else {
                $module = new $this->commands[$command]['module']($this, $this->config);
                $this->debug("Passing to module ".$this->commands[$command]['module']);
                $module->execute($command);
            }
        }
    }

    private function loadConfig()
    {
        $this->config = new Config();
        $this->config->load($this->localConfig, $this->defaultConfig);
    }

    private function loadModules()
    {
        foreach (new \DirectoryIterator($this->moduleDir) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            } else {
                $this->commands = array_merge($this->commands, $this->loadModule($fileInfo->getBasename('.php')));
            }
        }
    }

    private function loadModule($module)
    {
        require_once $this->moduleDir.'/'.$module.'.php';

        if (class_exists($module)) {
            return $module::getCommands();
        } else {
            return [];
        }
    }

    private function checkSetup()
    {
        $setupRan = false;

        $apikey = $this->config->getValue('trello.apikey');
        $token = $this->config->getValue('trello.token');

        if ($apikey === false || $token === false) {
            $this->setup($apikey, $token);
            $apikey = $this->config->getValue('trello.apikey');
            $token = $this->config->getValue('trello.token');

            $setupRan = true;
        }

        $passed = $this->testConnection($apikey, $token);
        if ($passed === true) {
            $this->debug('Setup validated');
        } else {
            $this->msg('Connection to Trello not valid', self::MSG_ERR);
            $this->msg($passed."\n");

            $this->config->setValue('trello.apikey', false);
            $this->config->setValue('trello.token', false);

            $setupRan = $this->checkSetup();
        }

        return $setupRan;
    }

    private function setup($apikey, $token)
    {
        $this->msg('Trello setup not complete', self::MSG_WARN);
        $this->msg("You need to enter an API key and Token, which can be found at\nhttps://trello.com/app-key\n");
        $apikey = $this->question('API key', $apikey);
        $token = $this->question('Token', $token);

        $this->config->setValue('trello.apikey', $apikey);
        $this->config->setValue('trello.token', $token);

        $this->config->save();

        $this->msg("\nSaved, testing connection...");
    }

    private function testConnection($apikey, $token)
    {
        $client = new Client();
        $client->authenticate($apikey, $token, Client::AUTH_URL_CLIENT_ID);

        try {
            $boards = $client->api('member')->boards()->all('me');

            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    private function showHelp($command = null)
    {
        $indent = '  ';

        if ($command) {
            $this->debug('Help for '.$command);

            $this->msg('Usage: php trellomate [--debug] '.$command);
            $this->msg('');
            $this->msg($this->commands[$command]['long']);

        } else {
            $this->debug("General Help");

            $this->msg('Usage: php trellomate [--debug] <command>');
            $this->msg('');

            $this->msg($indent.'COMMANDS');

            // work out the longest command, so we can line it all up
            $commands = [];
            $maxLength = 0;
            foreach ($this->commands as $command => $info) {
                $maxLength = strlen($command) > $maxLength ? strlen($command) : $maxLength;
                $commands[] = [$command, $info['short']];
            }

            foreach ($commands as $command) {
                $this->msg($indent.str_pad($command[0], $maxLength).$indent.$indent.$command[1]);
            }
        }
    }
}

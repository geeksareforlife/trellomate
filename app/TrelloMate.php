<?php

namespace GeeksAreForLife\TrelloMate;

use cli\Arguments;
use Trello\Client;

class TrelloMate
{
    private $defaultConfig = __DIR__.'/defaultConfig.json';
    private $localConfig = __DIR__.'/config.json';

    private $config;

    private $output;

    private $client;

    private $moduleDir = __DIR__.'/modules';

    private $commands = [
        'internal'  => [
            'help'        => [
                'short'        => 'Display the help for a command',
                'long'         => "Displays the help for a command.\n\n  Usage:\n  php trellomate [--debug] help <command>",
                'module'       => '',
            ],
        ],
    ];

    public function __construct()
    {
        $arguments = new Arguments();
        $arguments->addFlag(['debug', 'd'], 'Turn on debug output');

        $arguments->parse();

        $debug = $arguments['debug'] ? true : false;
        $this->output = new Output($debug);

        $this->loadConfig();
        $this->loadModules();

        // are we actually setup?
        $this->output->debug('Checking Setup');
        if ($this->checkSetup()) {
            // setup had to run, give option of continuing command
            if (!$this->output->yesno('Would you like to contine with your command')) {
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

    private function process($command)
    {
        $this->output->debug('Processing '.$command);

        if (strpos($command, ':')) {
            list($namespace, $command) = explode(":", strtolower($command));
        } else {
            $namespace = 'internal';
            $command = strtolower($command);
        }

        // should we deal with this internally?
        if ($namespace == 'internal' && $command == 'help') {
            $this->showHelp();
        } else {
            // lookup module
            if (!isset($this->commands[$namespace][$command])) {
                $this->output->msg('Invalid command', Output::MSG_ERR);
            } else {
                $module = new $this->commands[$namespace][$command]['module']($this->client, $this->output, $this->config);
                $this->output->debug('Passing to module '.$this->commands[$namespace][$command]['module']);
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
            $this->output->debug('Setup validated');
        } else {
            $this->output->msg('Connection to Trello not valid', Output::MSG_ERR);
            $this->output->msg($passed."\n");

            $this->config->setValue('trello.apikey', false);
            $this->config->setValue('trello.token', false);

            $setupRan = $this->checkSetup();
        }

        return $setupRan;
    }

    private function setup($apikey, $token)
    {
        $this->output->msg('Trello setup not complete', Output::MSG_WARN);
        $this->output->msg("You need to enter an API key and Token, which can be found at\nhttps://trello.com/app-key\n");
        $apikey = $this->output->question('API key', $apikey);
        $token = $this->output->question('Token', $token);

        $this->config->setValue('trello.apikey', $apikey);
        $this->config->setValue('trello.token', $token);

        $this->config->save();

        $this->output->msg("\nSaved, testing connection...");
    }

    private function testConnection($apikey, $token)
    {
        $this->client = new Client();
        $this->client->authenticate($apikey, $token, Client::AUTH_URL_CLIENT_ID);

        try {
            $boards = $this->client->api('member')->boards()->all('me');

            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    private function showHelp($command = null)
    {
        $indent = '  ';

        if ($command) {
            $this->output->debug('Help for '.$command);

            $this->output->msg('Usage: php trellomate [--debug] '.$command);
            $this->output->msg('');
            $this->output->msg($this->commands[$command]['long']);
        } else {
            $this->output->debug('General Help');

            $this->output->msg('Usage: php trellomate [--debug] <command>');
            $this->output->msg('');

            $this->output->msg($indent.'COMMANDS');

            // work out the longest command, so we can line it all up
            $outputCommands = [];
            $maxLength = 0;
            foreach ($this->commands as $namespace => $commands) {
                foreach ($commands as $command => $info) {
                    $fullCommand = $namespace == 'internal' ? $command : $namespace.':'.$command;
                    $maxLength = strlen($fullCommand) > $maxLength ? strlen($fullCommand) : $maxLength;
                    $outputCommands[] = [$fullCommand, $info['short']];
                }
            }

            foreach ($outputCommands as $command) {
                $this->output->msg($indent.str_pad($command[0], $maxLength).$indent.$indent.$command[1]);
            }
        }
    }
}

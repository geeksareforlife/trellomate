<?php

use GeeksAreForLife\TrelloMate\Module;

class Base extends Module
{
    private $trello;
    private $output;
    private $config;

    public function __construct(&$trello, &$output, &$config)
    {
        $this->trello = $trello;
        $this->output = $output;
        $this->config = $config;
    }

    public function getCommands()
    {
        $commands = [
            'base' => [
                'version'    => [
                    'short'        => 'Outputs the script version',
                    'long'         => 'Outputs the script version',
                    'module'       => 'Base',
                ],
            ],
        ];

        return $commands;
    }

    public function execute($command)
    {
        $this->output->debug('executing');
        if ($command == 'version') {
            $this->showVersion();
        }
    }

    private function showVersion()
    {
        $version = $this->config->getValue('version');

        $this->output->msg('TrelloMate version '.$version);
        $this->output->msg('https://github.com/geeksareforlife/trellomate');
    }
}

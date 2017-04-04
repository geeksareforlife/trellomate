<?php

use GeeksAreForLife\TrelloMate\Module;

class Base extends Module
{
    private $trello;
    private $config;

    public function __construct(&$trello, &$config)
    {
        $this->trello = $trello;
        $this->config = $config;
    }

    public function getCommands()
    {
        $commands = [
            'version'    => [
                'short'        => 'Outputs the script version',
                'long'         => 'Outputs the script version',
                'module'       => 'Base',
            ],
        ];

        return $commands;
    }

    public function execute($command)
    {
        $this->trello->debug('executing');
        if ($command == 'version') {
            $this->showVersion();
        }
    }

    private function showVersion()
    {
        $version = $this->config->getValue('version');

        $this->trello->msg('TrelloMate version '.$version);
        $this->trello->msg('https://github.com/geeksareforlife/trellomate');
    }
}

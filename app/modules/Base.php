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

    public function execute($command, $arguments)
    {
    }
}

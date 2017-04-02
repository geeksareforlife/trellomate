<?php

use GeeksAreForLife\TrelloMate\Module;

class Base extends Module
{
    public function __construct()
    {
    }

    public function getCommands()
    {
        $commands = [
            'version'    => [
                'short'        => 'Outputs the script version',
                'long'         => 'Outputs the script version',
                'module'       => 'Base',
            ],
            'help'        => [
                'short'        => 'Display the help for a command',
                'long'         => "Displays the help for a command.\n\nUsage:\nphp trellomate help <command>",
                'module'       => 'Base',
            ],
        ];

        return $commands;
    }

    public function execute($command, $arguments)
    {
    }
}

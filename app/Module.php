<?php

namespace GeeksAreForLife\TrelloMate;

abstract class Module
{
    abstract public function __construct(&$trello, &$output, &$config);

    abstract public function getCommands();

    abstract public function execute($command);
}

<?php

namespace GeeksAreForLife\TrelloMate;

abstract class Module
{
	abstract public function getCommands();

	abstract public function execute($command, $arguments);
}
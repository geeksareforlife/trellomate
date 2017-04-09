<?php

use GeeksAreForLife\TrelloMate\Module;

class ScrumLite extends Module
{
    private $trello;
    private $output;
    private $config;

    private $moduleName = 'scrumlite';

    public function __construct(&$trello, &$output, &$config)
    {
        $this->trello = $trello;
        $this->output = $output;
        $this->config = $config;
    }

    public function getCommands()
    {
        $commands = [
            'scrumlite'    => [
                'new' => [
                    'short'        => 'Creates new project',
                    'long'         => 'Saves the boards that make up a ScrummLite project',
                    'module'       => 'ScrumLite',
                ],
            ],
        ];

        return $commands;
    }

    public function execute($command)
    {
        if ($command == 'new') {
            $this->setupNewProject();
        }
    }

    private function setupNewProject()
    {
        $productId = $this->trello->chooseBoard('Which board do you use for your Product Backlog?', $this->output);
        $releaseId = $this->trello->chooseBoard('Which board do you use for your Release Backlog?', $this->output);
        $sprintId = $this->trello->chooseBoard('Which board do you use for your Sprint?', $this->output);

        $saveName = $this->output->question('Project name');

        $baseKey = $this->createBaseKey($saveName);

        if ($this->config->getValue($baseKey, $this->moduleName) !== false) {
            if ($this->output->yesno('Project already exists, overwrite', 'n')) {
                return;
            }
        }

        $this->config->setValue($baseKey.'.product', $productId, $this->moduleName);
        $this->config->setValue($baseKey.'.release', $releaseId, $this->moduleName);
        $this->config->setValue($baseKey.'.sprint', $sprintId, $this->moduleName);

        $this->config->save();
    }

    private function createBaseKey($saveName)
    {
        $saveName = str_replace(' ', '-', $saveName);
        $saveName = str_replace('.', '_', $saveName);

        return 'projects.'.$saveName;
    }

    private function keyToName($key)
    {
        $key = str_replace('-', ' ', $key);
        $key = str_replace('_', '.', $key);

        return $key;
    }
}

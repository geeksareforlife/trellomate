<?php

use GeeksAreForLife\Utilities\Arrays;
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
                    'short'     => 'Creates new project',
                    'long'      => 'Saves the boards that make up a ScrummLite project',
                    'module'    => 'ScrumLite',
                ],
                'release' => [
                    'short'     => 'Prepares Release Backlog',
                    'long'      => 'Moves items from Product Backlog to Release Backlog based on labels',
                    'module'    => 'ScrumLite',
                ],
                'sprint' => [
                    'short'     => 'Prepares Sprint',
                    'long'      => 'Moves items from Release Backlog to Sprint based on labels',
                    'module'    => 'ScrumLite',
                ],
            ],
        ];

        return $commands;
    }

    public function execute($command)
    {
        if ($command == 'new') {
            $this->setupNewProject();
        } elseif ($command == 'release') {
            $this->prepareRelease();
        } elseif ($command == 'sprint') {
            $this->prepareSprint();
        }
    }

    private function setupNewProject()
    {
        $productId = $this->trello->chooseBoard('Which board do you use for your Product Backlog?', $this->output);
        $releaseId = $this->trello->chooseBoard('Which board do you use for your Release Backlog?', $this->output);
        $releaseList = $this->trello->chooseList('What is the list name to put incoming taks in (Release)?', $releaseId, $this->output);
        $sprintId = $this->trello->chooseBoard('Which board do you use for your Sprint?', $this->output);
        $sprintList = $this->trello->chooseList('What is the list name to put incoming taks in (Sprint)?', $sprintId, $this->output);

        $saveName = $this->output->question('Project name');

        $baseKey = $this->createBaseKey($saveName);

        if ($this->config->getValue($baseKey, $this->moduleName) !== false) {
            if ($this->output->yesno('Project already exists, overwrite', 'n')) {
                return;
            }
        }

        $this->config->setValue($baseKey.'.product', $productId, $this->moduleName);
        $this->config->setValue($baseKey.'.release', $releaseId, $this->moduleName);
        $this->config->setValue($baseKey.'.releaselist', $releaseList, $this->moduleName);
        $this->config->setValue($baseKey.'.sprint', $sprintId, $this->moduleName);
        $this->config->setValue($baseKey.'.sprintlist', $sprintList, $this->moduleName);

        $this->config->save();
    }

    private function prepareRelease()
    {
        // What project are we working on?
        $projects = $this->config->getValue('projects', $this->moduleName);

        $projectList = [];
        foreach ($projects as $key => $boards) {
            $projectList[$key] = $this->keyToName($key);
        }

        $project = $this->output->selectFromList($projectList, 'Which project?');

        // get the config for that project
        // we should have:
        // config = [
        //   product     => boardID,
        //   release     => boardID,
        //   releaselist => listID,
        //   sprint      => boardID,
        //   sprintlist  => listID
        // ]
        $projectConfig = $projects[$project];

        // What label are we going to look for?
        $labels = $this->trello->getLabels($projectConfig['product']);
        $labelId = '';
        foreach ($labels as $label) {
            if ($label['name'] == 'Release') {
                $labelId = $label['id'];
                break;
            }
        }

        $this->moveCards($projectConfig['product'], $projectConfig['releaselist'], $labelId);
    }

    public function prepareSprint()
    {
        // What project are we working on?
        $projects = $this->config->getValue('projects', $this->moduleName);

        $projectList = [];
        foreach ($projects as $key => $boards) {
            $projectList[$key] = $this->keyToName($key);
        }

        $project = $this->output->selectFromList($projectList, 'Which project?');

        // get the config for that project
        // we should have:
        // config = [
        //   product     => boardID,
        //   release     => boardID,
        //   releaselist => listID,
        //   sprint      => boardID,
        //   sprintlist  => listID
        // ]
        $projectConfig = $projects[$project];

        // What label are we going to look for?
        $labels = $this->trello->getLabels($projectConfig['release']);
        $labelId = '';
        foreach ($labels as $label) {
            if ($label['name'] == 'Sprint') {
                $labelId = $label['id'];
                break;
            }
        }

        $this->moveCards($projectConfig['release'], $projectConfig['sprintlist'], $labelId);
    }

    private function moveCards($fromBoard, $toList, $byLabelId)
    {
        // Find all the cards with our label, sort and sanitise
        $cards = $this->trello->getCardsByListWithLabel($fromBoard, $byLabelId);
        $cards = $this->orderCardsIntoOneList($cards);
        $cards = Arrays::sanitiseArrayList($cards, ['id', 'name', 'desc']);

        // Create the cards in the "incoming" list of the release board
        // TODO - need to take the NEW description
        $this->trello->copyCardsToList($cards, $toList);

        // archive the old cards
        $this->trello->archiveCards($cards);
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

    private function orderCardsIntoOneList($cardsByList, $addArea = true)
    {
        $cards = [];

        $areas = array_keys($cardsByList);

        // what is the largest list?
        $count = 0;
        foreach ($areas as $area) {
            $count = count($cardsByList[$area]) > $count ? count($cardsByList[$area]) : $count;
        }

        for ($i = 0; $i < $count; $i++) {
            foreach ($areas as $area) {
                if (isset($cardsByList[$area][$i])) {
                    $card = $cardsByList[$area][$i];

                    if ($addArea) {
                        $card['desc'] = 'Product Area: **'.$area."**\n\n".$card['desc'];
                    }

                    $cards[] = $card;
                }
            }
        }

        return $cards;
    }
}

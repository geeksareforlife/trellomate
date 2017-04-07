<?php

use GeeksAreForLife\TrelloMate\Module;

class Packing extends Module
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
            'packing'    => [
                'reset' => [
                    'short'        => 'Removes all labels from a board',
                    'long'         => 'Removes all the labels from a user-selected board',
                    'module'       => 'Packing',
                ],
            ],
        ];

        return $commands;
    }

    public function execute($command)
    {
        if ($command == "reset") {
            $this->resetPackingBoard();
        }
    }

    private function resetPackingBoard()
    {
        $boardId = $this->trello->chooseBoard($this->output);
        $cards = $this->trello->getCards($boardId);
        foreach ($cards as $card) {
            $this->trello->removeLabels($card);
        }

        $this->output->msg('All labels removed');
    }
}

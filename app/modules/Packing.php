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
        if ($command == 'reset') {
            $this->resetPackingBoard();
        }
    }

    private function resetPackingBoard()
    {
        $boardId = $this->trello->chooseBoard('Choose a board to reset', $this->output);
        $cards = $this->trello->getCards($boardId);

        $this->output->msg('');
        $progressBar = $this->output->progress('Removing from '.count($cards).' cards', count($cards));

        foreach ($cards as $card) {
            $this->trello->removeLabels($card);
            $progressBar->tick();
        }

        $progressBar->finish();

        $this->output->msg('All labels removed');
    }
}

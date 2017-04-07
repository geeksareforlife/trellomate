<?php

namespace GeeksAreForLife\TrelloMate;

use Trello\Client;
use GeeksAreForLife\TrelloMate\Shims\IdLabels;

class Trello
{
    private $client;

    public function __construct($apikey, $token)
    {
        $this->client = new Client();
        $this->client->authenticate($apikey, $token, Client::AUTH_URL_CLIENT_ID);
    }

    public function testConnection()
    {
        try {
            $boards = $this->client->api('member')->boards()->all('me');

            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function chooseBoard($msg, &$output)
    {
        $boards = $this->client->members()->boards()->all('me');
        $boardList = [];
        foreach ($boards as $board) {
            $boardList[$board['id']] = $board['name'];
        }

        return $output->selectFromList($boardList, $msg);
    }

    public function getCards($boardId)
    {
        $cards = $this->client->boards()->cards()->all($boardId);

        return $cards;
    }

    public function removeLabels($card)
    {
        $idLabels = new IdLabels($this->client);
        foreach ($card['idLabels'] as $labelId) {
            $idLabels->remove($card['id'], $labelId);
        }
    }
}

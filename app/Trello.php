<?php

namespace GeeksAreForLife\TrelloMate;

use Trello\Client;
use GeeksAreForLife\TrelloMate\Shims\IdLabels;

class Trello
{
    private $client;

    private $cache = [];

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

    public function chooseBoard($msg, &$output, $useCache = true)
    {
        if (!$useCache || !isset($this->cache['boards'])) {
            $this->cache['boards'] = $this->client->members()->boards()->all('me');
        }
        $boardList = [];
        foreach ($this->cache['boards'] as $board) {
            $boardList[$board['id']] = $board['name'];
        }

        return $output->selectFromList($boardList, $msg);
    }

    public function chooseList($msg, $boardId, &$output, $useCache = true)
    {
        if (!$useCache || !isset($this->cache['lists'][$boardId])) {
            $this->cache['lists'][$boardId] = $this->client->boards()->lists()->all($boardId);
        }
        $lists = [];
        foreach ($this->cache['lists'][$boardId] as $list) {
            $lists[$list['id']] = $list['name'];
        }

        return $output->selectFromList($lists, $msg);
    }

    public function getCards($boardId)
    {
        return $this->client->boards()->cards()->all($boardId);
    }

    public function removeLabels($card)
    {
        $idLabels = new IdLabels($this->client);
        foreach ($card['idLabels'] as $labelId) {
            $idLabels->remove($card['id'], $labelId);
        }
    }

    public function getCardsByList($boardId)
    {
        $lists = $this->client->boards()->lists()->all($boardId);

        $cards = [];

        foreach ($lists as $list) {
            $cards[$list['name']] = $this->client->lists()->cards()->all($list['id']);
        }

        return $cards;
    }

    public function getCardsByListWithLabel($boardId, $labelId)
    {
        $allCards = $this->getCardsByList($boardId);

        $cardsWithLabel = [];

        foreach ($allCards as $list => $cards) {
            $listCards = [];
            foreach ($cards as $card) {
                if (in_array($labelId, $card['idLabels'])) {
                    $listCards[] = $card;
                }
            }
            if (count($listCards) > 0) {
                $cardsWithLabel[$list] = $listCards;
            }
        }

        return $cardsWithLabel;
    }

    public function getLabels($boardId)
    {
        return $this->client->boards()->labels()->all($boardId);
    }

    public function getCheckListsforCards($cards)
    {
        $newCards = [];
        foreach ($cards as $card) {
            $checklists = $this->getCheckListsforCard($card);
            $card['checklists'] = $checklists;
            $newCards[] = $card;
        }

        return $newCards;
    }

    public function getCheckListsforCard($card)
    {
        $checklists = $this->client->cards()->checklists()->all($card['id']);

        return $checklists;
    }

    public function copyCardsToList($cards, $listId)
    {
        foreach ($cards as $card) {
            if (!isset($card['id']) or !isset($card['name'])) {
                // not enough
                continue;
            }
            $newCard = [
                'name'              =>  $card['name'],
                'idCardSource'      =>  $card['id'],
                'keepFromSource'    =>  'attachments,checklists,comments,due,members,stickers',
                'idList'            =>  $listId
            ];
            $this->client->cards()->create($newCard);
        }
    }

    public function archiveCards($cards) {
        foreach ($cards as $card) {
            if (!isset($card['id'])) {
                continue;
            }
            $params = [
                'closed'    =>  'true'
            ];
            $this->client->cards()->update($card['id'], $params);
        }
    }
}

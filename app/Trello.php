<?php

namespace GeeksAreForLife\TrelloMate;

use Trello\Client;

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

}

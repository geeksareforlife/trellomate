<?php

namespace GeeksAreForLife\TrelloMate\Shims;

use Trello\Api\AbstractApi;

class IdLabels extends AbstractApi
{
    protected $path = 'cards/#id#/idLabels';

    /**
     * Remove a given label from a given card.
     *
     * @link https://trello.com/docs/api/card/#delete-1-cards-card-id-or-shortlink-labels-color
     *
     * @param string $id      the card's id or short link
     * @param string $idLabel the id of the label to remove
     *
     * @throws InvalidArgumentException If a label does not exist
     *
     * @return array card info
     */
    public function remove($id, $idLabel)
    {
        return $this->delete($this->getPath($id).'/'.$idLabel);
    }
}

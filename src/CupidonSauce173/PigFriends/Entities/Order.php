<?php

namespace CupidonSauce173\PigFriends\Entities;

use CupidonSauce173\PigFriends\FriendsLoader;

class Order
{
    private bool $mysql;
    private array $inputs;
    private int $event;

    /**
     * Method to execute the order (must be called at the end).
     */
    function execute(): void
    {
        FriendsLoader::getInstance()->container['multiFunctionQueue'][] = $this;
    }

    /**
     * Method to tell if the order has SQL interactions or to see if the order has SQL interactions.
     * @param bool|null $value
     * @return bool|null
     */
    function isSQL(bool $value = null): ?bool
    {
        if ($value === null) return $this->mysql;
        $this->mysql = $value;
        return null;
    }

    /**
     * Method to set the event that the order will request in the MultiFunctionThread.
     * @param int $event
     */
    function setCall(int $event): void
    {
        $this->event = $event;
    }

    /**
     * Method to see which event the order holds.
     * @return int
     */
    function getCall(): int
    {
        return $this->event;
    }

    /**
     * Method to see what inputs the order holds.
     * @return array
     */
    function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * Method to set the inputs of the order (data), must be an array.
     * @param array $inputs
     */
    function setInputs(array $inputs): void
    {
        $this->inputs = $inputs;
    }
}
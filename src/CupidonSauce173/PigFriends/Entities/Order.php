<?php

namespace CupidonSauce173\PigFriends\Entities;

use CupidonSauce173\PigFriends\FriendsLoader;

class Order
{
    private bool $mysql;
    private array $inputs;
    private int $event;

    function execute(): void
    {
        FriendsLoader::getInstance()->container['multiFunctionQueue'][] = $this;
    }

    /**
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
     * @param array $inputs
     */
    function setInputs(array $inputs): void
    {
        $this->inputs = $inputs;
    }

    /**
     * @param int $event
     */
    function setCall(int $event): void
    {
        $this->event = $event;
    }

    /**
     * @return int
     */
    function getCall(): int
    {
        return $this->event;
    }

    /**
     * @return array
     */
    function getInputs(): array
    {
        return $this->inputs;
    }
}
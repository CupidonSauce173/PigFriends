<?php

namespace CupidonSauce173\PigFriends\Entities;

use CupidonSauce173\PigFriends\FriendsLoader;

class Order
{
    private ?string $id = null;
    private bool $mysql = false;
    private array $inputs = [];
    private ?int $event = null;


    /**
     * Method to execute the order (must be called at the end).
     * @param bool $isOrderListener
     * @return string|null
     */
    function execute(bool $isOrderListener = false): ?string
    {
        $this->id = uniqid();
        if ($isOrderListener) {
            FriendsLoader::getInstance()->container['orderListener'][$this->id] = $this;
        }
        FriendsLoader::getInstance()->container['multiFunctionQueue'][$this->id] = $this;
        return null;
    }

    /**
     * Method to get the ID of the order object.
     * @return ?string
     */
    function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Method to tell if the order has SQL interactions.
     * @param bool $value
     * @return bool|null
     */
    function isSQL(bool $value = false): ?bool
    {
        if ($value === false) return $this->mysql;
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
     * Method to see which event the order will call.
     * @return int
     */
    function getCall(): ?int
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
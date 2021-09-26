<?php


namespace CupidonSauce173\PigFriends\Entities;

use DateTime;

class Request
{
    private int $id;
    private string $target;
    private string $sender;
    private DateTime $creationDate;
    private bool $accepted;

    /**
     * @param int $id
     */
    function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int|null
     */
    function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param string $target
     */
    function setTarget(string $target): void
    {
        $this->target = $target;
    }

    /**
     * @return string|null
     */
    function getTarget(): ?string
    {
        return $this->target;
    }

    /**
     * @param string $sender
     */
    function setSender(string $sender): void
    {
        $this->sender = $sender;
    }

    /**
     * @return string|null
     */
    function getSender(): ?string
    {
        return $this->sender;
    }

    /**
     * @param DateTime $dateTime
     */
    function setCreationDate(DateTime $dateTime): void
    {
        $this->creationDate = $dateTime;
    }

    /**
     * @return DateTime
     */
    function getCreationDate(): DateTime
    {
        return $this->creationDate;
    }

    /**
     * @return bool
     */
    function isAccepted(): bool
    {
        return $this->accepted;
    }

    /**
     * @param bool $accepted
     */
    function setAccepted(bool $accepted = true): void
    {
        $this->accepted = $accepted;
    }
}
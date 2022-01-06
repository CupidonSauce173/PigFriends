<?php


namespace CupidonSauce173\PigFriends\Entities;

use DateTime;
use Volatile;

class Request extends Volatile
{
    private int $id;
    private string $target;
    private string $sender;
    private string $senderUsername;
    private DateTime $creationDate;
    private bool $accepted;

    /**
     * Returns the Id of the request.
     * @return int|null
     */
    function getId(): ?int
    {
        return $this->id;
    }

    /**
     * To se the id of the request.
     * @param int $id
     */
    function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Returns the uuid of the target of this request.
     * @return string|null
     */
    function getTarget(): ?string
    {
        return $this->target;
    }

    /**
     * To set the uuid of the target of this request.
     * @param string $target
     */
    function setTarget(string $target): void
    {
        $this->target = $target;
    }

    /**
     * Returns the request sender uuid.
     * @return string|null
     */
    function getSender(): ?string
    {
        return $this->sender;
    }

    /**
     * To set the request sender uuid.
     * @param string $sender
     */
    function setSender(string $sender): void
    {
        $this->sender = $sender;
    }

    /**
     * Returns the username of the request's sender.
     * @return string|null
     */
    function getSenderUsername(): ?string
    {
        return $this->senderUsername;
    }

    /**
     * To set the username of the request's sender.
     * @param string $username
     */
    function setSenderUsername(string $username): void
    {
        $this->senderUsername = $username;
    }

    /**
     * Returns the dateTime of the request.
     * @return DateTime
     */
    function getCreationDate(): DateTime
    {
        return $this->creationDate;
    }

    /**
     * To set the creation date of the request.
     * @param DateTime $dateTime
     */
    function setCreationDate(DateTime $dateTime): void
    {
        $this->creationDate = $dateTime;
    }

    /**
     * Returns if the request has been accepted or not.
     * @return bool
     */
    function isAccepted(): bool
    {
        return $this->accepted;
    }

    /**
     * To set the state of the request.
     * @param bool $accepted
     */
    function setAccepted(bool $accepted = true): void
    {
        $this->accepted = $accepted;
    }
}
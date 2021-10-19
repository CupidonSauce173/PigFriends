<?php


namespace CupidonSauce173\PigFriends\Entities;

use CupidonSauce173\PigFriends\FriendsLoader;
use function strtolower;

class Friend
{
    const ALL_FRIENDS = 0;
    const ONLY_FAVORITES = 1;
    const NOBODY = 2;
    private array $friends = [];
    private array $favorites = [];
    private array $blocked = [];
    private array $requestSent = [];
    private string $player;
    private bool $notifyState;
    private bool $requestState;
    private int $joinMessage;

    function isFavorite(string $friend): bool
    {
        if (array_search($friend, $this->favorites) !== false) {
            return true;
        }
        return false;
    }

    function getJoinSetting(): int
    {
        return $this->joinMessage;
    }

    function setJoinSetting(int $state): void
    {
        $this->joinMessage = $state;
    }

    function getNotifyState(): bool
    {
        return $this->notifyState;
    }

    function setNotifyState(bool $state): void
    {
        $this->notifyState = $state;
    }

    function getRequestState(): bool
    {
        return $this->requestState;
    }

    function setRequestState(bool $state): void
    {
        $this->requestState = $state;
    }

    /**
     * Sets the player settings directly from the query.
     * @param bool $requestState
     * @param bool $notifyState
     * @param bool $joinMessage
     */
    function setRawSettings(bool $requestState, bool $notifyState, bool $joinMessage): void
    {
        $this->notifyState = $notifyState;
        $this->requestState = $requestState;
        $this->joinMessage = $joinMessage;
    }

    /**
     * Returns all the requests sent by the player.
     * @return array
     */
    function getRequestSent(): array
    {
        return $this->requestSent;
    }

    /**
     * Set all the requests sent by the player.
     * @param array $value
     */
    function setAllRequestSent(array $value): void
    {
        $this->requestSent = $value;
    }

    /**
     * Add a request in the requestSent list.
     * @param string $value
     */
    function addRequestSent(string $value): void
    {
        $this->requestSent[] = $value;
    }

    /**
     * Remove a request sent by the player.
     * @param string $value
     */
    function removeRequestSent(string $value): void
    {
        if (!isset($this->requestSent[$value])) return;
        unset($this->requestSent[$value]);
    }

    /**
     * Returns all the friends of the player.
     * @return array
     */
    function getFriends(): array
    {
        return $this->friends;
    }

    /**
     * Returns all the blocked players that the player blocked.
     * @return array
     */
    function getBlocked(): array
    {
        return $this->blocked;
    }

    /**
     * Returns the player username of the Friend.
     * @return string
     */
    function getPlayer(): string
    {
        return $this->player;
    }

    /**
     * Set the player username.
     * @param string $user
     */
    function setPlayer(string $user): void
    {
        $this->player = $user;
    }

    /**
     * Add a friend as favorite.
     * @param string $target Friend to set as favorite.
     * @return bool
     */
    function addFavorite(string $target): bool
    {
        if (isset($this->favorites[$target])) return false;
        $this->favorites[] = $target;
        return true;
    }

    /**
     * Remove a friend from the favorites.
     * @param string $target Friend to remove from the favorites
     * @return bool
     */
    function removeFavorite(string $target): bool
    {
        if (isset($this->favorites[$target])) {
            unset($this->favorites[$target]);
            return true;
        }
        return false;
    }

    /**
     * Add a player to the blocked list.
     * @param string $target The player to block
     * @return bool
     */
    function blockPlayer(string $target): bool
    {
        if (isset($this->blocked[$target])) return false;
        $this->blocked[] = $target;
        return true;
    }

    /**
     * Remove a player from the blocked list.
     * @param string $target The player to unblock.
     * @return bool
     */
    function unblockPlayer(string $target): bool
    {
        if (isset($this->blocked[$target])) {
            unset($this->blocked[$target]);
            return true;
        }
        return false;
    }

    /**
     * Add a player to a friend list.
     * @param string $target The player to add
     * @return bool
     */
    function addFriend(string $target): bool
    {
        if (isset($this->friends[$target])) return false;
        $this->friends[] = $target;
        return true;
    }

    /**
     * Remove a friend from a friend list.
     * @param string $target The player to remove
     * @return bool
     */
    function removeFriend(string $target): bool
    {
        if (isset($this->friends[$target])) {
            unset($this->friends[$target]);
            return true;
        }
        return false;
    }

    /**
     * Returns all the requests targeted to that player.
     * @return array|null
     */
    function getRequests(): ?array
    {
        if (!isset(FriendsLoader::getInstance()->container['requests'][strtolower($this->player)])) return null;
        return FriendsLoader::getInstance()->container['requests'][strtolower($this->player)];
    }
}
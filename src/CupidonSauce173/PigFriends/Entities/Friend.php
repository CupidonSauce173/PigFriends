<?php


namespace CupidonSauce173\PigFriends\Entities;

use CupidonSauce173\PigFriends\FriendsLoader;

use function strtolower;

class Friend
{
    private array $friends = [];
    private array $favorites = [];
    private array $blocked = [];
    private array $settings = [];
    private array $requestSent = [];
    private string $player;

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
        if(!isset($this->requestSent[$value])) return;
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
     * Sets the player settings directly from the query.
     * @param array $settings
     */
    function setRawSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * Returns the settings of the players as array.
     * @return array
     */
    function getRawSettings(): array
    {
        return $this->settings;
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
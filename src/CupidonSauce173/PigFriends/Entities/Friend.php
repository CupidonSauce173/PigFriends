<?php


namespace CupidonSauce173\PigFriends\Entities;

use CupidonSauce173\PigFriends\FriendsLoader;
use Threaded;
use function strtolower;

class Friend extends Threaded
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

    /**
     * Method to know if a friend (by username) is set as favorite.
     * @param string $friend
     * @return bool
     */
    function isFavorite(string $friend): bool
    {
        if (array_search($friend, $this->favorites) !== false) {
            return true;
        }
        return false;
    }

    /**
     * Method to get if the player receives a message when one of their friends joins the server.
     * @return int
     */
    function getJoinSetting(): int
    {
        return $this->joinMessage;
    }

    /**
     * Method to set if the player receives a message when one of t heir friends joins the server.
     * @param int $state
     */
    function setJoinSetting(int $state): void
    {
        $this->joinMessage = $state;
    }

    /**
     * Method to know if the notification setting has been set to true.
     * @return bool
     */
    function getNotifyState(): bool
    {
        return $this->notifyState;
    }

    /**
     * Method to set true or false the notification setting.
     * @param bool $state
     */
    function setNotifyState(bool $state): void
    {
        $this->notifyState = $state;
    }

    /**
     * Method to get the request setting.
     * @return bool
     */
    function getRequestState(): bool
    {
        return $this->requestState;
    }

    /**
     * Method to set the request setting.
     * @param bool $state
     */
    function setRequestState(bool $state): void
    {
        $this->requestState = $state;
    }

    /**
     * Method to set the player settings directly from the query.
     * @param bool $requestState
     * @param bool $notifyState
     * @param int $joinMessage
     */
    function setRawSettings(bool $requestState, bool $notifyState, int $joinMessage): void
    {
        $this->notifyState = $notifyState;
        $this->requestState = $requestState;
        $this->joinMessage = $joinMessage;
    }

    /**
     * Method to return all the requests sent by the player.
     * @return array
     */
    function getRequestSent(): array
    {
        return $this->requestSent;
    }

    /**
     * Method to set all the requests sent by the player.
     * @param array $value
     */
    function setAllRequestSent(array $value): void
    {
        $this->requestSent = $value;
    }

    /**
     * Method to add a request in the requestSent list.
     * @param string $value
     */
    function addRequestSent(string $value): void
    {
        $this->requestSent[] = $value;
    }

    /**
     * Method to remove a request sent by the player.
     * @param string $value
     */
    function removeRequestSent(string $value): void
    {
        if (!isset($this->requestSent[$value])) return;
        unset($this->requestSent[$value]);
    }

    /**
     * Method to return all the friends of the player.
     * @return array
     */
    function getFriends(): array
    {
        return $this->friends;
    }

    /**
     * Method to return all the blocked players that the player blocked.
     * @return array
     */
    function getBlocked(): array
    {
        return $this->blocked;
    }

    /**
     * Method to return the player username of the Friend.
     * @return string
     */
    function getPlayer(): string
    {
        return $this->player;
    }

    /**
     * Method to set the player username.
     * @param string $username
     */
    function setPlayer(string $username): void
    {
        $this->player = $username;
    }

    /**
     * Method to add a friend as favorite.
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
     * Method to remove a friend from the favorites.
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
     * Method to add a player to the blocked list.
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
     * Method to remove a player from the blocked list.
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
     * Method to add a player to a friend list.
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
     * Method to remove a friend from a friend list.
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
     * Method to return all the requests targeted to that player.
     * @return array|null
     */
    function getRequests(): ?array
    {
        if (!isset(FriendsLoader::getInstance()->container['requests'][strtolower($this->player)])) return null;
        return FriendsLoader::getInstance()->container['requests'][strtolower($this->player)];
    }
}
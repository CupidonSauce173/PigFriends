<?php


namespace CupidonSauce173\PigFriends\Entities;

use CupidonSauce173\PigFriends\FriendsLoader;
use Volatile;

class Friend extends Volatile
{
    const ALL_FRIENDS = 0;
    const ONLY_FAVORITES = 1;
    const NOBODY = 2;
    private array $friends = [];
    private array $favorites = [];
    private array $blocked = [];
    private array $requestSent = [];
    private string $uuid; # is now the uniqueId instead of username
    private bool $notifyState;
    private bool $requestState;
    private int $joinMessage;

    function handleOrderProtection(int $order): bool
    {
        return false;
    }

    /**
     * Method to know if a friend (by username) is set as favorite.
     * @param string $friend
     * @return bool
     */
    function isFavorite(string $friend): bool
    {
        if($this->findArrayInDataArray($this->favorites, $friend) == null) return false;
        return true;
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
     * Method to return the player uuid of the Friend.
     * @return string
     */
    function getPlayer(): string
    {
        return $this->uuid;
    }

    /**
     * Method to set the player username.
     * @param string $uuid
     */
    function setPlayer(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * Method to add a friend as a favorite.
     * @param string $target Friend to set as a favorite.
     * @return bool
     */
    function addFavorite(string $target): bool
    {
        $friendData = $this->findArrayInDataArray($this->friends, $target);
        if ($friendData === null) {
            return false;
        }
        $this->favorites[] = $friendData;
        return true;
    }

    /**
     * Method to remove a friend from the favorites.
     * @param string $targetUuid Friend to remove from the favorites.
     * @return bool
     */
    function removeFavorite(string $targetUuid): bool
    {
        return $this->unsetArrayElementByUsername($this->favorites, $targetUuid);
    }

    /**
     * Method to add a player to the blocked list.
     * @param string $targetUuid The player UUID to block.
     * @param string $targetUsername The player username to block.
     * @return bool
     */
    function blockPlayer(string $targetUuid, string $targetUsername): bool
    {
        $blockedData = $this->findArrayInDataArray($this->blocked, $targetUuid);
        if ($blockedData !== null) return false;
        $this->blocked[] = [$targetUuid, $targetUsername];
        return true;
    }

    /**
     * Method to remove a player from the blocked list.
     * @param string $targetUuid The player UUID to unblock.
     * @return bool
     */
    function unblockPlayer(string $targetUuid): bool
    {
        return $this->unsetArrayElementByUuid($this->blocked, $targetUuid);
    }

    /**
     * Method to add a player to a friend list.
     * @param string $targetUuid The player UUID to add.
     * @param string $friendUsername The player username.
     * @return bool
     */
    function addFriend(string $targetUuid, string $friendUsername): bool
    {
        $friendData = $this->findArrayInDataArray($this->friends, $targetUuid);
        if ($friendData !== null) return false;
        $this->friends[] = [$targetUuid, $friendUsername];
        return true;
    }

    /**
     * Method to remove a friend from a friend list.
     * @param string $targetUuid The target UUID to remove.
     * @return bool
     */
    function removeFriend(string $targetUuid): bool
    {
        return $this->unsetArrayElementByUuid($this->friends, $targetUuid);
    }

    /**
     * Method to return all the requests targeted to that player.
     * @return array|null
     */
    function getRequests(): ?array
    {
        $requests = [];
        /** @var Request $request */
        foreach (FriendsLoader::getInstance()->container['requests'] as $request) {
            if ($request->getTarget() === $this->uuid) {
                $requests[] = $request;
            }
        }
        if (empty($requests)) return null;
        return $requests;
    }


    private function findArrayInDataArray($data, $target)
    {
        foreach ((array)$data as $subArray) {
            if (in_array($target, (array)$subArray)) return $subArray;
        }
        return null;
    }

    private function unsetArrayElementByUuid(&$volatileArray, $uuidToRemove): bool
    {
        foreach ($volatileArray as $key => $object) {
            if ($object[0] === $uuidToRemove) {
                unset($volatileArray[$key]);
                return true;
            }
        }
        return false;
    }

    private function unsetArrayElementByUsername(&$volatileArray, $usernameToRemove): bool
    {
        foreach ($volatileArray as $key => $object) {
            if ($object[1] === $usernameToRemove) {
                unset($volatileArray[$key]);
                return true;
            }
        }
        return false;
    }

}
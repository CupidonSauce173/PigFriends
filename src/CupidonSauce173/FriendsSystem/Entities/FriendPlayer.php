<?php


namespace CupidonSauce173\FriendsSystem\Entities;

use CupidonSauce173\FriendsSystem\FriendsLoader;
use pocketmine\Player;

use function strtolower;

class FriendPlayer
{
    private array $friends;
    private array $favorites;
    private array $blocked;
    private Player $player;

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
     * Returns the player object of the FriendPlayer.
     * @return Player
     */
    function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * Add a friend as favorite.
     * @param string $target Friend to set as favorite.
     * @return bool
     */
    function addFavorite(string $target): bool
    {
        if (isset($this->favoriteList[$target])) return false;
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
        if (isset($this->favoriteList[$target])) {
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
        if (isset($this->friendList[$target])) return false;
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
        if (isset($this->friendList[$target])) {
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
        if(!isset(FriendsLoader::getInstance()->objectContainer['requests'][strtolower($this->player->getName())])) return null;
        return FriendsLoader::getInstance()->objectContainer['requests'][strtolower($this->player->getName())];
    }
}
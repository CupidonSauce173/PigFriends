<?php


namespace CupidonSauce173\PigFriends\Utils;

use CupidonSauce173\PigFriends\FriendsLoader;
use CupidonSauce173\PigFriends\Entities\Friend;

use pocketmine\Player;

class Api
{
    /**
     * To get the PlayerFriend object out of a username of an online player.
     * @param string $target Username of the player
     * @return Friend|null
     */
    function getFriendPlayerByName(string $target): ?Friend
    {
        if (isset(FriendsLoader::getInstance()->objectContainer['friends'][$target])) {
            return FriendsLoader::getInstance()->objectContainer['friends'][$target];
        }
        return null;
    }

    /**
     * Gets a Friend object from a Player object.
     * @param Player $target
     * @return Friend|null
     */
    function getFriendPlayer(Player $target): ?Friend
    {
        /** @var Friend $friend */
        foreach (FriendsLoader::getInstance()->container['friends'] as $friend) {
            if ($friend->getPlayer() === $target) return $friend;
        }
        return null;
    }
}
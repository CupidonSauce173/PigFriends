<?php


namespace CupidonSauce173\PigFriends\Utils;

use CupidonSauce173\PigFriends\FriendsLoader;
use CupidonSauce173\PigFriends\Entities\Friend;

class Api
{
    /**
     * Gets a Friend object from a username
     * @param string $target
     * @return Friend|null
     */
    function getFriendPlayer(string $target): ?Friend
    {
        /** @var Friend $friend */
        foreach (FriendsLoader::getInstance()->container['friends'] as $friend) {
            if ($friend->getPlayer() === $target) return $friend;
        }
        return null;
    }
}
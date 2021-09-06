<?php


namespace CupidonSauce173\FriendsSystem\Utils;

use CupidonSauce173\FriendsSystem\FriendsLoader;
use CupidonSauce173\FriendsSystem\Entities\FriendPlayer;
use pocketmine\Player;

class Api
{
    /**
     * To get the PlayerFriend object out of a username of an online player.
     * @param string $target Username of the player
     * @return FriendPlayer|null
     */
    function getFriendPlayerByName(string $target) : ?FriendPlayer
    {
        if(isset(FriendsLoader::getInstance()->objectContainer['friends'][$target])){
            return FriendsLoader::getInstance()->objectContainer['friends'][$target];
        }
        return null;
    }

    /**
     * Gets a FriendPlayer object from a Player object.
     * @param Player $target
     * @return FriendPlayer|null
     */
    function getFriendPlayer(Player $target) : ?FriendPlayer
    {
        /** @var FriendPlayer $friend */
        foreach(FriendsLoader::getInstance()->objectContainer['friends'] as $friend)
        {
            if($friend->getPlayer() === $target) return $friend;
        }
        return null;
    }
}
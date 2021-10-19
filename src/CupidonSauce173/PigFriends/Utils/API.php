<?php


namespace CupidonSauce173\PigFriends\Utils;


use CupidonSauce173\PigFriends\Entities\Friend;
use CupidonSauce173\PigFriends\FriendsLoader;
use function str_replace;

class Translation
{
    /**
     * Will translate a langKey from the langKey.ini to a readable message for the players.
     * @param string $message
     * @param array|null $langKey
     * @return string|null
     */
    static function Translate(string $message, array $langKey = null): ?string
    {
        if (!isset(FriendsLoader::getInstance()->container['langKeys'][$message])) return null;
        $text = FriendsLoader::getInstance()->container['langKeys'][$message];
        if ($langKey !== null) {
            $text = str_replace($langKey[0], $langKey[1], $text);
        }
        return $text;
    }
}

class FriendAPI
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
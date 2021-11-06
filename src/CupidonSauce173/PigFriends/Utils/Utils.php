<?php


namespace CupidonSauce173\PigFriends\Utils;


use CupidonSauce173\PigFriends\Entities\Friend;
use CupidonSauce173\PigFriends\FriendsLoader;
use function str_replace;

class Utils
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
            foreach ($langKey as $item => $value) {
                $text = str_replace('{' . $item . '}', $value, $text);
            }
        }
        return $text;
    }

    /**
     * Gets a Friend entity from a username
     * @param string $target
     * @return Friend|null
     */
    static function getFriendPlayer(string $target): ?Friend
    {
        /** @var Friend $friend */
        foreach (FriendsLoader::getInstance()->container['friends'] as $friend) {
            if ($friend->getPlayer() === $target) return $friend;
        }
        return null;
    }

    /**
     * Add a friend entity to the list of friends in the container.
     * @param Friend $friend
     */
    static function addFriendPlayer(Friend $friend): void
    {
        FriendsLoader::getInstance()->container['friends'][] = $friend;
    }

    /**
     * Remove a friend entity from the list of friends in the container.
     * @param Friend $friend
     */
    static function removeFriendPlayer(Friend $friend): void
    {
        if (isset(FriendsLoader::getInstance()->container['friends'][$friend])) {
            unset(FriendsLoader::getInstance()->container['friends'][$friend]);
        }
    }
}
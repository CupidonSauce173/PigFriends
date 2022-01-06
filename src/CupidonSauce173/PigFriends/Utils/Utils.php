<?php


namespace CupidonSauce173\PigFriends\Utils;


use CupidonSauce173\PigFriends\Entities\Friend;
use CupidonSauce173\PigFriends\FriendsLoader;
use function array_search;
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
     * Gets a Friend entity from a UniqueId string
     * @param string $targetUniqueId
     * @return Friend
     */
    static function getFriendEntity(string $targetUniqueId): ?Friend
    {
        /** @var Friend $friend */
        foreach (FriendsLoader::getInstance()->container['friends'] as $index => $friend) {
            if ($friend->getPlayer() === $targetUniqueId) {
                return $friend;
            }
        }
        return null;
    }

    /**
     * Add a friend entity to the list of friends in the container.
     * @param Friend $friend
     */
    static function addFriendEntity(Friend $friend): void
    {
        FriendsLoader::getInstance()->container['friends'][] = $friend;
    }

    /**
     * Remove a friend entity from the list of friends in the container.
     * @param Friend $friend
     */
    static function removeFriendEntity(Friend $friend): void
    {
        $index = self::getFriendEntityIndex($friend);
        if ($index !== false) {
            unset(FriendsLoader::getInstance()->container['friends'][$index]);
        }
    }

    /**
     * @param Friend $friend
     * @return false|int|string
     */
    static function getFriendEntityIndex(Friend $friend)
    {
        $friends = (array)FriendsLoader::getInstance()->container['friends'];
        return array_search($friend, $friends);
    }
}
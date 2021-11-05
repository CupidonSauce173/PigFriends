<?php


namespace CupidonSauce173\PigFriends\Utils;


use CupidonSauce173\PigFriends\Entities\Friend;
use CupidonSauce173\PigFriends\Entities\Request;
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
            foreach($langKey as $item => $value){
                $text = str_replace( '{' . $item . '}', $value, $text);
            }
        }
        return $text;
    }

    /**
     * Gets a Friend object from a username
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
     * @param Friend $friend
     */
    static function addFriendPlayer(Friend $friend): void
    {
        FriendsLoader::getInstance()->container['friends'][] = $friend;
    }

    /**
     * @param Friend $friend
     */
    static function removeFriendPlayer(Friend $friend): void
    {
        if (isset(FriendsLoader::getInstance()->container['friends'][$friend])) {
            unset(FriendsLoader::getInstance()->container['friends'][$friend]);
        }
    }

    /**
     * @param string $author
     * @param string $sender
     * @return bool
     */
    static function requestExists(string $author, string $sender): bool
    {
        /** @var Request $request */
        foreach(FriendsLoader::getInstance()->container['requests'] as $request){
            if($request->getSender() === $author && $request->getTarget() === $sender) return true;
        }
        return false;
    }
}
<?php

namespace CupidonSauce173\PigFriends;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

class EventsListener implements Listener
{
    function onJoin(PlayerJoinEvent $event): void
    {
        FriendsLoader::getInstance()->container['players'][] = $event->getPlayer()->getName();
    }

    function onLeave(PlayerQuitEvent $event): void
    {
        $index = array_search($event->getPlayer()->getName(), FriendsLoader::getInstance()->container['players']);
        unset(FriendsLoader::getInstance()->container['players'][$index]);
    }
}
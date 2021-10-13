<?php

namespace CupidonSauce173\PigFriends;

use CupidonSauce173\PigFriends\Entities\Order;
use CupidonSauce173\PigFriends\Threads\MultiFunctionThread;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

class EventsListener implements Listener
{
    function onJoin(PlayerJoinEvent $event): void
    {
        FriendsLoader::getInstance()->container['players'][] = $event->getPlayer()->getName();

        # Will create a new Friend object.
        $order = new Order();
        $order->isSQL(true);
        $order->setInputs([$event->getPlayer()->getName()]);
        $order->setCall(MultiFunctionThread::CREATE_FRIEND_ENTITY);
        $order->execute();
    }

    function onLeave(PlayerQuitEvent $event): void
    {
        $index = array_search($event->getPlayer()->getName(), FriendsLoader::getInstance()->container['players']);
        unset(FriendsLoader::getInstance()->container['players'][$index]);
    }

    function onHit(EntityDamageEvent $event)
    {

    }
}
<?php

namespace CupidonSauce173\PigFriends;

use CupidonSauce173\PigFriends\Entities\Order;
use CupidonSauce173\PigFriends\Threads\MultiFunctionThread;
use CupidonSauce173\PigFriends\Utils\Utils;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

class EventsListener implements Listener
{
    /**
     * @param PlayerJoinEvent $event
     */
    function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer()->getName();
        FriendsLoader::getInstance()->container['players'][] = $player;

        # Will create a new Friend object.
        $order = new Order();
        $order->isSQL(true);
        $order->setInputs([$player]);
        $order->setCall(MultiFunctionThread::CREATE_FRIEND_ENTITY);
        $order->execute();
    }

    /**
     * @param PlayerQuitEvent $event
     */
    function onLeave(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer()->getName();
        if (!isset(FriendsLoader::getInstance()->container['players'][$player])) return;
        unset(FriendsLoader::getInstance()->container['players'][$player]);
        Utils::removeFriendPlayer(Utils::getFriendPlayer($player));
    }

    /**
     * @param EntityDamageEvent $event
     */
    function onHit(EntityDamageEvent $event)
    {
        /*
         * TODO: Implement friendly fire (on/off).
         */
    }
}
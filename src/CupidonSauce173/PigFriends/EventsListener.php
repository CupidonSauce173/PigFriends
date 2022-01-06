<?php

namespace CupidonSauce173\PigFriends;

use CupidonSauce173\PigFriends\Entities\Order;
use CupidonSauce173\PigFriends\Threads\MultiFunctionThread;
use CupidonSauce173\PigFriends\Utils\Utils;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use function in_array;

class EventsListener implements Listener
{
    /*
     * 1. Add a constructor to register the friendly-fire feature.
     * 2. Finish the EntityDamageEvent for the friendly-fire feature.
     */

    /**
     * @param PlayerJoinEvent $event
     */
    function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $playerUuid = $player->getUniqueId()->toString();
        FriendsLoader::getInstance()->container['players'][] = $playerUuid;

        # Will create a new Friend object.
        $order = new Order();
        $order->isSQL(true);
        $order->setInputs([$playerUuid, $player->getName()]);
        $order->setCall(MultiFunctionThread::CREATE_FRIEND_ENTITY);
        $order->execute();
    }

    /**
     * @param PlayerQuitEvent $event
     */
    function onLeave(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer()->getUniqueId()->toString();
        $friend = Utils::getFriendEntity($player);
        if (!in_array($player, (array)FriendsLoader::getInstance()->container['players'])) return;
        unset(FriendsLoader::getInstance()->container['players'][$player]);
        Utils::removeFriendEntity($friend);
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
<?php

namespace CupidonSauce173\PigFriends\Tasks;

use CupidonSauce173\PigFriends\Entities\Order;
use CupidonSauce173\PigFriends\FriendsLoader;
use CupidonSauce173\PigFriends\Utils\ListenerConstants;
use CupidonSauce173\PigFriends\Utils\Utils;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use Ramsey\Uuid\UuidInterface;

class OrderListenerTask extends Task
{
    public function onRun(): void
    {
        var_dump(FriendsLoader::getInstance()->container['requests']);
        /*
         * This class will listen to any special state of the Order objects
         * Example, when there is a SEND_NEW_REQUEST order called and the order already exists,
         * it will create a special state where it needs to send the information to a player that the request
         * already exists.
         */
        /** @var Order $order */
        foreach (FriendsLoader::getInstance()->container['orderListener'] as $order) {
            $inputs = $order->getInputs();
            unset(FriendsLoader::getInstance()->container['orderListener'][$order->getId()]);
            switch ($order->getCall()) {
                case ListenerConstants::REQUEST_ALREADY_EXISTS:
                    $this->requestAlreadyExists($inputs[0], $inputs[1]);
                    break;
                case ListenerConstants::REQUEST_CREATED:
                    $this->requestCreated($inputs[0], $inputs[1]);
                    break;
                case ListenerConstants::USER_NOT_CREATED:
                    $this->userNotRegistered($inputs[0], $inputs[1]);
                    break;
                case ListenerConstants::UNKNOWN_ERROR:
                    $this->unknownError($inputs[0], $inputs[1], $inputs[2]);
                    break;
                case ListenerConstants::ORDER_PROTECTION:
                    $this->orderProtectionMessage($inputs[0], $inputs[1]);
            }
        }
    }

    /**
     * Will send an error message to the player informing them that the request already exists.
     * @param UuidInterface $uuid
     * @param string $receiver
     */
    private function requestAlreadyExists(UuidInterface $uuid, string $receiver): void
    {
        $player = FriendsLoader::getInstance()->getServer()->getPlayerByUUID($uuid);
        if ($player instanceof Player) {
            $player->sendMessage(Utils::Translate('error.already.already.sent', ['target' => $receiver]));
        }
    }

    /**
     * Will send a confirmation message that the request has been created.
     * @param UuidInterface $uuid
     * @param string $receiver
     */
    private function requestCreated(UuidInterface $uuid, string $receiver): void
    {
        $player = FriendsLoader::getInstance()->getServer()->getPlayerByUUID($uuid);
        if ($player instanceof Player) {
            $player->sendMessage(Utils::Translate('utils.request.sent', ['target' => $receiver]));
        }
    }

    /**
     * Notify the player that the target isn't registered in the database.
     * @param UuidInterface $uuid
     * @param string $target
     */
    private function userNotRegistered(UuidInterface $uuid, string $target): void
    {
        $player = FriendsLoader::getInstance()->getServer()->getPlayerByUUID($uuid);
        if ($player instanceof Player) {
            $player->sendMessage(Utils::Translate('error.player.not.registered', ['target' => $target]));
        }
    }

    /**
     * Notify the player that an unknown error occurred. Will show some information.
     * @param UuidInterface $uuid
     * @param string $target
     * @param int $methodCalled
     */
    private function unknownError(UuidInterface $uuid, string $target, int $methodCalled): void
    {
        $player = FriendsLoader::getInstance()->getServer()->getPlayerByUUID($uuid);
        if ($player instanceof Player) {
            $player->sendMessage(Utils::Translate('error.unknown', ['target' => $target, 'event' => (string)$methodCalled]));
        }
    }

    /**
     * Notify the player that they reached the max amount of times they can perform an action and must wait x amount of time before doing it again.
     * @param UuidInterface $uuid
     * @param int $nextReset
     */
    private function orderProtectionMessage(UuidInterface $uuid, int $nextReset): void
    {
        $player = FriendsLoader::getInstance()->getServer()->getPlayerByUUID($user);
        if ($player instanceof Player) {
            $player->sendMessage(Utils::Translate('error.order.protection', ['nextReset' => $nextReset]));
        }
    }
}
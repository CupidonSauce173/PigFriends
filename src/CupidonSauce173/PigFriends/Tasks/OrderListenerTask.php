<?php

namespace CupidonSauce173\PigFriends\Tasks;

use CupidonSauce173\PigFriends\Entities\Order;
use CupidonSauce173\PigFriends\FriendsLoader;
use CupidonSauce173\PigFriends\Utils\ListenerConstants;
use CupidonSauce173\PigFriends\Utils\Utils;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class OrderListenerTask extends Task
{

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick)
    {
        /*
         * This class will listen to any special state of the Order objects
         * Example, when there is a SEND_NEW_REQUEST order called and the order already exists,
         * it will create a special state where it needs to send the information to a player that the request
         * already exists.
         */
        /** @var Order $order */
        foreach(FriendsLoader::getInstance()->container['orderListener'] as $order){
            $inputs = $order->getInputs();
            unset(FriendsLoader::getInstance()->container['orderListener'][$order->getId()]);
            switch ($order->getCall()){
                case ListenerConstants::REQUEST_ALREADY_EXISTS:
                    $this->requestAlreadyExists($inputs[0], $inputs[1]);
                    break;
                case ListenerConstants::REQUEST_CREATED:
                    $this->requestCreated($inputs[0], $inputs[1]);
                    break;
            }
        }
    }

    /**
     * Will send a error message to the player informing them that the request already exists.
     * @param string $author
     * @param string $receiver
     */
    function requestAlreadyExists(string $author, string $receiver): void
    {
        $player = FriendsLoader::getInstance()->getServer()->getPlayer($author);
        if($player instanceof Player){
            $player->sendMessage(Utils::Translate('error.already.already.sent', ['target' => $receiver]));
        }
    }

    /**
     * Will send a confirmation message that the reqeust has been created.
     * @param string $author
     * @param string $receiver
     */
    function requestCreated(string $author, string $receiver): void
    {
        $player = FriendsLoader::getInstance()->getServer()->getPlayer($author);
        if($player instanceof Player){
            $player->sendMessage(Utils::Translate('utils.request.sent', ['target' => $receiver]));
        }
    }
}
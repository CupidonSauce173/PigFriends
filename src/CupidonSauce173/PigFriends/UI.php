<?php


namespace CupidonSauce173\PigFriends;

use CupidonSauce173\PigFriends\Entities\Friend;
use CupidonSauce173\PigFriends\Entities\Order;
use CupidonSauce173\PigFriends\Lib\FormAPI;
use CupidonSauce173\PigFriends\Threads\MultiFunctionThread;
use CupidonSauce173\PigFriends\Utils\Utils;
use pocketmine\Player;

class UI
{
    const SET_FAVORITE = 0;
    const UNSET_FAVORITE = 1;
    const REMOVE_FRIEND = 2;
    const BLOCK_PLAYER = 3;

    private array $pageContainer = [];

    private FormAPI $uiApi;

    public function __construct()
    {
        $this->uiApi = new FormAPI();
    }

    /**
     * Main page of the UI, will display all the options.
     * @param Player $player The player that receives the UI.
     */
    function mainUI(Player $player): void
    {
        $ui = $this->uiApi->createSimpleForm(function (Player $player, $data) {
            if ($data === null) return;
            $friend = Utils::getFriendEntity($player->getName());
            switch ($data) {
                case 0:
                    $this->addPage($player, $friend);
                    break;
                case 1:
                    $this->friendsPage($player, 0, $friend);
                    break;
                case 2:
                    $this->settingsPage($player, $friend);
                    break;
                case 3:
                    break;
            }
        });

        $ui->setTitle(Utils::Translate('ui.main.title'));
        $ui->setContent(Utils::Translate('ui.main.content'));
        $ui->addButton(Utils::Translate('ui.button.add.friend'));
        $ui->addButton(Utils::Translate('ui.button.friends'));
        $ui->addButton(Utils::Translate('ui.button.settings'));
        $ui->addButton(Utils::Translate('ui.button.close'));
        $ui->sendToPlayer($player);
    }

    /**
     * AddPage where the player can add a friend via username.
     * @param Player $player
     * @param Friend $friend The Friend object related to the player.
     */
    function addPage(Player $player, Friend $friend): void
    {
        $ui = $this->uiApi->createCustomForm(function (Player $player, $data) use ($friend) {
            if ($data === null) return;
            if (empty($data[0])) {
                $player->sendMessage(Utils::Translate('error.incorrect.input'));
                return;
            }
            if (isset($friend->getRequests()[$data[0]])) {
                $player->sendMessage(Utils::Translate('error.already.sent', ['target' => $data[0]]));
                return;
            }
            if(in_array(strtolower($data[0]), array_map('strtolower', (array)$friend->getFriends()))) {
                $player->sendMessage(Utils::Translate('error.already.friend', ['friend' => $data[0]]));
                return;
            }

            # Create a friend request.
            $order = new Order();
            $order->setCall(MultiFunctionThread::SEND_NEW_REQUEST);
            $order->isSQL(true);
            $order->setInputs([$player->getName(), $data[0]]);
            $order->execute();
        });
        $ui->setTitle(Utils::Translate('ui.main.title'));
        $ui->addInput(Utils::Translate('ui.addPage.input'));
        $ui->sendToPlayer($player);
    }

    /**
     * List of friends of the player.
     * @param Player $player The player that receives the UI.
     * @param int $page The current page of the player.
     * @param Friend $friend The Friend object related to the player.
     */
    function friendsPage(Player $player, int $page, Friend $friend): void
    {
        $friends = $friend->getFriends();
        if (!isset($friend->getFriends()[0])) {
            $player->sendMessage(Utils::Translate('error.no.friends'));
            return;
        }
        $name = $player->getName();
        if (!isset($this->pageContainer[$name])) {
            $this->pageContainer[$name] = ['page' => 0];
        }

        $limit = (int)FriendsLoader::getInstance()->container['config']['friend-per-page'];
        $start = $page * $limit; # If page = 0, start = 0, if page = 0, start = 10 (if friend-per-page: 10)
        $stop = $start + $limit; # If page = 0, stop = 10 (if friend-per-page: 10), if page = 1, stop = 20 (start = 10)

        $ui = $this->uiApi->createSimpleForm(function (Player $player, $data) use ($name, $friend, $start, $stop, $page) {
            if ($data === null) return;

            $realIndex = $data + $start;

            if (isset($friend->getFriends()[$realIndex])) {
                $this->selectedFriend($player, $friend, $friend->getFriends()[$realIndex]);
            } else {
                if ($this->pageContainer[$name]['remains']) {
                    $this->friendsPage($player, $page + 1, $friend);
                }
            }
        });
        $ui->setTitle(Utils::Translate('ui.main.title'));
        $ui->setContent(Utils::Translate('ui.friend.list.content'));

        $remains = true;
        for (; $start <= $stop; $start++) {
            if (isset($friends[$start])) {
                $ui->addButton($friends[$start]);
            } else {
                $remains = false;
                break;
            }
        }
        $this->pageContainer[$name]['remains'] = false;
        if ($remains) {
            $ui->addButton(Utils::Translate('ui.button.next'));
            $this->pageContainer[$name]['remains'] = true;
        }

        $ui->addButton(Utils::Translate('ui.button.close'));
        $ui->sendToPlayer($player);
    }

    /**
     * Page to show options for the selected friend from the friendsPage UI.
     * @param Player $player
     * @param Friend $friend
     * @param string $selectedFriend
     */
    function selectedFriend(Player $player, Friend $friend, string $selectedFriend): void
    {
        $isFavorite = $friend->isFavorite($selectedFriend);
        $ui = $this->uiApi->createSimpleForm(function (Player $player, $data) use ($friend, $selectedFriend, $isFavorite) {
            if ($data === null) return;
            switch ($data) {
                case 0:
                    if ($isFavorite) {
                        $this->confirmationPage($player, self::UNSET_FAVORITE, [$selectedFriend, $friend]);
                        break;
                    }
                    $this->confirmationPage($player, self::SET_FAVORITE, [$selectedFriend, $friend]);
                    break;
                case 1:
                    $this->confirmationPage($player, self::BLOCK_PLAYER, [$selectedFriend, $friend]);
                    break;
                case 2:
                    $this->confirmationPage($player, self::REMOVE_FRIEND, [$selectedFriend, $friend]);
                    break;
            }
        });
        $ui->setTitle($selectedFriend);
        $ui->setContent(Utils::Translate('ui.friend.content'));
        if ($isFavorite) {
            $ui->addButton(Utils::Translate('ui.button.unset.favorite'));
        } else {
            $ui->addButton(Utils::Translate('ui.button.set.favorite'));
        }
        $ui->addButton(Utils::Translate('ui.button.block'));
        $ui->addButton(Utils::Translate('ui.button.remove'));
        $ui->addButton(Utils::Translate('ui.button.close'));
        $ui->sendToPlayer($player);
    }

    /**
     * Confirmation page for multiple types of events.
     * @param Player $player The player that receives the UI.
     * @param int $event The event of the confirmation.
     * @param array|null $options The options of the event.
     */
    function confirmationPage(Player $player, int $event, array $options = null): void
    {
        $ui = $this->uiApi->createSimpleForm(function (Player $player, $data) use ($event, $options) {
            if ($data === null) return;

            if ($data === 0) {
                $order = new Order();
                switch ($event) {
                    case self::SET_FAVORITE:
                        $order->setCall(MultiFunctionThread::ADD_FAVORITE); #
                        $order->setInputs([$options[1], $options[0]]);
                        $player->sendMessage(Utils::Translate('utils.favorite.add.success', ['target' => $options[0]]));
                        break;
                    case self::UNSET_FAVORITE:
                        $order->setCall(MultiFunctionThread::REMOVE_FAVORITE); #
                        $order->setInputs([$options[1], $options[0]]);
                        $player->sendMessage(Utils::Translate('utils.favorite.remove.success', ['target' => $options[0]]));
                        break;
                    case self::REMOVE_FRIEND:
                        $order->setCall(MultiFunctionThread::REMOVE_FRIEND); #
                        $order->setInputs([$options[1], $options[0]]);
                        $player->sendMessage(Utils::Translate('utils.remove.success', ['target' => $options[0]]));
                        break;
                    case self::BLOCK_PLAYER:
                        $order->setCall(MultiFunctionThread::BLOCK_PLAYER); #
                        $order->setInputs([$options[1], $options[0]]);
                        $player->sendMessage(Utils::Translate('utils.block.success', ['target' => $options[0]]));
                        break;
                }
                $order->isSQL(true);
                $order->execute();
            }
        });
        $ui->setTitle(Utils::Translate('ui.confirmation.title'));
        $ui->setTitle(Utils::Translate('ui.confirmation.content'));
        $ui->addButton(Utils::Translate('ui.button.confirmation'));
        $ui->addButton(Utils::Translate('ui.button.close'));
        $ui->sendToPlayer($player);
    }

    /**
     * Settings page where the player can change few personal settings.
     * @param Player $player The player that receives the UI.
     * @param Friend $friend The Friend object related to the player.
     */
    function settingsPage(Player $player, Friend $friend): void
    {
        $ui = $this->uiApi->createCustomForm(function (Player $player, $data) use ($friend) {
            if ($data === null) return;

            $order = new Order();
            $order->setCall(MultiFunctionThread::UPDATE_USER_SETTINGS);
            $order->setInputs([$friend, [$data[0], $data[1], $data[2]]]);
            $order->execute();

            $player->sendMessage(Utils::Translate('utils.settings.updated'));
        });
        $ui->setTitle(Utils::Translate('ui.main.title'));
        $ui->addToggle(Utils::Translate('ui.settings.toggle.notify'), $friend->getNotifyState());
        $ui->addToggle(Utils::Translate('ui.settings.toggle.request'), $friend->getRequestState());
        $ui->addDropdown(Utils::Translate('ui.settings.content.notify'),
            [
                Utils::Translate('ui.settings.dropdown.never'),
                Utils::Translate('ui.settings.dropdown.favorites'),
                Utils::Translate('ui.settings.dropdown.all.friends')
            ], $friend->getJoinSetting());
        $ui->sendToPlayer($player);
    }
}
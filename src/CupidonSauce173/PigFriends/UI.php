<?php


namespace CupidonSauce173\PigFriends;

use CupidonSauce173\PigFriends\Entities\Friend;
use CupidonSauce173\PigFriends\Entities\Order;
use CupidonSauce173\PigFriends\Lib\FormAPI;
use CupidonSauce173\PigFriends\Threads\MultiFunctionThread;
use CupidonSauce173\PigFriends\Utils\Translation;
use pocketmine\Player;

class UI
{
    const SET_FAVORITE = 0;
    const UNSET_FAVORITE = 1;
    const REMOVE_FRIEND = 2;
    const BLOCK_PLAYER = 3;

    private array $pageContainer = []; # Contain the current page of the players using the UI.

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
            $friend = FriendsLoader::getInstance()->api->getFriendPlayer($player->getName());

            switch ($data) {
                case 0:
                    $this->addPage($player, $friend);
                    break;
                case 1:
                    $this->friendsPage($player, 1, $friend);
                    break;
                case 2:
                    $this->settingsPage($player, $friend);
                    break;
                case 3:
                    $this->helpPage($player, $friend);
                    break;
                case 4:
                    break;
            }
        });

        $ui->setTitle(Translation::Translate('ui.main.title'));
        $ui->setContent(Translation::Translate('ui.main.content'));
        $ui->addButton(Translation::Translate('ui.button.add.friend'));
        $ui->addButton(Translation::Translate('ui.button.friends'));
        $ui->addButton(Translation::Translate('ui.button.settings'));
        $ui->addButton(Translation::Translate('ui.button.help'));
        $ui->addButton(Translation::Translate('ui.button.close'));
        $ui->sendToPlayer($player);
    }

    /**
     * AddPage where the player can add a friend via username.
     * @param Player $player
     * @param Friend $friend The Friend object related to the player.
     */
    function addPage(Player $player, Friend $friend): void
    {
        # Needs to add the createCustomForm method in the lib.
        $ui = $this->uiApi->createCustomForm(function (Player $player, $data) use ($friend) {
            if ($data[0] == null) return;
            if (isset($friend->getRequests()[$data[0]])) {
                $player->sendMessage(Translation::Translate('error.already.sent', ['target' => $data[0]]));
                return;
            }

            # Create a friend request.
            $order = new Order();
            $order->setCall(MultiFunctionThread::SEND_NEW_REQUEST);
            $order->isSQL(true);
            $order->setInputs([$player->getName(), $data[0]]);
            $order->execute();

            $player->sendMessage(Translation::Translate('utils.request.sent', ['target' => $data[0]]));
        });
        $ui->setTitle(Translation::Translate('ui.main.title'));
        $ui->addInput(Translation::Translate('ui.addPage.input'));
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
        $name = $player->getName();
        if (!isset($this->pageContainer[$name])) {
            $this->pageContainer[$name] = ['page' => 0];
        }

        $friends = $friend->getFriends();
        $limit = (int)FriendsLoader::getInstance()->container['config']['friend-per-page'];
        $start = $limit * $page;

        for ($i = 0; $i <= $start; $i++) {
            unset($friends[$i]);
        }

        $ui = $this->uiApi->createSimpleForm(function (Player $player, $data) use ($name, $friend) {
            # Prepare close & next page places.
            $nextPage = null;
            $listCount = count($this->pageContainer[$name]['content']);
            if ($this->pageContainer[$name]['remains']) {
                $nextPage = $listCount + 1;
            }
            $close = $listCount + 1;
            if ($nextPage !== null) {
                $close = $nextPage + 1;
            }
            if ($data === $nextPage) {
                $this->friendsPage($player, $this->pageContainer[$name]['page'] + 1, $friend);
                return;
            }
            if ($data === $close) return;
            $this->selectedFriend($player, $friend, $data);
        });
        $ui->setTitle(Translation::Translate('ui.main.title'));

        $remains = true;
        for ($i = 0; $i < $limit; $i++) {
            if (isset($friends[$i])) {
                $ui->addButton($friends[$i]);
            } else {
                $remains = false;
                break;
            }
        }
        if ($remains) {
            $ui->addButton(Translation::Translate('ui.button.next'));
        }
        $this->pageContainer[$name]['content'] = $friends;
        $this->pageContainer[$name]['remains'] = $remains;
        $ui->addButton(Translation::Translate('ui.button.close'));
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
        $ui = $this->uiApi->createSimpleForm(function (Player $player, $data) use ($friend, $selectedFriend) {
            switch ($data) {
                case 0:
                    # Set friend as favorite
                    if ($friend->isFavorite($selectedFriend)) {
                        $this->confirmationPage($player, self::UNSET_FAVORITE, $friend, [$selectedFriend]);
                        break;
                    }
                    $this->confirmationPage($player, self::SET_FAVORITE, $friend, [$selectedFriend]);
                    break;
                case 1:
                    # Block friend
                    $this->confirmationPage($player, self::BLOCK_PLAYER, $friend, [$selectedFriend]);
                    break;
                case 2:
                    # Remove friend
                    $this->confirmationPage($player, self::REMOVE_FRIEND, $friend, [$selectedFriend]);
                    break;
                case 3:
                    return;
            }
        });
        $ui->setTitle($selectedFriend);
        $ui->addButton(Translation::Translate('ui.button.set.favorite'));
        $ui->addButton(Translation::Translate('ui.button.block'));
        $ui->addButton(Translation::Translate('ui.button.remove'));
        $ui->addButton(Translation::Translate('ui.button.close'));
        $ui->sendToPlayer($player);
    }

    /**
     * Confirmation page for multiple types of events.
     * @param Player $player The player that receives the UI.
     * @param int $event The event of the confirmation.
     * @param array|null $options The options of the event.
     * @param Friend $friend The Friend object related to the player.
     */
    function confirmationPage(Player $player, int $event, Friend $friend, array $options = null): void
    {
        $ui = $this->uiApi->createSimpleForm(function (Player $player, $data) use ($friend, $event, $options) {
            switch ($data) {
                case 0:
                    $order = new Order();
                    /*
                     * TODO: Add local-process for each of them.
                     */
                    switch ($event) {
                        case self::SET_FAVORITE:
                            $order->setCall(MultiFunctionThread::ADD_FAVORITE);
                            $order->setInputs([$player->getName(), $options[0]]);
                            break;
                        case self::UNSET_FAVORITE:
                            $order->setCall(MultiFunctionThread::REMOVE_FAVORITE);
                            $order->setInputs([$player->getName(), $options[0]]);
                            break;
                        case self::REMOVE_FRIEND:
                            $order->setCall(MultiFunctionThread::REMOVE_FRIEND);
                            $order->setInputs([$player->getName(), $options[0]]);
                            break;
                        case self::BLOCK_PLAYER:
                            $order->setCall(MultiFunctionThread::BLOCK_PLAYER);
                            $order->setInputs([$player->getName(), $options[0]]);
                            break;
                    }
                    $order->isSQL(true);
                    $order->execute();
                    break;
                case 1:
                    break;
            }
        });
        $ui->addButton(Translation::Translate('ui.button.confirmation'));
        $ui->addButton(Translation::Translate('ui.button.close'));
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
            # Process data.
            $friend->setNotifyState($data[0]);
            $friend->setRequestState($data[1]);
            $friend->setJoinSetting($data[2]);

            # Creating a new order request to update user settings.
            $order = new Order();
            $order->setCall(MultiFunctionThread::UPDATE_USER_SETTINGS);
            $order->setInputs([$player->getName(), [$data[0], $data[1], $data[2]]]);
        });
        $ui->setTitle(Translation::Translate('ui.main.title'));
        $ui->addToggle(Translation::Translate('ui.settings.toggle.notify'));
        $ui->addToggle(Translation::Translate('ui.settings.toggle.request'));
        $ui->addDropdown(Translation::Translate('ui.settings.content.notify'),
            [
                Translation::Translate('ui.settings.dropdown.never'),
                Translation::Translate('ui.settings.dropdown.favorites'),
                Translation::Translate('ui.settings.dropdown.all.friends')
            ]);
        $ui->sendToPlayer($player);
    }

    /**
     * Help page for the plugin.
     * @param Player $player The player that receives the UI.
     * @param Friend $friend The Friend object related to the player.
     */
    function helpPage(Player $player, Friend $friend): void
    {
        /*
        Format for the helpPage UI.
        1. Description of the plugin.
        2. List of commands + their args and description.
        3. Player preferences.
        4. Requests.
         */
    }
}
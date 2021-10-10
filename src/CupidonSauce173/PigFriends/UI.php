<?php


namespace CupidonSauce173\PigFriends;

use CupidonSauce173\PigFriends\Entities\Friend;
use CupidonSauce173\PigFriends\Lib\FormAPI;
use CupidonSauce173\PigFriends\Utils\Translation;

use pocketmine\Player;

class UI
{
    const SET_FAVORITE = 0;
    const UNSET_FAVORITE = 1;
    const REMOVE_FRIEND = 2;
    const BLOCK_PLAYER = 3;

    private array $pageContainer; # Contain the current page of the players using the UI.

    private FormAPI $uiApi;

    public function __construct()
    {
        $this->uiApi = new FormAPI();
    }

    /**
     * Main page of the UI, will display all the options.
     * @param Player $player The player that receives the UI.
     */
    function mainUI(Player $player)
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
    function addPage(Player $player, Friend $friend)
    {
        # Needs to add the createCustomForm method in the lib.
    }

    /**
     * Settings page where the player can change few personal settings.
     * @param Player $player The player that receives the UI.
     * @param Friend $friend The Friend object related to the player.
     */
    function settingsPage(Player $player, Friend $friend)
    {
        /*
        List of settings for the player

        1. Can receive friend requests. (Toggle)
        2. Gets a notification when receiving a request. (Toggle)
        3. Gets a notification when friend jumping online. (DropDown)
          a. Never
          b. Only favorites
          c. All friends

        */

        # Needs to add the createCustomForm method in the lib.
    }

    /**
     * List of friends of the player.
     * @param Player $player The player that receives the UI.
     * @param int $page The current page of the player.
     * @param Friend $friend The Friend object related to the player.
     */
    function friendsPage(Player $player, int $page, Friend $friend)
    {
        /*
        Format for the friendsPage UI.
        1. friend_01
        2. friend_02
        3. friend_03
        4. friend_04
        5. Next page (If friends-by-page limit was set to 4.)
        6. Close

        If Next page clicked, calls friendsPage($player, $page + 1)
         */


    }

    /**
     * Help page for the plugin.
     * @param Player $player The player that receives the UI.
     * @param Friend $friend The Friend object related to the player.
     */
    function helpPage(Player $player, Friend $friend)
    {
        /*
        Format for the helpPage UI.
        1. Description of the plugin.
        2. List of commands + their args and description.
        3. Player preferences.
        4. Requests.
         */
    }

    /**
     * Confirmation page for multiple types of events.
     * @param Player $player The player that receives the UI.
     * @param int $event The event of the confirmation.
     * @param array|null $options The options of the event.
     * @param Friend $friend The Friend object related to the player.
     */
    function confirmationPage(Player $player, int $event, Friend $friend, array $options = null)
    {

    }
}
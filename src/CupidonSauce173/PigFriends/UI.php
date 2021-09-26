<?php


namespace CupidonSauce173\PigFriends;

use pocketmine\Player;

class UI
{
    const SET_FAVORITE = 0;
    const UNSET_FAVORITE = 1;
    const REMOVE_FRIEND = 2;
    const BLOCK_PLAYER = 3;

    /**
     * Main page of the UI, will display all the options.
     * @param Player $player The player that receives the UI.
     */
    function mainUI(Player $player)
    {
        /*
        List of buttons for the mainUI

        Add a friend
        Friends
        Settings
        Help
        Close

         */
    }

    /**
     * Settings page where the player can change few personal settings.
     * @param Player $player The player that receives the UI.
     */
    function settingsPage(Player $player)
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
    }

    /**
     * List of friends of the player.
     * @param Player $player The player that receives the UI.
     * @param int $page The current page of the player.
     */
    function friendsPage(Player $player, int $page)
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
     */
    function helpPage(Player $player)
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
     */
    function confirmationPage(Player $player, int $event, array $options = null)
    {

    }
}
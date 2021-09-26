<?php


namespace CupidonSauce173\PigFriends\Threads;

use Thread;
use mysqli;

class MultiFunctionThread extends Thread
{
    /*
     * Event Field
     */
    const REFUSE_REQUEST = 0;
    const ACCEPT_REQUEST = 1;
    const SEND_NEW_REQUEST = 2;
    const REMOVE_FRIEND = 3;
    const ADD_FAVORITE = 4;
    const REMOVE_FAVORITE = 5;

    /*
     * Custom Field
     */
    const CUSTOM_QUERY = 6;

    private mysqli $db;

    /**
     * MultiFunctionThread constructor.
     * @param int $process
     * @param array $inputs
     * @param bool $mysql
     */
    function __construct(int $process, array $inputs, bool $mysql = false)
    {
        if ($mysql) {
            $this->db = new mysqli(
                $inputs[2]['ip'],
                $inputs[2]['user'],
                $inputs[2]['password'],
                $inputs[2]['database'],
                $inputs[2]['port']
            );
        }
        switch ($process) {
            case self::REFUSE_REQUEST:
                $this->refuseRequest($inputs[0]);
                break;
            case self::ACCEPT_REQUEST:
                $this->acceptRequest($inputs[0], $inputs[1]);
                break;
            case self::SEND_NEW_REQUEST:
                $this->sendNewRequest($inputs[0], $inputs[1]);
                break;
            case self::REMOVE_FRIEND:
                $this->removeFriend($inputs[0], $inputs[1]);
                break;
            case self::ADD_FAVORITE:
                $this->addFavorite($inputs[0], $inputs[1]);
                break;
            case self::REMOVE_FAVORITE:
                $this->removeFavorite($inputs[0], $inputs[1]);
                break;
            case self::CUSTOM_QUERY:
                $this->customQuery($inputs[0], $inputs[1]);
        }
    }

    /**
     * Will send over a query to the MySQL server.
     * @param string $query
     * @param array $data
     */
    function customQuery(string $query, array $data): void
    {
        /*
         * TODO: Implement this.
         */
    }

    /**
     * Will delete the request from the MySQL server.
     * @param int $requestId
     */
    function refuseRequest(int $requestId): void
    {
        $query = $this->db->prepare("DELETE FROM FriendRequests WHERE id = ?");
        $query->bind_param('i', $requestId);
        $query->execute();
        $query->close();

        $this->db->close();
    }

    /**
     * Will create a new friendship in the MySQL server and delete the request.
     * @param string $player
     * @param array $requestData
     */
    function acceptRequest(string $player, array $requestData): void
    {
        # Deleting the friend request.
        $query = $this->db->prepare("DELETE FROM FriendRequests WHERE id = ?");
        $query->bind_param('i', $requestData['id']);
        $query->execute();
        $query->close();

        $string = "INSERT INTO FriendRelations (base_player,friend) VALUES (?,?)";
        # Creating first relation base_player -> friend.
        $query = $this->db->prepare($string);
        $query->bind_param('ss', $player, $requestData['friend']);
        $query->execute();
        $query->close();
        # Creating second relation friend -> base_player.
        $query = $this->db->prepare($string);
        $query->bind_param('ss', $requestData['friend'], $player);
        $query->execute();
        $query->close();

        # Closing connection.
        $this->db->close();
    }

    /**
     * Will create a new request in the MySQL server.
     * @param string $author
     * @param string $target
     */
    function sendNewRequest(string $author, string $target): void
    {
        $query = $this->db->prepare("INSERT INTO FriendRequests (sender,receiver) VALUES (?,?)");
        $query->bind_param('ss', $author, $target);
        $query->execute();
        $query->close();
        $this->db->close();
    }

    /**
     * Will remove a friend of a player in the MySQL server.
     * @param string $sender
     * @param string $target
     */
    function removeFriend(string $sender, string $target): void
    {
        $string = "DELETE FROM FriendRelations WHERE base_player = ? AND friend = ?";
        # Deleting base relation base_player -> friend.
        $query = $this->db->prepare($string);
        $query->bind_param('ss', $sender, $target);
        $query->execute();
        $query->close();
        # Deleting second relation friend -> base_player.
        $query = $this->db->prepare($string);
        $query->bind_param('ss', $target, $sender);
        $query->execute();
        $query->close();
    }

    /**
     * Will add a player to the favorite list of the sender in the MySQL server.
     * @param string $sender
     * @param string $target
     */
    function addFavorite(string $sender, string $target): void
    {
        /*
         * TODO: Implement this.
         */
    }

    /**
     * Will remove a player from the favorite list of the sender in the MySQL server.
     * @param string $sender
     * @param string $target
     */
    function removeFavorite(string $sender, string $target): void
    {
        /*
         * TODO: Implement this.
         */
    }
}
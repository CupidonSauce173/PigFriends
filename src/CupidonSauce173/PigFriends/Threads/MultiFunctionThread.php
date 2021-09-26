<?php


namespace CupidonSauce173\PigFriends\Threads;

use CupidonSauce173\PigFriends\Entities\Order;

use Thread;
use mysqli;
use Volatile;

use function microtime;

class MultiFunctionThread extends Thread
{
    const REFUSE_REQUEST = 0;
    const ACCEPT_REQUEST = 1;
    const SEND_NEW_REQUEST = 2;
    const REMOVE_FRIEND = 3;
    const ADD_FAVORITE = 4;
    const REMOVE_FAVORITE = 5;
    const CUSTOM_QUERY = 6;

    private Volatile $container;

    private mysqli $db;

    function __construct(Volatile $container)
    {
        $this->container = $container;
    }

    function run()
    {
        $nextTime = microtime(true) + 1;

        include($this->container['folder'] . '\Entities\Order.php');

        while ($this->container['runThread']) {
            if (microtime(true) >= $nextTime) {
                $this->ProcessThread();
                $nextTime = microtime(true) + 1;
            }
        }
    }

    function ProcessThread(): void
    {

        /** @var Order $order */
        foreach ($this->container['multiFunctionQueue'] as $order) {
            if ($order->isSQL()) {
                if ($this->db->ping() === false) {
                    var_dump('MySQL connection was closed. Opening it over MultiFunctionThread.');
                    $this->db = new mysqli(
                        $this->container['mysql-data']['ip'],
                        $this->container['mysql-data']['user'],
                        $this->container['mysql-data']['password'],
                        $this->container['mysql-data']['database'],
                        $this->container['mysql-data']['port'],
                    );
                }
            }
            $inputs = $order->getInputs();
            switch ($order->getCall()) {
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
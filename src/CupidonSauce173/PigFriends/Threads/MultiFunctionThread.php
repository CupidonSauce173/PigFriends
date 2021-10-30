<?php


namespace CupidonSauce173\PigFriends\Threads;

use CupidonSauce173\PigFriends\Entities\Friend;
use CupidonSauce173\PigFriends\Entities\Order;
use mysqli;
use Thread;
use Volatile;
use function microtime;
use function var_dump;

class MultiFunctionThread extends Thread
{
    const REFUSE_REQUEST = 0;
    const ACCEPT_REQUEST = 1;
    const SEND_NEW_REQUEST = 2;
    const REMOVE_FRIEND = 3;
    const ADD_FAVORITE = 4;
    const REMOVE_FAVORITE = 5;
    const CUSTOM_QUERY = 6;
    const CREATE_FRIEND_ENTITY = 7;
    const UPDATE_USER_SETTINGS = 8;
    const BLOCK_PLAYER = 9;
    const UNBLOCK_PLAYER = 10;

    private Volatile $container;

    private mysqli $db;

    # TODO: Need to create a method to insert a new row if the player doesn't exist in the database.

    function __construct(Volatile $container)
    {
        $this->container = $container;
    }

    function run()
    {
        $nextTime = microtime(true) + 1;

        include($this->container['folder'] . '\Entities\Order.php');
        include($this->container['folder'] . '\Entities\Friend.php');

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
            $inputs = (array)$order->getInputs();
            unset($this->container['multiFunctionQueue'][$order]);
            switch ($order->getCall()) {
                case self::REMOVE_FRIEND:
                    $this->removeFriend($inputs[0], $inputs[1]);
                    break;
                # Utils field
                case self::CUSTOM_QUERY:
                    $this->customQuery($inputs[0], $inputs[1]);
                    break;
                case self::CREATE_FRIEND_ENTITY:
                    $this->createFriendEntity($inputs[0]);
                    break;
                case self::UPDATE_USER_SETTINGS:
                    $this->updateUserSettings($inputs[0], $inputs[1]);
                    break;
                # Favorite field.
                case self::ADD_FAVORITE:
                    $this->addRemoveFavorite($inputs[0], $inputs[1], self::ADD_FAVORITE);
                    break;
                case self::REMOVE_FAVORITE:
                    $this->addRemoveFavorite($inputs[0], $inputs[1], self::REMOVE_FAVORITE);
                    break;
                # Blocking field.
                case self::BLOCK_PLAYER:
                    $this->blockUnblockPlayer($inputs[0], $inputs[1], self::BLOCK_PLAYER);
                    break;
                case self::UNBLOCK_PLAYER:
                    $this->blockUnblockPlayer($inputs[0], $inputs[1], self::UNBLOCK_PLAYER);
                    break;
                # Request field.
                case self::REFUSE_REQUEST:
                    $this->refuseRequest($inputs[0]);
                    break;
                case self::ACCEPT_REQUEST:
                    $this->acceptRequest($inputs[0], $inputs[1]);
                    break;
                case self::SEND_NEW_REQUEST:
                    $this->sendNewRequest($inputs[0], $inputs[1]);
                    break;
            }
        }
    }

    /**
     * Will remove a friend of a player in the MySQL server.
     * @param string $sender
     * @param string $target
     */
    function removeFriend(string $sender, string $target): void
    {
        # Deleting base relation base_player -> friend.
        $query = $this->db->prepare('DELETE FROM FriendRelations WHERE base_player = ? AND friend = ?');
        $query->bind_param('ss', $sender, $target);
        $query->execute();
        $query->close();

        # Deleting second relation friend -> base_player.
        $query = $this->db->prepare('DELETE FROM FriendRelations WHERE base_player = ? AND friend = ?');
        $query->bind_param('ss', $target, $sender);
        $query->execute();
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
     * Method to create the friend entity.
     * @param string $player
     */
    function createFriendEntity(string $player): void
    {
        # Creating the friend entity.
        $entity = new Friend();
        $entity->setPlayer($player);

        # Prepare & Execute query to verify if player exists in the database & create it if it doesn't.
        $queryString =
            'INSERT INTO FriendSettings (player)
                 SELECT * FROM (SELECT ?) as tmp
                    WHERE NOT EXISTS (
                        SELECT player FROM FriendSettings WHERE player = ?
                    ) LIMIT 1;
            ';
        $query = $this->db->prepare($queryString);
        $query->bind_param('s', $player);
        $query->execute();
        $query->close();

        # Prepare & Execute query to get player settings.
        $queryString = "SELECT request_state, notify_state, join_message FROM FriendSettings WHERE player = ? LIMIT 1;";
        $query = $this->db->prepare($queryString);
        $query->bind_param('s', $player);
        $query->execute();

        # Process data.
        $results = $query->get_result();
        while ($row = $results->fetch_assoc()) {
            $entity->setJoinSetting($row['join_message']);
            $entity->setNotifyState($row['notify_state']);
            $entity->setRequestState($row['request_state']);
        }
        $query->close();

        # Prepare & Execute query to get all friend relations targeted to the player.
        $queryString =
            "SELECT
              FriendRelations.id, FriendRelations.friend, FriendRelations.reg_date,
              RelationState.relation_id as state_id, RelationState.is_favorite, RelationState.is_blocked
              FROM (FriendSettings
                     INNER JOIN FriendRelations ON FriendSettings.player = FriendRelations.base_player
                     INNER JOIN RelationState ON FriendRelations.id = RelationState.relation_id
                   ) WHERE FriendSettings.player = ?;
            ";
        $query = $this->db->prepare($queryString);
        $query->bind_param('s', $player);
        $query->execute();

        # Process data.
        $results = $query->get_result();
        $relations = [];
        while ($row = $results->fetch_assoc()) {
            $relations[] = $row;
        }
        $query->close();

        # Creating the friends, favorites and blocked players lists.
        foreach ($relations as $relation) {
            if ($relation['is_blocked']) {
                $entity->blockPlayer($relation['friend']);
                return;
            }
            $entity->addFriend($relation['friend']);
            if ($relation['is_favorite']) {
                $entity->addFavorite($relation['friend']);
            }
        }
    }

    /**
     * @param string $player
     * @param array $data
     */
    function updateUserSettings(string $player, array $data): void
    {
        # Preparing values
        $n = $data[0];
        $r = $data[1];
        $j = $data[2];

        # Updating user settings in MySQL.
        $query = $this->db->prepare('UPDATE FriendSettings SET request_state = ?, notify_state = ?, join_message = ? WHERE player = ?');
        $query->bind_param('iiis', $n, $r, $j, $player);
        $query->execute();
    }

    function addRemoveFavorite(string $sender, string $target, int $option): void
    {
        /*
         * TODO: Implement this.
         */
    }

    function blockUnblockPlayer(string $sender, string $target, int $option): void
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
        $query = $this->db->prepare('DELETE FROM FriendRequests WHERE id = ?');
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
        $query = $this->db->prepare('DELETE FROM FriendRequests WHERE id = ?');
        $query->bind_param('i', $requestData['id']);
        $query->execute();
        $query->close();

        # Creating first relation base_player -> friend.
        $query = $this->db->prepare('INSERT INTO FriendRelations (base_player,friend) VALUES (?,?)');
        $query->bind_param('ss', $player, $requestData['friend']);
        $query->execute();
        $query->close();

        # Creating second relation friend -> base_player.
        $query = $this->db->prepare('INSERT INTO FriendRelations (base_player,friend) VALUES (?,?)');
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
        $query = $this->db->prepare('INSERT INTO FriendRequests (sender,receiver) VALUES (?,?)');
        $query->bind_param('ss', $author, $target);
        $query->execute();
        $query->close();
    }
}
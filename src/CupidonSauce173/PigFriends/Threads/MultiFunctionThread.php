<?php


namespace CupidonSauce173\PigFriends\Threads;

use CupidonSauce173\PigFriends\Entities\Friend;
use CupidonSauce173\PigFriends\Entities\Order;
use CupidonSauce173\PigFriends\Utils\ListenerConstants;
use Exception;
use mysqli;
use Thread;
use Volatile;
use function microtime;
use function mysqli_connect;
use function sprintf;

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

    # TODO: Need to create a method to insert a new row if the player doesn't exist in the database.

    /**
     * @throws Exception
     */
    function __construct(Volatile $container)
    {
        $this->container = $container;
    }

    function run()
    {
        $nextTime = microtime(true) + 1;

        include($this->container['folder'] . '\Entities\Order.php');
        include($this->container['folder'] . '\Entities\Friend.php');
        include($this->container['folder'] . '\Utils\ListenerConstants.php');

        $link = mysqli_connect(
            $this->container['mysql-data']['ip'],
            $this->container['mysql-data']['user'],
            $this->container['mysql-data']['password'],
            $this->container['mysql-data']['database'],
            $this->container['mysql-data']['port']
        );

        while ($this->container['runThread']) {
            if (microtime(true) >= $nextTime) {
                $this->ProcessThread($link);
                $nextTime = microtime(true) + 1;
            }
        }
    }

    /**
     * @param mysqli $link
     */
    function ProcessThread(mysqli $link): void
    {
        /** @var Order $order */
        foreach ($this->container['multiFunctionQueue'] as $order) {
            $inputs = (array)$order->getInputs();
            unset($this->container['multiFunctionQueue'][$order->getId()]);
            switch ($order->getCall()) {
                case self::REMOVE_FRIEND:
                    $this->removeFriend($inputs[0], $inputs[1], $link);
                    break;
                # Utils field
                case self::CUSTOM_QUERY:
                    $this->customQuery($inputs[0], $inputs[1], $link);
                    break;
                case self::CREATE_FRIEND_ENTITY:
                    $this->createFriendEntity($inputs[0], $link);
                    break;
                case self::UPDATE_USER_SETTINGS:
                    $this->updateUserSettings($inputs[0], $inputs[1], $link);
                    break;
                # Favorite field.
                case self::ADD_FAVORITE:
                    $this->addRemoveFavorite($inputs[0], $inputs[1], self::ADD_FAVORITE, $link);
                    break;
                case self::REMOVE_FAVORITE:
                    $this->addRemoveFavorite($inputs[0], $inputs[1], self::REMOVE_FAVORITE, $link);
                    break;
                # Blocking field.
                case self::BLOCK_PLAYER:
                    $this->blockUnblockPlayer($inputs[0], $inputs[1], self::BLOCK_PLAYER, $link);
                    break;
                case self::UNBLOCK_PLAYER:
                    $this->blockUnblockPlayer($inputs[0], $inputs[1], self::UNBLOCK_PLAYER, $link);
                    break;
                # Request field.
                case self::REFUSE_REQUEST:
                    $this->refuseRequest($inputs[0], $link);
                    break;
                case self::ACCEPT_REQUEST:
                    $this->acceptRequest($inputs[0], $inputs[1], $link);
                    break;
                case self::SEND_NEW_REQUEST:
                    $this->sendNewRequest($inputs[0], $inputs[1], $link);
                    break;
            }
        }
    }

    /**
     * Will remove a friend of a player in the MySQL server.
     * @param Friend $friend
     * @param string $target
     * @param mysqli $link
     */
    function removeFriend(Friend $friend, string $target, mysqli $link): void
    {
        $queryString =
            'DELETE FROM FriendRelations 
                WHERE (base_player = ? AND friend = ?)
                OR    (base_player = ? AND friend = ?)
            ';
        $stmt = $link->prepare($queryString);
        $stmt->bind_param('ssss', $sender, $target, $target, $friend->getPlayer());
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Will send over a query to the MySQL server.
     * @param string $query
     * @param array $data
     * @param mysqli $link
     */
    function customQuery(string $query, array $data, mysqli $link): void
    {
        $stmt = $link->prepare($query);
        $stmt->bind_param($data[0], $data[1]);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Method to create the friend entity.
     * @param string $player
     * @param mysqli $link
     */
    function createFriendEntity(string $player, mysqli $link): void
    {
        # Creating the friend entity.
        $entity = new Friend();
        $entity->setPlayer($player);

        # Prepare & Execute query to verify if player exists in the database & create it if it doesn't.
        $queryString =
            'INSERT INTO FriendSettings (player)
                 SELECT * FROM ( SELECT ? ) as tmp
                    WHERE NOT EXISTS (
                        SELECT player FROM FriendSettings WHERE player = ?
                    ) LIMIT 1;
            ';
        $stmt = $link->prepare($queryString);
        $stmt->bind_param('ss', $player, $player);
        $stmt->execute();
        $stmt->close();

        # Prepare & Execute query to get player settings.
        $queryString = "SELECT request_state, notify_state, join_message FROM FriendSettings WHERE player = ? LIMIT 1;";
        $stmt = $link->prepare($queryString);
        $stmt->bind_param('s', $player);
        $stmt->execute();

        # Process data.
        $results = $stmt->get_result();
        while ($row = $results->fetch_assoc()) {
            $entity->setJoinSetting($row['join_message']);
            $entity->setNotifyState((bool)$row['notify_state']);
            $entity->setRequestState((bool)$row['request_state']);
        }
        $stmt->close();

        # Prepare & Execute query to get all friend relations targeted to the player.
        $queryString =
            'SELECT
              FriendRelations.id, FriendRelations.friend, FriendRelations.reg_date,
              RelationState.relation_id as state_id, RelationState.is_favorite, RelationState.is_blocked
              FROM ( FriendSettings
                      INNER JOIN FriendRelations ON FriendSettings.player = FriendRelations.base_player
                      INNER JOIN RelationState ON FriendRelations.id = RelationState.relation_id
                   ) WHERE FriendSettings.player = ?;
            ';
        $stmt = $link->prepare($queryString);
        $stmt->bind_param('s', $player);
        $stmt->execute();

        # Process data.
        $results = $stmt->get_result();
        $relations = [];
        while ($row = $results->fetch_assoc()) {
            $relations[] = $row;
        }
        $stmt->close();

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
        $this->container['friends'][] = $entity;
    }

    /**
     * Method to update the user settings.
     * @param Friend $friend
     * @param array $data
     * @param mysqli $link
     */
    function updateUserSettings(Friend $friend, array $data, mysqli $link): void
    {
        # Preparing values
        $n = (int)$data[0];
        $r = (int)$data[1];
        $j = $data[2];

        $friend->setNotifyState($data[0]);
        $friend->setRequestState($data[1]);
        $friend->setJoinSetting($data[2]);

        # Updating user settings in MySQL.
        $stmt = $link->prepare('UPDATE FriendSettings SET request_state = ?, notify_state = ?, join_message = ? WHERE player = ?');
        $stmt->bind_param('iiis', $n, $r, $j, $friend->getPlayer());
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param Friend $friend
     * @param string $target
     * @param int $option
     * @param mysqli $link
     */
    function addRemoveFavorite(Friend $friend, string $target, int $option, mysqli $link): void
    {
        /*
         * TODO: Implement this.
         */
    }

    /**
     * @param Friend $friend
     * @param string $target
     * @param int $option
     * @param mysqli $link
     */
    function blockUnblockPlayer(Friend $friend, string $target, int $option, mysqli $link): void
    {
        /*
         * TODO: Implement this.
         */
    }

    /**
     * Will delete the request from the MySQL server.
     * @param int $requestId
     * @param mysqli $link
     */
    function refuseRequest(int $requestId, mysqli $link): void
    {
        $stmt = $link->prepare('DELETE FROM FriendRequests WHERE id = ?');
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Will create a new friendship in the MySQL server and delete the request.
     * @param string $player
     * @param array $requestData
     * @param mysqli $link
     */
    function acceptRequest(string $player, array $requestData, mysqli $link): void
    {
        # Deleting the friend request.
        $stmt = $link->prepare('DELETE FROM FriendRequests WHERE id = ?');
        $stmt->bind_param('i', $requestData['id']);
        $stmt->execute();
        $stmt->close();

        # Creating first relation base_player -> friend.
        $stmt = $link->prepare('INSERT INTO FriendRelations (base_player,friend) VALUES (?,?), (?,?)');
        $stmt->bind_param('ssss', $player, $requestData['friend'], $requestData['friend'], $player);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Will create a new request in the MySQL server.
     * @param string $author
     * @param string $target
     * @param mysqli $link
     */
    function sendNewRequest(string $author, string $target, mysqli $link): void
    {
        $queryString =
            sprintf("
            INSERT INTO FriendRequests (sender, receiver)
            SELECT * FROM 
            ( SELECT '%a', '%t') as tmp
            WHERE NOT EXISTS (
              SELECT sender,receiver FROM FriendRequests
                WHERE sender = ? AND receiver = ? )
            AND (
              SELECT IFNULL( (SELECT RelationState.is_blocked FROM
              ( FriendSettings
                 INNER JOIN FriendRelations on FriendSettings.player = FriendRelations.base_player
                 INNER JOIN RelationState on FriendRelations.id = RelationState.relation_id
              ) WHERE FriendSettings.player = ?), FALSE)
            ) = FALSE LIMIT 1
            ", $author, $target);
        $stmt = $link->prepare($queryString);
        $stmt->bind_param('sss', $author, $target, $target);
        $stmt->execute();

        $order = new Order();
        $order->isSQL(false);
        if ($stmt->affected_rows === 0) {
            $order->setCall(ListenerConstants::REQUEST_ALREADY_EXISTS);
        } else {
            $order->setCall(ListenerConstants::REQUEST_CREATED);
        }
        $order->setInputs([$author, $target]);
        $this->container['orderListener'][$order->execute(true)] = $order;

        $stmt->close();
    }
}
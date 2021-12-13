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
    const SEND_NEW_REQUEST = 0;
    const UPDATE_USER_SETTINGS = 1;
    const BLOCK_PLAYER = 2;
    const UNBLOCK_PLAYER = 3;
    const ADD_FAVORITE = 4;
    const REMOVE_FAVORITE = 5;
    const REMOVE_FRIEND = 6;
    const CREATE_FRIEND_ENTITY = 7;
    const ACCEPT_REQUEST = 8;
    const REFUSE_REQUEST = 9;
    const CUSTOM_QUERY = 10;

    private Volatile $container;
    private Volatile $protectionContainer;

    /**
     * @throws Exception
     */
    function __construct(Volatile $container)
    {
        $this->container = $container;
        $this->protectionContainer = new Volatile();
        $this->protectionContainer['orderTypes'] = [
            self::SEND_NEW_REQUEST => [],
            self::UPDATE_USER_SETTINGS => [],
            self::BLOCK_PLAYER => [],
            self::UNBLOCK_PLAYER => [],
            self::ADD_FAVORITE => [],
            self::REMOVE_FAVORITE => [],
            self::REMOVE_FRIEND => []
        ];
        $this->protectionContainer['indexToString'] = [
            0 => 'SEND_NEW_REQUEST',
            1 => 'UPDATE_USER_SETTINGS',
            2 => 'BLOCK_PLAYER',
            3 => 'UNBLOCK_PLAYER',
            4 => 'ADD_FAVORITE',
            5 => 'REMOVE_FAVORITE',
            6 => 'REMOVE_FRIEND'
        ];
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
                $this->ProcessOrderProtection();
                $this->ProcessThread($link);
                $nextTime = microtime(true) + 1;
            }
        }
    }

    /**
     * Method that will attach a new order to the player and return true if the player maxed out the amount of orders they can do.
     * @param string $user
     * @param int $order
     * @return bool
     */
    private function attachOrderToUser(string $user, int $order): bool
    {
        if (!isset($this->protectionContainer['orderTypes'][$order][$user])) {
            $this->protectionContainer['orderTypes'][$order][$user] = [
                'amount' => 1,
                'nextReset' => 60
            ];
            return false;
        }
        $amount = $this->protectionContainer['orderTypes'][$order][$user]['amount'] + 1;
        if ($amount >= $this->container['config']['protection'][$this->protectionContainer['indexToString'][$order]]) return true;
        $this->protectionContainer['orderTypes'][$order][$user]['amount'] = $amount;
        return false;
    }

    /**
     * Method that will process the order protection system. Doing -1
     */
    private function ProcessOrderProtection(): void
    {
        foreach ($this->protectionContainer['orderTypes'] as $order => $clients) {
            foreach ($clients as $user => $data) {
                if ($data['nextReset'] === 0) {
                    unset($this->protectionContainer['orderTypes'][$order][$user]);
                } else {
                    $current = $this->protectionContainer['orderTypes'][$order][$user]['nextReset'] - 1;
                    $this->protectionContainer['orderTypes'][$order][$user]['nextReset'] = $current;
                }
            }
        }
    }

    /**
     * Method to process the thread.
     * @param mysqli $link
     */
    private function ProcessThread(mysqli $link): void
    {
        /** @var Order $order */
        foreach ($this->container['multiFunctionQueue'] as $order) {
            unset($this->container['multiFunctionQueue'][$order->getId()]);
            $inputs = (array)$order->getInputs();
            if (isset($this->protectionContainer['orderTypes'][$order->getCall()])) {
                if ($this->attachOrderToUser($inputs[0]['player'], $order->getCall())) {
                    $listenerOrder = new Order();
                    $listenerOrder->setCall(ListenerConstants::ORDER_PROTECTION);
                    $listenerOrder->setInputs([
                        $inputs[0]['player'],
                        $this->protectionContainer['orderTypes'][$order->getCall()][$inputs[0]['player']]['nextReset']
                    ]);
                    $state = $listenerOrder->execute(true);
                    $this->container['orderListener'][$state] = $listenerOrder;
                    return;
                }
            }
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
                    $this->blockPlayer($inputs[0], $inputs[1], $link);
                    break;
                case self::UNBLOCK_PLAYER:
                    $this->unblockPlayer($inputs[0], $inputs[1], $link);
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
     * Method to remove a friend of a player in the MySQL server.
     * @param Friend $friend
     * @param string $target
     * @param mysqli $link
     */
    private function removeFriend(Friend $friend, string $target, mysqli $link): void
    {
        $player = $friend->getPlayer();
        # Delete entries from RelationState.
        $queryString = '
            DELETE FROM RelationState
            WHERE relation_id = (SELECT FriendRelations.id FROM (
                FriendSettings INNER JOIN FriendRelations ON FriendSettings.player = FriendRelations.base_player
            ) WHERE FriendRelations.base_player = ? AND FriendRelations.friend = ?
              OR FriendRelations.base_player = ? AND FriendRelations.friend = ?);';
        $stmt = $link->prepare($queryString);
        $stmt->bind_param('ssss', $sender, $target, $target, $player);
        $stmt->execute();
        var_dump($stmt->error_list);
        $stmt->close();
        # Delete entries from FriendRelations.
        $queryString = '
            DELETE FROM FriendRelations 
               WHERE (base_player = ? AND friend = ?)
               OR    (base_player = ? AND friend = ?);';
        $stmt = $link->prepare($queryString);
        $stmt->bind_param('ssss', $sender, $target, $target, $player);
        $stmt->execute();
        var_dump($stmt->error_list);
        $stmt->close();
    }

    /**
     * Method to send over a custom query to the MySQL server.
     * @param string $query
     * @param array $data
     * @param mysqli $link
     */
    private function customQuery(string $query, array $data, mysqli $link): void
    {
        $stmt = $link->prepare($query);
        $stmt->bind_param($data[0], ...$data[1]);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Method to create the friend entity.
     * @param string $player
     * @param mysqli $link
     */
    private function createFriendEntity(string $player, mysqli $link): void
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
                    ) LIMIT 1;';
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
              FriendRelations.friend, RelationState.is_favorite, RelationState.is_blocked
              FROM ( FriendSettings
                      INNER JOIN FriendRelations ON FriendSettings.player = FriendRelations.base_player
                      INNER JOIN RelationState ON FriendRelations.id = RelationState.relation_id
                   ) WHERE FriendSettings.player = ?;';
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
            } else {
                $entity->addFriend($relation['friend']);
                if ($relation['is_favorite']) {
                    $entity->addFavorite($relation['friend']);
                }
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
    private function updateUserSettings(Friend $friend, array $data, mysqli $link): void
    {
        # Preparing values
        $n = (int)$data[0];
        $r = (int)$data[1];
        $j = $data[2];
        $player = $friend->getPlayer();

        $friend->setNotifyState($data[0]);
        $friend->setRequestState($data[1]);
        $friend->setJoinSetting($data[2]);

        # Updating user settings in MySQL.
        $stmt = $link->prepare('UPDATE FriendSettings SET request_state = ?, notify_state = ?, join_message = ? WHERE player = ?;');
        $stmt->bind_param('iiis', $n, $r, $j, $player);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Method to set or unset a friend a favorite.
     * @param Friend $friend
     * @param string $target
     * @param int $option
     * @param mysqli $link
     */
    private function addRemoveFavorite(Friend $friend, string $target, int $option, mysqli $link): void
    {

        if ($option === self::ADD_FAVORITE) {
            $state = 1;
            $friend->addFavorite($target);
        } else {
            $state = 0;
            $friend->removeFavorite($target);
        }

        # Updating RelationState to "favorite"
        $queryString = '
            UPDATE RelationState SET is_favorite = ?
            WHERE relation_id = (SELECT FriendRelations.id FROM (
                FriendSettings INNER JOIN FriendRelations ON FriendSettings.player = FriendRelations.base_Player
            ) WHERE FriendRelations.base_player = ? AND FriendRelations.friend = ?);';
        $player = $friend->getPlayer();
        $stmt = $link->prepare($queryString);
        $stmt->bind_param('iss', $state, $player, $target);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Method to unblock someone from sending friend requests to the player.
     * @param Friend $friend
     * @param string $target
     * @param mysqli $link
     * @param bool $inverseFriendTarget
     */
    private function unblockPlayer(Friend $friend, string $target, mysqli $link, bool $inverseFriendTarget = false): void
    {
        if ($inverseFriendTarget) {
            $player = $target;
            $target = $player;
        } else {
            $player = $friend->getPlayer();
        }

        # First, delete RelationState entry.
        $queryString = '
            DELETE FROM RelationState
            WHERE relation_id = (SELECT FriendRelations.id FROM (
                FriendSettings INNER JOIN FriendRelations ON FriendSettings.player = FriendRelations.base_player
            ) WHERE FriendRelations.base_player = ? AND FriendRelations.friend = ?);';
        $stmt = $link->prepare($queryString);
        $stmt->bind_param('ss', $player, $target);
        $stmt->execute();
        $stmt->close();

        # Second, delete the FriendRelation entry.
        $stmt = $link->prepare('DELETE FROM FriendRelations WHERE base_player = ? AND friend = ?;');
        $stmt->bind_param('ss', $player, $target);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Method to block someone from sending friend requests to the player.
     * @param Friend $friend
     * @param string $target
     * @param mysqli $link
     */
    private function blockPlayer(Friend $friend, string $target, mysqli $link): void
    {
        $player = $friend->getPlayer();
        if ((array)in_array($target, $friend->getFriends())) {
            # Process if $target & $friend are friends.
            $friend->removeFriend($target);
            if ($friend->isFavorite($target)) {
                $friend->removeFavorite($target);
            }
            $queryString = '
            UPDATE RelationState SET is_favorite = 0, is_blocked = 1
            WHERE relation_id = (SELECT FriendRelations.id FROM (
                FriendSettings INNER JOIN FriendRelations ON FriendSettings.player = FriendRelations.base_Player
            ) WHERE FriendRelations.base_player = ? AND FriendRelations.Friend = ?);';

            $this->unblockPlayer($friend, $target, $link, true);
        } else {
            # Process if they aren't friends.
            $queryString = 'INSERT INTO FriendRelations (base_player,friend) VALUES (?,?)'; # First create the new relation.
            $stmt = $link->prepare($queryString);
            $stmt->bind_param('ss', $player, $target);
            $stmt->execute();
            $stmt->close();

            $queryString = '
            UPDATE RelationState SET is_blocked = 0
            WHERE relation_id = (SELECT FriendRelations.id FROM (
                FriendSettings INNER JOIN FriendRelations ON FriendSettings.player = FriendRelations.base_Player
            ) WHERE FriendRelations.base_player = ? AND FriendRelations.Friend = ?);'; # Then setting it as blocked.
        }
        $stmt = $link->prepare($queryString);
        $stmt->bind_param('ss', $player, $target);
        $stmt->execute();
        $stmt->close();

        $friend->blockPlayer($target); # And  block player locally.
    }

    /**
     * Method to delete the request from the MySQL server.
     * @param int $requestId
     * @param mysqli $link
     */
    private function refuseRequest(int $requestId, mysqli $link): void
    {
        $stmt = $link->prepare('DELETE FROM FriendRequests WHERE id = ?;');
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Method to create a new friendship in the MySQL server and delete the request.
     * @param string $player
     * @param array $requestData
     * @param mysqli $link
     */
    private function acceptRequest(string $player, array $requestData, mysqli $link): void
    {
        # Deleting the friend request.
        $stmt = $link->prepare('DELETE FROM FriendRequests WHERE id = ?;');
        $stmt->bind_param('i', $requestData['id']);
        $stmt->execute();
        $stmt->close();

        # Creating first relation base_player -> friend.
        $stmt = $link->prepare('INSERT INTO FriendRelations (base_player,friend) VALUES (?,?), (?,?);');
        $stmt->bind_param('ssss', $player, $requestData['friend'], $requestData['friend'], $player);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Method to create a new request in the MySQL server.
     * @param string $author
     * @param string $target
     * @param mysqli $link
     */
    private function sendNewRequest(string $author, string $target, mysqli $link): void
    {
        # %a = author, %t = target
        $queryString =
            sprintf("
            INSERT INTO FriendRequests (sender, receiver)
            SELECT * FROM 
            ( SELECT '%s', '%s') as tmp
            WHERE NOT EXISTS (
              SELECT sender,receiver FROM FriendRequests
                WHERE sender = ? AND receiver = ? )
            AND (
              SELECT IFNULL( (SELECT RelationState.is_blocked FROM
              ( FriendSettings
                 INNER JOIN FriendRelations on FriendSettings.player = FriendRelations.base_player
                 INNER JOIN RelationState on FriendRelations.id = RelationState.relation_id
              ) WHERE FriendSettings.player = ?), FALSE)
            ) = FALSE LIMIT 1;
            ", $author, $target);
        $stmt = $link->prepare($queryString);
        $stmt->bind_param('sss', $author, $target, $target);
        $stmt->execute();

        $order = new Order();
        $order->isSQL(false);
        if ($stmt->affected_rows === 0) {
            $order->setCall(ListenerConstants::REQUEST_ALREADY_EXISTS);
            $order->setInputs([$author, $target]);
        } elseif ($stmt->affected_rows === 1) {
            $order->setCall(ListenerConstants::REQUEST_CREATED);
            $order->setInputs([$author, $target]);
        } else {
            if (isset($stmt->error_list[0])) {
                if ($stmt->error_list[0]['errno'] === 1452) {
                    $order->setCall(ListenerConstants::USER_NOT_CREATED);
                    $order->setInputs([$author, $target]);
                } else {
                    $order->setCall(ListenerConstants::UNKNOWN_ERROR);
                    $order->setInputs([$author, $target, self::SEND_NEW_REQUEST]);
                }
            } else {
                $order->setCall(ListenerConstants::UNKNOWN_ERROR);
                $order->setInputs([$author, $target, self::SEND_NEW_REQUEST]);
            }
        }
        $state = $order->execute(true);
        $this->container['orderListener'][$state] = $order;
        $stmt->close();
    }
}
<?php


namespace CupidonSauce173\PigFriends\Threads;

use CupidonSauce173\PigFriends\Entities\Friend;
use CupidonSauce173\PigFriends\Entities\Order;
use CupidonSauce173\PigFriends\Entities\Request;
use CupidonSauce173\PigFriends\Utils\ListenerConstants;
use Exception;
use mysqli;
use Ramsey\Uuid\UuidInterface;
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
        include($this->container['folder'] . '\Entities\Request.php');
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
            $inputs = $order->getInputs();
            var_dump($inputs);
            if (isset($this->protectionContainer['orderTypes'][$order->getCall()])) {
                if ($inputs[0] instanceof UuidInterface) {
                    $uuid = $inputs[0]->__toString();
                } elseif ($inputs[0] instanceof Friend) {
                    $uuid = $inputs[0]->getPlayer();
                } else {
                    $uuid = $inputs['uuid'];
                }
                if ($this->attachOrderToUser($uuid, $order->getCall())) {
                    $listenerOrder = new Order();
                    $listenerOrder->setCall(ListenerConstants::ORDER_PROTECTION);
                    $listenerOrder->setInputs([
                        $inputs[0],
                        $this->protectionContainer['orderTypes'][$order->getCall()][$inputs[0]]['nextReset']
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
                    $this->createFriendEntity($inputs[0], $inputs[1], $link);
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
                    $this->acceptRequest($inputs[0], $link);
                    break;
                case self::SEND_NEW_REQUEST:
                    $this->sendNewRequest($inputs[0], $inputs[1], $link);
                    break;
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
     * Method to remove a friend of a player in the MySQL server.
     * @param Friend $friend
     * @param string $target
     * @param mysqli $link
     */
    private function removeFriend(Friend $friend, string $target, mysqli $link): void
    {
        $targetUuid = $this->retrieveTargetUuid($target, $link);
        $playerUuid = $friend->getPlayer();

        $queryString = '
        DELETE rs, fr
        FROM RelationState rs
        JOIN FriendRelations fr ON rs.relation_id = fr.id
        JOIN FriendSettings fs ON fr.base_player = fs.player
        WHERE (
            fr.base_player = ? AND fr.friend = ?
        ) OR (
            fr.base_player = ? AND fr.friend = ?
        );';

        $stmt = $link->prepare($queryString);
        $stmt->bind_param('ssss', $playerUuid, $targetUuid, $targetUuid, $playerUuid);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Will retrieve the Uuid of a player from the MySQL database.
     * @param string $target
     * @param mysqli $link
     * @return string|null
     */
    private function retrieveTargetUuid(string $target, mysqli $link): ?string
    {
        $stmt = $link->prepare('SELECT player FROM FriendSettings WHERE lastUsername = ?');
        $stmt->bind_param('s', $target);
        $stmt->execute();

        # Process uuid.
        $results = $stmt->get_result();
        $targetUuid = false;
        while ($row = $results->fetch_assoc()) {
            $targetUuid = $row['player'];
        }
        $stmt->close();

        if (!$targetUuid) return null;
        return $targetUuid;
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
     * @param string $playerUuid
     * @param string $lastUsername
     * @param mysqli $link
     */
    private function createFriendEntity(string $playerUuid, string $lastUsername, mysqli $link): void
    {
        # Creating the friend entity.
        $entity = new Friend();
        $entity->setPlayer($playerUuid);

        # Prepare & Execute query to verify if player exists in the database & create it if it doesn't.
        $queryString = sprintf(
            "INSERT INTO FriendSettings (player,lastUsername)
                 SELECT * FROM ( SELECT '%s', '%s' ) as tmp
                    WHERE NOT EXISTS (
                        SELECT player FROM FriendSettings WHERE player = ? AND lastUsername = ?
                    ) LIMIT 1;",
            $playerUuid, $lastUsername);
        $stmt = $link->prepare($queryString);
        $stmt->bind_param('ss', $playerUuid, $lastUsername);
        $stmt->execute();
        $stmt->close();

        # Prepare & Execute query to get player settings.
        $queryString = "SELECT request_state, notify_state, join_message FROM FriendSettings WHERE player = ? LIMIT 1;";
        $stmt = $link->prepare($queryString);
        $stmt->bind_param('s', $playerUuid);
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
            'SELECT FriendRelations.friend, RelationState.is_favorite, RelationState.is_blocked
              FROM ( FriendSettings
                      INNER JOIN FriendRelations ON FriendSettings.player = FriendRelations.base_player
                      INNER JOIN RelationState ON FriendRelations.id = RelationState.relation_id
                   ) WHERE FriendSettings.player = ?;';
        $queryString =
            '
            SELECT
                a.player, FriendRelations.friend,
                b.lastUsername, relationState.is_favorite,
                RelationState.is_blocked
            FROM FriendSettings a
            JOIN FriendRelations ON a.player = FriendRelations.base_player
            JOIN FriendSettings b ON FriendRelations.friend = b.player
            JOIN RelationState ON FriendRelations.id = RelationState.relation_id
            WHERE a.player = ?;
            ';
        $stmt = $link->prepare($queryString);
        $stmt->bind_param('s', $playerUuid);
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
                $entity->addFriend($relation['friend'], $relation['lastUsername']);
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
        $notification = (int)$data[0];
        $request = (int)$data[1];
        $joinMessage = $data[2];
        $playerUuid = $friend->getPlayer();

        $friend->setNotifyState($data[0]);
        $friend->setRequestState($data[1]);
        $friend->setJoinSetting($data[2]);

        # Updating user settings in MySQL.
        $stmt = $link->prepare('UPDATE FriendSettings SET request_state = ?, notify_state = ?, join_message = ? WHERE player = ?;');
        $stmt->bind_param('iiis', $request, $notification, $joinMessage, $playerUuid);
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

        $targetUuid = $this->retrieveTargetUuid($target, $link);

        # Locally remove the friend from the favorite list.
        if ($option === self::ADD_FAVORITE) {
            $state = 1;
            $friend->addFavorite($target);
        } else {
            $state = 0;
            $friend->removeFavorite($target);
        }

        # Updating RelationState to "favorite" in MySQL
        $queryString = '
            UPDATE RelationState SET is_favorite = ?
            WHERE relation_id = (SELECT FriendRelations.id FROM (
                FriendSettings INNER JOIN FriendRelations ON FriendSettings.player = FriendRelations.base_Player
            ) WHERE FriendRelations.base_player = ? AND FriendRelations.friend = ?);';
        $playerUuid = $friend->getPlayer();
        $stmt = $link->prepare($queryString);
        $stmt->bind_param('iss', $state, $playerUuid, $targetUuid);
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
        $playerUuid = $friend->getPlayer();

        /** @var Request $request */
        foreach ($friend->getRequests() as $request) {
            if ($request->getSenderUsername() === $target) {
                $id = $request->getId();
                $targetUuid = $request->getSender();
                # Delete the request in the database and locally.
                unset($this->container['requests'][$id]);
                $stmt = $link->prepare('DELETE FROM FriendRequests WHERE id = ?;');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
            } else {
                $targetUuid = $this->retrieveTargetUuid($target, $link);
            }
        }

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

            $this->unblockPlayer($friend->getPlayer(), $targetUuid, $link, true);
        } else {
            # Process if they aren't friends.
            $queryString = 'INSERT INTO FriendRelations (base_player,friend) VALUES (?,?)'; # First create the new relation.
            $stmt = $link->prepare($queryString);
            $stmt->bind_param('ss', $playerUuid, $targetUuid);
            $stmt->execute();
            $stmt->close();

            $queryString = '
            UPDATE RelationState SET is_blocked = 0
            WHERE relation_id = (SELECT FriendRelations.id FROM (
                FriendSettings INNER JOIN FriendRelations ON FriendSettings.player = FriendRelations.base_Player
            ) WHERE FriendRelations.base_player = ? AND FriendRelations.Friend = ?);'; # Then setting it as blocked.
        }
        $stmt = $link->prepare($queryString);
        $stmt->bind_param('ss', $playerUuid, $targetUuid);
        $stmt->execute();
        $stmt->close();

        $friend->blockPlayer($target); # And block player locally.
    }

    /**
     * Method to unblock someone from sending friend requests to the player.
     * @param string $friendUuid
     * @param string $target
     * @param mysqli $link
     * @param bool $reverseFriendTarget
     */
    private function unblockPlayer(string $friendUuid, string $target, mysqli $link, bool $reverseFriendTarget = false): void
    {
        $targetUuid = $this->retrieveTargetUuid($target, $link);

        if ($reverseFriendTarget) {
            $player = $targetUuid;
            $targetUuid = $player;
        } else {
            $player = $friendUuid;
        }

        # First, delete RelationState entry.
        $queryString = '
            DELETE FROM RelationState
            WHERE relation_id = (SELECT FriendRelations.id FROM (
                FriendSettings INNER JOIN FriendRelations ON FriendSettings.player = FriendRelations.base_player
            ) WHERE FriendRelations.base_player = ? AND FriendRelations.friend = ?);';
        $stmt = $link->prepare($queryString);
        $stmt->bind_param('ss', $player, $targetUuid);
        $stmt->execute();
        $stmt->close();

        # Second, delete the FriendRelation entry.
        $stmt = $link->prepare('DELETE FROM FriendRelations WHERE base_player = ? AND friend = ?;');
        $stmt->bind_param('ss', $player, $targetUuid);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Method to delete the request from the MySQL server.
     * @param Request $request
     * @param mysqli $link
     */
    private function refuseRequest(Request $request, mysqli $link): void
    {
        $id = $request->getId();
        unset($this->container['requests'][$id]);

        $stmt = $link->prepare('DELETE FROM FriendRequests WHERE id = ?;');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Method to create a new friendship in the MySQL server and delete the request.
     * @param Request $request
     * @param mysqli $link
     */
    private function acceptRequest(Request $request, mysqli $link): void
    {
        $playerUuid = $request->getTarget();
        $targetUuid = $request->getSender();
        $requestId = $request->getId();

        unset($this->container['requests'][$requestId]);

        # Deleting the friend request.
        $stmt = $link->prepare('DELETE FROM FriendRequests WHERE id = ?;');
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        $stmt->close();

        # Creating relation rows
        $stmt = $link->prepare('INSERT INTO FriendRelations (base_player,friend) VALUES (?,?), (?,?);');
        $stmt->bind_param('ssss', $playerUuid, $targetUuid, $targetUuid, $playerUuid);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Method to create a new request in the MySQL server.
     * @param UuidInterface $uuid
     * @param string $target
     * @param mysqli $link
     */
    private function sendNewRequest(UuidInterface $uuid, string $target, mysqli $link): void
    {
        $authorUuid = $uuid->toString();
        $targetUuid = $this->retrieveTargetUuid($target, $link);

        if ($targetUuid === null) {
            $order = new Order();
            $order->setCall(ListenerConstants::USER_NOT_CREATED);
            $order->setInputs([$uuid, $target]);
            $state = $order->execute(true);
            $this->container['orderListener'][$state] = $order;
            return;
        }

        $queryString = sprintf("
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
            ", $authorUuid, $targetUuid);

        $stmt = $link->prepare($queryString);
        $stmt->bind_param('sss', $authorUuid, $targetUuid, $targetUuid);
        $stmt->execute();

        $order = new Order();
        $order->isSQL(false);
        if ($stmt->affected_rows === 0) {
            $order->setCall(ListenerConstants::REQUEST_ALREADY_EXISTS);
            $order->setInputs([$uuid, $target]);
        } elseif ($stmt->affected_rows === 1) {
            $order->setCall(ListenerConstants::REQUEST_CREATED);
            $order->setInputs([$uuid, $target]);
        } else {
            $order->setCall(ListenerConstants::UNKNOWN_ERROR);
            $order->setInputs([$uuid, $target, self::SEND_NEW_REQUEST]);
        }
        $state = $order->execute(true);
        $this->container['orderListener'][$state] = $order;
        $stmt->close();
    }
}
<?php


namespace CupidonSauce173\PigFriends\Threads;

use CupidonSauce173\PigFriends\Entities\Friend;
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
    const CREATE_FRIEND_ENTITY = 7;

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
                    break;
                case self::CREATE_FRIEND_ENTITY:
                    $this->createFriendEntity($inputs[0]);
                    break;
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

    function createFriendEntity(string $player): void
    {
        # This whole process could be only one or two query, but I need to think more about it.

        # Prepare & Execute query for the player settings.
        $query = $this->db->prepare("SELECT (request_state,notify_state) FROM FriendSettings WHERE player = ?");
        $query->bind_param('s', $player);
        $query->execute();

        # Retrieve & Process data.
        $results = $query->get_result();

        $settings = [];
        while ($row = $results->fetch_assoc()) {
            $settings['request_state'] = $row['request_state'];
            $settings['notify_state'] = $row['notify_state'];
        }
        $query->close();

        # Prepare & Execute query for relations related to the player.
        $query = $this->db->prepare("SELECT (id,friend,reg_date) FROM FriendRelation WHERE base_player = ?");
        $query->bind_param('s', $player);
        $query->execute();

        # Retrieve & Process data.
        $results = $query->get_result();

        $relations = [];
        $ids = [];
        while ($row = $results->fetch_assoc()) {
            $relations[] = [
                'id' => (int)$row['id'],
                'friend' => $row['friend'], # Might rename 'friend' to 'target' since a 'friend' can be set as 'blocked' in the database.
                'reg_date' => $row['reg_date']
            ];
            $ids[] = (int)$row['id'];
        }
        $query->close();

        # Creating list of clauses and types.
        $clause = implode(',', array_fill(0, count($relations), '?'));
        $types = str_repeat('i', count($relations));

        # Prepare & Execute query for relation states.
        $query = $this->db->prepare("SELECT (relation_id,is_favorite,is_blocked) FROM RelationState WHERE relation_id IN ($clause)");
        $query->bind_param($types, ...$ids);
        $query->execute();

        # Creating & Setting friend entity.
        $entity = new Friend();
        $entity->setRawSettings($settings);
        $entity->setPlayer($player);

        # Creating the friends, favorites and blocked players lists.
        $results = $query->get_result();
        while ($row = $results->fetch_assoc()) {
            foreach ($relations as $relation) {
                if ((int)$row['relation_id'] !== $relation['id']) return;
                if ($row['is_blocked']) {
                    $entity->blockPlayer($relation['friend']);
                    return;
                }
                $entity->addFriend($relation['friend']);
                if ($row['is_favorite']) {
                    $entity->addFavorite($relation['friend']);
                }
            }
        }
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
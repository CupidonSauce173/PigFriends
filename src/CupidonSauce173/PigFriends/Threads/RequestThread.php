<?php


namespace CupidonSauce173\PigFriends\Threads;

use CupidonSauce173\PigFriends\Entities\Request;
use DateTime;
use Exception;
use mysqli;
use Thread;
use Volatile;
use function array_fill;
use function count;
use function implode;
use function microtime;
use function str_repeat;

class RequestThread extends Thread
{
    private Volatile $container;

    /**
     * @param Volatile $container
     */
    function __construct(Volatile $container)
    {
        $this->container = $container;
    }

    /**
     * @throws Exception
     */
    function run()
    {
        $nextTime = microtime(true) + $this->container['config']['request-check-time'];

        include($this->container['folder'] . '\Entities\Request.php');

        $link = mysqli_connect(
            $this->container['mysql-data']['ip'],
            $this->container['mysql-data']['user'],
            $this->container['mysql-data']['password'],
            $this->container['mysql-data']['database'],
            $this->container['mysql-data']['port']
        );

        while ($this->container['runThread']) {
            if (microtime(true) >= $nextTime) {
                $this->processThread($link);
                $nextTime = microtime(true) + $this->container['config']['request-check-time'];
            }
        }
    }

    /**
     * @param mysqli $link
     * @throws Exception
     */
    private function processThread(mysqli $link): void
    {
        # Create, prepare & execute the query.
        $clause = implode(',', array_fill(0, count($this->container['players']), '?'));
        $types = str_repeat('s', count($this->container['players']));
        if (empty($clause) ?? empty($types)) return;

        $queryString = "
            SELECT FriendRequests.id, FriendRequests.receiver, FriendRequests.sender, FriendSettings.lastUsername as senderUsername, FriendRequests.reg_date
            FROM ( FriendSettings
                    INNER JOIN FriendRequests ON FriendSettings.player = FriendRequests.sender
                 ) WHERE FriendRequests.receiver IN ($clause);
        ";

        $stmt = $link->prepare($queryString);
        $stmt->bind_param($types, ...(array)$this->container['players']);
        $stmt->execute();

        $results = $stmt->get_result();

        if ($results === false) return;

        while ($row = $results->fetch_assoc()) {
            if (isset($this->container['requests'][(int)$row['id']])) return;
            $dateTime = new DateTime($row['reg_date']);
            # Build request data
            $requestClass = new Request();
            $requestClass->setId((int)$row['id']);
            $requestClass->setSender($row['sender']);
            $requestClass->setSenderUsername($row['senderUsername']);
            $requestClass->setTarget($row['receiver']);
            $requestClass->setCreationDate($dateTime);
            # Sets the request in the container.
            $this->container['requests'][(int)$row['id']] = $requestClass;
        }
        $stmt->close();
    }
}
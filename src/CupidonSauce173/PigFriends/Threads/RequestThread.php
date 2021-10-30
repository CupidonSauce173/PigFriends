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
use function var_dump;

class RequestThread extends Thread
{
    private Volatile $container;
    private mysqli $db;

    /**
     * @param Volatile $container
     */
    function __construct(Volatile $container)
    {
        $this->container = $container;
    }

    function run()
    {
        $nextTime = microtime(true) + $this->container['config']['request-check-time'];

        include($this->container['folder'] . '\Entities\Request.php');

        while ($this->container['runThread']) {
            if (microtime(true) >= $nextTime) {
                $this->processThread();
                $nextTime = microtime(true) + $this->container['config']['request-check-time'];
            }
        }
    }

    private function processThread(): void
    {
        if (!$this->db) {
            $this->db = new mysqli(
                $this->container['mysql-data']['ip'],
                $this->container['mysql-data']['user'],
                $this->container['mysql-data']['password'],
                $this->container['mysql-data']['database'],
                $this->container['mysql-data']['port']
            );
        }
        # Create, prepare & execute the query.
        $clause = implode(',', array_fill(0, count($this->container['players']), '?'));
        $types = str_repeat('s', count($this->container['players']));
        if (empty($clause) ?? empty($types)) return;
        $stmt = $this->db->prepare("SELECT id,sender,receiver,reg_date FROM FriendRequests WHERE receiver IN ($clause)");
        $stmt->bind_param($types, ...(array)$this->container['players']);
        $stmt->execute();

        $results = $stmt->get_result();

        if ($results === false) return;

        while ($row = $results->fetch_assoc()) {
            try {
                if (array_search((int)$row['id'], $this->container['requests'][$row['receiver']])) return;
                $dateTime = new DateTime($row['reg_date']);
                # Build request data
                $requestClass = new Request();
                $requestClass->setId((int)$row['id']);
                $requestClass->setSender($row['sender']);
                $requestClass->setTarget($row['receiver']);
                $requestClass->setCreationDate($dateTime);
                $this->container['requests'][$row['receiver']][(int)$row['id']] = $requestClass;
            } catch (Exception $e) {
                var_dump('problem while processing row in RequestThread.');
            }
        }
        $stmt->close();
    }
}
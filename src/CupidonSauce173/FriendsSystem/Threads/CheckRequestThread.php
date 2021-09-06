<?php


namespace CupidonSauce173\FriendsSystem\Threads;

use Thread;
use Volatile;
use mysqli;
use DateTime;
use Exception;

use function implode;
use function str_repeat;
use function array_fill;
use function count;
use function var_dump;

class CheckRequestThread extends Thread
{
    /*
     * TODO: Rewrite this shit because at it is right now, it will
     * TODO: keep getting the same requests over and over.
     */
    private array $players;
    private array $dbInfo;
    private array $requests;

    private Volatile $container;

    /**
     * CheckRequestTask constructor.
     * @param array $players
     * @param array $dbInfo
     * @param array $requests
     * @param Volatile $container
     */
    function __construct(array $players, array $dbInfo, array $requests, Volatile $container)
    {
        $this->players = $players;
        $this->dbInfo = $dbInfo;
        $this->requests = $requests;
        $this->container = $container;
    }

    function run()
    {
        $db = new mysqli(
            $this->dbInfo['ip'],
            $this->dbInfo['user'],
            $this->dbInfo['password'],
            $this->dbInfo['database'],
            $this->dbInfo['port']
        );
        # Create, prepare & execute the query.
        $clause = implode(',', array_fill(0, count($this->players), '?'));
        $types = str_repeat('s', count($this->players));
        $stmt = $db->prepare("SELECT id,sender,receiver,reg_date FROM FriendRequests WHERE receiver IN ($clause)");
        $stmt->bind_param($types, ...$this->players);
        $stmt->execute();

        $results = $stmt->get_result();
        if ($results === null) return;

        $rawRequests = [];
        while ($row = $results->fetch_assoc()) {
            try {
                $dateTime = new DateTime($row['reg_date']);
                # Build request data
                $request = [
                    'id' => (int)$row['id'],
                    'sender' => $row['sender'],
                    'receiver' => $row['receiver'],
                    'reg_date' => $dateTime
                ];
                $rawRequests[$row['receiver']][] = $request;
            } catch (Exception $e) {
                var_dump('problem while creating a new DateTime');
            }
        }
        $this->container['request'] = $rawRequests;
    }
}
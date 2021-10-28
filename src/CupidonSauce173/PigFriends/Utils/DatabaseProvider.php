<?php


namespace CupidonSauce173\PigFriends\Utils;

use CupidonSauce173\PigFriends\FriendsLoader;
use mysqli;

class DatabaseProvider
{
    private array $sqlInfo;

    /**
     * DatabaseProvider constructor.
     */
    function __construct(array $sqlInfo)
    {
        $this->sqlInfo = $sqlInfo;
        $this->createDatabaseStructure();
    }

    /**
     * Will create the database structure in MySQL.
     */
    function createDatabaseStructure(): void
    {
        $db = new mysqli(
            $this->sqlInfo['ip'],
            $this->sqlInfo['user'],
            $this->sqlInfo['password'],
            $this->sqlInfo['database'],
            $this->sqlInfo['port']
        );
        if ($db->connect_error) {
            FriendsLoader::getInstance()->getLogger()->error($db->connect_error);
            FriendsLoader::getInstance()->getServer()->shutdown();
        }

        /*
         * ---> FriendRequests <---
         * Will hold every friend requests for 24 hours.
         *
         * ---> FriendRelations <---
         * Will hold the base_player and friend, basically, when a request has been accepted, it will
         * create two new rows, row 1 for base_player will be {username_1} and friend will be {username_02},
         * the second row, base_player = {username_2} and friend = {username_1}. To ease the retrieval of
         * the friends.
         *
         * ---> RelationState <---
         * Will hold the state of the relations from FriendRelations, to know if the player has been set as favorite
         * or as blocked (yes, this table handle the blocked players (when a player blocks a player from sending them
         * friend requests)).
         *
         * ---> FriendSettings Table <---
         * Will store the settings of the player.
         */
        $db->query(
            'CREATE TABLE IF NOT EXISTS FriendRequests(
           id MEDIUMINT NOT NULL AUTO_INCREMENT,
           sender VARCHAR(15) NOT NULL,
           receiver VARCHAR(15) NOT NULL,
           reg_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
           PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;
        ');
        $db->query('
        CREATE TABLE IF NOT EXISTS FriendRelations(
           id MEDIUMINT NOT NULL AUTO_INCREMENT,
           base_player VARCHAR(15) NOT NULL,
           friend VARCHAR(15) NOT NULL,
           reg_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
           PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;
        ');
        $db->query('
        CREATE TABLE IF NOT EXISTS RelationState(
           relation_id MEDIUMINT NOT NULL,
           is_favorite BOOLEAN NOT NULL DEFAULT FALSE,
           is_blocked BOOLEAN NOT NULL DEFAULT FALSE,
           FOREIGN KEY (relation_id) REFERENCES FriendRelations(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;
        ');
        $db->query('
        CREATE TABLE IF NOT EXISTS FriendSettings(
           player VARCHAR(15) NOT NULL,
           request_state BOOLEAN NOT NULL DEFAULT TRUE,
           notify_state BOOLEAN NOT NULL DEFAULT FALSE,
           join_message INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;
        ');
        $db->query('
        CREATE TABLE IF NOT EXISTS FriendsConfigs(
            sector VARCHAR(255) NOT NULL,
            notify BOOLEAN NOT NULL,
            permission BOOLEAN NOT NULL,
            request_check_time INT NOT NULL,
            friend_hits BOOLEAN NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;
        ');
        $db->close();
    }
}
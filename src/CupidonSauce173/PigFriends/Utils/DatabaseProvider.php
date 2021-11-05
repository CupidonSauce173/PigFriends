<?php


namespace CupidonSauce173\PigFriends\Utils;

use CupidonSauce173\PigFriends\FriendsLoader;
use function mysqli_connect;
use function mysqli_query;
use function mysqli_select_db;
use function var_dump;

class DatabaseProvider
{
    /**
     * DatabaseProvider constructor.
     */
    function __construct(array $sqlInfo)
    {
        $this->createDatabaseStructure($sqlInfo);
    }

    /**
     * Will create the database structure in MySQL.
     * @param array $sqlInfo
     */
    function createDatabaseStructure(array $sqlInfo): void
    {
        $link = mysqli_connect(
            $sqlInfo['ip'],
            $sqlInfo['user'],
            $sqlInfo['password'],
            null,
            $sqlInfo['port']
        );
        if ($link->connect_error) {
            FriendsLoader::getInstance()->getLogger()->error($link->connect_error);
            FriendsLoader::getInstance()->getServer()->shutdown();
        }
        $s_db = mysqli_select_db($link, $sqlInfo['database']);
        if (!$s_db) {
            mysqli_query($link, 'CREATE DATABASE ' . $sqlInfo['database']);
            var_dump('Database ' . $sqlInfo['database'] . ' has been created.');
        }
        mysqli_query($link, '
        CREATE TABLE IF NOT EXISTS FriendsConfigs(
            sector VARCHAR(255) NOT NULL,
            notify BOOLEAN NOT NULL,
            permission BOOLEAN NOT NULL,
            request_check_time INT NOT NULL,
            friend_hits BOOLEAN NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;
        ');
        mysqli_query($link, '
        CREATE TABLE IF NOT EXISTS FriendSettings(
           player VARCHAR(15) NOT NULL,
           request_state BOOLEAN NOT NULL DEFAULT TRUE,
           notify_state BOOLEAN NOT NULL DEFAULT FALSE,
           join_message INT NOT NULL DEFAULT 0,
           PRIMARY KEY (player)
        ) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;
        ');
        mysqli_query($link, '
        CREATE TABLE IF NOT EXISTS FriendRequests(
           id MEDIUMINT NOT NULL AUTO_INCREMENT,
           sender VARCHAR(15) NOT NULL,
           receiver VARCHAR(15) NOT NULL,
           reg_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
           PRIMARY KEY (id),
           FOREIGN KEY (sender) REFERENCES FriendSettings(player),
           FOREIGN KEY (receiver) REFERENCES FriendSettings(player)
        ) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;
        ');
        mysqli_query($link, '
        CREATE TABLE IF NOT EXISTS FriendRelations(
           id MEDIUMINT NOT NULL AUTO_INCREMENT,
           base_player VARCHAR(15) NOT NULL,
           friend VARCHAR(15) NOT NULL,
           reg_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
           PRIMARY KEY (id),
           FOREIGN KEY (base_player) REFERENCES FriendSettings(player),
           FOREIGN KEY (friend) REFERENCES FriendSettings(player)
        ) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;
        ');
        mysqli_query($link, '
        CREATE TABLE IF NOT EXISTS RelationState(
           relation_id MEDIUMINT NOT NULL,
           is_favorite BOOLEAN NOT NULL DEFAULT FALSE,
           is_blocked BOOLEAN NOT NULL DEFAULT FALSE,
           FOREIGN KEY (relation_id) REFERENCES FriendRelations(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;
        ');
        mysqli_close($link);
    }
}
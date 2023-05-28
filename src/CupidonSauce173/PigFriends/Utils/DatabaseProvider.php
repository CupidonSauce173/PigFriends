<?php


namespace CupidonSauce173\PigFriends\Utils;

use CupidonSauce173\PigFriends\FriendsLoader;
use function mysqli_connect;

class DatabaseProvider
{
    /**
     * @param array $sqlInfo
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
        $s_db = $link->select_db($sqlInfo['database']);
        if (!$s_db) {
            $link->query('CREATE DATABASE ' . $sqlInfo['database']);
        }
        $link->query('
        CREATE TABLE IF NOT EXISTS FriendsConfigs(
            sector VARCHAR(255) NOT NULL,
            notify BOOLEAN NOT NULL,
            permission BOOLEAN NOT NULL,
            request_check_time INT NOT NULL,
            friend_hits BOOLEAN NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;
        ');
        $link->query('
        CREATE TABLE IF NOT EXISTS FriendSettings(
           player VARCHAR(255) NOT NULL,
           lastUsername VARCHAR(255) NOT NULL,
           request_state BOOLEAN NOT NULL DEFAULT TRUE,
           notify_state BOOLEAN NOT NULL DEFAULT FALSE,
           join_message INT NOT NULL DEFAULT 0,
           PRIMARY KEY (player)
        ) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;
        ');
        $link->query('
        CREATE TABLE IF NOT EXISTS FriendRequests(
           id MEDIUMINT NOT NULL AUTO_INCREMENT,
           sender VARCHAR(255) NOT NULL,
           receiver VARCHAR(255) NOT NULL,
           reg_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
           PRIMARY KEY (id),
           FOREIGN KEY (sender) REFERENCES FriendSettings(player),
           FOREIGN KEY (receiver) REFERENCES FriendSettings(player),
           INDEX FriendRequests_idx_sender_receiver (sender,receiver)
        ) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;
        ');
        $link->query('
        CREATE TABLE IF NOT EXISTS FriendRelations(
           id MEDIUMINT NOT NULL AUTO_INCREMENT,
           base_player VARCHAR(255) NOT NULL,
           friend VARCHAR(255) NOT NULL,
           reg_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
           PRIMARY KEY (id),
           FOREIGN KEY (base_player) REFERENCES FriendSettings(player),
           FOREIGN KEY (friend) REFERENCES FriendSettings(player)
        ) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;
        ');
        $link->query('
        CREATE TABLE IF NOT EXISTS RelationState(
           relation_id MEDIUMINT NOT NULL,
           is_favorite BOOLEAN NOT NULL DEFAULT FALSE,
           is_blocked BOOLEAN NOT NULL DEFAULT FALSE,
           FOREIGN KEY (relation_id) REFERENCES FriendRelations(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;
        ');

        # Create the required triggers.

        # FriendRelationTrigger will create a new entry in the RelationState table
        # when a new FriendRelation entry has been created.
        /*
        $link->query('
        CREATE TRIGGER FriendRelationTrigger 
          AFTER INSERT
          ON FriendRelations FOR EACH ROW
          BEGIN
            INSERT INTO RelationState(relation_id) VALUES (new.id);
          END;
        ');
        $link->close();
        */
    }
}
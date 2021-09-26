<p align="center">
  <img width="150" height="150" src="https://github.com/CupidonSauce173/PigFriends/blob/main/FriendsIcon.png" />
</p>
<h1 align="center"> PigFriends </h1>
<p align="center">Join my discord: https://discord.gg/2QAPHbqrny </p>
<p align="center">This is a friends system that was designed for the now deleted Pigraid Network </p>

### Known Issues

- **Not ready to be used, I didn't even put it in a testing server yet, I didn't finish to write everything I want before starting debugging and fixing all that mess of a code lol**
- **This page is also outdated, I'll update it when I'm nearly done with the plugin.**

| **Feature**                 | **State** | 
| --------------------------- |:----------:|
| MultiThreaded System        | 🧰 |
| Friend Object         | 🧰 |
| Request Object              | 🧰 |
| Simple API                  | 🧰 |
| Translation System          | 🧰 |
| Command Customization       | 🧰 |
| Automated MySQL Constructor | 🧰 |

### Prerequisites

- Working MySQL Server.

### Introduction

This is a simple yet complete friends system where players are allowed to create a list of friends, set favorites and later, send them customizable gifts with all data stored in a MySQL server. The plugin contains a simple API if you want to create third-party addons. This is a part of the Pigraid Network System.

### FriendPlayer Object

| **Property** | **DataType** | **Description** |
| ------------ | :---------- | :------------- |
| $friends     | Array       | List of the friends. |
| $favorites   | Array       | List of favorites. |
| $blocked     | Array       | List of blocked players. |
| $player      | Player      | Target player (PMMP). |

You can get and set the properties of the FriendPlayers whenever you want.

```php
# Get the list of friends.
$friend->getFriends();

# Get the list of blocked players.
$friend->getBlocked();

# Add a favorite friend to the favorites list.
$friend->addFavorite($target);

# Remove a favorite friend from the favorites list.
$friend->removeFavorite($target);

# Block a player from sending friend requests.
$friend->blockPlayer($target);

# Unblock a player from sending friend requests.
$friend->unblockPlayer($target);

# Add a player to the friends list.
$friend->addFriend($target);

# Remove a player from the friends list.
$friend->removeFriend($target);
```


### Request Object

| **Property** | **DataType** | **Description** |
| ------------ | :---------- | :------------- |
| $id          | Int         | Id of the request. |
| $target      | String      | Target of the request. |
| $sender      | String      | Author of the request. |
| $creationDate| DateTime    | Creation date of the request. |
| $accepted    | Boolean     | If the request has been accepted or not. |

You can get and set the properties of the requests whenever you want.

```php
# Get / Set the id.
$request->setId($id);
$request->getId(); # Returns Int.

# Get / Set the target.
$request->setTarget($target);
$request->getTarget(); # Returns String.

# Get / Set the sender.
$request->setSender($author);
$request->getSender(); # Returns String.

# Get / Set the creation date.
$request->setCreationDate($dateTime);
$request->getCreationDate(); # Returns DateTime.

# Get / Set state (if accepted or not).
$request->setAccepted(true / false) # Set to true by default.
$request->isAccepted(); # Returns Boolean.
```

### Config File
 
The configuration file allows you to modify pretty much any aspect of the plugin. You can set the command you want, and it's aliases, the permission and if the players needs the permission to use the command. You can also set the delays between checks from the database for new requests. Here's the list of settings you're allowed to change from the config file.

```yml
# Config for the friends command.

# MySQL server config
mysql-data:
  user: sqlUser
  password: sqlPassword
  database: Minecraft
  port: 3306
  ip: 127.0.0.1

command-main: friends
command-aliases:
  - fr
  - friend

# Note that whatever permission you put "PigFriends." will always be at the beginning.
# In this example, the permission will be PigFriends.permission.friends.
permission: permission.friends

# true = use a permission, false = don't use a permission.
use-permission: true

# The limit of friends that someone can have.
friend-limit: 10

# The amount of friends per page (message / UI)
friend-per-page: 10

# Request check delay (in seconds)
request-check-time: 2
```

### API

The plugin offers a small and simple API that you can use along the requests and FriendPlayer methods. Here are all the methods from the API.

First, you need to register the API. 

```php

public FriendsLoader $api;

function onEnable(){
   $this->api = $this->getServer()->getPluginManager()->getPlugin('PigFriends');
}
```

Then, you can use the API like you wish.

```php

# Get Friend object by name.
$api->getFriendPlayerByName($target); # Returns Friend or null (if didn't find anyone with that name online).

# Get FrienddPlayer object by PMMP Player.
$api->getFriendPlayer($target); # Returns Friend or null (if didn't find any Friend with the supplied PMMP Player object).
# Quick note, if this method returns null, it probably means that the Friend object is still being created and you should try again, even if a player has no friend, it will create a Friend object.
```

### How it works?

When the server starts, it will schedule a repeating task (with a timer that you can set in the config file) to check new requests, when creating a new request, it will not register it in the server, it will send it over the MySQL server and the CheckRequestTask thread will detect it and then create a new request object and display it to the player if they are online. For now, requests are scheduled to be destroyed after 24 hours of being created, the CheckRequestTask thread will also take care of this.

### Command List


| **Name** | **Description** |
| ------------ | :---------- |
| /f add (target) | Send a request to a player to be their friend.
| /f remove (target) | Remove a player from your friends list.
| /f block (target) | Block a player from sending you friend requests.
| /f unblock (target) | Unblock a player from sending you friend requests.
| /f favorite set (target) | Set a friend as favorite.
| /f favorite unset (target) | Unset a friend as favorite.
| /f list (page) | Opens a list of your friends (chat message).
| /f help | Opens an UI with all the explaination on how the plugin works.

### MultiFunctionThread Class

This class is the multi-thread base of the plugin, it will take case of creating / deleting requests, remove friends, add / remove favorites and pretty much everything that is related to MySQL. You can also send over your custom queries via MultiFunctionThread::CUSTOM_QUERY (note that you will need to supply in the $inputs the query (args[0]) and the other data if necessary (args[1]). Here is an example of how to call the CUSTOM_QUERY function.

```php
$inputs = [
    'SELECT id FROM FriendRequests WHERE player = ?',
    ['player' => $friend],
    $dbInfo
];
$multiFunctionThread = new MultiFunctionThread(MultiFunctionThread::CUSTOM_QUERY, $inputs);
$multiFunctionThread->Start() && $multiFunctionThread->Join();
```

Note that this will never return something, so don't try to select things, only insert, delete or modify as you need.

### Translation Class

Simple class to translate messages from the langKeys.ini. You can call it via Translation::Translate($messageIndex, $keys); Will return null or a string.

### Future features

- Implementation of the PigNotify plugin (as soft-depend) to create a new notification when a player receives a friend request or get their request accepted. 
- Implementation of a gift system where players can send gifts (that the owner would set in a gift.yml file).
- Player settings, this feature is already somewhat implemented-ish (only in the database) but basically, players will be able to block all incoming requests, notifications, enable / disable a message that would be sent to them if a player jumps online and more.

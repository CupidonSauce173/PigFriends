<p align="center">
  <img width="150" height="150" src="https://github.com/CupidonSauce173/PigFriends/blob/main/FriendsIcon.png" />
</p>
<h1 align="center"> PigFriends </h1>
<p align="center">Join my discord: https://discord.gg/2QAPHbqrny </p>
<p align="center">This is a friends system that was designed for the now deleted Pigraid Network </p>

### Known Issues

- **Not ready to be used, I didn't even put it in a testing server yet, I didn't finish writing everything I want before
  starting debugging and fixing all that mess of a code lol**
- **Documentation last update: 2021-11-06**

| **Feature**                 | **State** | 
| --------------------------- |:----------:|
| MultiThreaded System        | ✔️ |
| Friend Object         | ✔️ |
| Request Object              | ✔️ |
| Simple API                  | 🧰 |
| Translation System          | ✔️ |
| Command Customization       | ✔️ |
| Automated MySQL Constructor | ✔️ |
| Sector Feature For Configs  | ❌ |
| Disable / Enable Friendly Fire | ❌ |

### Prerequisites

- Working MySQL Server.

### Introduction

This is a simple yet complete friends system where players are allowed to create a list of friends, set favorites and
later, send them customizable gifts with all data stored in a MySQL server. The plugin contains a simple API if you want
to create third-party addons. This is a part of the Pigraid Network System.

### Friend Entity

| **Property** | **DataType** | **Description** |
| ------------ | :---------- | :------------- |
| $friends     | Array       | List of the friends. |
| $favorites   | Array       | List of favorites. |
| $blocked     | Array       | List of blocked players. |
| $player      | Player      | Target player (PMMP). |
| $notifyState | bool        | If the player can receive notifications. |
| $requestState| bool        | If the player can receive friend requests.
| $joinMessage | int         | Which setting is set for when a friend joins the server. |

You can get and set the properties of the FriendPlayers whenever you want.

```php
# Method to know if a friend (by username) is set as favorite.
$friend->isFavorite(string $friend): bool

# Method to get if the player receives a message when one of their friends joins the server.
$friend->getJoinSetting(): int

# Method to set if the player receives a message when one of t heir friends joins the server.
$friend->setJoinSetting(int $state): void

# Method to know if the notification setting has been set to true.
$friend->getNotifyState(): bool

# Method to set true or false the notification setting.
$friend->setNotifyState(bool $state): void

# Method to get the request setting.
$friend->getRequestState(): bool

# Method to set the request setting.
$friend->setRequeststate(bool $state): void

# Method to set the player settings directly from the query.
$friend->setRawSettings(bool $requestState, bool $notifyState, int $joinMessage): void

# Method to return all the requests sent by the player.
# \\MIGHT BE CHANGED//
$friend->getRequestSent(): array

# Method to set all the requests sent by the player.
# \\MIGHT BE CHANGED//
$friend->setAllRequestSent(array $value): void

# Method to add a request in the requestSent list.
# \\MIGHT BE CHANGED//
$friend->addRequestSent(string $value): void

# Method to remove a request sent by the player.
# \\MIGHT BE CHANGED//
$friend->removeRequestSent(string $value): void

# Method to return all the friends of the player.
$friend->getFriends(): array

# Method to return all the blocked players that the player blocked.
$friend->getBlocked(): array 

# Method to return the player username of the Friend.
# \\MIGHT BE CHANGED//
$friend->getPlayer(): string

# Method to set the player username.
# \\MIGHT BE CHANGED//
$friend->setPlayer(string $username): void

# Method to add a friend as favorite.
$friend->addFavorite(string $target): bool

# Method to remove a friend from the favorites.
$friend->removeFavorite(string $target): bool

# Method to add a player to the blocked list.
$friend->blockPlayer(string $target): bool

# Method to remove a player from the blocked list.
$friend->unblockPlayer(string $target): bool

# Method to add a player to a friend list.
$friend->addFriend(string $target): bool

# Method to remove a friend from a friend list.
$friend->removeFriend(string $target): bool

# Method to return all the requests targeted to the player.
# \\MIGHT BE CHANGED//
$friend->getRequests(): ?array

# List of available setting for the joinMessage setting.

const ALL_FRIENDS = 0; # Will receive a message every times a friend joins the server.
const ONLY_FAVORITE = 1; # Will receive a message only when favorite friend joins the server.
const NOBODY = 2; # Will never receive a message whenever a friend / favorite joins the server.
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

### Order object

Orders are objects that are created & executed to call different methods in the MTF (MultiFunctionThread) class. It also
works with the OrderListenerTask for special requests from the MTF.

| **Property** | **DataType** | **Description** |
| ------------ | :---------- | :------------- |
| $id          | String      | Id of the request. |
| $mysql       | Bool        | If the order needs a SQL connection |
| $inputs      | Array       | Required data for the order |
| $event       | Int         | Const from the MultiFunctionThread or ListenerConstants. |

```php
# Method to execute the order (must be called at the end).
$order->execute(bool $isListener = false) : ?string

# Method to get the ID of the order.
$order->getId(): ?string

# Method to tell if the order has SQL interactions.
$order->isSQL(bool $value = false): ?bool

# Method to set the event that the order will request in the MultiFunctionThread
# or the OrderListenerTask (if it's a special task).
$order->setCall(int $event): void

# Method to see which event the order will call, returns null if not set yet.
$order->getCall(): ?int

# Method to see what inputs the order holds.
$order->getInputs() : array

# Method to set the inputs of the order (data), must be an array.
$order->setInputs(array $inputs): void
```

#### Constants that can be used by the Order Object.

```php
MultiFunctionThread
    const REFUSE_REQUEST = 0; # Calls refuseRequest()
    const ACCEPT_REQUEST = 1; # Calls acceptRequest()
    const SEND_NEW_REQUEST = 2; # Calls sendNewRequest()
    const REMOVE_FRIEND = 3; # Calls removeFriend()
    const ADD_FAVORITE = 4; # Calls addRemoveFavorite()
    const REMOVE_FAVORITE = 5; # Calls addRemoveFavorite()
    const CUSTOM_QUERY = 6; # Calls customQuery()
    const CREATE_FRIEND_ENTITY = 7; # Calls createFriendEntity()
    const UPDATE_USER_SETTINGS = 8; # Calls updateUserSettings()
    const BLOCK_PLAYER = 9; # Calls blockUnblockPlayer()
    const UNBLOCK_PLAYER = 10; # Calls blockUnblockPlayer()
    
ListenerConstants
    const REQUEST_ALREADY_EXISTS = 1; # Calls requestAlreadyExists()
    const REQUEST_CREATED = 2; # Calls requestCreated()

# Note : ListenerConstants methods are in the OrderListenerTask class.
```

### Config File

The configuration file allows you to modify pretty much any aspect of the plugin. You can set the command you want, and
it's aliases, the permission and if the players needs the permission to use the command. You can also set the delays
between checks from the database for new requests. Here's the list of settings you're allowed to change from the config
file.

### Note

In the future, there will be a config sector feature where you will be able to create different sectors in a SQL table.
So you can group different servers together if you have a network.

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

# Task that checks any special state for certain actions (should be left at 1 second)
# This task mostly just sends messages to the players after performing certain tasks.
order-listener-task-time: 1

# Friendly fire (true = can hit friends, false = can't hit friends)
friendly-fire: true

# Soft-depends

# PigNotify Feature.
pig-notify: false

# Commando Feature (for registering sub-commands on client-side).
commando: false

# NEVER CHANGE THIS SETTING.
development: true
```

### API

The plugin offers a small and simple API that you can use along the requests and FriendPlayer methods. Here are all the
methods from the API.

First, you need to register the API.

```php

public FriendsLoader $api;

function onEnable(){
   $this->api = $this->getServer()->getPluginManager()->getPlugin('PigFriends');
}

# Note, you will mostly only need this if you want to access something
# like the container where it holds most of the important data.

# Here is the container structure if you want to manipulate certain
# information directly from it.

$container = new Volatille();
$container['config'] # All the config information (array).
$container['friends'] # All the friend entities.
$container['requests'] # All the request objects.
$container['langKeys'] # All the information from the langKeys.ini
$container['mysql-data'] # SQL connection information.
$container['players'] # All the usernames of the connected players.
$container['multiFunctionQueue'] # Holds all the Order objects that are being executed. (MultiFunctionThread)
$container['orderListener'] # Holds all the Order objects that are being executed (OrderListenerTask)
$container['runThread'] # Set to true, if false, the MultiFunctionThread & RequestThread will stop.
$container['folder'] # Folder of the plugin (to include files in the different threads).
```

Then, you can use the API like you wish.

```php

# Gets a Friend entity from a username.
Utils::getFriendEntity(string $target) : ?Friend

# Add a friend entity to the list of friends in the container.
Utils::addFriendEntity(Friend $friend) : void

# Remove a friend entity from the list of friends in the container.
Utils::removeFriendEntity(Friend $friend) : void

# Will translate a langKey from the langKey.ini to a readable message for the players.
Utils::Translate(string $message, array $langKey = null): ?string

```

### How it works?

When the server starts, it will schedule a repeating task (with a timer that you can set in the config file) to check
new requests, when creating a new request, it will not register it in the server, it will send it over the MySQL server
and the CheckRequestTask thread will detect it and then create a new request object and display it to the player if they
are online. For now, requests are scheduled to be destroyed after 24 hours of being created, the CheckRequestTask thread
will also take care of this.

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
| /f help | Opens an UI with all the explanation on how the plugin works.

### MultiFunctionThread Class

This class is the multi-thread base of the plugin, it will take case of creating / deleting requests, remove friends,
add / remove favorites and pretty much everything that is related to MySQL. You can also send over your custom queries
via MultiFunctionThread::CUSTOM_QUERY (note that you will need to supply in the $inputs the query (args[0]) and the
other data if necessary (args[1])). In order to execute a function in the MultiFunctionThread class, you must create a
new order object and then execute it. Here's an example on how to call the CUSTOM_QUERY method.

```php
$order = new Order();
$order->setCall(MultiFunctionThread::CUSTOM_QUERY);
$order->isSQL(true);
$data = [0, 'CupidonSauce173']; # Values
$order->setInputs([
  'UPDATE FriendSettings SET request_state = ? WHERE player = ?',
  ['is', $data] # i = 0, s = 'CupidonSauce173'
]);
$order->execute();
```

Note that this will never return something, so don't try to select things, only insert, delete or modify as you need.

### Translation Class

Simple class to translate messages from the langKeys.ini. You can call it via Utils::Translate($messageIndex, $keys);
Will return null or a string.

### MySQL Database Structure

<p align="center">
  <img src="https://github.com/CupidonSauce173/PigFriends/blob/main/PigFriendsDataStructure.png" />
</p>

### Future features

- Implementation of the PigNotify plugin (as soft-depend) to create a new notification when a player receives a friend
  request or get their request accepted.
- Implementation of a gift system where players can send gifts (that the owner would set in a gift.yml file).
- Player settings, this feature is already somewhat implemented-ish (only in the database) but basically, players will
  be able to block all incoming requests, notifications, enable / disable a message that would be sent to them if a
  player jumps online and more.

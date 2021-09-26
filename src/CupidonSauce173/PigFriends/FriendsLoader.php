<?php


namespace CupidonSauce173\PigFriends;

use CupidonSauce173\PigFriends\Threads\RequestThread;
use CupidonSauce173\PigFriends\Utils\Api;
use CupidonSauce173\PigFriends\Utils\DatabaseProvider;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

use Thread;
use Volatile;

use function file_exists;
use function array_map;
use function preg_match;
use function parse_ini_file;

class FriendsLoader extends PluginBase
{
    public static FriendsLoader $instance;
    public Api $api;

    # Threaded field
    public Thread $requestThread;
    public Volatile $container;


    function onEnable()
    {
        $this->api = new Api();
        new DatabaseProvider();
        $this->getServer()->getPluginManager()->registerEvents(new EventsListener(), $this);

        # Verification of the config files.
        if (!file_exists($this->getDataFolder() . 'config.yml')) {
            $this->saveResource('config.yml');
        }
        if (!file_exists($this->getDataFolder() . 'langKeys.ini')) {
            $this->saveResource('langKeys.ini');
        }

        $config = new Config($this->getDataFolder() . 'config.yml', Config::YAML);
        if (preg_match('/[^A-Za-z-.]/', $this->container['configs']['permission'])) {
            $this->getLogger()->error('Wrong permission setting. Please do not put any special characters.');
            $this->getServer()->shutdown();
        }

        # Register the commands
        $this->getServer()->getCommandMap()->register('PigFriends', new Commands());

        # MySQL field & Threads
        $this->container = new Volatile();
        $this->container['config'] = [];
        $this->container['friends'] = [];
        $this->container['requests'] = [];
        $this->container['langKeys'] = [];
        $this->container['mysql-data'] = [];
        $this->container['players'] = [];

        # Populating container with configs, plugin folder & mysql-data & langKeys.
        $this->container['config'] = $config->getAll();
        $this->container['folder'] = __DIR__;
        $this->container['langKeys'] = array_map('\stripcslashes', parse_ini_file($this->getDataFolder() . 'langKeys.ini', false, INI_SCANNER_RAW));
        $this->container['mysql-data'] = $this->container['config']['mysql-data'];

        # Starting the RequestThread.
        $this->requestThread = new RequestThread($this->container);
        $this->requestThread->start();
    }

    /**
     * @return FriendsLoader
     */
    function onLoad(): FriendsLoader
    {
        return self::$instance;
    }

    /**
     * @return static
     */
    public static function getInstance(): self
    {
        return self::$instance;
    }
}
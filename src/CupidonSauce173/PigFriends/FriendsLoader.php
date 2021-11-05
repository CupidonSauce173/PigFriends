<?php


namespace CupidonSauce173\PigFriends;

use CupidonSauce173\PigFriends\Threads\MultiFunctionThread;
use CupidonSauce173\PigFriends\Threads\RequestThread;
use CupidonSauce173\PigFriends\Utils\DatabaseProvider;
use CupidonSauce173\PigFriends\Utils\Utils;
use Exception;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use Thread;
use Volatile;
use function array_map;
use function file_exists;
use function parse_ini_file;
use function preg_match;

class FriendsLoader extends PluginBase
{
    public static FriendsLoader $instance;
    public Utils $api;

    # Threaded field
    public Thread $requestThread;
    public Thread $multiFunctionThread;

    public Volatile $container;

    /**
     * @return FriendsLoader
     */
    public static function getInstance(): self
    {
        return self::$instance;
    }

    /**
     * @throws Exception
     */
    function onEnable()
    {
        # Verification of the config files.
        if (!file_exists($this->getDataFolder() . 'config.yml')) {
            $this->saveResource('config.yml');
        }
        if (!file_exists($this->getDataFolder() . 'langKeys.ini')) {
            $this->saveResource('langKeys.ini');
        }

        $config = new Config($this->getDataFolder() . 'config.yml', Config::YAML);

        $this->initThreadField($config);

        new DatabaseProvider($config->get('mysql-data'));

        if (preg_match('/[^A-Za-z-.]/', $this->container['config']['permission'])) {
            $this->getLogger()->error('Wrong permission settings. Please do not put any special characters.');
            $this->getServer()->shutdown();
        }
        if ($config->get('development') === true) {
            $this->getLogger()->warning('PigFriends is under development, please remove this plugin in order to start the server.');
            $this->getServer()->shutdown();
            return;
        }

        $this->api = new Utils();
        $this->getServer()->getPluginManager()->registerEvents(new EventsListener(), $this);

        # Register the commands
        $this->getServer()->getCommandMap()->register('PigFriends', new Commands());
    }

    /**
     * Method to start the different threads that the plugin will need.
     * @throws Exception
     */
    function initThreadField(Config $config): void
    {
        # MySQL field & Threads
        $this->container = new Volatile();
        $this->container['config'] = [];
        $this->container['friends'] = [];
        $this->container['requests'] = [];
        $this->container['langKeys'] = [];
        $this->container['mysql-data'] = [];
        $this->container['players'] = [];
        $this->container['multiFunctionQueue'] = [];
        $this->container['runThread'] = true;

        # Populating container with configs, plugin folder & mysql-data & langKeys.
        $this->container['config'] = $config->getAll();
        $this->container['folder'] = __DIR__;
        $this->container['langKeys'] = array_map('\stripcslashes', parse_ini_file($this->getDataFolder() . 'langKeys.ini', false, INI_SCANNER_RAW));
        $this->container['mysql-data'] = $this->container['config']['mysql-data'];

        # Starting the RequestThread.
        $this->requestThread = new RequestThread($this->container);
        $this->requestThread->start();

        # Starting MultiFunctionThread.
        $this->multiFunctionThread = new MultiFunctionThread($this->container);
        $this->multiFunctionThread->start();
    }

    function onDisable()
    {
        # Will stop all threads from running.
        $this->container['runThread'] = false;
    }

    /**
     * @return void
     */
    function onLoad(): void
    {
        self::$instance = $this;
    }
}
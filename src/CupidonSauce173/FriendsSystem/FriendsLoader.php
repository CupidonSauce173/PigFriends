<?php


namespace CupidonSauce173\FriendsSystem;

use CupidonSauce173\FriendsSystem\Entities\Request;
use CupidonSauce173\FriendsSystem\Threads\CheckRequestThread;
use CupidonSauce173\FriendsSystem\Utils\Api;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;

use Thread;
use Volatile;

use function file_exists;
use function array_map;
use function array_search;
use function preg_match;
use function parse_ini_file;

class FriendsLoader extends PluginBase
{
    public static FriendsLoader $instance;
    public array $langKeys;
    public array $config;
    public Api $api;

    # Threaded field
    public Thread $requestThread;
    public Volatile $sharedContainer;

    # Container for the friend and request objects
    public array $objectContainer = [
        'friends' => [],
        'requests' => []
    ];


    public function onEnable()
    {
        self::$instance = $this;
        $this->api = new Api();

        if (!file_exists($this->getDataFolder() . 'config.yml')) {
            $this->saveResource('config.yml');
        }
        if (!file_exists($this->getDataFolder() . 'langKeys.ini')) {
            $this->saveResource('langKeys.ini');
        }

        $this->langKeys = array_map('\stripcslashes', parse_ini_file($this->getDataFolder() . 'langKeys.ini', false, INI_SCANNER_RAW));

        $config = new Config($this->getDataFolder() . 'config.yml', Config::YAML);
        $this->config = $config->getAll();
        if (preg_match('/[^A-Za-z-.]/', $this->config['permission'])) {
            $this->getLogger()->error('Wrong permission setting. Please do not put any special characters.');
            $this->getServer()->shutdown();
        }

        # Register the commands
        $this->getServer()->getCommandMap()->register('PigFriends',new Commands());

        # MySQL field & Threads
        $this->sharedContainer = new Volatile();

        $this->requestThread = new CheckRequestThread([], $this->config['mysql-data'], $this->objectContainer['requests'], $this->sharedContainer);
        $this->requestThread->start();

        # Request check task
        /*
         * This block of code will take care of gathering, building and populating the requests
         * That are targeted to the online players.
         */
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(
            function (): void {
                if (!$this->requestThread->isRunning()) {
                    $names = [];
                    foreach ($this->getServer()->getOnlinePlayers() as $player) {
                        $names[] = $player->getName();
                    }
                    $this->requestThread = new CheckRequestThread($names, $this->config['mysql-data'], $this->objectContainer['requests'], $this->sharedContainer);
                    $this->requestThread->start() && $this->requestThread->join();
                    foreach ($names as $name) {
                        if (!isset($this->sharedContainer['requests'][$name])) return;
                        foreach ($this->sharedContainer['requests'][$name] as $data) {
                            // Sketchy as fuck field
                            if(array_search((object)$data, $this->sharedContainer['requests'][$name]) !== false) return;
                            // Sketchy as fuck field end
                            $request = new Request();
                            $request->setId($data['id']);
                            $request->setSender($data['sender']);
                            $request->setTarget($data['target']);
                            $request->setAccepted($data['isAccepted']);
                            $request->setCreationDate($data['dateTime']);
                            $this->objectContainer['requests'][$name][] = $request;
                        }
                    }
                }
            }
        ), $this->config['request-check-time'] * 20);
    }

    /**
     * @return FriendsLoader|void
     */
    public function onLoad()
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
<?php


namespace CupidonSauce173\PigFriends;

use CupidonSauce173\PigFriends\Entities\Order;
use CupidonSauce173\PigFriends\Entities\Request;
use CupidonSauce173\PigFriends\Threads\MultiFunctionThread;
use CupidonSauce173\PigFriends\Utils\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat as TF;
use function array_search;
use function array_shift;
use function count;
use function is_int;
use function round;
use function strtolower;

class Commands extends Command implements PluginIdentifiableCommand
{
    private UI $ui;

    function __construct()
    {
        parent::__construct(FriendsLoader::getInstance()->container['config']['friends'],
            Utils::Translate('message.command.description'),
            '/' . FriendsLoader::getInstance()->container['config']['command-main'],
            FriendsLoader::getInstance()->container['config']['command.aliases']
        );
        if (FriendsLoader::getInstance()->container['config']['use-permission']) {
            $this->setPermission(('PigFriends.' . FriendsLoader::getInstance()->container['config']['permission']));
        }

        $this->ui = new UI();
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     */
    function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (FriendsLoader::getInstance()->container['config']['use-permission']) {
            if (!$sender->hasPermission($this->getPermission())) {
                $sender->sendMessage(Utils::Translate('error.command.no.permission'));
                return;
            }
        }
        if (!isset($args[0])) {
            /** @var Player $sender */
            $this->ui->mainUI($sender);
            return;
        }
        /** @var Player $sender */
        $friend = FriendsLoader::getInstance()->api->getFriendPlayer($sender->getName());
        if ($friend == null) return; # Means that the object is still being created.
        switch ($args[0]) {
            case 'add':
                if (!isset($args[1])) {
                    $sender->sendMessage(Utils::Translate('error.bad.args'));
                    break;
                }
                $target = $args[1];
                foreach ($friend->getFriends() as $pFriend) {
                    if (strtolower($pFriend) == strtolower($target)) {
                        $sender->sendMessage(Utils::Translate('error.already.friend', ['friend' => $target]));
                        break;
                    }
                    $order = new Order();
                    $order->isSQL(true);
                    $order->setCall(MultiFunctionThread::SEND_NEW_REQUEST);
                    $order->setInputs([
                        $sender->getName(),
                        $target,
                    ]);
                    $order->execute();
                    break;
                }
                break;
            case 'remove':
                if (!isset($args[1])) {
                    $sender->sendMessage(Utils::Translate('error.bad.args'));
                    break;
                }
                $target = $args[1];
                foreach ($friend->getFriends() as $pFriend) {
                    if (strtolower($pFriend) == strtolower($target)) {
                        $friend->removeFriend(strtolower($target));
                        $sender->sendMessage(Utils::Translate('utils.friend.removed', ['friend' => $target]));
                        break;
                    }
                    $sender->sendMessage(Utils::Translate('error.not.friend', ['friend' => $target]));
                    break;
                }
                break;
            case 'accept':
                if (!isset($args[1])) {
                    $sender->sendMessage(Utils::Translate('error.bad.args'));
                    break;
                }
                $target = strtolower($args[1]);
                $requests = $friend->getRequests();
                if ($requests == null) {
                    $sender->sendMessage(Utils::Translate('error.no.requests', ['target' => $target]));
                    break;
                }
                /** @var Request $request */
                foreach ($requests as $request) {
                    if ($request->getSender() == $target) return;
                    $order = new Order();
                    $order->isSQL(true);
                    $order->setCall(MultiFunctionThread::ACCEPT_REQUEST);
                    $order->setInputs([
                        $sender->getName(),
                        $request->getId(),
                        FriendsLoader::getInstance()->container['mysql-data']
                    ]);
                    $order->execute();
                    $sender->sendMessage(Utils::Translate('utils.request.accepted', ['target' => $target]));
                    break;
                }
                break;
            case 'refuse':
                if (!isset($args[1])) {
                    $sender->sendMessage(Utils::Translate('error.bad.args'));
                    break;
                }
                $target = strtolower($args[1]);
                $requests = $friend->getRequests();
                if ($requests == null) {
                    $sender->sendMessage(Utils::Translate('no.requests', ['target' => $target]));
                    break;
                }
                /** @var Request $request */
                foreach ($requests as $request) {
                    if ($request->getSender() == $target) return;
                    $order = new Order();
                    $order->isSQL(true);
                    $order->setCall(MultiFunctionThread::REFUSE_REQUEST);
                    $order->setInputs([
                        $request->getId(),
                        null,
                        FriendsLoader::getInstance()->container['mysql-data']
                    ]);
                    $order->execute();
                    $sender->sendMessage(Utils::Translate('utils.request.refused', ['target' => $target]));
                    break;
                }
                break;
            case 'list':
                $friends = $friend->getFriends();
                $count = count($friends);
                $maxPerPage = FriendsLoader::getInstance()->container['config']['friend-per-page'];
                $pages = round($count / $maxPerPage);
                if (isset($args[1])) {
                    if (is_int((int)$args[1])) {
                        if ($pages > (int)$args[1]) {
                            $sender->sendMessage(Utils::Translate('error.page.not.found', ['selectedPage' => (int)$args[1]]));
                            break;
                        } else {
                            for ($pass = (int)$args[1] * $maxPerPage; $pass === 0; $pass--) {
                                array_shift($friends);
                            }
                            $sender->sendMessage(Utils::Translate('friend.list.title'));
                            foreach ($friends as $f) {
                                if (FriendsLoader::getInstance()->getServer()->getPlayer($f) !== null) {
                                    $sender->sendMessage(TF::GREEN . $f);
                                } else {
                                    $sender->sendMessage(TF::RED . $f);
                                }
                            }
                            $sender->sendMessage(Utils::Translate('command.remaining.pages',
                                [
                                    'currentPage' => $pages - 1,
                                    'totalPages' => $pages
                                ]));
                        }
                    }
                } else {
                    $sender->sendMessage(Utils::Translate('friend.list.title'));
                    $i = 0;
                    foreach ($friends as $f) {
                        if ($i == $maxPerPage) return;
                        if (FriendsLoader::getInstance()->getServer()->getPlayer($f) !== null) {
                            $sender->sendMessage(TF::GREEN . $f);
                        } else {
                            $sender->sendMessage(TF::RED . $f);
                        }
                        $i++;
                    }
                    $sender->sendMessage(Utils::Translate('command.remaining.pages',
                        [
                            'currentPage' => $pages - 1,
                            'totalPages' => $pages
                        ]));
                }
                break;
            case 'block':
                if (!isset($args[1])) {
                    $sender->sendMessage(Utils::Translate('error.bad.args'));
                    break;
                }
                $target = strtolower($args[1]);
                if (array_search($target, $friend->getBlocked())) {
                    $sender->sendMessage(Utils::Translate('error.already.blocked', ['target' => $target]));
                    break;
                }
                $friend->blockPlayer($target);
                $sender->sendMessage(Utils::Translate('utils.player.blocked', ['target' => $target]));
                break;
            case 'unblock':
                if (!isset($args[1])) {
                    $sender->sendMessage(Utils::Translate('error.bad.args'));
                    break;
                }
                $target = strtolower($args[1]);
                if (!array_search($target, $friend->getBlocked())) {
                    $sender->sendMessage(Utils::Translate('error.not.blocked', ['target' => $target]));
                    break;
                }
                $friend->unblockPlayer($target);
                $sender->sendMessage(Utils::Translate('utils.player.unblocked', ['target' => $target]));
                break;
            case 'favorite':
                if (!isset($args[1]) or !isset($args[2])) {
                    $sender->sendMessage(Utils::Translate('error.bad.args'));
                    break;
                }
                switch ($args[1]) {
                    case 'add':
                        /*
                         * TODO: Implement /f favorite add.
                         */
                        break;
                    case 'remove':
                        /*
                         * TODO: Implement /f favorite remove.
                         */
                        break;
                }
                break;
        }
    }

    /**
     * @return Plugin
     */
    function getPlugin(): Plugin
    {
        return FriendsLoader::getInstance();
    }
}
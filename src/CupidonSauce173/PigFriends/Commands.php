<?php


namespace CupidonSauce173\PigFriends;

use CupidonSauce173\PigFriends\Entities\Order;
use CupidonSauce173\PigFriends\Entities\Request;
use CupidonSauce173\PigFriends\Threads\MultiFunctionThread;
use CupidonSauce173\PigFriends\Utils\Translation;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat as TF;

use function strtolower;
use function array_shift;
use function array_search;
use function round;
use function count;
use function is_int;

class Commands extends Command implements PluginIdentifiableCommand
{
    public UI $ui;

    function __construct()
    {
        parent::__construct(FriendsLoader::getInstance()->container['configs']['friends'],
            Translation::Translate('message.command.description'),
            '/' . FriendsLoader::getInstance()->container['configs']['command-main'],
            FriendsLoader::getInstance()->container['configs']['command.aliases']
        );
        if (FriendsLoader::getInstance()->container['configs']['use-permission']) {
            $this->setPermission(('PigFriends.' . FriendsLoader::getInstance()->container['configs']['permission']));
        }
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     */
    function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (FriendsLoader::getInstance()->container['configs']['use-permission']) {
            if (!$sender->hasPermission($this->getPermission())) {
                $sender->sendMessage(Translation::Translate('command.no.permission'));
                return;
            }
        }
        if (!isset($args[0])) {
            /** @var Player $sender */
            $this->ui->mainUI($sender);
            return;
        }
        /** @var Player $sender */
        $friend = FriendsLoader::getInstance()->api->getFriendPlayer($sender);
        if ($friend == null) return; # Means that the object is still being created.
        switch ($args[0]) {
            case 'add':
                if (!isset($args[1])) {
                    $sender->sendMessage(Translation::Translate('bad.args'));
                    break;
                }
                $target = $args[1];
                foreach ($friend->getFriends() as $pFriend) {
                    if (strtolower($pFriend) == strtolower($target)) {
                        $sender->sendMessage(Translation::Translate('already.friend', ['friend' => $target]));
                        break;
                    }
                    /*
                     * TODO: Send friend request to the target.
                     */
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
                    $sender->sendMessage(Translation::Translate('bad.args'));
                    break;
                }
                $target = $args[1];
                foreach ($friend->getFriends() as $pFriend) {
                    if (strtolower($pFriend) == strtolower($target)) {
                        $friend->removeFriend(strtolower($target));
                        $sender->sendMessage(Translation::Translate('friend.removed', ['friend' => $target]));
                        break;
                    }
                    $sender->sendMessage(Translation::Translate('not.friend', ['friend' => $target]));
                    break;
                }
                break;
            case 'accept':
                if (!isset($args[1])) {
                    $sender->sendMessage(Translation::Translate('bad.args'));
                    break;
                }
                $target = strtolower($args[1]);
                $requests = $friend->getRequests();
                if ($requests == null) {
                    $sender->sendMessage(Translation::Translate('no.requests', ['target' => $target]));
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
                    $sender->sendMessage(Translation::Translate('request.accepted', ['target' => $target]));
                    break;
                }
                break;
            case 'refuse':
                if (!isset($args[1])) {
                    $sender->sendMessage(Translation::Translate('bad.args'));
                    break;
                }
                $target = strtolower($args[1]);
                $requests = $friend->getRequests();
                if ($requests == null) {
                    $sender->sendMessage(Translation::Translate('no.requests', ['target' => $target]));
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
                    $sender->sendMessage(Translation::Translate('request.refused', ['target' => $target]));
                    break;
                }
                break;
            case 'list':
                $friends = $friend->getFriends();
                $count = count($friends);
                $maxPerPage = FriendsLoader::getInstance()->container['configs']['friend-per-page'];
                $pages = round($count / $maxPerPage);
                if (isset($args[1])) {
                    if (is_int((int)$args[1])) {
                        if ($pages > (int)$args[1]) {
                            $sender->sendMessage(Translation::Translate('page.not.found', ['selectedPage' => (int)$args[1]]));
                            break;
                        } else {
                            for ($pass = (int)$args[1] * $maxPerPage; $pass === 0; $pass--) {
                                array_shift($friends);
                            }
                            $sender->sendMessage(Translation::Translate('friend.list.title'));
                            foreach ($friends as $f) {
                                if (FriendsLoader::getInstance()->getServer()->getPlayer($f) !== null) {
                                    $sender->sendMessage(TF::GREEN . $f);
                                } else {
                                    $sender->sendMessage(TF::RED . $f);
                                }
                            }
                            $sender->sendMessage(Translation::Translate('command.remaining.pages',
                                [
                                    'currentPage' => $pages - 1,
                                    'totalPages' => $pages
                                ]));
                        }
                    }
                } else {
                    $sender->sendMessage(Translation::Translate('friend.list.title'));
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
                    $sender->sendMessage(Translation::Translate('command.remaining.pages',
                        [
                            'currentPage' => $pages - 1,
                            'totalPages' => $pages
                        ]));
                }
                break;
            case 'block':
                if (!isset($args[1])) {
                    $sender->sendMessage(Translation::Translate('bad.args'));
                    break;
                }
                $target = strtolower($args[1]);
                if (array_search($target, $friend->getBlocked())) {
                    $sender->sendMessage(Translation::Translate('already.blocked', ['target' => $target]));
                    break;
                }
                $friend->blockPlayer($target);
                $sender->sendMessage(Translation::Translate('player.blocked', ['target' => $target]));
                break;
            case 'unblock':
                if (!isset($args[1])) {
                    $sender->sendMessage(Translation::Translate('bad.args'));
                    break;
                }
                $target = strtolower($args[1]);
                if (!array_search($target, $friend->getBlocked())) {
                    $sender->sendMessage(Translation::Translate('already.not.blocked', ['target' => $target]));
                    break;
                }
                $friend->unblockPlayer($target);
                $sender->sendMessage(Translation::Translate('player.unblocked', ['target' => $target]));
                break;
            case 'favorite':
                if (!isset($args[1]) or !isset($args[2])) {
                    $sender->sendMessage(Translation::Translate('bad.args'));
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
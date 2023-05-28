<?php


namespace CupidonSauce173\PigFriends\Lib;


use pocketmine\form\Form as IForm;
use pocketmine\player\Player;

abstract class Form implements IForm
{

    protected array $data = [];
    /** @var callable */
    private $callable;

    /**
     * @param callable|null $callable $callable
     */
    function __construct(?callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * @param Player $player
     * @see Player::sendForm()
     */
    function sendToPlayer(Player $player): void
    {
        $player->sendForm($this);
    }

    /**
     * @param Player $player
     * @param mixed $data
     */
    function handleResponse(Player $player, $data): void
    {
        $this->processData($data);
        $callable = $this->getCallable();
        if ($callable !== null) {
            $callable($player, $data);
        }
    }

    /**
     * @param $data
     */
    function processData(&$data): void
    {

    }

    /**
     * @return callable|null
     */
    function getCallable(): ?callable
    {
        return $this->callable;
    }

    function jsonSerialize(): array
    {
        return $this->data;
    }
}
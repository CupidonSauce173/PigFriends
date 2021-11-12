<?php

namespace CupidonSauce173\PigFriends\Lib;

class ModalForm extends Form
{
    private string $content = '';

    /**
     * @param callable|null $callable
     */
    function __construct(?callable $callable)
    {
        parent::__construct($callable);
        $this->data['type'] = 'modal';
        $this->data['title'] = '';
        $this->data['content'] = $this->content;
        $this->data['button1'] = '';
        $this->data['button2'] = '';
    }

    /**
     * @param string $title
     */
    function setTitle(string $title): void
    {
        $this->data['title'] = $title;
    }

    /**
     * @return string
     */
    function getContent(): string
    {
        return $this->data['content'];
    }

    /**
     * @param string $content
     */
    function setContent(string $content): void
    {
        $this->data['content'] = $content;
    }
}
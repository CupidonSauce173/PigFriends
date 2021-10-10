<?php

namespace CupidonSauce173\PigFriends\Lib;

class ModalForm extends Form
{
    private string $content = '';

    /**
     * @param callable|null $callable
     */
    function __construct(?callable $callable) {
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
    function setTitle(string $title) : void {
        $this->data['title'] = $title;
    }

    /**
     * @return string
     */
    function getTitle() : string {
        return $this->data['title'];
    }

    /**
     * @return string
     */
    function getContent() : string {
        return $this->data['content'];
    }

    /**
     * @param string $content
     */
    function setContent(string $content) : void {
        $this->data['content'] = $content;
    }

    /**
     * @param string $text
     */
    function setButton1(string $text) : void {
        $this->data['button1'] = $text;
    }

    /**
     * @return string
     */
    function getButton1() : string {
        return $this->data['button1'];
    }

    /**
     * @param string $text
     */
    function setButton2(string $text) : void {
        $this->data['button2'] = $text;
    }

    /**
     * @return string
     */
    function getButton2() : string {
        return $this->data['button2'];
    }
}
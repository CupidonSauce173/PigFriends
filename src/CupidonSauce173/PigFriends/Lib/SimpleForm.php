<?php


namespace CupidonSauce173\PigFriends\Lib;

use function count;

class SimpleForm extends Form
{

    private string $content = '';

    private array $labelMap = [];

    /**
     * @param callable|null $callable $callable
     */
    function __construct(?callable $callable)
    {
        parent::__construct($callable);
        $this->data['type'] = 'form';
        $this->data['title'] = '';
        $this->data['content'] = $this->content;
    }

    function processData(&$data): void
    {
        $data = $this->labelMap[$data] ?? null;
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

    /**
     * @param string $text
     * @param int $imageType
     * @param string $imagePath
     * @param string|null $label
     */
    function addButton(string $text, int $imageType = -1, string $imagePath = '', ?string $label = null): void
    {
        $content = ['text' => $text];
        if ($imageType !== -1) {
            $content['image']['type'] = $imageType === 0 ? 'path' : 'url';
            $content['image']['data'] = $imagePath;
        }
        $this->data['buttons'][] = $content;
        $this->labelMap[] = $label ?? count($this->labelMap);
    }
}
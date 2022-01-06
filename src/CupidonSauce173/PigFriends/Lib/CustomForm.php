<?php

namespace CupidonSauce173\PigFriends\Lib;

use function count;
use function is_array;

class CustomForm extends Form
{
    private array $labelMap = [];

    /**
     * @param callable|null $callable
     */
    function __construct(?callable $callable)
    {
        parent::__construct($callable);
        $this->data['type'] = 'custom_form';
        $this->data['title'] = '';
        $this->data['content'] = [];
    }

    /**
     * @param $data
     */
    function processData(&$data): void
    {
        if (is_array($data)) {
            $new = [];
            foreach ($data as $i => $v) {
                $new[$this->labelMap[$i]] = $v;
            }
            $data = $new;
        }
    }

    /**
     * @param string $title
     */
    function setTitle(string $title): void
    {
        $this->data['title'] = $title;
    }

    /**
     * @param string $text
     * @param bool|null $default
     * @param string|null $label
     */
    function addToggle(string $text, bool $default = null, ?string $label = null): void
    {
        $content = ['type' => 'toggle', 'text' => $text];
        if ($default !== null) {
            $content['default'] = $default;
        }
        $this->addContent($content);
        $this->labelMap[] = $label ?? count($this->labelMap);
    }

    /**
     * @param array $content
     */
    private function addContent(array $content): void
    {
        $this->data['content'][] = $content;
    }

    /**
     * @param string $text
     * @param array $options
     * @param int|null $default
     * @param string|null $label
     */
    function addDropdown(string $text, array $options, int $default = null, ?string $label = null): void
    {
        $this->addContent(['type' => 'dropdown', 'text' => $text, 'options' => $options, 'default' => $default]);
        $this->labelMap[] = $label ?? count($this->labelMap);
    }

    /**
     * @param string $text
     * @param string $placeholder
     * @param string|null $default
     * @param string|null $label
     */
    function addInput(string $text, string $placeholder = '', string $default = null, ?string $label = null): void
    {
        $this->addContent(['type' => 'input', 'text' => $text, 'placeholder' => $placeholder, 'default' => $default]);
        $this->labelMap[] = $label ?? count($this->labelMap);
    }
}
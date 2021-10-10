<?php

namespace CupidonSauce173\PigFriends\Lib;

class customForm extends Form
{
    private array $labelMap = [];

    /**
     * @param callable|null $callable
     */
    function __construct(?callable $callable) {
        parent::__construct($callable);
        $this->data['type'] = 'custom_form';
        $this->data['title'] = '';
        $this->data['content'] = [];
    }

    function processData(&$data) : void {
        if(is_array($data)) {
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
     * @param string $text
     * @param string|null $label
     */
    function addLabel(string $text, ?string $label = null) : void {
        $this->addContent(['type' => 'label', 'text' => $text]);
        $this->labelMap[] = $label ?? count($this->labelMap);
    }

    /**
     * @param string $text
     * @param bool|null $default
     * @param string|null $label
     */
    function addToggle(string $text, bool $default = null, ?string $label = null) : void {
        $content = ['type' => 'toggle', 'text' => $text];
        if($default !== null) {
            $content['default'] = $default;
        }
        $this->addContent($content);
        $this->labelMap[] = $label ?? count($this->labelMap);
    }

    /**
     * @param string $text
     * @param int $min
     * @param int $max
     * @param int $step
     * @param int $default
     * @param string|null $label
     */
    function addSlider(string $text, int $min, int $max, int $step = -1, int $default = -1, ?string $label = null) : void {
        $content = ['type' => 'slider', 'text' => $text, 'min' => $min, 'max' => $max];
        if($step !== -1) {
            $content['step'] = $step;
        }
        if($default !== -1) {
            $content['default'] = $default;
        }
        $this->addContent($content);
        $this->labelMap[] = $label ?? count($this->labelMap);
    }

    /**
     * @param string $text
     * @param array $steps
     * @param int $defaultIndex
     * @param string|null $label
     */
    function addStepSlider(string $text, array $steps, int $defaultIndex = -1, ?string $label = null) : void {
        $content = ['type' => 'step_slider', 'text' => $text, 'steps' => $steps];
        if($defaultIndex !== -1) {
            $content['default'] = $defaultIndex;
        }
        $this->addContent($content);
        $this->labelMap[] = $label ?? count($this->labelMap);
    }

    /**
     * @param string $text
     * @param array $options
     * @param int|null $default
     * @param string|null $label
     */
    function addDropdown(string $text, array $options, int $default = null, ?string $label = null) : void {
        $this->addContent(['type' => 'dropdown', 'text' => $text, 'options' => $options, 'default' => $default]);
        $this->labelMap[] = $label ?? count($this->labelMap);
    }

    /**
     * @param string $text
     * @param string $placeholder
     * @param string|null $default
     * @param string|null $label
     */
    function addInput(string $text, string $placeholder = '', string $default = null, ?string $label = null) : void {
        $this->addContent(['type' => 'input', 'text' => $text, 'placeholder' => $placeholder, 'default' => $default]);
        $this->labelMap[] = $label ?? count($this->labelMap);
    }

    /**
     * @param array $content
     */
    private function addContent(array $content) : void {
        $this->data['content'][] = $content;
    }
}
<?php

namespace Atom\Template;

use Atom\Template\Exception\TemplateException;

class Template
{
    /**
     * Templates
     * @var array
     */
    protected $templates;

    /**
     * Data
     * @var array
     */
    protected $data;

    /**
     * Template Construct
     * @param array $templates Templates
     * @param array $data      Data
     */
    public function __construct($templates = [], $data = [])
    {
        $this->templates = $templates;
        $this->data = $data;
    }

    /**
     * Set templates
     * @param array $templates Templates
     * @return $this
     */
    public function setTemplate(array $templates)
    {
        $this->templates = $templates;
        return $this;
    }

    /**
     * Set Data
     * @param array $data Data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Template render
     * @return [type] [description]
     */
    public function render()
    {
        ob_start();

        extract($this->data);

        foreach ($this->templates as $template) {
            $file = $this->loadFile($template);
            include($file);
        }

        $html = ob_get_clean();

        return $html;
    }

    /**
     * Load file
     * @param  string $template
     * @return string
     */
    public function loadFile($template)
    {
        $file = VIEW_PATH . str_replace('.', '/', $template) . '.php';
        if (!file_exists($file)) {
            throw new TemplateException(TemplateException::ERR_MSG_TEMPLATE_NOT_EXISTS);
        }
        return $file;
    }
}

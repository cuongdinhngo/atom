<?php

namespace Atom\Libs\PhpToJs;

use Atom\Libs\PhpToJs\Exception\TransformerException;
use Atom\Http\Globals;

class Transformer extends Globals
{
    /**
     * File
     * @var string
     */
    protected $file;

    /**
     * JS namespace
     * @var string
     */
    protected $namespace;

    /**
     * Transformer Construct
     */
    public function __construct()
    {
        $this->file = config('phpToJs.transform_php_to_js_file');
        $this->namespace = config('phpToJs.namespace');
    }

    /**
     * Cast variables
     * @param  array  $variables
     * @return void
     */
    protected function cast(array $variables)
    {
        $tmp = [];
        foreach ($variables as $key => $value) {
            $tmp[] = $this->initializeVariable($key, $value);
        }

        $js = $this->constructNamespace() . implode('', $tmp);
        $this->bind($js);
    }

    /**
     * Bind variables to file
     * @param  string $js JS variables
     * @return void
     */
    protected function bind($js)
    {
        $file = VIEW_PATH . str_replace('.', '/', $this->file) . ".php";
        if (!file_exists($file)) {
            throw new TransformerException(TransformerException::ERR_MSG_FILE_NOT_EXISTS);
        }
        $content = '<?php if (isset($_SESSION["jsVariables"])) { echo $_SESSION["jsVariables"]; unset($_SESSION["jsVariables"]); } ?>';
        file_put_contents($file, $content);
        $_SESSION['jsVariables'] = "<script>{$js}</script>";
    }

    /**
     * Set namespace
     * @return string
     */
    protected function constructNamespace()
    {
        if ($this->namespace == 'window') {
            return '';
        }

        return "window.{$this->namespace} = window.{$this->namespace} || {};";
    }

    /**
     * Initialize Variable
     * @param  string $key
     * @param  mixed  $value
     * @return string
     */
    protected function initializeVariable($key, $value)
    {
        return "{$this->namespace}.{$key} = {$this->convertToJavaScript($value)};";
    }

    /**
     * Convert to JS
     * @param  mixed $value
     * @return json
     */
    protected function convertToJavaScript($value)
    {
        return json_encode($value);
    }
}

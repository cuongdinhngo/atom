<?php

namespace Atom\Views;

use Atom\Views\ViewFactory;
use Atom\Views\Exception\ViewException;

class View extends ViewFactory
{
    static $viewFactory;

    /**
     * View construct
     * @param ViewFactory|null $viewFactory
     */
    public function __construct(ViewFactory $viewFactory = null)
    {
        static::$viewFactory = $viewFactory ?? new ViewFactory();
    }

    /**
     * Render view
     * @param  string $directory
     * @param  mixed $data
     * @return void
     */
    public static function render(string $directory, $data)
    {
        if (!is_array($data)) {
            throw new ViewException(ViewException::ERR_MSG_INVALID_ARG);
        }
        static::$directory = $directory;
        static::$data = $data;
        static::createView();
    }
}

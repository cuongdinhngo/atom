<?php

namespace Atom\Facades\Support;

class Facade
{
	 /**
     * __callStatic
     * @param  string $method
     * @param  mixed $args
     * @return void
     */
    public static function __callStatic($method, $args) {
        return $this->$method(...$args);
    }
}

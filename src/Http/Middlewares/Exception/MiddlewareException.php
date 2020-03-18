<?php

namespace Atom\Http\Middlewares\Exception;

class MiddlewareException extends \Exception
{
    const ERR_MSG_NO_MIDDLEWARES = "No Middlewares";
    const ERR_MSG_INVALID_MIDDLEWARES = "Invalid Middlewares";
    const ERR_MSG_MIDDLEWARE_NOT_EXISTS = "Middleware Not Exists";
    const ERR_MSG_MIDDLEWARE_FAIL = "Middleware Fails";
}
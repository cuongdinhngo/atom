<?php

namespace Atom\Http\Exception;

class ResponseException extends \Exception
{
    const ERR_MSG_INVALID_ARGUMENTS = "Invalid Arguments";
    const ERR_MSG_INVALID_HTTP_CODE = "Invalid Http Code";
}

<?php

namespace Atom\Guard\Exception;

class GuardException extends \Exception
{
    const ERR_MSG_NO_KEY = "No App Key";
    const ERR_MSG_INVALID_GUARD_KEYS = "Invalid Guard Keys";
    const ERR_MSG_UNAUTHORIZED = "Unauthorized";
}

<?php

namespace Atom\Controllers\Exception;

class ControllerException extends \Exception
{
    const ERR_MSG_INVALID_CONTROLLER = "Controller Not Found";
    const ERR_MSG_ACTION_FAIL = "Action Failed";
    const ERR_MSG_DOCTRINE_NOT_USE = "Doctrine Not Used";
}

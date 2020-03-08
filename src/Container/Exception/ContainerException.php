<?php

namespace Atom\Container\Exception;

class ContainerException extends \Exception
{
    const ERR_MSG_CLASS_NOT_INSTANTIABLE = "Class %s is not instantiable";
    const ERR_MSG_DEPENDENCY_NOT_RESOLVE = "Can not resolve class dependency %s";
}

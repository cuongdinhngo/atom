<?php

namespace Atom\Storage\Exception;

class StorageException extends \Exception
{
    const ERR_MSG_NOT_FOUND = "Storage Not Found";
    const ERR_MSG_UNKNOW_FILE = "Unknow File";
    const ERR_MSG_UPLOAD_FAIL = "Upload Fail";
}

<?php

namespace Atom\File\Exception;

class ImageException extends \Exception
{
    const ERR_MSG_BAD_REQUEST = "Bad Request";
    const ERR_MSG_UPLOAD_FAIL = "Image Upload Fail";
    const ERR_MSG_UNKNOW_FILE = "Unknow File";
    const ERR_MSG_FILE_TOO_LARGE = "File Too Large";
    const ERR_MSG_FILE_NOT_EXIST = "File Not Exist";
}

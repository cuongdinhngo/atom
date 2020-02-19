<?php

namespace Atom\File\Exception;

class FileException extends \Exception
{
    const ERR_MSG_FILE_TOO_LARGE = "File Too Large";
    const ERR_MSG_SAVE_FILE_FAIL = "Save File Fail";
    const ERR_MSG_UNKNOW_FILE = "Unknow File";
    const ERR_MSG_FILENAME_ALREADY_USED = "Filename Already Used";
}

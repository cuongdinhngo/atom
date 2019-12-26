<?php

namespace Atom\Db\Exception;

class DatabaseException extends \Exception
{
	const ERR_MSG_CONNECTION_FAIL = "Database Connection Failed";
	const ERR_MSQ_BAD_REQUEST = "Bad Request";
	const ERR_CODE_BAD_REQUEST = 400;
	const ERR_MSG_INVALID_ARGUMENTS = "Invalid Arguments";
}
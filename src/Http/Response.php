<?php

namespace Atom\Http;

use Atom\Http\Globals;
use Atom\Http\Exception\ResponseException;

class Response
{
    /**
     * Handle Json Error
     * @param  int $errno
     */
    protected static function handleJsonError($errno)
    {
        $messages = array(
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters' //PHP >= 5.3.3
        );
        throw new DomainException(
            isset($messages[$errno])
            ? $messages[$errno]
            : 'Unknown JSON error: ' . $errno
        );
    }

    /**
     * Convert to Json
     * @param  mixed $values
     * @param  int $code
     * @param  int $option
     * @return json
     */
    public static function toJson($values, $code = null, $option = JSON_UNESCAPED_UNICODE)
    {
        $json = json_encode($values, $option);
        if ($errno = json_last_error()) {
            static::handleJsonError($errno);
        }
        // static::responseCode($code);
        print_r($json);
    }

    /**
     * Redirect
     * @param  string $uri
     * @param  array  $data
     * @return void
     */
    public static function redirect(string $uri, array $data = [])
    {
        if (!is_array($data)) {
            throw new ResponseException(ResponseException::ERR_MSG_INVALID_ARGUMENTS);
        }
        $server = Globals::server();
        $params = empty($data) ? '': '?'.http_build_query($data);
        $uri = rtrim(dirname($server["PHP_SELF"]), '/\\') . $uri;
        $url = $server['REQUEST_SCHEME'] . '://' . $server['HTTP_HOST'] . $uri . $params;
        header("Location: {$url}");
        exit();
    }

    /**
     * Set Http Response Code
     * @param  int|null $code
     * @return void
     */
    public static function responseCode($code = null)
    {
        if ($code !== null) {
            $message = static::$phrases[$code];
            if (empty($message)) {
                throw new ResponseException(ResponseException::ERR_MSG_INVALID_HTTP_CODE);
            }

            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
            header($protocol . ' ' . $code . ' ' . $message);
            $GLOBALS['http_response_code'] = $code;
        } else {
            $GLOBALS['http_response_code'] = 200;
        }
    }

    /**
     * Http Codes
     * @var $phrases
     */
    protected static $phrases = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];
}

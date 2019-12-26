<?php

namespace Atom\Guard;

use Atom\Libs\JWT\JWT;
use Atom\Guard\Exception\GuardException;
use Atom\Http\Globals;

class Token
{
    /**
     * Generate Token
     * @param  $payload
     * @param  $key
     * @return string
     */
    public static function generate($payload, $key = null)
    {
        $key = $key ?? env('APP_KEY');
        if (empty($key)) {
            throw new GuardException(GuardException::ERR_MSG_NO_KEY);
        }
        return JWT::encode($payload, $key);
    }

    /**
     * Decode Token
     * @param  string $token
     * @return mixed
     */
    public static function decode($token)
    {
        $key = env('APP_KEY');
        if (empty($key)) {
            throw new GuardException(GuardException::ERR_MSG_NO_KEY);
        }
        return JWT::decode($token, $key, array_keys(JWT::$supported_algs));
    }

    /**
     * Create Token expire
     * @return string
     */
    public static function expire()
    {
        $tokeLifetime = env('SESSION_LIFETIME');
        return strtotime("+ {$tokeLifetime} mins");
    }

    /**
     * Get Token
     * @return string
     */
    public static function get()
    {
        $token = Globals::session('user_token');
        if (empty($token)) {
            $header = getHeaders();
            if (false === isset($header['Authorization'])) {
                return;
            }
            $token = substr($header['Authorization'], 7);
        }
        return $token;
    }
}

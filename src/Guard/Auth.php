<?php

namespace Atom\Guard;

use Atom\Guard\Exception\GuardException;
use Atom\Db\Database;
use Atom\Http\Response;
use Atom\Http\Globals;
use Atom\Guard\Token;
use Atom\Libs\JWT\JWT;

trait Auth
{
    /**
     * Get user
     */
    public static function user()
    {
        try {
            $user = Globals::session('user');
            if (empty($user)) {
                $token = Token::get();
                $payload = Token::decode($token);
                $user = $payload->user;
            }
            return $user[0];
        } catch (\Exception $e) {
            throw new GuardException(GuardException::ERR_MSG_UNAUTHORIZED);
        }
    }

    /**
     * Login
     * @param  array  $request
     * @param  array  $response
     * @return void
     */
    public static function login(array $request, array $response)
    {
        $auth = config('app.auth');
        $guards = explode(',', $auth['guard']);
        $checkGuard = array_diff($guards, array_keys($request));
        if (false === empty($checkGuard)) {
            throw new GuardException(GuardException::ERR_MSG_INVALID_GUARD_KEYS);
        }

        list($guardId, $guardPasswd) = $guards;
        empty($response) ? extract(config('app.auth.response')) : extract($response);
        $condition = [
            [$guardId, '=', $request[$guardId]],
            [$guardPasswd, '=', $request[$guardPasswd]]
        ];
        $user = (new Database())->table($auth['table'])->select()->where($condition)->first();
        if (empty($user)) {
            Response::redirect($fail, ['error' => $error]);
        }

        //Store guardId
        Globals::setSession($guardId, $request[$guardId]);
        //Store user
        Globals::setSession('user', $user);
        //Store user_token
        $payload = [
            'exp' => Token::expire(),
            'iat' => strtotime('now'),
            'user' => $user,
        ];
        $token = Token::generate($payload);
        Globals::setSession('user_token', $token);

        if (empty($success)) {
            return Response::toJson(['Token' => $token]);
        } else {
            Response::redirect($success);
        }
    }

    /**
     * Check authorization
     * @return mixed
     */
    public static function check()
    {
        try {
            $token = Token::get();
            $payload = Token::decode($token);
            return;
        } catch (\Exception $e) {
            throw new GuardException(GuardException::ERR_MSG_UNAUTHORIZED);
        }
    }
}

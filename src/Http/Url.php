<?php

namespace Atom\Http;

use Atom\Http\Globals;
use Atom\Http\Exception\UrlException;
use Atom\File\Log;
use DateTime;

class Url extends Globals
{
    /**
     * Secret Key
     * @var string
     */
    protected $key;

    /**
     * URL construct
     */
    public function __construct()
    {
        $this->key = env('APP_KEY');
    }

    /**
     * Get Http protocal
     *
     * @return string
     */
    protected function protocol()
    {
        return stripos($_SERVER['SERVER_PROTOCOL'], 'http') === 0 ? 'http://' : 'https://';
    }

    /**
     * Get Domain
     *
     * @return string
     */
    protected function domain()
    {
        return $_SERVER['HTTP_HOST'];
    }

    /**
     * Get full url with query string
     *
     * @return string
     */
    protected function full()
    {
        return $this->protocol() . $this->domain() . $_SERVER['REQUEST_URI'];
    }

    protected function extractUri()
    {
        return strtok($_SERVER["REQUEST_URI"], '?');
    }

    /**
     * Get current path without query string
     *
     * @return string Ex: "https://abc.local/api/users" or "https://abc.local/users"
     */
    protected function current()
    {
        return $this->protocol() . $this->domain() . $this->extractUri();
    }

    protected function previous()
    {
        return $_SERVER['HTTP_REFERER'];
    }

    /**
     * Generate signed URL
     *
     * @param  string   $uri        URI (ex: /users/add)
     * @param  array    $params     Parameters
     * @param  int|null $expiration Expiration (minutes)
     *
     * @return string
     */
    protected function signedUrl(string $uri, array $params = [], int $expiration = null)
    {
        if (array_key_exists('signature', $params)) {
            throw new UrlException(UrlException::ERR_MSG_URL_INVALID_PARAMS);
        }

        if ($expiration) {
            $time = new DateTime(now());
            $time->modify("+{$expiration} minutes");
            $params = $params + ['expires' => $time->format("U")];
        }

        $params = $params + ['signature' => $this->generateSignature($uri, $params)];

        unset($time);
        return $this->protocol() . $this->domain() . $uri . '?' . http_build_query($params);

    }

    /**
     * Generate temporary Signed URL
     *
     * @param  string $uri        URI (ex: /users/add)
     * @param  int    $expiration Expiration (minutes)
     * @param  array  $params     Parameters
     *
     * @return string
     */
    protected function temporarySignedUrl(string $uri, int $expiration, array $params = [])
    {
        return $this->signedUrl($uri, $params, $expiration);
    }

    /**
     * Prepare Data
     *
     * @param  string $uri    URI
     * @param  array  $params Parameters
     *
     * @return string
     */
    protected function prepareData(string $uri, array $params)
    {
        return http_build_query(
            $params + ['protocol' => $this->protocol(), 'domain' => $this->domain(), 'uri' => $uri]
        );
    }

    /**
     * Generate Signature
     *
     * @param  string $uri    URI
     * @param  array  $params Parameters
     *
     * @return string
     */
    protected function generateSignature(string $uri, array $params)
    {
        return hash_hmac('sha256', $this->prepareData($uri, $params), $this->key);
    }

    /**
     * Identify Signature
     *
     * @return boolean
     */
    protected function identifySignature()
    {
        parse_str($_SERVER['QUERY_STRING'], $params);
        return $this->hasCorrectSignature($params['signature'], $params) &&
                $this->isExpiredSignature($params['expires']);
    }

    /**
     * Check correct signature
     *
     * @param  string  $signature Signature
     * @param  array   $params    Parameter
     *
     * @return boolean
     */
    protected function hasCorrectSignature($signature, $params)
    {
        unset($params['signature']);
        return $signature == $this->generateSignature($this->extractUri(), $params);
    }

    /**
     * Signature is expired
     *
     * @param  int  $expires Expiration (Unix Timestamp)
     *
     * @return boolean
     */
    protected function isExpiredSignature($expires)
    {
        $time = new DateTime();
        $time->setTimestamp($expires);
        return $time->format("Y-m-d H:i:s") > now();
    }
}

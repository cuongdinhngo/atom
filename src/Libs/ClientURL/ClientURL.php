<?php

namespace Atom\Libs\ClientURL;

use Atom\Libs\ClientURL\Exception\ClientURLException;

class ClientURL
{
    /**
     * Connection
     * @var [type]
     */
    protected $con;

    protected $info = [];

    protected $result;

    protected $methods = ["GET", "HEAD", "POST", "PUT", "DELETE", "PATCH"];

    /**
     * Client URL construct
     */
    public function __construct()
    {
        $this->con = curl_init();
    }

    /**
     * Set URL
     * @param  string $url
     * @return $this
     */
    public function url($url)
    {
        curl_setopt($this->con, CURLOPT_URL, $url);
        return $this;
    }

    /**
     * Set header
     * @param  array $header
     * @return $this
     */
    public function header($header)
    {
        curl_setopt($this->con, CURLOPT_HTTPHEADER, $header);
        return $this;
    }

    /**
     * Set Return Transfer
     * @param  boolean $isReturn
     * @return $this
     */
    public function returnTransfer($isReturn)
    {
        curl_setopt($this->con, CURLOPT_RETURNTRANSFER, $isReturn);
        return $this;
    }

    /**
     * Set Post fields
     * @param  array|json $request
     * @return $this
     */
    public function postFields($request)
    {
        curl_setopt($this->con, CURLOPT_POSTFIELDS, $request);
        return $this;
    }

    /**
     * Set port
     * @param  string|integer $port
     * @return $this
     */
    public function port($port)
    {
        curl_setopt($this->con, CURLOPT_PORT, $port);
        return $this;
    }

    /**
     * Set multiple options for a cURL transfer
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        curl_setopt_array($this->con, $options);
        return $this;
    }

    /**
     * Customize request method
     * @param  string $method
     * @return $this
     */
    public function customRequest($method)
    {
        if (in_array(strtoupper($method), $this->methods)) {
            curl_setopt($this->con, CURLOPT_CUSTOMREQUEST, $method);
            return $this;
        }
        throw new ClientURLException(ClientURLException::ERR_MSG_METHOD_NOT_EXIST);
    }

    /**
     * Set User-Agent header
     * @param  string $agent
     * @return $this
     */
    public function userAgent($agent)
    {
        curl_setopt($this->con, CURLOPT_USERAGENT, $agent);
        return $this;
    }

    /**
     * Set time-out
     * @param  integer $seconds
     * @return $this
     */
    public function timeOut($seconds)
    {
        curl_setopt($this->con, CURLOPT_TIMEOUT, $seconds);
        return $this;
    }

    /**
     * Set username and password
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function setUserPwd($username, $password)
    {
        curl_setopt($this->con, CURLOPT_USERPWD, $username . ":" . $password);
        return $this;
    }

    /**
     * Perform a cURL session
     * @return mixed
     */
    public function exec()
    {
        $result = curl_exec($this->con);
        if($result === false || curl_errno($this->con))
        {
            $result = curl_error($this->con);
        }
        $this->result = $result;
        return $this;
    }

    /**
     * Get information regarding a specific transfer
     * @return $this
     */
    public function info()
    {
        $this->info = curl_getinfo($this->con);
        return $this;
    }

    /**
     * Gets information about the last transfer
     * @return array
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Get Result
     * @return mixed
     */
    public function get()
    {
        return $this->exec();
    }

    /**
     * Return array
     * @return array
     */
    public function toArray()
    {
        return json_decode($this->result, true);
    }

    /**
     * Transfer a URL without user interaction.
     * @param  string $url
     * @param  array  $param
     * @return void
     */
    public function execCommand($url, array $param)
    {
        $curl = "curl -X POST -H 'Content-Type: application/json'";
        $payload = json_encode($param);
        $curl .= " -d '$payload' '$url'";
        $curl .= ' > /dev/null 2>&1 &';
        exec($curl);
    }

    /**
     * Destruct
     */
    public function __destruct()
    {
        curl_close($this->con);
    }
}

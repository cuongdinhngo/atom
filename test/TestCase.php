<?php

namespace Atom\Test;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Original $_SERVER backup
     */
    protected $originalServer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
        parent::tearDown();
    }

    /**
     * Set up $_SERVER for HTTP testing
     */
    protected function mockServer(array $overrides = []): void
    {
        $defaults = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_HOST' => 'localhost',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'HTTPS' => 'off',
        ];

        $_SERVER = array_merge($_SERVER, $defaults, $overrides);
    }
}

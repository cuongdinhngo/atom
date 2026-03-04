<?php

namespace Atom\Test\Http;

use Atom\Test\TestCase;
use Atom\Http\Url;

class UrlTest extends TestCase
{
    private $url;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up env for Url constructor (requires APP_KEY)
        putenv('APP_KEY=test-secret-key-12345');

        $this->mockServer([
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/users?page=1',
            'HTTPS' => 'off',
        ]);

        $this->url = new Url();
    }

    protected function tearDown(): void
    {
        putenv('APP_KEY');
        parent::tearDown();
    }

    // --- Protocol ---

    public function testProtocolReturnsHttp()
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $url = new Url();

        // Use reflection to call protected method
        $ref = new \ReflectionMethod($url, 'protocol');
        $ref->setAccessible(true);

        $this->assertEquals('http://', $ref->invoke($url));
    }

    public function testProtocolAlwaysReturnsHttpDueToStriposMatch()
    {
        // BUG: protocol() uses stripos($protocol, 'http') === 0, which matches
        // both 'HTTP/1.1' and 'HTTPS/1.1' since both start with 'http'.
        // This means it always returns 'http://', never 'https://'.
        $_SERVER['SERVER_PROTOCOL'] = 'HTTPS/1.1';
        $url = new Url();

        $ref = new \ReflectionMethod($url, 'protocol');
        $ref->setAccessible(true);

        $result = $ref->invoke($url);
        $this->assertEquals('http://', $result);
    }

    // --- Domain ---

    public function testDomainReturnsHost()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $url = new Url();

        $ref = new \ReflectionMethod($url, 'domain');
        $ref->setAccessible(true);

        $this->assertEquals('example.com', $ref->invoke($url));
    }

    // --- Generate Signature ---

    public function testGenerateSignatureIsConsistent()
    {
        $ref = new \ReflectionMethod($this->url, 'generateSignature');
        $ref->setAccessible(true);

        $sig1 = $ref->invoke($this->url, '/users', ['page' => '1']);
        $sig2 = $ref->invoke($this->url, '/users', ['page' => '1']);

        $this->assertEquals($sig1, $sig2);
    }

    public function testGenerateSignatureDiffersWithDifferentParams()
    {
        $ref = new \ReflectionMethod($this->url, 'generateSignature');
        $ref->setAccessible(true);

        $sig1 = $ref->invoke($this->url, '/users', ['page' => '1']);
        $sig2 = $ref->invoke($this->url, '/users', ['page' => '2']);

        $this->assertNotEquals($sig1, $sig2);
    }

    public function testGenerateSignatureDiffersWithDifferentUri()
    {
        $ref = new \ReflectionMethod($this->url, 'generateSignature');
        $ref->setAccessible(true);

        $sig1 = $ref->invoke($this->url, '/users', []);
        $sig2 = $ref->invoke($this->url, '/posts', []);

        $this->assertNotEquals($sig1, $sig2);
    }

    // --- Has Correct Signature ---

    public function testHasCorrectSignatureReturnsTrue()
    {
        $genRef = new \ReflectionMethod($this->url, 'generateSignature');
        $genRef->setAccessible(true);

        $_SERVER['REQUEST_URI'] = '/users';
        $params = ['page' => '1'];
        $signature = $genRef->invoke($this->url, '/users', $params);

        $checkRef = new \ReflectionMethod($this->url, 'hasCorrectSignature');
        $checkRef->setAccessible(true);

        $allParams = array_merge($params, ['signature' => $signature]);
        $result = $checkRef->invoke($this->url, $signature, $allParams);

        $this->assertTrue($result);
    }

    public function testHasCorrectSignatureReturnsFalse()
    {
        $_SERVER['REQUEST_URI'] = '/users';

        $checkRef = new \ReflectionMethod($this->url, 'hasCorrectSignature');
        $checkRef->setAccessible(true);

        $result = $checkRef->invoke($this->url, 'fake-signature', ['signature' => 'fake-signature']);
        $this->assertFalse($result);
    }

    // --- Is Expired Signature ---

    public function testIsExpiredSignatureReturnsTrueForFuture()
    {
        $ref = new \ReflectionMethod($this->url, 'isExpiredSignature');
        $ref->setAccessible(true);

        $futureTimestamp = time() + 3600;
        $result = $ref->invoke($this->url, $futureTimestamp);

        $this->assertTrue($result);
    }

    public function testIsExpiredSignatureReturnsFalseForPast()
    {
        $ref = new \ReflectionMethod($this->url, 'isExpiredSignature');
        $ref->setAccessible(true);

        $pastTimestamp = time() - 3600;
        $result = $ref->invoke($this->url, $pastTimestamp);

        $this->assertFalse($result);
    }

    // --- Full URL ---

    public function testFullReturnsCompleteUrl()
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/users?page=1';

        $url = new Url();
        $ref = new \ReflectionMethod($url, 'full');
        $ref->setAccessible(true);

        $result = $ref->invoke($url);
        $this->assertStringContainsString('example.com', $result);
        $this->assertStringContainsString('/users?page=1', $result);
    }

    // --- Current URL ---

    public function testCurrentReturnsUrlWithoutQueryString()
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/users?page=1';

        $url = new Url();
        $ref = new \ReflectionMethod($url, 'current');
        $ref->setAccessible(true);

        $result = $ref->invoke($url);
        $this->assertStringContainsString('example.com/users', $result);
        $this->assertStringNotContainsString('page=1', $result);
    }
}

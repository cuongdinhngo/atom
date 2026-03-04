<?php

namespace Atom\Test\Http;

use Atom\Test\TestCase;
use Atom\Http\Request;

class RequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Define constants needed by Request -> parseUriParams -> route()
        if (!defined('ROUTE_PATH')) {
            define('ROUTE_PATH', sys_get_temp_dir() . '/atom_routes/');
        }

        // Create route files directory and minimal route files
        if (!is_dir(ROUTE_PATH)) {
            mkdir(ROUTE_PATH, 0777, true);
        }
        if (!file_exists(ROUTE_PATH . 'web.php')) {
            file_put_contents(ROUTE_PATH . 'web.php', '<?php return [];');
        }
        if (!file_exists(ROUTE_PATH . 'api.php')) {
            file_put_contents(ROUTE_PATH . 'api.php', '<?php return [];');
        }

        $this->mockServer([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'HTTP_HOST' => 'localhost',
        ]);
        // Ensure non-API request
        unset($_SERVER['HTTP_CONTENT_TYPE']);
        $_GET = [];
        $_POST = [];
        $_FILES = [];
    }

    // --- Magic methods ---

    public function testMagicSetAndGet()
    {
        $request = new Request();
        $request->name = 'John';
        $this->assertEquals('John', $request->name);
    }

    public function testMagicIsset()
    {
        $request = new Request();
        $request->name = 'John';
        $this->assertTrue(isset($request->name));
        $this->assertFalse(isset($request->nonexistent));
    }

    public function testMagicUnset()
    {
        $request = new Request();
        $request->name = 'John';
        unset($request->name);
        $this->assertFalse(isset($request->name));
    }

    // --- ArrayAccess ---

    public function testArrayAccessSet()
    {
        $request = new Request();
        $request['key'] = 'value';
        $this->assertEquals('value', $request['key']);
    }

    public function testArrayAccessExists()
    {
        $request = new Request();
        $request['key'] = 'value';
        $this->assertTrue(isset($request['key']));
        $this->assertFalse(isset($request['nonexistent']));
    }

    public function testArrayAccessUnset()
    {
        $request = new Request();
        $request['key'] = 'value';
        unset($request['key']);
        $this->assertFalse(isset($request['key']));
    }

    public function testArrayAccessGetNonExistent()
    {
        $request = new Request();
        $this->assertNull($request['nonexistent']);
    }

    // --- all() ---

    public function testAllReturnsArray()
    {
        $request = new Request();
        $this->assertIsArray($request->all());
    }

    // --- headers() ---

    public function testHeadersReturnsAll()
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $request = new Request();
        $headers = $request->headers();

        $this->assertIsArray($headers);
    }

    public function testHeadersReturnsSingleKey()
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $request = new Request();
        $accept = $request->headers('Accept');

        $this->assertEquals('text/html', $accept);
    }

    // --- Method ---

    public function testMethodIsSet()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = new Request();
        $this->assertEquals('POST', $request->method);
    }

    // --- URI ---

    public function testUriIsSet()
    {
        $_SERVER['REQUEST_URI'] = '/users';
        unset($_SERVER['HTTP_CONTENT_TYPE']);
        $request = new Request();
        $this->assertNotEmpty($request->uri);
    }

    // --- mapParams ---

    public function testMapParams()
    {
        $request = new Request();
        $result = $request->mapParams('/users/123', '/users/{id}');

        $this->assertEquals(['id' => '123'], $result);
    }

    public function testMapParamsMultiple()
    {
        $request = new Request();
        $result = $request->mapParams('/users/123/posts/456', '/users/{userId}/posts/{postId}');

        $this->assertEquals(['userId' => '123', 'postId' => '456'], $result);
    }

    public function testMapParamsNoParams()
    {
        $request = new Request();
        $result = $request->mapParams('/users', '/users');

        $this->assertEmpty($result);
    }

    // --- getParametersByMethod ---

    public function testGetParametersByMethodGet()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['page' => '1'];
        $request = new Request();

        $this->assertEquals('GET', $request->method);
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        parent::tearDown();
    }
}

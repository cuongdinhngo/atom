<?php

namespace Atom\Test\Http;

use Atom\Test\TestCase;
use Atom\Http\Globals;

class GlobalsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockServer([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/users?page=1',
            'HTTP_HOST' => 'example.com',
        ]);
    }

    // --- path() ---

    public function testPathReturnsPathWithoutQuery()
    {
        $_SERVER['REQUEST_URI'] = '/users?page=1';
        // path() calls uri() which calls isApi()
        // We need to ensure isApi() returns false
        unset($_SERVER['HTTP_CONTENT_TYPE']);

        $path = Globals::path();
        $this->assertEquals('/users', $path);
    }

    // --- method() ---

    public function testMethodReturnsRequestMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertEquals('POST', Globals::method());
    }

    public function testMethodReturnsGet()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertEquals('GET', Globals::method());
    }

    // --- server() ---

    public function testServerReturnsSpecificElement()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $this->assertEquals('example.com', Globals::server('HTTP_HOST'));
    }

    public function testServerReturnsAllWhenNull()
    {
        $result = Globals::server();
        $this->assertIsArray($result);
        $this->assertEquals($_SERVER, $result);
    }

    // --- get() ---

    public function testGetReturnsGetData()
    {
        $_GET = ['page' => '1', 'sort' => 'name'];
        $this->assertEquals(['page' => '1', 'sort' => 'name'], Globals::get());
    }

    // --- post() ---

    public function testPostReturnsPostData()
    {
        $_POST = ['name' => 'John', 'email' => 'john@test.com'];
        $this->assertEquals(['name' => 'John', 'email' => 'john@test.com'], Globals::post());
    }

    // --- files() ---

    public function testFilesReturnsFilesData()
    {
        $_FILES = ['photo' => ['name' => 'test.jpg']];
        $this->assertEquals(['photo' => ['name' => 'test.jpg']], Globals::files());
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        parent::tearDown();
    }
}

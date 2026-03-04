<?php

namespace Atom\Test\Http;

use Atom\Test\TestCase;
use Atom\Http\Response;

class ResponseTest extends TestCase
{
    // --- toJson() ---

    public function testToJsonOutputsJson()
    {
        $data = ['name' => 'John', 'age' => 30];

        ob_start();
        Response::toJson($data);
        $output = ob_get_clean();

        $decoded = json_decode($output, true);
        $this->assertEquals('John', $decoded['name']);
        $this->assertEquals(30, $decoded['age']);
    }

    public function testToJsonWithUnicode()
    {
        $data = ['greeting' => 'Xin chao'];

        ob_start();
        Response::toJson($data);
        $output = ob_get_clean();

        $this->assertStringContainsString('Xin chao', $output);
    }

    public function testToJsonWithNestedData()
    {
        $data = ['user' => ['name' => 'John', 'roles' => ['admin']]];

        ob_start();
        Response::toJson($data);
        $output = ob_get_clean();

        $decoded = json_decode($output, true);
        $this->assertEquals('John', $decoded['user']['name']);
        $this->assertEquals(['admin'], $decoded['user']['roles']);
    }
}

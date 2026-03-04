<?php

namespace Atom\Test\Helpers;

use Atom\Test\TestCase;

class HelpersTest extends TestCase
{
    // --- stripSpace() ---

    public function testStripSpaceRemovesWhitespace()
    {
        $this->assertEquals('helloworld', stripSpace('hello world'));
    }

    public function testStripSpaceMultipleSpaces()
    {
        $this->assertEquals('abc', stripSpace('a b c'));
    }

    public function testStripSpaceEmptyString()
    {
        $this->assertEquals('', stripSpace(''));
    }

    public function testStripSpaceNoSpaces()
    {
        $this->assertEquals('hello', stripSpace('hello'));
    }

    // --- json() ---

    public function testJsonEncodesArray()
    {
        $data = ['name' => 'John', 'age' => 30];
        $result = json($data);
        $this->assertEquals('{"name":"John","age":30}', $result);
    }

    public function testJsonEncodesUnicode()
    {
        $data = ['name' => 'Cuong'];
        $result = json($data);
        $this->assertStringContainsString('Cuong', $result);
    }

    public function testJsonEncodesNestedArray()
    {
        $data = ['user' => ['name' => 'John', 'roles' => ['admin', 'editor']]];
        $result = json($data);
        $decoded = json_decode($result, true);
        $this->assertEquals($data, $decoded);
    }

    // --- gps2Num() ---

    public function testGps2NumWithFraction()
    {
        $this->assertEquals(48.0, gps2Num('48/1'));
    }

    public function testGps2NumWithDecimalFraction()
    {
        $this->assertEquals(30.0, gps2Num('60/2'));
    }

    public function testGps2NumWithSingleValue()
    {
        $this->assertEquals(48, gps2Num('48'));
    }

    public function testGps2NumWithZeroDenominator()
    {
        // PHP 8 throws DivisionByZeroError for float(0) / float(0)
        $this->expectException(\DivisionByZeroError::class);
        gps2Num('0/0');
    }

    // --- now() ---

    public function testNowReturnsDateTimeFormat()
    {
        $result = now();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);
    }

    public function testNowReturnsCurrentDate()
    {
        $result = now();
        $this->assertStringStartsWith(date('Y-m-d'), $result);
    }

    // --- today() ---

    public function testTodayReturnsDateFormat()
    {
        $result = today();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $result);
    }

    public function testTodayReturnsCurrentDate()
    {
        $this->assertEquals(date('Y-m-d'), today());
    }

    // --- getHeaders() ---

    public function testGetHeadersExtractsHttpHeaders()
    {
        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        $headers = getHeaders();

        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertEquals('text/html', $headers['Accept']);
    }

    public function testGetHeadersIgnoresNonHttpKeys()
    {
        $_SERVER = [
            'SERVER_NAME' => 'localhost',
            'DOCUMENT_ROOT' => '/var/www',
            'HTTP_HOST' => 'example.com',
        ];

        $headers = getHeaders();

        $this->assertArrayHasKey('Host', $headers);
        $this->assertArrayNotHasKey('SERVER_NAME', $headers);
        $this->assertArrayNotHasKey('DOCUMENT_ROOT', $headers);
    }

    public function testGetHeadersEmptyWhenNoHttpHeaders()
    {
        $_SERVER = ['SERVER_NAME' => 'localhost'];
        $headers = getHeaders();
        $this->assertEmpty($headers);
    }

    // --- isApi() ---

    public function testIsApiWithApiInUri()
    {
        $_SERVER['REQUEST_URI'] = '/api/users';
        $_SERVER['HTTP_CONTENT_TYPE'] = 'text/html';

        $this->assertTrue(isApi());
    }

    public function testIsApiWithJsonContentType()
    {
        $_SERVER['REQUEST_URI'] = '/users';
        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';

        $this->assertTrue(isApi());
    }

    public function testIsApiReturnsFalseForNonApi()
    {
        $_SERVER['REQUEST_URI'] = '/users';
        // Clear any HTTP_CONTENT_TYPE
        unset($_SERVER['HTTP_CONTENT_TYPE']);

        $this->assertFalse(isApi());
    }

    // --- url() ---

    public function testUrlReturnsHttpUrl()
    {
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['HTTP_HOST'] = 'example.com';

        $this->assertEquals('http://example.com', url());
    }

    public function testUrlReturnsHttpsUrl()
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'example.com';

        $this->assertEquals('https://example.com', url());
    }

    public function testUrlWithPath()
    {
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['HTTP_HOST'] = 'example.com';

        $this->assertEquals('http://example.com/users', url('users'));
    }

    // --- env() ---

    public function testEnvReturnsValue()
    {
        putenv('TEST_VAR=hello');
        $this->assertEquals('hello', env('TEST_VAR'));
        putenv('TEST_VAR'); // cleanup
    }

    public function testEnvThrowsOnMissingVar()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('INVALID ENV VALUE');
        env('NONEXISTENT_VAR_12345');
    }
}

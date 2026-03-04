<?php

namespace Atom\Test\Libs\JWT;

use Atom\Test\TestCase;
use Atom\Libs\JWT\JWT;
use Atom\Libs\JWT\SignatureInvalidException;
use Atom\Libs\JWT\ExpiredException;
use Atom\Libs\JWT\BeforeValidException;
use DomainException;
use InvalidArgumentException;
use UnexpectedValueException;

class JWTTest extends TestCase
{
    private $key = 'test-secret-key';

    protected function setUp(): void
    {
        parent::setUp();
        JWT::$leeway = 0;
        JWT::$timestamp = null;
    }

    // --- Encode / Decode round-trip ---

    public function testEncodeAndDecode()
    {
        $payload = ['sub' => '1234', 'name' => 'John'];
        $token = JWT::encode($payload, $this->key);
        $decoded = JWT::decode($token, $this->key, ['HS256']);

        $this->assertEquals('1234', $decoded->sub);
        $this->assertEquals('John', $decoded->name);
    }

    public function testEncodeProducesThreeSegments()
    {
        $token = JWT::encode(['test' => true], $this->key);
        $segments = explode('.', $token);
        $this->assertCount(3, $segments);
    }

    // --- Algorithm support ---

    public function testEncodeWithHS384()
    {
        $payload = ['data' => 'test'];
        $token = JWT::encode($payload, $this->key, 'HS384');
        $decoded = JWT::decode($token, $this->key, ['HS384']);
        $this->assertEquals('test', $decoded->data);
    }

    public function testEncodeWithHS512()
    {
        $payload = ['data' => 'test'];
        $token = JWT::encode($payload, $this->key, 'HS512');
        $decoded = JWT::decode($token, $this->key, ['HS512']);
        $this->assertEquals('test', $decoded->data);
    }

    public function testDecodeWithWrongAlgorithmThrows()
    {
        $token = JWT::encode(['data' => 'test'], $this->key, 'HS256');

        $this->expectException(UnexpectedValueException::class);
        JWT::decode($token, $this->key, ['HS384']);
    }

    // --- Signature verification ---

    public function testDecodeWithInvalidSignatureThrows()
    {
        $token = JWT::encode(['data' => 'test'], $this->key);

        $this->expectException(SignatureInvalidException::class);
        JWT::decode($token, 'wrong-key', ['HS256']);
    }

    // --- Expiration ---

    public function testDecodeExpiredTokenThrows()
    {
        JWT::$timestamp = time() - 100;
        $payload = ['data' => 'test', 'exp' => time() - 50];
        $token = JWT::encode($payload, $this->key);

        JWT::$timestamp = time();
        $this->expectException(ExpiredException::class);
        JWT::decode($token, $this->key, ['HS256']);
    }

    public function testDecodeValidExpiration()
    {
        $payload = ['data' => 'test', 'exp' => time() + 3600];
        $token = JWT::encode($payload, $this->key);
        $decoded = JWT::decode($token, $this->key, ['HS256']);

        $this->assertEquals('test', $decoded->data);
    }

    // --- NBF (not before) ---

    public function testDecodeBeforeNbfThrows()
    {
        $payload = ['data' => 'test', 'nbf' => time() + 3600];
        $token = JWT::encode($payload, $this->key);

        $this->expectException(BeforeValidException::class);
        JWT::decode($token, $this->key, ['HS256']);
    }

    // --- Leeway ---

    public function testDecodeWithLeeway()
    {
        $payload = ['data' => 'test', 'exp' => time() - 5];
        $token = JWT::encode($payload, $this->key);

        JWT::$leeway = 10;
        $decoded = JWT::decode($token, $this->key, ['HS256']);
        $this->assertEquals('test', $decoded->data);
    }

    // --- Custom timestamp ---

    public function testDecodeWithCustomTimestamp()
    {
        $fixedTime = 1700000000;
        JWT::$timestamp = $fixedTime;

        $payload = ['data' => 'test', 'exp' => $fixedTime + 3600];
        $token = JWT::encode($payload, $this->key);
        $decoded = JWT::decode($token, $this->key, ['HS256']);

        $this->assertEquals('test', $decoded->data);
    }

    // --- Base64 ---

    public function testUrlsafeB64EncodeDecode()
    {
        $data = 'Hello, World! Special chars: +/=';
        $encoded = JWT::urlsafeB64Encode($data);
        $decoded = JWT::urlsafeB64Decode($encoded);

        $this->assertEquals($data, $decoded);
        // URL-safe means no +, /, or =
        $this->assertStringNotContainsString('+', $encoded);
        $this->assertStringNotContainsString('/', $encoded);
        $this->assertStringNotContainsString('=', $encoded);
    }

    // --- JSON ---

    public function testJsonEncodeAndDecode()
    {
        $data = ['key' => 'value', 'number' => 42];
        $encoded = JWT::jsonEncode($data);
        $decoded = JWT::jsonDecode($encoded);

        $this->assertEquals('value', $decoded->key);
        $this->assertEquals(42, $decoded->number);
    }

    // --- Edge cases ---

    public function testDecodeEmptyKeyThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        JWT::decode('some.jwt.token', '', ['HS256']);
    }

    public function testDecodeInvalidSegmentsThrows()
    {
        $this->expectException(UnexpectedValueException::class);
        JWT::decode('invalid-token', $this->key, ['HS256']);
    }

    public function testSignWithUnsupportedAlgorithmThrows()
    {
        $this->expectException(DomainException::class);
        JWT::sign('message', $this->key, 'UNSUPPORTED');
    }

    public function testEncodeWithKeyId()
    {
        $token = JWT::encode(['data' => 'test'], $this->key, 'HS256', 'key-1');
        $segments = explode('.', $token);
        $header = json_decode(JWT::urlsafeB64Decode($segments[0]));

        $this->assertEquals('key-1', $header->kid);
    }
}

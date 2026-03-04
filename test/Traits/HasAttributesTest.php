<?php

namespace Atom\Test\Traits;

use Atom\Test\TestCase;
use ArrayAccess;

/**
 * Concrete class using the HasAttributes trait for testing
 */
class AttributeStub implements ArrayAccess
{
    use \Atom\Traits\HasAttributes;
}

class HasAttributesTest extends TestCase
{
    private $stub;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stub = new AttributeStub();
    }

    // --- Magic __get / __set ---

    public function testMagicSetAndGet()
    {
        $this->stub->name = 'John';
        $this->assertEquals('John', $this->stub->name);
    }

    public function testMagicSetOverwrites()
    {
        $this->stub->name = 'John';
        $this->stub->name = 'Jane';
        $this->assertEquals('Jane', $this->stub->name);
    }

    // --- Magic __isset ---

    public function testMagicIsset()
    {
        $this->stub->name = 'John';
        $this->assertTrue(isset($this->stub->name));
        $this->assertFalse(isset($this->stub->nonexistent));
    }

    // --- Magic __unset ---

    public function testMagicUnset()
    {
        $this->stub->name = 'John';
        unset($this->stub->name);
        $this->assertFalse(isset($this->stub->name));
    }

    // --- ArrayAccess: offsetSet ---

    public function testOffsetSet()
    {
        $this->stub['key'] = 'value';
        $this->assertEquals('value', $this->stub['key']);
    }

    public function testOffsetSetNull()
    {
        $this->stub[] = 'appended';
        $this->assertEquals('appended', $this->stub->attributes[0]);
    }

    // --- ArrayAccess: offsetExists ---

    public function testOffsetExists()
    {
        $this->stub['key'] = 'value';
        $this->assertTrue(isset($this->stub['key']));
        $this->assertFalse(isset($this->stub['nonexistent']));
    }

    // --- ArrayAccess: offsetUnset ---

    public function testOffsetUnset()
    {
        $this->stub['key'] = 'value';
        unset($this->stub['key']);
        $this->assertFalse(isset($this->stub['key']));
    }

    public function testOffsetUnsetNonExistent()
    {
        // Should not throw
        unset($this->stub['nonexistent']);
        $this->assertFalse(isset($this->stub['nonexistent']));
    }

    // --- ArrayAccess: offsetGet ---

    public function testOffsetGetReturnsValue()
    {
        $this->stub['key'] = 'value';
        $this->assertEquals('value', $this->stub['key']);
    }

    public function testOffsetGetReturnsNullForNonExistent()
    {
        $this->assertNull($this->stub['nonexistent']);
    }

    // --- mapAttributes ---

    public function testMapAttributes()
    {
        $data = ['name' => 'John', 'email' => 'john@test.com'];
        $this->stub->mapAttributes($data);

        $this->assertEquals('John', $this->stub->name);
        $this->assertEquals('john@test.com', $this->stub->email);
    }

    // --- setAttributes / getAttributes ---

    public function testSetAndGetAttributes()
    {
        $data = ['a' => 1, 'b' => 2];
        $this->stub->setAttributes($data);
        $this->assertEquals($data, $this->stub->getAttributes());
    }

    public function testGetAttributesEmpty()
    {
        $this->assertEquals([], $this->stub->getAttributes());
    }

    // --- Mixed usage ---

    public function testMixedMagicAndArrayAccess()
    {
        $this->stub->name = 'John';
        $this->assertEquals('John', $this->stub['name']);

        $this->stub['email'] = 'john@test.com';
        $this->assertEquals('john@test.com', $this->stub->email);
    }
}

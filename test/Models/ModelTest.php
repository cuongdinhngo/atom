<?php

namespace Atom\Test\Models;

use Atom\Test\TestCase;
use Atom\Models\Model;

/**
 * Concrete test model that bypasses DB connection
 */
class TestModel extends Model
{
    protected ?string $table = 'test_table';

    public function __construct()
    {
        // Skip parent constructor to avoid DB connection
        $this->db = new \Atom\Test\Db\MockDbDriver();
    }
}

class ModelTest extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new TestModel();
    }

    // --- getTable() ---

    public function testGetTableReturnsTableName()
    {
        $this->assertEquals('test_table', $this->model->getTable());
    }

    // --- toArray() ---

    public function testToArrayReturnsEmptyByDefault()
    {
        $this->assertEquals([], $this->model->toArray());
    }

    public function testToArrayReturnsAttributes()
    {
        $this->model->name = 'John';
        $this->model->email = 'john@test.com';

        $result = $this->model->toArray();
        $this->assertEquals('John', $result['name']);
        $this->assertEquals('john@test.com', $result['email']);
    }

    // --- HasAttributes trait via Model ---

    public function testMagicGetSet()
    {
        $this->model->name = 'John';
        $this->assertEquals('John', $this->model->name);
    }

    public function testMagicIsset()
    {
        $this->model->name = 'John';
        $this->assertTrue(isset($this->model->name));
        $this->assertFalse(isset($this->model->nonexistent));
    }

    public function testMagicUnset()
    {
        $this->model->name = 'John';
        unset($this->model->name);
        $this->assertFalse(isset($this->model->name));
    }

    // --- mapAttributes / setAttributes / getAttributes ---

    public function testMapAttributes()
    {
        $data = ['name' => 'John', 'email' => 'john@test.com'];
        $this->model->mapAttributes($data);

        $this->assertEquals('John', $this->model->name);
        $this->assertEquals('john@test.com', $this->model->email);
    }

    public function testSetAndGetAttributes()
    {
        $data = ['a' => 1, 'b' => 2];
        $this->model->setAttributes($data);
        $this->assertEquals($data, $this->model->getAttributes());
    }

    // --- setFillable / hasFillable ---

    public function testSetFillable()
    {
        $this->model->setFillable(['name', 'email']);
        $this->assertTrue($this->model->hasFillable());
    }

    public function testHasFillableDefaultFalse()
    {
        $this->assertFalse($this->model->hasFillable());
    }

    // --- checkTable ---

    public function testCheckTableReturnsTrue()
    {
        $this->assertTrue($this->model->checkTable());
    }
}

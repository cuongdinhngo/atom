<?php

namespace Atom\Test\Container;

use Atom\Test\TestCase;
use Atom\Container\Container;
use Atom\Container\Exception\ContainerException;

// Test fixtures
class SimpleClass
{
    public $value = 'simple';
}

class ClassWithDependency
{
    public $dependency;

    public function __construct(SimpleClass $dep)
    {
        $this->dependency = $dep;
    }
}

class ClassWithDefault
{
    public $name;

    public function __construct(string $name = 'default')
    {
        $this->name = $name;
    }
}

class ContainerTest extends TestCase
{
    private $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
    }

    public function testSetAndGetBinding()
    {
        $this->container->set(SimpleClass::class);
        $instance = $this->container->get(SimpleClass::class);

        $this->assertInstanceOf(SimpleClass::class, $instance);
        $this->assertEquals('simple', $instance->value);
    }

    public function testSetWithConcrete()
    {
        $this->container->set('simple', SimpleClass::class);
        $instance = $this->container->get('simple');

        $this->assertInstanceOf(SimpleClass::class, $instance);
    }

    public function testAutoRegisterOnGet()
    {
        // Getting a class that hasn't been set should auto-register and resolve
        $instance = $this->container->get(SimpleClass::class);
        $this->assertInstanceOf(SimpleClass::class, $instance);
    }

    public function testResolveSimpleClass()
    {
        $instance = $this->container->resolve(SimpleClass::class);
        $this->assertInstanceOf(SimpleClass::class, $instance);
    }

    public function testResolveClassWithDependency()
    {
        $instance = $this->container->resolve(ClassWithDependency::class);

        $this->assertInstanceOf(ClassWithDependency::class, $instance);
        $this->assertInstanceOf(SimpleClass::class, $instance->dependency);
    }

    public function testResolveClassWithDefaultParameter()
    {
        $instance = $this->container->resolve(ClassWithDefault::class);

        $this->assertInstanceOf(ClassWithDefault::class, $instance);
        $this->assertEquals('default', $instance->name);
    }

    public function testResolveNonInstantiableClassThrows()
    {
        $this->expectException(ContainerException::class);
        $this->container->resolve(\Iterator::class);
    }
}

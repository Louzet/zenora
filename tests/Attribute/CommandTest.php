<?php

namespace Zenora\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Zenora\Attribute\Command;

#[Command('test:command', 'This is a test command')]
class CommandTest extends TestCase
{
    public function test_command_attribute_can_be_retrieved()
    {
        $reflectionClass = new ReflectionClass($this);
        $attributes = $reflectionClass->getAttributes(Command::class);

        $this->assertCount(1, $attributes);

        $commandAttribute = $attributes[0]->newInstance();

        $this->assertInstanceOf(Command::class, $commandAttribute);
    }

    public function test_command_attribute_has_correct_name()
    {
        $reflectionClass = new ReflectionClass($this);
        $attribute = $reflectionClass->getAttributes(Command::class)[0]->newInstance();

        $this->assertEquals('test:command', $attribute->name);
    }

    public function test_command_attribute_has_correct_description()
    {
        $reflectionClass = new ReflectionClass($this);
        $attribute = $reflectionClass->getAttributes(Command::class)[0]->newInstance();

        $this->assertEquals('This is a test command', $attribute->description);
    }
}

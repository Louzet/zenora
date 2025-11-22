<?php

namespace Zenora\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Zenora\Attribute\Option;

class OptionTest extends TestCase
{
    public function dummy_method(
        #[Option('foo', shortcut: 'f', help: 'The foo option')] string $foo,
        #[Option('bar')] int $bar
    )
    {
    }

    public function nameless_option(
        #[Option] string $baz
    )
    {
    }

    public function test_option_attribute_can_be_retrieved()
    {
        $reflectionMethod = new ReflectionMethod($this, 'dummy_method');
        $parameters = $reflectionMethod->getParameters();

        $fooParameter = $parameters[0];
        $attributes = $fooParameter->getAttributes(Option::class);

        $this->assertCount(1, $attributes);

        $optionAttribute = $attributes[0]->newInstance();
        $this->assertInstanceOf(Option::class, $optionAttribute);
    }

    public function test_option_attribute_properties_are_correct()
    {
        $reflectionMethod = new ReflectionMethod($this, 'dummy_method');
        $parameters = $reflectionMethod->getParameters();

        // Test first parameter
        $fooAttribute = $parameters[0]->getAttributes(Option::class)[0]->newInstance();
        $this->assertEquals('foo', $fooAttribute->name);
        $this->assertEquals('f', $fooAttribute->shortcut);
        $this->assertEquals('The foo option', $fooAttribute->help);

        // Test second parameter
        $barAttribute = $parameters[1]->getAttributes(Option::class)[0]->newInstance();
        $this->assertEquals('bar', $barAttribute->name);
        $this->assertNull($barAttribute->shortcut);
        $this->assertNull($barAttribute->help);
    }

    public function test_option_attribute_allows_defaults()
    {
        $reflectionMethod = new ReflectionMethod($this, 'nameless_option');
        $attribute = $reflectionMethod->getParameters()[0]->getAttributes(Option::class)[0]->newInstance();

        $this->assertNull($attribute->name);
        $this->assertNull($attribute->shortcut);
        $this->assertNull($attribute->help);
    }
}

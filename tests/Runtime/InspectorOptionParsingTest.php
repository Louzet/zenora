<?php

namespace Zenora\Tests\Runtime;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Zenora\Attribute\Option;
use Zenora\Runtime\Inspector;

class InspectorOptionParsingTest extends TestCase
{
    public function test_metadata_marks_boolean_flags_without_value(): void
    {
        $cmd = new #[\Zenora\Attribute\Command('dummy')] class {
            public function handle(#[Option(shortcut: 'f')] bool $force = false): void {}
        };
        $inspector = new Inspector($cmd);
        $meta = $inspector->getMetadata();

        $this->assertTrue($meta->options['force']['requiresValue'] === false);
        $this->assertContains('force', $meta->options['force']['flags']);
    }

    public function test_metadata_marks_string_options_with_value(): void
    {
        $cmd = new #[\Zenora\Attribute\Command('dummy2')] class {
            public function handle(#[Option(shortcut: 'n')] string $name = 'x'): void {}
        };
        $inspector = new Inspector($cmd);
        $meta = $inspector->getMetadata();

        $this->assertTrue($meta->options['name']['requiresValue']);
        $this->assertSame('string', $meta->options['name']['type']);
    }
}

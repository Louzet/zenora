<?php

namespace Zenora\Tests\Runtime;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Zenora\Attribute\Option;
use Zenora\Runtime\Inspector;

class InspectorTest extends TestCase
{
    #[DataProvider('falseyFlagProvider')]
    public function test_bool_options_parse_falsey_values(mixed $flagValue): void
    {
        [$inspector, $handler] = $this->makeInspector();
        $params = $inspector->resolveParams($handler, ['flag' => $flagValue], [], []);

        $this->assertSame([false], $params);
    }

    #[DataProvider('truthyFlagProvider')]
    public function test_bool_options_parse_truthy_values(mixed $flagValue): void
    {
        [$inspector, $handler] = $this->makeInspector();
        $params = $inspector->resolveParams($handler, ['flag' => $flagValue], [], []);

        $this->assertSame([true], $params);
    }

    public function test_bool_option_defaults_are_used_when_flag_missing(): void
    {
        [$inspector, $handler] = $this->makeInspector();
        $params = $inspector->resolveParams($handler, [], [], []);

        $this->assertSame([false], $params);
    }

    public function test_bool_option_shortcut_supports_truthy_flag(): void
    {
        [$inspector, $handler] = $this->makeInspector();
        $params = $inspector->resolveParams($handler, ['f' => true], [], []);

        $this->assertSame([true], $params);
    }

    private function makeInspector(): array
    {
        $command = new class {
            public function handle(#[Option(shortcut: 'f')] bool $flag = false): void {}
        };

        $handler = new ReflectionMethod($command, 'handle');

        return [new Inspector($command), $handler];
    }

    public static function falseyFlagProvider(): array
    {
        return [
            ['0'],
            ['false'],
            ['FALSE'],
            ['off'],
            ['OFF'],
            ['no'],
            ['NO'],
            ['disable'],
            ['disabled'],
            [0],
            [false],
        ];
    }

    public static function truthyFlagProvider(): array
    {
        return [
            ['1'],
            ['true'],
            ['TRUE'],
            ['on'],
            ['ON'],
            ['yes'],
            ['YES'],
            ['enable'],
            ['enabled'],
            [1],
            [true],
        ];
    }
}

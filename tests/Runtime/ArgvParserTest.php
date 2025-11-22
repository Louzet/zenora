<?php

namespace Zenora\Tests\Runtime;

use PHPUnit\Framework\TestCase;
use Zenora\Runtime\ArgvParser;

class ArgvParserTest extends TestCase
{
    public function test_parses_command_flags_and_args(): void
    {
        [$cmd, $flags, $args] = ArgvParser::parse([
            'script.php',
            'deploy',
            '--env=prod',
            '--force',
            '-v',
            'service-a',
            'service-b'
        ]);

        $this->assertSame('deploy', $cmd);
        $this->assertSame([
            'env' => 'prod',
            'force' => true,
            'v' => true,
        ], $flags);
        $this->assertSame(['service-a', 'service-b'], $args);
    }

    public function test_returns_null_command_when_missing(): void
    {
        [$cmd, $flags, $args] = ArgvParser::parse(['script.php']);

        $this->assertNull($cmd);
        $this->assertSame([], $flags);
        $this->assertSame([], $args);
    }

    public function test_supports_flag_without_value(): void
    {
        [$cmd, $flags, $args] = ArgvParser::parse(['script.php', 'run', '--debug']);

        $this->assertSame('run', $cmd);
        $this->assertSame(['debug' => true], $flags);
        $this->assertSame([], $args);
    }

    public function test_supports_combined_short_flags(): void
    {
        [, $flags, ] = ArgvParser::parse(['script.php', 'run', '-abc']);
        $this->assertSame(['a' => true, 'b' => true, 'c' => true], $flags);
    }

    public function test_supports_short_flag_value_consuming_next_token(): void
    {
        [, $flags, ] = ArgvParser::parse(['script.php', 'run', '-f', '2']);
        $this->assertSame(['f' => '2'], $flags);
    }

    public function test_supports_combined_short_with_value(): void
    {
        [, $flags, ] = ArgvParser::parse(['script.php', 'run', '-ab=c']);
        $this->assertSame(['a' => true, 'b' => 'c'], $flags);
    }

    public function test_supports_attached_value_short_flag(): void
    {
        [, $flags, ] = ArgvParser::parse(['script.php', 'run', '-aforce']);
        $this->assertSame(['a' => 'force'], $flags);
    }

    public function test_long_flag_value_starting_with_dash_requires_separator(): void
    {
        [, $flags, $args] = ArgvParser::parse(['script.php', 'run', '--', '--literal']);
        $this->assertSame([], $flags);
        $this->assertSame(['--literal'], $args);
    }

    public function test_supports_long_flag_value_consuming_next_token(): void
    {
        [, $flags, ] = ArgvParser::parse(['script.php', 'run', '--force', 'yes']);
        $this->assertSame(['force' => 'yes'], $flags);
    }

    public function test_supports_long_flag_negative_number_value(): void
    {
        [, $flags, ] = ArgvParser::parse(['script.php', 'run', '--threshold', '-5']);
        $this->assertSame(['threshold' => '-5'], $flags);
    }

    public function test_double_dash_forces_positional_args(): void
    {
        [, $flags, $args] = ArgvParser::parse(['script.php', 'run', '--', '--not-a-flag', '-q']);
        $this->assertSame([], $flags);
        $this->assertSame(['--not-a-flag', '-q'], $args);
    }

    public function test_duplicate_flag_conflict_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ArgvParser::parse(['script.php', 'run', '--force', '--force=false']);
    }
}

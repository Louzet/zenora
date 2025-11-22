<?php

namespace Zenora\Tests\Runtime;

use PHPUnit\Framework\TestCase;
use Zenora\Runtime\ArgvParser;

class ArgvParserEdgeTest extends TestCase
{
    public function test_combined_flags_and_separator(): void
    {
        [$cmd, $flags, $args] = ArgvParser::parse([
            'script.php', 'deploy', '-abc', '--', '--not', 'positional'
        ]);

        $this->assertSame('deploy', $cmd);
        $this->assertEquals(['a' => true, 'b' => true, 'c' => true], $flags);
        $this->assertEquals(['--not', 'positional'], $args);
    }
}

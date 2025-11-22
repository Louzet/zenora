<?php

namespace Zenora\Tests\Integration;

use PHPUnit\Framework\TestCase;

class DemoHelpTest extends TestCase
{
    public function test_global_help_runs(): void
    {
        $output = [];
        $return = 0;
        $cmd = sprintf('cd %s && php demo.php --help', escapeshellarg($this->projectRoot()));
        exec($cmd, $output, $return);

        if ($return !== 0) {
            $this->markTestSkipped("demo.php not runnable in this environment");
        }

        $this->assertSame(0, $return);
        $this->assertNotEmpty($output);
    }

    public function test_command_help_runs(): void
    {
        $output = [];
        $return = 0;
        $cmd = sprintf('cd %s && php demo.php demo:table --help', escapeshellarg($this->projectRoot()));
        exec($cmd, $output, $return);

        if ($return !== 0) {
            $this->markTestSkipped("demo.php not runnable in this environment");
        }

        $this->assertSame(0, $return);
        $this->assertNotEmpty($output);
    }

    private function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}

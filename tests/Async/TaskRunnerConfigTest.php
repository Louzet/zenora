<?php

namespace Zenora\Tests\Async;

use PHPUnit\Framework\TestCase;
use Zenora\Async\TaskRunner;
use Zenora\Terminal\Cursor;
use Zenora\UI\ProgressBar;

class TaskRunnerConfigTest extends TestCase
{
    public function test_custom_tick_and_width_are_used(): void
    {
        $cursor = new class extends Cursor {
            public function clearLine(): void {}
            public function hide(): void {}
            public function show(): void {}
        };

        $runner = new TaskRunner($cursor, "\033[31m", 1000, 10, ['*']);

        ob_start();
        $runner->progress('Test', function ($pulse, ProgressBar $bar) {
            $bar->setTotal(2)->setWidth(10);
            $bar->advance();
            $pulse();
            $bar->advance();
            $pulse();
        });
        $output = ob_get_clean();

        $this->assertStringContainsString('Test', $output);
        $this->assertStringContainsString('100%', $output);
    }
}

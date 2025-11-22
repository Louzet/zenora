<?php

namespace Zenora\Tests\Async;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Zenora\Async\TaskRunner;
use Zenora\Terminal\Cursor;
use Zenora\UI\ProgressBar;

class TaskRunnerTest extends TestCase
{
    public function test_spin_returns_result_and_restores_cursor_after_pulse(): void
    {
        [$cursor, $runner] = $this->makeRunner();

        ob_start();
        $result = $runner->spin('Working...', function ($pulse) {
            $pulse();
            return 'done';
        });
        ob_end_clean();

        $this->assertSame('done', $result);
        $this->assertSame(1, $cursor->hideCount);
        $this->assertSame(1, $cursor->showCount);
        $this->assertGreaterThanOrEqual(1, $cursor->clearLineCount);
        $this->assertSame('hide', $cursor->calls[0]);
        $this->assertSame('show', end($cursor->calls));
    }

    public function test_spin_restores_cursor_when_task_throws(): void
    {
        [$cursor, $runner] = $this->makeRunner();

        ob_start();
        try {
            $runner->spin('Boom...', function ($pulse) {
                $pulse();
                throw new RuntimeException('boom');
            });
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        } finally {
            ob_end_clean();
        }

        $this->assertSame(1, $cursor->hideCount);
        $this->assertSame(1, $cursor->showCount);
        $this->assertGreaterThanOrEqual(1, $cursor->clearLineCount);
        $this->assertSame('show', end($cursor->calls));
    }

    public function test_spin_handles_task_without_pulse_calls(): void
    {
        [$cursor, $runner] = $this->makeRunner();

        ob_start();
        $result = $runner->spin('Quick job...', function () {
            return 'fast';
        });
        ob_end_clean();

        $this->assertSame('fast', $result);
        $this->assertSame(1, $cursor->hideCount);
        $this->assertSame(1, $cursor->showCount);
        $this->assertGreaterThanOrEqual(1, $cursor->clearLineCount);
        $this->assertSame('show', end($cursor->calls));
    }

    public function test_progress_bar_tracks_completion_and_renders(): void
    {
        [$cursor, $runner] = $this->makeRunner();

        ob_start();
        $barRef = null;
        $result = $runner->spin('Working...', function ($pulse, ProgressBar $bar) use (&$barRef) {
            $barRef = $bar->setTotal(3);
            for ($i = 0; $i < 3; $i++) {
                $bar->advance();
                $pulse();
            }
            $bar->finish();
            return 'done';
        });
        $output = ob_get_clean();

        $this->assertSame('done', $result);
        $this->assertNotNull($barRef);
        $snapshot = $barRef->snapshot();
        $this->assertSame(3, $snapshot['current']);
        $this->assertSame(3, $snapshot['total']);
        $this->assertTrue($snapshot['finished']);
        $this->assertStringContainsString('100%', $output);
        $this->assertStringContainsString('3/3', $output);
    }

    public function test_progress_bar_can_run_without_total(): void
    {
        [$cursor, $runner] = $this->makeRunner();

        ob_start();
        $barRef = null;
        $runner->spin('Counting...', function ($pulse, ProgressBar $bar) use (&$barRef) {
            $barRef = $bar;
            for ($i = 0; $i < 2; $i++) {
                $bar->advance();
                $pulse();
            }
        });
        $output = ob_get_clean();

        $this->assertNotNull($barRef);
        $snapshot = $barRef->snapshot();
        $this->assertSame(2, $snapshot['current']);
        $this->assertNull($snapshot['total']);
        $this->assertTrue($snapshot['active']);
        $this->assertStringContainsString('(2)', $output);
    }

    public function test_progress_wrapper_omits_spinner_icon(): void
    {
        [$cursor, $runner] = $this->makeRunner();

        ob_start();
        $runner->progress('Processing...', function ($pulse, ProgressBar $bar) {
            $bar->setTotal(1)->setMessage('file.txt');
            $bar->advance();
            $pulse();
        });
        $output = ob_get_clean();

        $this->assertStringContainsString('Processing...', $output);
        $this->assertStringContainsString('file.txt', $output);
        $this->assertStringNotContainsString('⠋', $output); // first spinner frame should be absent
    }

    /**
     * @return array{0: FakeCursor, 1: TaskRunner}
     */
    private function makeRunner(): array
    {
        $cursor = new FakeCursor();
        return [$cursor, new TaskRunner($cursor, "\033[31m", 1000)];
    }
}

class FakeCursor extends Cursor
{
    public int $hideCount = 0;
    public int $showCount = 0;
    public int $clearLineCount = 0;
    /** @var list<string> */
    public array $calls = [];

    public function hide(): void
    {
        $this->calls[] = 'hide';
        $this->hideCount++;
    }

    public function show(): void
    {
        $this->calls[] = 'show';
        $this->showCount++;
    }

    public function clearLine(): void
    {
        $this->calls[] = 'clearLine';
        $this->clearLineCount++;
    }
}

<?php

namespace Zenora\Async;

use Fiber;
use Zenora\Terminal\Cursor;
use Zenora\UI\ProgressBar;
use Zenora\UI\ProgressState;

/**
 * Drives fiber-based tasks with a spinner and optional progress bar.
 * Rendering stays in the main loop to avoid corrupting terminal output.
 */
class TaskRunner
{
  private array $frames = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];

  public function __construct(
    private Cursor $cursor,
    private string $colorCode,
    private int $tickMicroseconds = 80000,
    private int $progressWidth = 20,
    private ?array $spinnerFrames = null,
    private array $progressChars = ['filled' => '█', 'empty' => '░'],
    private ?string $progressColor = null
  ) {}

  /**
   * Run a task with an animated spinner and optional progress bar.
   * The task receives ($pulse, ProgressBar $bar) and should call $pulse() to yield.
   * Set $withSpinner to false to only render the progress bar.
   */
  public function spin(string $message, callable $task, bool $withSpinner = true): mixed
  {
    try {
      $this->cursor->hide();
      $state = new ProgressState();
      $progressBar = new ProgressBar($state);
      $fiber = new Fiber($task);
      $pulse = static fn() => Fiber::suspend();

      $fiber->start($pulse, $progressBar);
      $i = 0;

      while (!$fiber->isTerminated()) {
        $this->cursor->clearLine();
        $frames = $this->spinnerFrames ?? $this->frames;
        $frame = $frames[$i++ % count($frames)];
        $prefix = $withSpinner ? "{$this->colorCode}{$frame}\033[0m " : '';
        $line = "{$prefix}{$message}";
        if ($state->active) {
          $line .= ' ' . $this->renderProgress($state);
        }
        echo $line;
        usleep($this->tickMicroseconds);
        if ($fiber->isSuspended()) {
          // Clear spinner line so any task output appears on a fresh line
          $this->cursor->clearLine();
          $fiber->resume();
        }
      }

      return $fiber->getReturn();
    } finally {
      $this->cursor->clearLine();
      $this->cursor->show();
    }
  }

  /**
   * Convenience wrapper: progress bar only, no spinner.
   */
  public function progress(string $message, callable $task): mixed
  {
    return $this->spin($message, $task, false);
  }

  /**
   * Build a textual progress bar, supporting determinate and indeterminate modes.
   */
  private function renderProgress(ProgressState $state): string
  {
    $width = $state->width > 0 ? $state->width : $this->progressWidth;
    $total = $state->total ?? 0;
    $current = $state->current;
    $color = $this->progressColor ?? $this->colorCode;
    $filledChar = $this->progressChars['filled'] ?? '█';
    $emptyChar = $this->progressChars['empty'] ?? '░';

    if ($total > 0) {
      $ratio = min(1, $current / $total);
      $filled = (int)round($ratio * $width);
      $percent = (int)round($ratio * 100);
      $progressBar = str_repeat($filledChar, $filled) . str_repeat($emptyChar, max(0, $width - $filled));
      return sprintf('%s[%s]\033[0m %3d%% (%d/%d)%s', $color, $progressBar, $percent, $current, $total, $state->message ? " {$state->message}" : '');
    }

    // Indeterminate total: show a pulsating bar segment based on current steps
    $pos = $current % max(1, $width);
    $progressBar = '';
    for ($i = 0; $i < $width; $i++) {
      $progressBar .= ($i === $pos) ? $filledChar : $emptyChar;
    }
    return sprintf('%s[%s]\033[0m (%d)%s', $color, $progressBar, $current, $state->message ? " {$state->message}" : '');
  }
}

<?php

namespace Zenora;

use Zenora\Async\TaskRunner;
use Zenora\Interface\WriterInterface;
use Zenora\Terminal\Cursor;
use Zenora\Terminal\Tty;
use Zenora\Theme\CyberTheme;
use Zenora\Theme\ThemeInterface;
use Zenora\UI\Select;
use Zenora\UI\Table;

/**
 * Application context passed to command handlers.
 * Provides IO helpers, task runner, tables, select, and theme access.
 */
class Context
{
  public Cursor $cursor;
  public Tty $tty;
  public ThemeInterface $theme;
  private ?TaskRunner $taskRunner = null;

  public function __construct(public WriterInterface $io, ?ThemeInterface $theme = null)
  {
    $this->cursor = new Cursor();
    $this->tty = new Tty();
    $this->theme = $theme ?? new CyberTheme();
  }

  public function success(string $msg): void
  {
    $this->io->write($this->theme->success() . $this->theme->icon('success') . " ")->line($msg);
  }

  public function title(string $text): void
  {
    $this->io->write("\n" . $this->theme->primary() . strtoupper($text) . "\n");
  }

  public function select(string $q, array $opts): string
  {
    return (new Select($this->cursor, $this->tty))->ask($q, $opts);
  }

  public function work(string $msg, callable $cb): mixed
  {
    return $this->getRunner()->spin($msg, $cb);
  }

  /**
   * Run a task with a progress bar (no spinner). Callback receives ($pulse, ProgressBar $bar).
   */
  public function progress(string $msg, callable $cb): mixed
  {
    return $this->getRunner()->progress($msg, $cb);
  }

  /**
   * Render a data table with auto-sized columns.
   */
  public function table(array $headers, array $rows, string $style = Table::STYLE_ROUNDED, array $options = []): void
  {
    $table = (new Table())
      ->setHeaders($headers)
      ->setStyle($style);

    if (!isset($options['header_preset']) && !isset($options['header_prefix'])) {
      $table->setHeaderStyle($this->theme->primary() . "\033[1m", "\033[0m");
    }

    if (array_key_exists('header_preset', $options)) {
      $table->setHeaderPreset($options['header_preset']);
    }

    if (array_key_exists('header_prefix', $options) || array_key_exists('header_suffix', $options)) {
      $table->setHeaderStyle(
        $options['header_prefix'] ?? null,
        $options['header_suffix'] ?? null
      );
    }

    if (array_key_exists('alignments', $options)) {
      $table->setAlignments($options['alignments']);
    }
    if (array_key_exists('formatter', $options)) {
      $table->setFormatter($options['formatter']);
    }

    foreach ($rows as $row) {
      $table->addRow($row);
    }

    $table->render();
  }

  /**
   * Configure the shared task runner (tick rate, progress width/chars, spinner frames/colors).
   */
  public function configureTasks(?int $tickMicroseconds = null, ?int $progressWidth = null, ?array $spinnerFrames = null, ?array $progressChars = null, ?string $progressColor = null): void
  {
    $this->taskRunner = new TaskRunner(
      $this->cursor,
      $this->theme->primary(),
      $tickMicroseconds ?? 80000,
      $progressWidth ?? 20,
      $spinnerFrames,
      $progressChars ?? ['filled' => '█', 'empty' => '░'],
      $progressColor ?? $this->theme->primary()
    );
  }

  private function getRunner(): TaskRunner
  {
    if ($this->taskRunner === null) {
      $this->configureTasks();
    }
    return $this->taskRunner;
  }
}

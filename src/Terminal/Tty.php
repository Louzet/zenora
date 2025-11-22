<?php

namespace Zenora\Terminal;

/**
 * Thin wrapper to toggle raw terminal mode for key reading (POSIX only).
 */
class Tty
{
  private ?string $originalMode = null;

  public function setRawMode(): void {
    if (PHP_OS_FAMILY === 'Windows') return;
    if (!function_exists('stream_isatty') || !stream_isatty(STDIN)) return;
    if ($this->originalMode) return;

    $this->originalMode = shell_exec('stty -g');
    shell_exec('stty -icanon -echo');
  }

  public function restoreMode(): void
  {
    if (PHP_OS_FAMILY === 'Windows') {
      $this->originalMode = null;
      return;
    }
    if ($this->originalMode && function_exists('stream_isatty') && stream_isatty(STDIN)) {
      shell_exec("stty {$this->originalMode}");
      $this->originalMode = null;
    }
  }

  public function readKey(): string
  {
    $stdin = fopen('php://stdin', 'r');
    $key = fread($stdin, 1);
    if ($key === "\033") {
      $key .= fread($stdin, 2);
    }

    fclose($stdin);
    return $key;
  }
}

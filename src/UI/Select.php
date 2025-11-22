<?php

namespace Zenora\UI;

use Zenora\Terminal\Cursor;
use Zenora\Terminal\Tty;

/**
 * Interactive list selector (arrow keys + enter) with non-TTY fallback.
 */
class Select
{
  public function __construct(private Cursor $cursor, private Tty $tty) {}

  public function ask(string $question, array $options): string
  {
    if (empty($options)) {
      throw new \InvalidArgumentException('Select requires at least one option.');
    }

    if (!function_exists('stream_isatty') || !stream_isatty(STDIN)) {
      echo "\033[33m?\033[0m $question (non-interactive, defaulting to first option)\n";
      return $options[0];
    }

    $this->tty->setRawMode();
    $this->cursor->hide();

    $current = 0;
    echo "\033[32m?\033[0m \033[1m$question\033[0m\n";

    try {
      while (true) {
        foreach ($options as $idx => $opt) {
          $this->cursor->clearLine();
          echo ($idx === $current ? "\033[36m> $opt\033[0m" : "\033[2m  $opt\033[0m") . "\n";
        }

        $key = $this->tty->readKey();
        if ($key === "\033[A") $current = max(0, $current - 1);
        if ($key === "\033[B") $current = min(count($options) - 1, $current + 1);
        if ($key === "\n") break;

        $this->cursor->moveUp(count($options));
      }
    } finally {
      $this->tty->restoreMode();
      $this->cursor->show();
    }

    $this->cursor->moveUp(count($options) + 1); // +1 pour la question
    for($i=0; $i<=count($options); $i++) {
      $this->cursor->clearLine();
      echo "\n";
    }
    
    $this->cursor->moveUp(count($options) + 1);

    return $options[$current];
  }
}

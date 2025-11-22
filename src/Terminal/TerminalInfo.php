<?php

namespace Zenora\Terminal;

/**
 * Utility to discover terminal width with fallbacks.
 */
class TerminalInfo
{
  /**
   * Best-effort terminal width detection with sensible fallback.
   */
  public static function getWidth(): int
  {
    $width = (int) shell_exec('tput cols 2>/dev/null');
    if ($width <= 0) {
      $width = (int) getenv('COLUMNS');
    }

    return $width > 0 ? $width : 80;
  }
}

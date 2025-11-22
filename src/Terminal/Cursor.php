<?php

namespace Zenora\Terminal;

/**
 * Provides small helpers to control the terminal cursor with ANSI escape codes.
 */
class Cursor
{
  /**
   * Hide the cursor until it is explicitly shown again.
   */
  public function hide(): void {
    echo "\033[?25l";
  }

  /**
   * Restore the visibility of the cursor after it was hidden.
   */
  public function show(): void {
    echo "\033[?25h";
  }

  /**
   * Move the cursor up by a given number of lines without changing the column.
   */
  public function moveUp(int $lines = 1): void {
    echo "\033[{$lines}A";
  }

  /**
   * Move the cursor to a specific 1-based column on the current line.
   */
  public function moveToCol(int $col = 1): void {
    echo "\033[{$col}G";
  }

  /**
   * Clear the current line and return the cursor to the start of the line.
   */
  public function clearLine(): void {
    echo "\r\033[2K";
  }
}

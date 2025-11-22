<?php

namespace Zenora\UI;

use Zenora\Terminal\TerminalInfo;

/**
 * Renders terminal tables with styles, alignment, wrapping, and header presets.
 */
class Table
{
  public const STYLE_ASCII = 'ascii';
  public const STYLE_BOX = 'box';
  public const STYLE_ROUNDED = 'rounded';

  private const BORDERS = [
    self::STYLE_ASCII => [
      'tl' => '+', 'tr' => '+', 'bl' => '+', 'br' => '+',
      'h' => '-', 'v' => '|', 'x' => '+',
      't' => '+', 'b' => '+', 'l' => '+', 'r' => '+'
    ],
    self::STYLE_BOX => [
      'tl' => '┌', 'tr' => '┐', 'bl' => '└', 'br' => '┘',
      'h' => '─', 'v' => '│', 'x' => '┼',
      't' => '┬', 'b' => '┴', 'l' => '├', 'r' => '┤'
    ],
    self::STYLE_ROUNDED => [
      'tl' => '╭', 'tr' => '╮', 'bl' => '╰', 'br' => '╯',
      'h' => '─', 'v' => '│', 'x' => '┼',
      't' => '┬', 'b' => '┴', 'l' => '├', 'r' => '┤'
    ],
  ];
  private const HEADER_PRESETS = [
    'default' => "\033[48;5;238m\033[97m", // dark gray bg + bright fg
    'inverse' => "\033[7m",
    'blue' => "\033[44m\033[97m",
    'green' => "\033[42m\033[97m",
    'yellow' => "\033[43m\033[30m",
    'magenta' => "\033[45m\033[97m",
    'cyan' => "\033[46m\033[30m",
  ];

  private array $headers = [];
  private array $rows = [];
  private string $style = self::STYLE_ROUNDED;
  private string $headerPrefix = "\033[48;5;238m\033[97m"; // dark background + bright text
  private string $headerSuffix = "\033[0m";
  private array $alignments = []; // index => 'left'|'right'
  private $formatter = null;

  public function setHeaders(array $headers): self
  {
    $this->headers = $headers;
    return $this;
  }

  public function addRow(array $row): self
  {
    $this->rows[] = $row;
    return $this;
  }

  public function setStyle(string $style): self
  {
    if (isset(self::BORDERS[$style])) {
      $this->style = $style;
    }
    return $this;
  }

  /**
   * Quickly apply a named header style (see HEADER_PRESETS).
   */
  public function setHeaderPreset(string $preset): self
  {
    if (isset(self::HEADER_PRESETS[$preset])) {
      $this->headerPrefix = self::HEADER_PRESETS[$preset];
    }
    return $this;
  }

  /**
   * Customize header styling with ANSI codes. Pass null to keep defaults.
   */
  public function setHeaderStyle(?string $prefix, ?string $suffix = "\033[0m"): self
  {
    if ($prefix !== null) $this->headerPrefix = $prefix;
    if ($suffix !== null) $this->headerSuffix = $suffix;
    return $this;
  }

  /**
   * Set column alignments by index: 'left' or 'right'.
   */
  public function setAlignments(array $alignments): self
  {
    $this->alignments = $alignments;
    return $this;
  }

  /**
   * Set a per-cell formatter callable ($rowIndex, $colIndex, $value): string
   */
  public function setFormatter(callable $formatter): self
  {
    $this->formatter = $formatter;
    return $this;
  }

  public function render(): void
  {
    $chars = self::BORDERS[$this->style];
    $colWidths = $this->calculateColumnWidths();

    $this->drawLine($colWidths, $chars['tl'], $chars['t'], $chars['tr'], $chars['h']);
    $this->drawRow($this->headers, $colWidths, $chars['v'], true, null);
    $this->drawLine($colWidths, $chars['l'], $chars['x'], $chars['r'], $chars['h']);

    foreach ($this->rows as $index => $row) {
      $this->drawRow($row, $colWidths, $chars['v'], false, $index);
    }

    $this->drawLine($colWidths, $chars['bl'], $chars['b'], $chars['br'], $chars['h']);
  }

  private function calculateColumnWidths(): array
  {
    $widths = [];
    $terminalWidth = TerminalInfo::getWidth();

    foreach ($this->headers as $idx => $header) {
      $widths[$idx] = $this->width($header);
    }

    foreach ($this->rows as $row) {
      foreach ($row as $idx => $cell) {
        $widths[$idx] = max($widths[$idx] ?? 0, $this->width((string)$cell));
      }
    }

    $totalPadding = (count($widths) * 3) + 1;
    $totalContent = array_sum($widths);
    if (($totalContent + $totalPadding) > $terminalWidth && $totalContent > 0) {
      $availableSpace = max(10, $terminalWidth - $totalPadding);
      $ratio = $availableSpace / $totalContent;
      foreach ($widths as $idx => $w) {
        $widths[$idx] = max(3, (int) floor($w * $ratio));
      }
    }

    return $widths;
  }

  private function drawLine(array $widths, string $left, string $mid, string $right, string $line): void
  {
    echo $left;
    $segments = [];
    foreach ($widths as $w) {
      $segments[] = str_repeat($line, $w + 2);
    }
    echo implode($mid, $segments);
    echo $right . PHP_EOL;
  }

  private function drawRow(array $row, array $widths, string $sep, bool $isHeader = false, ?int $rowIndex = null): void
  {
    $linesPerCol = [];
    $maxLines = 1;
    foreach ($widths as $idx => $width) {
      $content = (string)($row[$idx] ?? '');
      if ($this->formatter && !$isHeader) {
        $content = ($this->formatter)($rowIndex, $idx, $content);
      }
      $lines = $this->wrapCell($content, $width);
      $linesPerCol[$idx] = $lines;
      $maxLines = max($maxLines, count($lines));
    }

    for ($lineIdx = 0; $lineIdx < $maxLines; $lineIdx++) {
      echo $sep;
      foreach ($widths as $idx => $width) {
        $contentLine = $linesPerCol[$idx][$lineIdx] ?? '';
        $padLen = $width - $this->width($contentLine);
        $padding = str_repeat(' ', max(0, $padLen));
        $align = $this->alignments[$idx] ?? 'left';
        $cellText = $align === 'right'
          ? $padding . $contentLine
          : $contentLine . $padding;

        $cell = $isHeader
          ? "{$this->headerPrefix} " . $cellText . " {$this->headerSuffix}"
          : " " . $cellText . " ";

        echo $cell . $sep;
      }
      echo PHP_EOL;
    }
  }

  private function width(string $str): int
  {
    if (function_exists('mb_strwidth')) return mb_strwidth($str, 'UTF-8');
    if (function_exists('mb_strlen')) return mb_strlen($str, 'UTF-8');
    return strlen($str);
  }

  private function trimWidth(string $str, int $max): string
  {
    if (function_exists('mb_strimwidth')) {
      return mb_strimwidth($str, 0, max(0, $max - 1), '…', 'UTF-8');
    }
    return substr($str, 0, max(0, $max - 1)) . '…';
  }

  private function wrapCell(string $str, int $max): array
  {
    if ($max <= 1) return [$this->trimWidth($str, $max)];

    $lines = [];
    $remaining = $str;
    while ($this->width($remaining) > $max) {
      if (function_exists('mb_strimwidth')) {
        $lines[] = rtrim(mb_strimwidth($remaining, 0, $max, '', 'UTF-8'));
        $remaining = mb_substr($remaining, mb_strlen($lines[array_key_last($lines)], 'UTF-8'), null, 'UTF-8');
      } else {
        $lines[] = substr($remaining, 0, $max);
        $remaining = substr($remaining, $max);
      }
    }
    $lines[] = $remaining;

    return array_map(fn($l) => $this->trimTrailing($l), $lines);
  }

  private function trimTrailing(string $str): string
  {
    return rtrim($str, "\r\n");
  }
}

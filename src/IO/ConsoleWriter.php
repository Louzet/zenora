<?php

namespace Zenora\IO;

use Zenora\Interface\WriterInterface;
use Zenora\Theme\ThemeInterface;

class ConsoleWriter implements WriterInterface
{
  private array $styles = [];
  private const COLORS = [
    'red' => 31,
    'green' => 32,
    'yellow' => 33,
    'blue' => 34,
    'cyan' => 36,
    'white' => 37
  ];

  public function __construct(private ThemeInterface $theme) {}

  public function write(string $message): self
  {
    $code = $this->styles ? "\033[" . implode(';', $this->styles) . "m" : "";
    echo $code . $message . "\033[0m";
    $this->styles = [];
    return $this;
  }

  public function line(string $message = ''): self
  {
    $this->write($message . PHP_EOL);
    return $this;
  }

  public function color(string $color): self
  {
    if (isset(self::COLORS[$color])) $this->styles[] = self::COLORS[$color];
    return $this;
  }

  public function bold(): self
  {
    $this->styles[] = 1;
    return $this;
  }

  public function reset(): self
  {
    $this->styles = [];
    echo "\033[0m";
    return $this;
  }

  public function getTheme(): ThemeInterface
  {
    return $this->theme;
  }
}

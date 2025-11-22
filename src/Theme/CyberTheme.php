<?php

namespace Zenora\Theme;

class CyberTheme implements ThemeInterface
{
  public function primary(): string {
    return "\033[1;35m";
  }

  public function secondary(): string {
    return "\033[36m";
  }

  public function success(): string {
    return "\033[1;32m";
  }

  public function error(): string {
    return "\033[1;31m";
  }

  public function warning(): string {
    return "\033[33m";
  }

  public function dim(): string {
    return "\033[2;37m";
  }

  public function icon(string $name): string
  {
    return match($name) {
      'success' => '✔', 'error' => '✖', 'arrow' => '➜', default => '•'
    };
  }
}

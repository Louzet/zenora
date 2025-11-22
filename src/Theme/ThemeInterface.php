<?php

namespace Zenora\Theme;

interface ThemeInterface
{
  public function primary(): string;
  public function secondary(): string;
  public function success(): string;
  public function error(): string;
  public function warning(): string;
  public function dim(): string;
  public function icon(string $name): string;
}

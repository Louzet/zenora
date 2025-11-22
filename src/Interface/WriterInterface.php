<?php

namespace Zenora\Interface;

interface WriterInterface
{
  public function write(string $message): self;
  public function line(string $message = ''): self;
  public function color(string $color): self;
  public function bold(): self;
  public function reset(): self;
}

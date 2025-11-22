<?php

namespace Zenora\Runtime;

readonly class CommandInfo
{
  /**
   * @param array<string, array{type: string, required: bool}> $arguments
   * @param array<string, array{attr: mixed, type: string, default: mixed}> $options
   */
  public function __construct(
    public string $name,
    public string $description,
    public array $arguments,
    public array $options,
  ) {}
}

<?php

namespace Zenora\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
readonly class Option {
  public function __construct(
    public ?string $name = null,
    public ?string $shortcut = null,
    public ?string $help = null
  ) {}
}

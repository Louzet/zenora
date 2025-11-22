<?php

namespace Zenora\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Command
{
  public function __construct(public string $name, public ?string $description = null) {}
}

<?php

namespace Zenora\UI;

class ProgressState
{
  public int $current = 0;
  public ?int $total = null;
  public bool $active = false;
  public bool $finished = false;
  public ?string $message = null;
  public int $width = 20;
}

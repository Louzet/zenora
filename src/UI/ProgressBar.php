<?php

namespace Zenora\UI;

/**
 * Fiber-friendly progress bar that only mutates shared state.
 * Rendering is delegated to the caller (e.g. TaskRunner) to avoid
 * corrupting terminal output from inside a fiber.
 */
class ProgressBar
{
  public function __construct(private ProgressState $state) {}

  public function setTotal(?int $total): self
  {
    $this->state->active = true;
    $this->state->total = $total !== null && $total < 0 ? 0 : $total;
    return $this;
  }

  public function setMessage(?string $message): self
  {
    $this->state->active = true;
    $this->state->message = $message;
    return $this;
  }

  public function setCurrent(int $current): self
  {
    $this->state->active = true;
    $this->state->current = max(0, $current);
    if ($this->state->total !== null) {
      $this->state->current = min($this->state->current, $this->state->total);
    }
    return $this;
  }

  public function advance(int $step = 1): self
  {
    $this->state->active = true;
    $this->state->current += max(0, $step);
    if ($this->state->total !== null) {
      $this->state->current = min($this->state->current, $this->state->total);
    }
    return $this;
  }

  public function finish(): self
  {
    $this->state->active = true;
    $this->state->finished = true;
    if ($this->state->total !== null) {
      $this->state->current = $this->state->total;
    }
    return $this;
  }

  public function setWidth(int $width): self
  {
    $this->state->width = max(5, $width);
    return $this;
  }

  /**
   * Returns a snapshot for external renderers or assertions.
   *
   * @return array{current:int,total:?int,active:bool,finished:bool,message:?string}
   */
  public function snapshot(): array
  {
    return [
      'current' => $this->state->current,
      'total' => $this->state->total,
      'active' => $this->state->active,
      'finished' => $this->state->finished,
      'message' => $this->state->message,
    ];
  }

  public function getState(): ProgressState
  {
    return $this->state;
  }
}

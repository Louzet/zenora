<?php

namespace Zenora\Tests\UI;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Zenora\Terminal\Cursor;
use Zenora\Terminal\Tty;
use Zenora\UI\Select;

class SelectTest extends TestCase
{
    public function test_throws_when_options_are_empty(): void
    {
        $select = new Select(new DummyCursor(), new DummyTty());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Select requires at least one option.');

        $select->ask('Question ?', []);
    }
}

// Minimal dummies to avoid touching the real terminal
class DummyCursor extends Cursor
{
    public function hide(): void {}
    public function show(): void {}
    public function moveUp(int $lines = 1): void {}
    public function clearLine(): void {}
}

class DummyTty extends Tty
{
    public function setRawMode(): void {}
    public function restoreMode(): void {}
    public function readKey(): string { return "\n"; }
}

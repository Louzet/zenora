<?php

namespace Zenora\Tests\Zenora;

use PHPUnit\Framework\TestCase;
use Zenora\Attribute\Command;
use Zenora\Context;
use Zenora\Interface\WriterInterface;
use Zenora\Theme\CyberTheme;
use Zenora\Zenora;

#[Command('test:hook')]
class HookCommand
{
    public function handle(Context $ctx, HookService $service): void
    {
        $service->called = true;
        $ctx->io->line('ok');
    }
}

class HookService
{
    public bool $called = false;
}

class BufferWriter implements WriterInterface
{
    public string $buffer = '';

    public function write(string $message): self { $this->buffer .= $message; return $this; }
    public function line(string $message = ''): self { $this->buffer .= $message . PHP_EOL; return $this; }
    public function color(string $color): self { return $this; }
    public function bold(): self { return $this; }
    public function reset(): self { return $this; }
}

class ZenoraHooksTest extends TestCase
{
    public function test_services_and_hooks_are_invoked(): void
    {
        $beforeCalled = false;
        $afterCalled = false;
        $service = new HookService();
        $writer = new BufferWriter();
        $zenith = new Zenora(new CyberTheme(), $writer);

        $zenith
            ->register(HookCommand::class)
            ->registerService(HookService::class, $service)
            ->before(function () use (&$beforeCalled) { $beforeCalled = true; })
            ->after(function () use (&$afterCalled) { $afterCalled = true; });

        $zenith->ignite(['cli', 'test:hook']);

        $this->assertTrue($beforeCalled);
        $this->assertTrue($afterCalled);
        $this->assertTrue($service->called);
        $this->assertStringContainsString('ok', $writer->buffer);
    }
}

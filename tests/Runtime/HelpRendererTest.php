<?php

namespace Zenora\Tests\Runtime;

use PHPUnit\Framework\TestCase;
use Zenora\Interface\WriterInterface;
use Zenora\Runtime\CommandInfo;
use Zenora\Runtime\HelpRenderer;
use Zenora\Theme\CyberTheme;

class HelpRendererTest extends TestCase
{
    public function test_render_command_wraps_long_lines(): void
    {
        $io = new BufferWriter();
        $renderer = new HelpRenderer($io, new CyberTheme());

        $longHelp = str_repeat('longhelp ', 5);
        $info = new CommandInfo(
            'demo:test',
            'A demo command',
            ['name' => ['type' => 'string', 'required' => true]],
            [
                'opt' => [
                    'attr' => (object)['shortcut' => 'o', 'name' => null, 'help' => $longHelp],
                    'type' => 'string',
                    'requiresValue' => true,
                    'flags' => ['opt', 'o'],
                    'default' => null,
                ]
            ]
        );

        $renderer->renderCommand($info);

        $output = $io->getBuffer();
        $this->assertStringContainsString('demo:test', $output);
        $this->assertStringContainsString('USAGE:', $output);
        $this->assertStringContainsString('name', $output);
        $this->assertStringContainsString('opt', $output);
        $this->assertStringContainsString('longhelp', $output);
    }
}

class BufferWriter implements WriterInterface
{
    private string $buffer = '';

    public function write(string $message): self
    {
        $this->buffer .= $message;
        return $this;
    }

    public function line(string $message = ''): self
    {
        $this->buffer .= $message . PHP_EOL;
        return $this;
    }

    public function color(string $color): self { return $this; }
    public function bold(): self { return $this; }
    public function reset(): self { return $this; }

    public function getBuffer(): string
    {
        return $this->buffer;
    }
}

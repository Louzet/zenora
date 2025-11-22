<?php

namespace Zenora\Runtime;

use Zenora\Interface\WriterInterface;
use Zenora\Theme\ThemeInterface;
use Zenora\Terminal\TerminalInfo;

/**
 * Renders global/command help with theme-aware coloring and soft wrapping.
 */
class HelpRenderer
{
  public function __construct(
    private WriterInterface $io,
    private ThemeInterface $theme
  ) {}

  public function renderGlobal(array $registry): void
  {
    $this->io->line();
    $this->io->write($this->theme->primary() . ' ZENITH CLI ')->line('v1.0');
    $this->io->line('Usage: bin/zenith <command> [options]');

    $this->io->line()->write($this->theme->secondary() . 'AVAILABLE COMMANDS:')->line();

    foreach ($registry as $class) {
      try {
        $inspector = new Inspector(new $class());
        $meta = $inspector->getMetadata();

        $name = str_pad($meta->name, 25);
        $desc = $this->theme->dim() . ($meta->description ?? '') . "\033[0m";

        $this->io->write('  ' . $this->theme->success() . $name)->line($desc);
      } catch (\Throwable) {
        continue;
      }
    }
    $this->io->line();
  }

  public function renderCommand(CommandInfo $info): void
  {
    $t = $this->theme;
    $width = TerminalInfo::getWidth();
    $wrap = fn(string $text) => $this->wrap($text, max(40, $width - 6));

    $this->io->line();
    $this->io->write($t->primary() . 'COMMAND: ')->line($t->success() . $info->name);
    if ($info->description) {
      $this->io->line('  ' . $info->description);
    }

    $usageArgs = '';
    foreach ($info->arguments as $name => $det) {
      $usageArgs .= $det['required'] ? "<$name> " : "[$name] ";
    }

    $this->io->line();
    $this->io->write($t->secondary() . 'USAGE:')->line();
    $this->io->line("  bin/zenith {$info->name} {$usageArgs}[options]");

    if (!empty($info->arguments)) {
      $this->io->line()->write($t->secondary() . 'ARGUMENTS:')->line();
      foreach ($info->arguments as $name => $details) {
        $req = $details['required'] ? $t->error() . '*' : '';
        $type = $t->dim() . "({$details['type']})";
        $padName = str_pad($name, 20);
        $this->io->line($wrap("  {$t->success()}{$padName} {$type} {$req}"));
      }
    }

    if (!empty($info->options)) {
      $this->io->line()->write($t->secondary() . 'OPTIONS:')->line();
      foreach ($info->options as $name => $data) {
        $attr = $data['attr'];
        $short = $attr->shortcut ? "-{$attr->shortcut}, " : '    ';
        $flagName = $attr->name ?? $name;
        $flag = "{$short}--{$flagName}";
        $padFlag = str_pad($flag, 25);
        $help = $attr->help ?? '';
        $typeLabel = $data['requiresValue'] ? $t->dim() . ' (value)' : $t->dim() . ' (flag)';
        $default = $data['default'] !== null
          ? $t->dim() . '[default: ' . json_encode($data['default']) . ']'
          : '';

        $this->io->line($wrap("  {$t->warning()}{$padFlag} \033[0m{$typeLabel} {$help} {$default}"));
      }
    }

    $this->io->line()->write($t->secondary() . 'GLOBAL:')->line();
    $this->io->line("  {$t->warning()}    --help\033[0m                   Show this help message");

    $this->io->line();
  }

  private function wrap(string $text, int $width): string
  {
    $lines = [];
    foreach (explode("\n", $text) as $line) {
      $lines[] = wordwrap($line, $width, "\n  ", true);
    }
    return implode("\n", $lines);
  }
}

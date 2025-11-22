<?php

namespace Zenora;

use ReflectionClass;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Zenora\Attribute\Command;
use Zenora\Interface\WriterInterface;
use Zenora\IO\ConsoleWriter;
use Zenora\Runtime\ArgvParser;
use Zenora\Runtime\CommandInfo;
use Zenora\Runtime\HelpRenderer;
use Zenora\Runtime\Inspector;
use Zenora\Theme\CyberTheme;
use Zenora\Theme\ThemeInterface;

/**
 * Core kernel: registers commands, resolves dependencies, parses argv, and runs handlers.
 */
class Zenora
{
  private array $registry = [];
  private ThemeInterface $theme;
  private WriterInterface $writer;
  /** @var array<string, mixed> */
  private array $services = [];
  /** @var array<string, array<string, mixed>> */
  private array $commandServices = [];
  /** @var list<callable> */
  private array $beforeHooks = [];
  /** @var list<callable> */
  private array $afterHooks = [];

  public function __construct(?ThemeInterface $theme = null, ?WriterInterface $writer = null)
  {
    $this->theme = $theme ?? new CyberTheme();
    $this->writer = $writer ?? new ConsoleWriter($this->theme);
    $this->services = [
      WriterInterface::class => $this->writer,
      'theme' => $this->theme,
    ];
  }

  public static function forge() {
    return new static();
  }

  public function register(string $class): self
  {
    $this->registry[] = $class;
    return $this;
  }

  /**
   * Scan a directory and auto-register classes marked with #[Command].
   * @param string $dir absolute path to scan
   * @param string $namespace base namespace corresponding to $dir
   */
  public function discover(string $dir, string $namespace): self
  {
    if (!is_dir($dir)) return $this;

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
      if ($file->isDir() || $file->getExtension() !== 'php') continue;
      require_once $file->getPathname();

      $relativePath = substr($file->getPathname(), strlen(rtrim($dir, DIRECTORY_SEPARATOR)) + 1);
      $classPath = str_replace(['/', '\\'], ['\\', '\\'], str_replace('.php', '', $relativePath));
      $className = rtrim($namespace, '\\') . '\\' . $classPath;

      if (!class_exists($className)) continue;
      $ref = new ReflectionClass($className);
      if ($ref->isAbstract()) continue;
      if ($ref->getAttributes(Command::class)) {
        $this->register($className);
      }
    }

    return $this;
  }

  /**
   * Override the theme (and console writer) before running.
   */
  public function withTheme(ThemeInterface $theme): self
  {
    $this->theme = $theme;
    $this->writer = new ConsoleWriter($theme);
    $this->services[WriterInterface::class] = $this->writer;
    $this->services['theme'] = $this->theme;
    return $this;
  }

  /**
   * Register a service (available for type-hint injection).
   */
  public function registerService(string $id, mixed $service): self
  {
    $this->services[$id] = $service;
    return $this;
  }

  public function registerServiceFor(string $commandClass, string $id, mixed $service): self
  {
    $this->commandServices[$commandClass][$id] = $service;
    return $this;
  }

  /**
   * Hook executed before each command. Signature: fn(string $name, string $class, array $services)
   */
  public function before(callable $hook): self
  {
    $this->beforeHooks[] = $hook;
    return $this;
  }

  /**
   * Hook executed after each command. Signature: fn(string $name, string $class, mixed $result)
   */
  public function after(callable $hook): self
  {
    $this->afterHooks[] = $hook;
    return $this;
  }

  public function ignite(array $argv): int
  {
    try {
      [$cmdName, $flags, $args] = ArgvParser::parse($argv);
      $isHelp = isset($flags['help']) || isset($flags['h']);
      $renderer = new HelpRenderer($this->writer, $this->theme);

      // If the first token is a flag (e.g. --help), treat it as global help with no command
      if ($cmdName && str_starts_with($cmdName, '-')) {
        $flags[ltrim($cmdName, '-')] = true;
        $cmdName = null;
        $isHelp = true;
      }

      // No command provided: show global help
      if (!$cmdName) {
        $renderer->renderGlobal($this->registry);
        return 0;
      }

      $class = $this->findCommand($cmdName);
      if (!$class) {
        if ($isHelp) {
          $renderer->renderGlobal($this->registry);
          return 0;
        }
        $this->writer->bold()->color('red')->line("Command '$cmdName' not found.");
        return 1;
      }

      if ($isHelp) {
        $inspector = new Inspector(new $class());
        $renderer->renderCommand($inspector->getMetadata());
        return 0;
      }

      $instance = new $class();
      $inspector = new Inspector($instance);
      $handler = $inspector->getHandler();

      $services = $this->services;
      if (isset($this->commandServices[$class])) {
        $services = array_merge($services, $this->commandServices[$class]);
      }

      foreach ($this->beforeHooks as $hook) {
        $hook($cmdName, $class, $services);
      }

      $params = $inspector->resolveParams($handler, $flags, $args, $services);
      $result = $handler->invokeArgs($instance, $params);

      foreach ($this->afterHooks as $hook) {
        $hook($cmdName, $class, $result);
      }

      return is_int($result) ? $result : 0;
    } catch (\Throwable $e) {
      $this->renderException($e);
      return 1;
    }
  }

  private function findCommand(string $name): ?string
  {
    foreach ($this->registry as $class) {
      $attr = (new ReflectionClass($class))->getAttributes(Command::class)[0] ?? null;
      if ($attr && $attr->newInstance()->name === $name) {
        return $class;
      }
    }

    return null;
  }

  private function renderException(\Throwable $e): void
  {
    $this->writer->line();
    $this->writer->bold()->color('red')->write(' FATAL ');
    $this->writer->color('red')->line(' ' . $e->getMessage());
    $this->writer->color('yellow')->line(" in {$e->getFile()}:{$e->getLine()}");
  }
}

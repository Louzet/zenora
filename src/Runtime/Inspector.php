<?php

namespace Zenora\Runtime;

use ReflectionClass;
use ReflectionMethod;
use Zenora\Attribute\Command;
use Zenora\Attribute\Option;
use Zenora\Context;
use Zenora\Interface\WriterInterface;

/**
 * Inspects command classes, resolves handlers, and builds metadata/arguments.
 */
class Inspector
{
  public function __construct(private object $instance) {}

  public function getHandler(): ReflectionMethod {
    $ref = new ReflectionClass($this->instance);
    if ($ref->hasMethod('handle')) return $ref->getMethod('handle');
    throw new \RuntimeException("Method handle() missing in " . $ref->getName());
  }

  public function getMetadata(): CommandInfo
  {
    $ref = new ReflectionClass($this->instance);
    $cmdAttr = $ref->getAttributes(Command::class)[0] ?? null;
    if (!$cmdAttr) {
      throw new \RuntimeException("Class " . $ref->getName() . " has no #[Command] attribute");
    }
    $cmdInst = $cmdAttr->newInstance();

    $args = [];
    $opts = [];
    $method = $this->getHandler();

    foreach ($method->getParameters() as $param) {
      $name = $param->getName();
      $type = $param->getType()?->getName() ?? 'mixed';

      // Ignore injected services (Context, Writer, Theme, etc.)
      if ($type && str_starts_with($type, 'Zenora\\')) continue;

      $optAttr = $param->getAttributes(Option::class)[0] ?? null;
      if ($optAttr) {
        $optInstance = $optAttr->newInstance();
        $flags = [
          $optInstance->name ?? $name,
          $optInstance->shortcut ?? null
        ];
        $opts[$name] = [
          'attr' => $optInstance,
          'type' => $type,
          'requiresValue' => $type !== 'bool',
          'flags' => array_values(array_filter($flags)),
          'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
        ];
        continue;
      }

      $args[$name] = [
        'type' => $type,
        'required' => !$param->isOptional(),
      ];
    }

    return new CommandInfo(
      $cmdInst->name,
      $cmdInst->description ?? '',
      $args,
      $opts
    );
  }

  public function resolveParams(ReflectionMethod $method, array $flags, array $args, array $services): array
  {
    $inject = [];
    foreach ($method->getParameters() as $param) {
      $type = $param->getType()?->getName();
      $name = $param->getName();

      // 1. Injection Service
      if (isset($services[$type])) { $inject[] = $services[$type]; continue; }
      // 2. Injection Context Factory
      if ($type === Context::class) {
        $inject[] = new Context($services[WriterInterface::class], $services['theme'] ?? null);
        continue;
      }

      // 3. Options
      $attr = $param->getAttributes(Option::class)[0] ?? null;
      if ($attr) {
        $opt = $attr->newInstance();
        $key = $opt->name ?? $name;
        $val = $flags[$key] ?? ($opt->shortcut ? ($flags[$opt->shortcut] ?? null) : null);
        $val ??= ($param->isDefaultValueAvailable() ? $param->getDefaultValue() : null);

        // Casting simple
        if ($type === 'int') $val = (int)$val;
        if ($type === 'bool') $val = $this->parseBool($val);
        $inject[] = $val;
        continue;
      }

      // 4. Args
      $inject[] = !empty($args) ? array_shift($args) :
        ($param->isDefaultValueAvailable() ? $param->getDefaultValue() : throw new \Exception("Missing arg $name"));
    }

    return $inject;
  }

  private function parseBool(mixed $val): bool
  {
    if (is_bool($val)) return $val;
    if (is_int($val)) return $val !== 0;
    if (is_string($val)) {
      $normalized = strtolower(trim($val));
      $falsey = ['0', 'false', 'off', 'no', 'n', 'disable', 'disabled'];
      $truthy = ['1', 'true', 'on', 'yes', 'y', 'enable', 'enabled'];

      if (in_array($normalized, $falsey, true)) return false;
      if (in_array($normalized, $truthy, true)) return true;
    }

    return (bool)$val;
  }
}

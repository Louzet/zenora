## Zenora CLI Toolkit (v0.1)

Zenora is a PHP 8.3+ toolkit to build rich CLIs quickly: attributes for commands/options, a configurable task runner with fiber-friendly spinner/progress bar, styled tables, interactive selects, and themed output.

### Install
```bash
composer require louzet/zenora
```

### Quick start
```php

use Zenora\Attribute\Command;
use Zenora\Attribute\Option;
use Zenora\Context;
use Zenora\Zenora;

#[Command('app:hello', description: 'Say hello')]
class HelloCommand {

  public function handle(
    Context $ctx,
    #[Option(shortcut: 'n')] string $name = 'World'
    ): void {
    $ctx->success("Hello $name!");
  }
}

Zenora::forge()
  ->register(HelloCommand::class)
  ->ignite($argv);
```

Run:
```bash
php bin/console app:hello -n Alice
php bin/console app:hello --help   # auto help
php bin/console --help             # list commands
```

### Parsing behavior
- Supports `--key=value`, `--key value`, `-k value`, combined shorts `-abc`, `-ab=c`, and attached value `-afoo`.
- Negative numbers after flags are allowed (`--threshold -5`); for other values starting with `-`, use `--` to stop parsing or `--key=value`.
- Use `--` to stop flag parsing: `cmd -- --literal -text`.
- Duplicate flags with conflicting values throw.

### Services & hooks
```php
$app = Zenith::forge()
  ->registerService(MyService::class, new MyService())
  ->before(fn($name, $class) => /* log */)
  ->after(fn($name, $class, $result) => /* metrics */)
  ->register(MyCommand::class)
  ->ignite($argv);
```
Per-command services: `registerServiceFor(CommandClass::class, id, service)`.

### Task runner & progress
```php
use Zenora\UI\ProgressBar;

$ctx->configureTasks(tickMicroseconds: 50_000, progressWidth: 30, progressChars: ['filled' => '#', 'empty' => '.']);
$ctx->work('Doing stuff...', function($pulse, ProgressBar $bar) {
  $bar->setTotal(3);
  foreach (range(1,3) as $i) { /* work */ $bar->advance()->setMessage("step $i"); $pulse(); }
  $bar->finish();
});
```
Use `$ctx->progress()` to show the bar without spinner.

### Tables
```php
use Zenora\UI\Table;

$ctx->table(
  ['ID','Name','Notes'],
  [[1,'Alice','Long note that wraps...']],
  style: Table::STYLE_BOX,
  options: [
    'header_preset' => 'blue',
    'alignments' => [0 => 'right', 2 => 'left'],
    'formatter' => fn($row,$col,$val) => strtoupper($val),
  ]
);
```
Headers default to the theme primary color; presets and custom ANSI codes are available.

### Interactive select
`Select` uses raw mode; in non-TTY it falls back to the first option. POSIX terminals are supported; Windows raw-mode handling is not implemented yet (fallback to non-interactive).

### Demo commands
Run in this repo:
```bash
php demo.php --help
php demo.php demo:progress
php demo.php demo:table
php demo.php demo:args -fr 5 target
php demo.php demo:service
```

### Theme defaults
Colors come from the active theme (default `CyberTheme`). Table headers and progress bars will use theme primary unless overridden.

### Known limitations
- Parsing is close to GNU but not identical; if a non-numeric value starts with `-`, use `--` to terminate parsing or quote it.
- Windows TTY support is not implemented; POSIX terminals recommended (Windows falls back to non-interactive select).
- No variadic args or custom value parsers yet.

### Tests
```bash
composer test
```

### Documentation
See `docs/` for more focused examples (tasks, tables, progress, services, hooks, and interactive widgets). You can run them locally to capture screenshots.

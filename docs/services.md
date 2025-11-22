# Services & Hooks

Inject custom services, instrument with before/after hooks, and log from services.

## Key points
- `registerService()` makes a service injectable by its class name.
- `registerServiceFor()` overrides per command.
- Hooks `before` / `after` are ideal for logging/metrics.
- Services can depend on `WriterInterface` for themed output.

## Example
```php
use Zenora\Attribute\Command;
use Zenora\Context;
use Zenora\Interface\WriterInterface;
use Zenora\Zenora;

class LoggerService
{
  public function __construct(private WriterInterface $io) {}
  public function info(string $msg): void { $this->io->color('cyan')->line("[info] $msg"); }
}

#[Command('demo:services')]
class ServicesDemo
{
  public function handle(Context $ctx, LoggerService $logger): void
  {
    $ctx->title('Services & Hooks Demo');
    $logger->info('Service injected with DI');
    $ctx->success('Demo finished.');
  }
}

$loggerService = new LoggerService(new \Zenora\IO\ConsoleWriter(new \Zenora\Theme\CyberTheme()));

Zenora::forge()
  ->register(ServicesDemo::class)
  ->registerService(LoggerService::class, $loggerService)
  ->before(fn($name) => print "\033[2m[before] $name\033[0m\n")
  ->after(fn($name) => print "\033[2m[after] $name\033[0m\n")
  ->ignite($argv);
```

## Tips
- Reuse a shared writer in services so styles are consistent.
- Hooks run around every command; keep them fast and side-effect free (e.g., async logging, metrics).

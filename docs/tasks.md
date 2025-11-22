# Tasks & Progress

How to use the runner, spinner, and progress bar with Fibers.

## Key points
- `work()` renders a spinner plus a progress bar.
- `progress()` renders the bar only.
- The callback gets `($pulse, ProgressBar $bar)` to update state and yield back to the renderer.
- Customize tick rate, bar width/chars via `$ctx->configureTasks()`.

## Example
```php
use Zenora\Attribute\Command;
use Zenora\Context;
use Zenora\UI\ProgressBar;
use Zenora\Zenora;

#[Command('demo:tasks')]
class TasksDemo
{
  public function handle(Context $ctx): void
  {
    $ctx->title('Tasks & Progress Demo');

    $ctx->work('Traitement avec spinner...', function ($pulse, ProgressBar $bar) {
      $bar->setTotal(4)->setMessage('init');
      foreach (range(1, 4) as $i) {
        usleep(120_000);
        $bar->advance()->setMessage("step $i/4");
        $pulse();
      }
      $bar->finish()->setMessage('done');
    });

    $ctx->io->line();

    $ctx->progress('Barre seule...', function ($pulse, ProgressBar $bar) {
      $bar->setTotal(3)->setMessage('bar only');
      foreach (range(1, 3) as $i) {
        usleep(100_000);
        $bar->advance()->setMessage("bar $i/3");
        $pulse();
      }
      $bar->finish()->setMessage('done');
    });
  }
}

Zenora::forge()
  ->register(TasksDemo::class)
  ->ignite($argv);
```

## Tips
- Call `$pulse()` after each logical unit of work to keep the UI fluid.
- Use `$bar->setMessage()` for the current item/file; keep it short to avoid wrapping.
- For indeterminate work, omit `setTotal()` and just call `advance()`.

# Interactive Select

Interactive choice with non-TTY fallback.

## Key points
- Uses raw mode in TTY; in non-TTY, falls back to the first option with a notice.
- Great for picking environments, actions, or quick choices.

## Example
```php
use Zenora\Attribute\Command;
use Zenora\Context;
use Zenora\Zenora;

#[Command('demo:select')]
class SelectDemo
{
  public function handle(Context $ctx): void
  {
    $ctx->title('Select Demo');
    $choice = $ctx->select('Choisis une option', ['Alpha', 'Beta', 'Gamma']);
    $ctx->success("Tu as choisi: $choice");
  }
}

Zenora::forge()
  ->register(SelectDemo::class)
  ->ignite($argv);
```

## Tips
- Keep option labels short to avoid wrapping.
- If running in CI or piping input, expect the non-interactive fallback.

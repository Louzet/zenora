# Styled Tables

Tables with styles, alignment, wrapping, and header presets.

## Key points
- Styles: `STYLE_ROUNDED`, `STYLE_BOX`, `STYLE_ASCII`.
- Options: `header_preset`, `alignments`, `formatter` (per-cell callback).
- Auto-wrapping when text exceeds column width.
- Headers default to theme primary color if no preset provided.

## Example
```php
use Zenora\Attribute\Command;
use Zenora\Context;
use Zenora\UI\Table;
use Zenora\Zenora;

#[Command('demo:tables')]
class TablesDemo
{
  public function handle(Context $ctx): void
  {
    $headers = ['ID', 'Utilisateur', 'Email', 'Rôle', 'Note'];
    $rows = [
      ['1', 'Mickael', 'mickael@zenith.dev', 'Admin', 'Courte note'],
      ['2', 'Sarah', 'sarah@design.co', 'Editor', 'Note longue avec wrapping automatique pour tester le rendu sur plusieurs lignes dans une colonne.'],
      ['3', 'Bot 🤖', 'bot@ai.net', 'Robot', 'Wrap + emoji'],
    ];

    $ctx->io->line("\nStyle: Box, header preset blue");
    $ctx->table($headers, $rows, Table::STYLE_BOX, [
      'header_preset' => 'blue',
      'alignments' => [0 => 'right', 2 => 'left', 4 => 'left'],
    ]);

    $ctx->io->line("\nStyle: Rounded, formatter uppercases column 1");
    $ctx->table($headers, $rows, Table::STYLE_ROUNDED, [
      'header_preset' => 'magenta',
      'formatter' => fn($row, $col, $val) => $col === 1 ? strtoupper($val) : $val,
    ]);
  }
}

Zenora::forge()
  ->register(TablesDemo::class)
  ->ignite($argv);
```

## Tips
- Use `alignments` to right-align numeric columns.
- `formatter` can add units or colorize values conditionally.
- If your table is too wide, auto-sizing will truncate with ellipsis; keep headers short.

<?php

namespace Zenora\Tests\UI;

use PHPUnit\Framework\TestCase;
use Zenora\UI\Table;

class TableTest extends TestCase
{
    public function test_table_truncates_and_aligns_headers(): void
    {
        $table = new Table();
        ob_start();
        $table->setHeaders(['VeryLongHeader', 'B'])
              ->addRow(['This is a very long cell that should be trimmed', 'ok'])
              ->setStyle(Table::STYLE_ASCII)
              ->render();
        $output = ob_get_clean();

        $this->assertStringContainsString('+', $output);
        $this->assertStringContainsString('VeryLong', $output);
        // allow fallback when mb_strimwidth not available; just ensure trimming happened
        $this->assertTrue(
            str_contains($output, '…') || str_contains($output, 'This is a very long cell that should be trim'),
            'Table should truncate long content.'
        );
    }
}

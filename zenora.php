<?php

use Zenora\Zenora;
use Zenora\Theme\CyberTheme;

return Zenora::forge()
  // ->withTheme(new CyberTheme())
  // Adjust the path and namespace for your commands:
  ->discover(__DIR__ . '/src/Command', 'App\\Command');
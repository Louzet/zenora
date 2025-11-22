<?php

namespace Zenora\Runtime;

/**
 * Minimal, GNU-like argv parser with combined shorts, inline values, and -- separator.
 */
final class ArgvParser
{
  /**
   * Parses CLI arguments into the command name, flags, and positional args.
   *
   * @return array{0: ?string, 1: array<string, mixed>, 2: array<int, string>}
   */
  public static function parse(array $argv): array
  {
    $command = null;
    $flags = [];
    $args = [];

    // Skip script name
    $tokens = array_slice($argv, 1);
    if ($tokens) {
      // First non-flag token is the command
      if (!str_starts_with($tokens[0], '-')) {
        $command = array_shift($tokens);
      }
    }

    $consumeArgs = false;
    for ($i = 0; $i < count($tokens); $i++) {
      $token = $tokens[$i];

      if ($consumeArgs) {
        $args[] = $token;
        continue;
      }

      if ($token === '--') {
        $consumeArgs = true;
        continue;
      }

      // Long form with inline value: --key=value
      if (str_starts_with($token, '--')) {
        $parts = explode('=', substr($token, 2), 2);
        $key = $parts[0];
        if (isset($parts[1])) {
          self::setFlag($flags, $key, $parts[1]);
        } else {
          $next = $tokens[$i + 1] ?? null;
          $looksLikeValue = $next !== null && (!str_starts_with($next, '--') && (!str_starts_with($next, '-') || self::looksLikeNegativeNumber($next)));
          if ($looksLikeValue) { self::setFlag($flags, $key, $next); $i++; }
          else { self::setFlag($flags, $key, true); }
        }
        continue;
      }

      // Combined shorts with inline value: -abc=value (value goes to last)
      if (str_starts_with($token, '-') && !str_starts_with($token, '--') && str_contains($token, '=')) {
        [$shorts, $value] = explode('=', substr($token, 1), 2);
        $chars = str_split($shorts);
        foreach ($chars as $idx => $c) {
          if ($idx === count($chars) - 1) self::setFlag($flags, $c, $value);
          else self::setFlag($flags, $c, true);
        }
        continue;
      }

      if (str_starts_with($token, '-') && strlen($token) > 1) {
        $shorts = substr($token, 1);
        // Combined shorts like -abc or single -f value
        if (strlen($shorts) > 1) {
          $tail = substr($shorts, 1);
          // Heuristic: treat as combined shorts when tail is short (<=2) and alphabetic, otherwise interpret as -aVALUE.
          if (strlen($tail) <= 2 && ctype_alpha($tail)) {
            foreach (str_split($shorts) as $c) self::setFlag($flags, $c, true);
          } else {
            self::setFlag($flags, $shorts[0], $tail);
          }
        } else {
          $key = $shorts;
          $next = $tokens[$i + 1] ?? null;
          $looksLikeValue = $next !== null && (!str_starts_with($next, '-') || self::looksLikeNegativeNumber($next)) && (is_numeric($next) || str_contains((string)$next, '='));
          if ($looksLikeValue) { self::setFlag($flags, $key, $next); $i++; }
          else { self::setFlag($flags, $key, true); }
        }
        continue;
      }

      $args[] = $token;
    }

    return [$command, $flags, $args];
  }

  private static function setFlag(array &$flags, string $key, mixed $value): void
  {
    if (array_key_exists($key, $flags) && $flags[$key] !== $value) {
      throw new \InvalidArgumentException("Duplicate flag '$key' with conflicting values.");
    }
    $flags[$key] = $value;
  }

  private static function looksLikeNegativeNumber(string $value): bool
  {
    return str_starts_with($value, '-') && is_numeric(substr($value, 1));
  }
}

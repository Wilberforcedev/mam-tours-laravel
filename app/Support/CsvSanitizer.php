<?php

namespace App\Support;

class CsvSanitizer
{
    /**
     * Make a row safe to open in spreadsheet applications without executing
     * user-controlled values as formulas.
     *
     * @param array<int, mixed> $values
     * @return array<int, string>
     */
    public static function row(array $values): array
    {
        return array_map([self::class, 'value'], $values);
    }

    /**
     * Prefix formula-like values with an apostrophe so spreadsheet software
     * treats them as text. CSV quoting alone does not prevent formula execution.
     *
     * @param mixed $value
     */
    private static function value($value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof \\DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        } elseif ($value instanceof \\Stringable) {
            $value = (string) $value;
        } elseif (!is_scalar($value)) {
            return '';
        } else {
            $value = (string) $value;
        }

        if (preg_match('/^(?:[\x00-\x20]|\xEF\xBB\xBF)*[=+\-@]/', $value) === 1) {
            return "'" . $value;
        }

        return $value;
    }
}

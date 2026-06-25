<?php

namespace App\Support;

class ActivityLogDetailsBuilder
{
    /**
     * @param  array<string, array{value?: mixed, label?: string}>  $fields
     * @return array{operation: string, fields: array<string, array{value: mixed, label?: string}>}
     */
    public static function created(array $fields): array
    {
        return [
            'operation' => 'create',
            'fields' => self::meaningfulFields($fields),
        ];
    }

    /**
     * @param  array<string, array{value?: mixed, label?: string}>  $fields
     * @return array{operation: string, fields: array<string, array{value: mixed, label?: string}>}
     */
    public static function deleted(array $fields): array
    {
        return [
            'operation' => 'delete',
            'fields' => self::meaningfulFields($fields),
        ];
    }

    /**
     * @param  array<string, array{value?: mixed, label?: string}>  $before
     * @param  array<string, array{value?: mixed, label?: string}>  $after
     * @return array{operation: string, fields: array<string, array{from: mixed, to: mixed, label?: string}>}
     */
    public static function updated(array $before, array $after): array
    {
        $changes = [];
        $keys = array_values(array_unique([...array_keys($before), ...array_keys($after)]));

        foreach ($keys as $key) {
            $from = self::normalize($before[$key]['value'] ?? null);
            $to = self::normalize($after[$key]['value'] ?? null);

            if ($from === $to) {
                continue;
            }

            $entry = [
                'from' => $before[$key]['value'] ?? null,
                'to' => $after[$key]['value'] ?? null,
            ];

            $label = $after[$key]['label'] ?? $before[$key]['label'] ?? null;

            if (filled($label)) {
                $entry['label'] = $label;
            }

            $changes[$key] = $entry;
        }

        return [
            'operation' => 'update',
            'fields' => $changes,
        ];
    }

    /**
     * @return array{value: mixed, label?: string}
     */
    public static function field(mixed $value, ?string $label = null): array
    {
        $entry = ['value' => $value];

        if (filled($label)) {
            $entry['label'] = $label;
        }

        return $entry;
    }

    /**
     * @param  array<string, array{value?: mixed, label?: string}>  $fields
     * @return array<string, array{value: mixed, label?: string}>
     */
    private static function meaningfulFields(array $fields): array
    {
        return array_filter(
            $fields,
            fn (array $entry) => self::normalize($entry['value'] ?? null) !== null,
        );
    }

    private static function normalize(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            $items = array_map(static fn ($item) => trim((string) $item), $value);
            $items = array_values(array_filter($items, static fn ($item) => $item !== ''));
            sort($items);

            return $items === [] ? null : implode(', ', $items);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return rtrim(rtrim(number_format((float) $value, 8, '.', ''), '0'), '.');
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}

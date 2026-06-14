<?php

namespace App\Support;

class PointTypes
{
    /**
     * @return list<string>
     */
    public static function normalize(mixed $types, ?string $fallbackType = null): array
    {
        $values = is_array($types)
            ? $types
            : ($fallbackType ? [$fallbackType] : []);

        $allowed = array_keys(NetworkCatalog::pointTypes());

        $normalized = [];

        foreach ($values as $value) {
            $key = is_string($value) ? trim($value) : '';

            if ($key === '' || ! in_array($key, $allowed, true)) {
                continue;
            }

            if (! in_array($key, $normalized, true)) {
                $normalized[] = $key;
            }
        }

        if ($normalized === []) {
            return ['junction'];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function apply(array $validated): array
    {
        $types = self::normalize(
            $validated['types'] ?? null,
            isset($validated['type']) ? (string) $validated['type'] : null,
        );

        unset($validated['type']);

        $validated['types'] = $types;
        $validated['type'] = $types[0];

        return $validated;
    }
}

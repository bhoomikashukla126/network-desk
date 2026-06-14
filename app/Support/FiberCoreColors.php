<?php

namespace App\Support;

class FiberCoreColors
{
    /**
     * Standard 12-fiber TIA-598 color sequence.
     *
     * @return list<string>
     */
    public static function palette(): array
    {
        return [
            '#007bff',
            '#fd7e14',
            '#28a745',
            '#8b4513',
            '#6c757d',
            '#f8f9fa',
            '#dc3545',
            '#212529',
            '#ffc107',
            '#6f42c1',
            '#e83e8c',
            '#17a2b8',
        ];
    }

    public static function forCoreNumber(int $coreNumber): string
    {
        $palette = self::palette();
        $index = ($coreNumber - 1) % count($palette);

        return $palette[$index];
    }

    /**
     * TIA-598 fiber color names aligned with {@see palette()}.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return [
            'Blue',
            'Orange',
            'Green',
            'Brown',
            'Slate',
            'White',
            'Red',
            'Black',
            'Yellow',
            'Violet',
            'Rose',
            'Aqua',
        ];
    }

    /**
     * @return list<array{value: string, name: string}>
     */
    public static function options(): array
    {
        $palette = self::palette();
        $names = self::names();

        return array_map(
            fn (string $hex, string $name) => ['value' => $hex, 'name' => $name],
            $palette,
            $names,
        );
    }
}

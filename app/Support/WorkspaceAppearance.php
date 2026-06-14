<?php

namespace App\Support;

class WorkspaceAppearance
{
    public const DEFAULT_THEME = 'forest';

    public const DEFAULT_COLOR_MODE = 'light';

    /** @var list<string> */
    public const COLOR_MODES = ['light', 'dark', 'system'];

    /**
     * @return array{
     *     theme_key: string,
     *     theme: string,
     *     color_mode: string,
     *     colors: array<string, string>
     * }
     */
    public static function fromWorkspace(?array $workspace): array
    {
        if (is_array($workspace['appearance'] ?? null)) {
            return $workspace['appearance'];
        }

        return self::buildPayload(
            (string) ($workspace['theme_key'] ?? $workspace['theme'] ?? self::DEFAULT_THEME),
            (string) ($workspace['color_mode'] ?? self::DEFAULT_COLOR_MODE),
        );
    }

    /**
     * @return array{
     *     theme_key: string,
     *     theme: string,
     *     color_mode: string,
     *     colors: array<string, string>
     * }
     */
    public static function buildPayload(string $themeKey, string $colorMode): array
    {
        $themes = config('themes', []);
        $key = array_key_exists($themeKey, $themes) ? $themeKey : self::DEFAULT_THEME;
        $mode = in_array($colorMode, self::COLOR_MODES, true) ? $colorMode : self::DEFAULT_COLOR_MODE;
        $colors = $themes[$key] ?? ($themes[self::DEFAULT_THEME] ?? []);

        if ($mode === 'dark') {
            $colors = array_merge($colors, [
                'background' => '#0f172a',
                'card_bg' => '#1e293b',
                'text_primary' => '#f1f5f9',
                'text_secondary' => '#cbd5e1',
                'text_muted' => '#94a3b8',
                'border' => '#334155',
            ]);
        }

        return [
            'theme_key' => $key,
            'theme' => $key,
            'color_mode' => $mode,
            'colors' => $colors,
        ];
    }

    /**
     * @param  array<string, mixed>  $workspace
     * @return array<string, mixed>
     */
    public static function mergeIntoContext(array $workspace): array
    {
        $appearance = self::fromWorkspace($workspace);

        return array_merge($workspace, [
            'theme_key' => $appearance['theme_key'],
            'theme' => $appearance['theme'],
            'color_mode' => $appearance['color_mode'],
            'appearance' => $appearance,
        ]);
    }

    /**
     * @param  array{
     *     theme_key: string,
     *     theme: string,
     *     color_mode: string,
     *     colors: array<string, string>
     * }  $appearance
     */
    public static function cssVariablesBlock(array $appearance): string
    {
        $lines = [];
        foreach ($appearance['colors'] as $key => $value) {
            $cssVar = str_replace('_', '-', $key);
            $lines[] = "    --theme-{$cssVar}: {$value};";
            if (preg_match('/^#([A-Fa-f0-9]{6})$/', (string) $value, $matches)) {
                $r = hexdec(substr($matches[1], 0, 2));
                $g = hexdec(substr($matches[1], 2, 2));
                $b = hexdec(substr($matches[1], 4, 2));
                $lines[] = "    --theme-{$cssVar}-rgb: {$r}, {$g}, {$b};";
            }
        }

        return ":root {\n".implode("\n", $lines)."\n}";
    }

    public static function htmlColorModeClass(array $appearance): string
    {
        return ($appearance['color_mode'] ?? self::DEFAULT_COLOR_MODE) === 'dark' ? 'dark' : '';
    }
}

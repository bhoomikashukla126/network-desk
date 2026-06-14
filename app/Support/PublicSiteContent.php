<?php

namespace App\Support;

class PublicSiteContent
{
    /** @var list<string> */
    public const LOCALES = ['en', 'hi'];

    public const SESSION_KEY = 'public_locale';

    public static function locale(): string
    {
        $locale = session(self::SESSION_KEY, config('welcome.default_locale', 'en'));

        return in_array($locale, self::LOCALES, true) ? $locale : 'en';
    }

    public static function setLocale(string $locale): void
    {
        session([self::SESSION_KEY => in_array($locale, self::LOCALES, true) ? $locale : 'en']);
    }

    public static function applyLocale(): void
    {
        app()->setLocale(self::locale());
    }

    /**
     * @return array<string, mixed>
     */
    public static function welcome(): array
    {
        $locale = self::locale();
        $text = trans('welcome', [], $locale);
        $media = config('welcome', []);

        $gallery = collect($media['gallery'] ?? [])
            ->map(function (array $shot, int $index) use ($text) {
                $copy = $text['gallery'][$index] ?? [];

                return array_merge($shot, $copy);
            })
            ->all();

        $heroMedia = $media['hero'] ?? [];
        $previewMedia = $media['preview'] ?? [];

        return [
            'locale' => $locale,
            'icon' => $media['icon'] ?? '◆',
            'logo' => $media['logo'] ?? null,
            'central_url' => $media['central_url'] ?? null,
            'hero_image' => $media['hero_image'] ?? null,
            'accent' => $media['accent'] ?? [],
            'sign_in' => $text['sign_in'] ?? 'Sign in',
            'docs_label' => $text['docs_label'] ?? 'Documentation',
            'home_label' => $text['home_label'] ?? 'Home',
            'cta' => $text['cta'] ?? '',
            'central_label' => $text['central_label'] ?? '',
            'auth_note' => $text['auth_note'] ?? '',
            'footer' => $text['footer'] ?? '',
            'features_heading' => $text['features_heading'] ?? '',
            'features_subheading' => $text['features_subheading'] ?? '',
            'hero' => array_merge($heroMedia, $text['hero'] ?? []),
            'highlights' => $text['highlights'] ?? [],
            'preview' => array_merge($previewMedia, $text['preview'] ?? []),
            'gallery' => $gallery,
            'features' => $text['features'] ?? [],
            'nav' => $text['nav'] ?? [],
            'ui' => $text['ui'] ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function documentation(): array
    {
        return trans('documentation', [], self::locale());
    }
}

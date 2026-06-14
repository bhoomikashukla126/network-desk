<?php

namespace App\Support;

class WorkspaceLocale
{
    /**
     * @return array<string, string>
     */
    public static function supported(): array
    {
        return config('workspace.languages', [
            'en' => 'English',
            'hi' => 'Hindi',
        ]);
    }

    public static function resolve(?string $language): string
    {
        $locale = $language ?: 'en';

        return array_key_exists($locale, static::supported()) ? $locale : 'en';
    }

    public static function apply(?string $language): string
    {
        $locale = static::resolve($language);
        app()->setLocale($locale);

        return $locale;
    }
}

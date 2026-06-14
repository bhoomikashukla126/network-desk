<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class MediaStorage
{
    public static function diskName(): string
    {
        return (string) config('filesystems.media_disk', 'public');
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(self::diskName());
    }

    public static function url(string $path): string
    {
        return self::disk()->url($path);
    }

    public static function isManagedReference(string $reference): bool
    {
        $path = self::resolvePath($reference);

        if ($path === null) {
            return false;
        }

        return str_starts_with($path, 'network-desk/images/');
    }

    public static function deleteReference(string $reference): bool
    {
        $path = self::resolvePath($reference);

        if ($path === null || ! self::isManagedReference($reference)) {
            return false;
        }

        return self::disk()->delete($path);
    }

    public static function resolvePath(string $reference): ?string
    {
        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        if (! str_starts_with($reference, 'http') && ! str_starts_with($reference, '/')) {
            return $reference;
        }

        if (str_starts_with($reference, '/storage/')) {
            return ltrim(substr($reference, strlen('/storage/')), '/');
        }

        foreach (self::urlBases() as $base) {
            if (str_starts_with($reference, $base.'/')) {
                return substr($reference, strlen($base) + 1);
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected static function urlBases(): array
    {
        $bases = [
            rtrim((string) config('filesystems.disks.'.self::diskName().'.url', ''), '/'),
            rtrim((string) env('AWS_URL', ''), '/'),
            rtrim((string) config('app.url', ''), '/').'/storage',
        ];

        return array_values(array_unique(array_filter($bases)));
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListingImageUrl
{
    public static function url(?string $path, string $fallbackSeed = 'revebnb'): string
    {
        if ($path === null || $path === '') {
            return self::placeholder($fallbackSeed);
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    public static function placeholder(string $seed, int $width = 1200, int $height = 1400): string
    {
        return "https://picsum.photos/seed/{$seed}/{$width}/{$height}";
    }
}

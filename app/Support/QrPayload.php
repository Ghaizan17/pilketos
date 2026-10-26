<?php

namespace App\Support;

use Illuminate\Support\Str;

final class QrPayload
{
    /**
     * Normalize a raw QR decode into a candidate UUID string.
     *
     * Mirrors the ICT Scan::parseMuridUuid() pattern: trim; unwrap a
     * legacy PHP-serialized payload ("s:..."); drop a trailing ">..."
     * suffix segment (Pilketos ignores the remainder); the caller then
     * validates the result with Str::isUuid().
     */
    public static function normalize(mixed $value): string
    {
        $value = is_string($value) ? trim($value) : '';

        if (str_starts_with($value, 's:')) {
            $decoded = @unserialize($value, ['allowed_classes' => false]);
            if (is_string($decoded)) {
                $value = trim($decoded);
            }
        }

        if (str_contains($value, '>')) {
            $value = trim(explode('>', $value, 2)[0]);
        }

        return $value;
    }

    public static function isValid(mixed $value): bool
    {
        return Str::isUuid(static::normalize($value));
    }
}

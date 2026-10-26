<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    protected $fillable = [
        'name',
        'grade',
        'major',
    ];

    /**
     * Split a classroom name into [grade, major].
     * Grade is the first whitespace-separated token, major is the
     * remainder or null when there is only one token.
     *
     * @return array{0: string, 1: string|null}
     */
    public static function parseName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $grade = $parts[0] ?? '';
        $major = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : null;

        return [$grade, $major];
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}

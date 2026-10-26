<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    protected $fillable = [
        'number',
        'ketua_name',
        'wakil_name',
        'tagline',
        'photo_path',
    ];

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}

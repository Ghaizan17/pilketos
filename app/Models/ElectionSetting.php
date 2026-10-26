<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElectionSetting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function isOpen(): bool
    {
        $value = static::query()->where('key', 'is_open')->value('value');

        if ($value === null) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Student extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'nipd',
        'nisn',
        'jk',
        'classroom_id',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function vote(): HasOne
    {
        return $this->hasOne(Vote::class);
    }

    /**
     * Urutkan untuk cetak: per kelas dulu (X, XI, XII, lalu nama kelas),
     * di dalam kelas murid tetap urut nama. Grade diurut via posisi
     * string agar "XII" tidak muncul sebelum "XI" secara alfabetis.
     */
    public function scopeOrderedForPrinting(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->leftJoin('classrooms', 'classrooms.id', '=', 'students.classroom_id')
            ->orderByRaw("CASE classrooms.grade WHEN 'X' THEN 1 WHEN 'XI' THEN 2 WHEN 'XII' THEN 3 ELSE 4 END")
            ->orderBy('classrooms.name')
            ->orderBy('students.name')
            ->select(['students.*', 'classrooms.name as classroom_name', 'classrooms.grade as classroom_grade']);
    }
}

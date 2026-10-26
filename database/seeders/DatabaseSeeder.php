<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Classroom;
use App\Models\ElectionSetting;
use App\Models\Student;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // NOTE: no WithoutModelEvents here on purpose — Student::booted() must
    // run so demo students exercise the same auto-UUID path as real rows.

    /** Demo classroom names (also matched by pilketos:clear-demo). */
    public const DEMO_CLASSROOMS = [
        'X RPL A',
        'X TKJ A',
        'XI RPL A',
        'XI TKJ A',
        'XII RPL A',
        'XII TKJ A',
    ];

    /** Demo candidates keyed by ballot number (also matched by pilketos:clear-demo). */
    public const DEMO_CANDIDATES = [
        1 => ['ketua_name' => 'Budi Santoso', 'wakil_name' => 'Dewi Lestari', 'tagline' => 'Maju Bersama, OSIS Jaya'],
        2 => ['ketua_name' => 'Andi Pratama', 'wakil_name' => 'Siti Rahmah', 'tagline' => 'Suara Kita, Masa Depan Kita'],
        3 => ['ketua_name' => 'Rizky Ramadhan', 'wakil_name' => 'Putri Ayu', 'tagline' => 'Aktif, Kreatif, Berprestasi'],
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        ElectionSetting::updateOrCreate(
            ['key' => 'is_open'],
            ['value' => 'true'],
        );

        $classrooms = [];
        foreach (self::DEMO_CLASSROOMS as $name) {
            [$grade, $major] = Classroom::parseName($name);
            $classrooms[] = Classroom::updateOrCreate(
                ['name' => $name],
                ['grade' => $grade, 'major' => $major],
            );
        }

        foreach (self::DEMO_CANDIDATES as $number => $paslon) {
            Candidate::updateOrCreate(
                ['number' => $number],
                $paslon + ['photo_path' => null],
            );
        }

        // 30 demo students, 5 per class. `uuid` is intentionally NOT set:
        // Student::booted() generates it, same as production rows.
        $perClass = 5;
        for ($i = 1; $i <= 30; $i++) {
            $classroom = $classrooms[(int) (($i - 1) / $perClass)];

            Student::updateOrCreate(
                ['nipd' => sprintf('DEMO%03d', $i)],
                [
                    'name' => sprintf('[DEMO] Siswa %02d', $i),
                    'jk' => $i % 2 === 0 ? 'P' : 'L',
                    'classroom_id' => $classroom->id,
                ],
            );
        }
    }
}

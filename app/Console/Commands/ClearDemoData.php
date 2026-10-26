<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\Classroom;
use App\Models\Student;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;

class ClearDemoData extends Command
{
    protected $signature = 'pilketos:clear-demo';

    protected $description = 'Delete [DEMO] students and seeder-created demo classrooms/candidates. Never touches real (non-demo) rows.';

    public function handle(): int
    {
        // Demo students: votes cascade-delete with them (votes.student_id FK).
        $students = Student::query()->where('name', 'like', '[DEMO]%')->delete();
        $this->info("Deleted {$students} demo student(s).");

        // Demo candidates: only numbers 1-3 AND only when the names still
        // match the seeder (a renamed/repurposed candidate is real data).
        $candidates = 0;
        foreach (DatabaseSeeder::DEMO_CANDIDATES as $number => $demo) {
            $candidate = Candidate::query()->where('number', $number)->first();

            if (! $candidate) {
                continue;
            }

            if ($candidate->ketua_name !== $demo['ketua_name']
                || $candidate->wakil_name !== $demo['wakil_name']) {
                $this->warn("Kept candidate No. {$number}: names no longer match demo data.");

                continue;
            }

            try {
                $candidate->delete();
                $candidates++;
            } catch (QueryException) {
                $this->warn("Kept candidate No. {$number}: still referenced by votes.");
            }
        }
        $this->info("Deleted {$candidates} demo candidate(s).");

        // Demo classrooms: only the seeder names, and only when empty after
        // the student cleanup above (a classroom with students is real data).
        $classrooms = 0;
        foreach (DatabaseSeeder::DEMO_CLASSROOMS as $name) {
            $classroom = Classroom::query()->where('name', $name)->first();

            if (! $classroom) {
                continue;
            }

            if ($classroom->students()->exists()) {
                $this->warn("Kept classroom {$name}: still has students.");

                continue;
            }

            $classroom->delete();
            $classrooms++;
        }
        $this->info("Deleted {$classrooms} demo classroom(s).");

        return self::SUCCESS;
    }
}

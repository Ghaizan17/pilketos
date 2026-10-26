<?php

namespace App\Imports;

use App\Models\Classroom;
use App\Models\Student;
use App\Services\StudentImportRunner;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Import murid via maatwebsite/excel. Mendukung dua format (deteksi
 * otomatis, lihat StudentImportRunner):
 *
 * - File Dapodik "Daftar Peserta Didik" langsung tanpa diubah
 *   (header baris 5, data dari baris 7; hanya kolom Nama, NIPD, JK,
 *   NISN, dan Rombel Saat Ini yang dipakai).
 * - Template lama (header baris 1: nama | nipd | nisn | jk | kelas).
 *
 * `collection()` di bawah menangani format template. `importPath()`
 * menangani kedua format via runner (dipakai halaman import
 * ber-progress dan test).
 */
class StudentsImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected int $importedCount = 0;

    protected int $skippedCount = 0;

    /** @var array<int, array{baris: int, alasan: string}> */
    protected array $skipSamples = [];

    public function headingRow(): int
    {
        return 1;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function collection(Collection $collection): void
    {
        if ($collection->isEmpty()) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($collection as $row) {
            $row = $row instanceof Collection ? $row->toArray() : (array) $row;
            $row = array_change_key_case($row, CASE_LOWER);

            $nama = StudentImportRunner::stringValue($row['nama'] ?? null);
            $nipd = StudentImportRunner::numberString($row['nipd'] ?? null);

            if ($nama === null || $nipd === null) {
                $this->skippedCount++;

                continue;
            }

            $rows[] = [
                'uuid' => (string) Str::uuid(),
                'name' => $nama,
                'nipd' => $nipd,
                'nisn' => StudentImportRunner::numberString($row['nisn'] ?? null),
                'jk' => StudentImportRunner::normalizeJk($row['jk'] ?? null),
                'classroom_id' => $this->resolveClassroomId(
                    StudentImportRunner::stringValue($row['kelas'] ?? null)
                ),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows === []) {
            return;
        }

        // `uuid` sengaja tidak masuk kolom update: UUID lahir sekali dan
        // dipakai sebagai payload QR/route, jadi import tidak boleh menimpanya.
        Student::upsert($rows, ['nipd'], [
            'name',
            'nisn',
            'jk',
            'classroom_id',
            'updated_at',
        ]);

        $this->importedCount += count($rows);
    }

    /**
     * Import satu file (format Dapodik atau template) sampai tuntas
     * secara sinkron. Dipakai halaman import fallback dan test.
     */
    public function importPath(string $path): void
    {
        $state = StudentImportRunner::importAll($path);

        if ($state['status'] === 'error') {
            throw new \RuntimeException($state['message'] ?? 'Import gagal.');
        }

        $this->importedCount += $state['imported'];
        $this->skippedCount += $state['skipped'];
        $this->skipSamples = $state['skips'];
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    /** @return array<int, array{baris: int, alasan: string}> */
    public function getSkipSamples(): array
    {
        return $this->skipSamples;
    }

    protected function resolveClassroomId(?string $name): ?int
    {
        if ($name === null || $name === '') {
            return null;
        }

        $parts = explode(' ', $name, 2);

        $classroom = Classroom::firstOrCreate(
            ['name' => $name],
            [
                'grade' => $parts[0],
                'major' => $parts[1] ?? null,
            ]
        );

        return $classroom->id;
    }
}

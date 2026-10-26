<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * Mesin import murid dua format:
 *
 * - Dapodik ("Daftar Peserta Didik"): header di baris 5
 *   (No | Nama | NIPD | JK | NISN | ... | Rombel Saat Ini),
 *   sub-header grup di baris 6, data dari baris 7.
 *   Hanya kolom Nama, NIPD, JK, NISN, dan Rombel Saat Ini yang
 *   dipakai; 60+ kolom lain (NIK, alamat, data ortu, dst.)
 *   sengaja diabaikan.
 * - Template lama: header baris 1 (nama | nipd | nisn | jk | kelas),
 *   data dari baris 2.
 *
 * Format dideteksi otomatis dari isi header. Baris tanpa nama atau
 * NIPD di-skip dan dihitung (maks 10 contoh disimpan untuk laporan).
 * Upsert by `nipd`; `uuid` tidak pernah ditimpa (lahir sekali).
 *
 * Chunked + cache-backed agar halaman Filament bisa menampilkan
 * progress bar tanpa queue worker: `start()` sekali, lalu `tick()`
 * berulang (dipoll Livewire) sampai status `done`.
 *
 * Setiap baca file hanya memuat rentang sel yang dibutuhkan (read
 * filter) lalu segera dibebaskan, agar file Dapodik besar tidak
 * menghabiskan memori PHP.
 */
class StudentImportRunner
{
    public const CHUNK_SIZE = 200;

    public const CACHE_TTL = 3600;

    private const MAX_SKIP_SAMPLES = 10;

    /** Kolom Dapodik (index 0-based): B Nama, C NIPD, D JK, E NISN, AQ Rombel. */
    private const DAPODIK = [
        'headerRow' => 5,
        'dataStart' => 7,
        'nama' => 1,
        'nipd' => 2,
        'jk' => 3,
        'nisn' => 4,
        'kelas' => 42,
    ];

    /** Kolom template lama (index 0-based): nama | nipd | nisn | jk | kelas. */
    private const TEMPLATE = [
        'headerRow' => 1,
        'dataStart' => 2,
        'nama' => 0,
        'nipd' => 1,
        'nisn' => 2,
        'jk' => 3,
        'kelas' => 4,
    ];

    /**
     * Mulai run baru. Return ID run; state awal tersimpan di cache.
     *
     * @throws \InvalidArgumentException bila format file tidak dikenali.
     */
    public static function start(string $path, bool $cleanup = false): string
    {
        $mode = static::detectMode($path);
        $total = max(0, static::highestRow($path) - static::spec($mode)['dataStart'] + 1);

        $runId = (string) Str::uuid();

        Cache::put(static::key($runId), [
            'path' => $path,
            'mode' => $mode,
            'total' => $total,
            'processed' => 0,
            'imported' => 0,
            'skipped' => 0,
            'skips' => [],
            'classrooms' => [],
            'cleanup' => $cleanup,
            'status' => 'processing',
            'message' => null,
        ], static::CACHE_TTL);

        return $runId;
    }

    /**
     * Proses satu chunk berikutnya. Idempoten bila run sudah done.
     */
    public static function tick(string $runId, int $chunkSize = self::CHUNK_SIZE): array
    {
        $state = static::state($runId);

        if ($state['status'] !== 'processing') {
            return $state;
        }

        $spec = static::spec($state['mode']);
        $from = $spec['dataStart'] + $state['processed'];
        $to = min($from + $chunkSize - 1, $spec['dataStart'] + $state['total'] - 1);

        try {
            $rows = static::readRows($state['path'], $from, $to, $state['mode']);
        } catch (\Throwable $e) {
            $state['status'] = 'error';
            $state['message'] = 'Gagal membaca file: '.$e->getMessage();
            static::cleanupFile($state);
            Cache::put(static::key($runId), $state, static::CACHE_TTL);

            return $state;
        }

        $now = now();
        $upserts = [];

        foreach ($rows as $offset => $row) {
            $line = $from + $offset;

            $nama = static::stringValue($row[$spec['nama']] ?? null);
            $nipd = static::numberString($row[$spec['nipd']] ?? null);

            if ($nama === null || $nipd === null) {
                $state['skipped']++;
                static::pushSkip($state, $line, $nama === null ? 'nama kosong' : 'NIPD kosong');

                continue;
            }

            $upserts[] = [
                'uuid' => (string) Str::uuid(),
                'name' => $nama,
                'nipd' => $nipd,
                'nisn' => static::numberString($row[$spec['nisn']] ?? null),
                'jk' => static::normalizeJk($row[$spec['jk']] ?? null),
                'classroom_id' => static::resolveClassroomId($state, static::stringValue($row[$spec['kelas']] ?? null)),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($upserts !== []) {
            // `uuid` sengaja tidak masuk kolom update: UUID lahir sekali dan
            // dipakai sebagai payload QR/route, jadi import tidak boleh menimpanya.
            Student::upsert($upserts, ['nipd'], [
                'name',
                'nisn',
                'jk',
                'classroom_id',
                'updated_at',
            ]);
            $state['imported'] += count($upserts);
        }

        $state['processed'] += max(0, $to - $from + 1);

        if ($state['processed'] >= $state['total']) {
            $state['status'] = 'done';
            static::cleanupFile($state);
        }

        Cache::put(static::key($runId), $state, static::CACHE_TTL);

        return $state;
    }

    /**
     * Jalankan import sampai tuntas dalam satu proses (untuk test dan
     * file kecil). Return state akhir.
     */
    public static function importAll(string $path, int $chunkSize = self::CHUNK_SIZE): array
    {
        $runId = static::start($path);

        do {
            $state = static::tick($runId, $chunkSize);
        } while ($state['status'] === 'processing');

        return $state;
    }

    public static function state(string $runId): array
    {
        return Cache::get(static::key($runId), [
            'status' => 'error',
            'message' => 'Run import tidak ditemukan (mungkin kedaluwarsa).',
            'total' => 0,
            'processed' => 0,
            'imported' => 0,
            'skipped' => 0,
            'skips' => [],
        ]);
    }

    public static function forget(string $runId): void
    {
        Cache::forget(static::key($runId));
    }

    protected static function key(string $runId): string
    {
        return 'student-import:'.$runId;
    }

    /** @return 'dapodik'|'template' */
    protected static function detectMode(string $path): string
    {
        // Hanya baca 5 baris x 5 kolom pertama (read filter) agar file
        // Dapodik besar tidak ikut termuat ke memori.
        $rows = static::loadRange($path, 1, 5, 'A', 'E');

        // Dapodik: baris 5 = No | Nama | NIPD | JK | NISN | ...
        $header5 = $rows[4] ?? [];
        $b5 = mb_strtolower(trim((string) ($header5[1] ?? '')));
        $c5 = mb_strtolower(trim((string) ($header5[2] ?? '')));

        if ($b5 === 'nama' && $c5 === 'nipd') {
            return 'dapodik';
        }

        // Template lama: baris 1 = nama | nipd | nisn | jk | kelas.
        $header1 = $rows[0] ?? [];
        $keys = array_map(fn ($v): string => mb_strtolower(trim((string) $v)), $header1);

        if (($keys[0] ?? '') === 'nama' && ($keys[1] ?? '') === 'nipd') {
            return 'template';
        }

        throw new \InvalidArgumentException(
            'Format file tidak dikenali. Gunakan file Dapodik (Daftar Peserta Didik) '.
            'langsung tanpa diubah, atau file template (header: nama | nipd | nisn | jk | kelas).'
        );
    }

    protected static function highestRow(string $path): int
    {
        // Hitung via metadata streaming (tanpa memuat sel ke memori).
        $reader = IOFactory::createReaderForFile($path);
        $info = $reader->listWorksheetInfo($path);

        return (int) ($info[0]['totalRows'] ?? 0);
    }

    /** @return array<int, array<int, mixed>> */
    protected static function readRows(string $path, int $from, int $to, string $mode): array
    {
        if ($from > $to) {
            return [];
        }

        $lastCol = $mode === 'dapodik' ? 'AQ' : 'E';

        return static::loadRange($path, $from, $to, 'A', $lastCol);
    }

    /**
     * Muat satu rentang sel saja (read filter), lalu bebaskan memori
     * spreadsheet segera setelah dibaca.
     *
     * @return array<int, array<int, mixed>>
     */
    protected static function loadRange(string $path, int $fromRow, int $toRow, string $fromCol, string $toCol): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadFilter(static::readFilter(
            $fromRow,
            $toRow,
            Coordinate::columnIndexFromString($fromCol),
            Coordinate::columnIndexFromString($toCol),
        ));

        $spreadsheet = $reader->load($path);

        try {
            /** @var array<int, array<int, mixed>> $rows */
            $rows = $spreadsheet->getActiveSheet()->rangeToArray(
                "{$fromCol}{$fromRow}:{$toCol}{$toRow}", null, true, true, false
            );
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }

        return array_values($rows);
    }

    protected static function readFilter(int $fromRow, int $toRow, int $fromCol, int $toCol): IReadFilter
    {
        return new class($fromRow, $toRow, $fromCol, $toCol) implements IReadFilter
        {
            public function __construct(
                private int $fromRow,
                private int $toRow,
                private int $fromCol,
                private int $toCol,
            ) {}

            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                if ($row < $this->fromRow || $row > $this->toRow) {
                    return false;
                }

                $col = Coordinate::columnIndexFromString($columnAddress);

                return $col >= $this->fromCol && $col <= $this->toCol;
            }
        };
    }

    protected static function cleanupFile(array $state): void
    {
        if (($state['cleanup'] ?? false) && isset($state['path']) && is_string($state['path']) && is_file($state['path'])) {
            @unlink($state['path']);
        }
    }

    /** @return array<string, mixed> */
    protected static function spec(string $mode): array
    {
        return $mode === 'dapodik' ? static::DAPODIK : static::TEMPLATE;
    }

    protected static function resolveClassroomId(array &$state, ?string $name): ?int
    {
        if ($name === null || $name === '') {
            return null;
        }

        if (isset($state['classrooms'][$name])) {
            return $state['classrooms'][$name];
        }

        $parts = explode(' ', $name, 2);

        $classroom = Classroom::firstOrCreate(
            ['name' => $name],
            [
                'grade' => $parts[0],
                'major' => $parts[1] ?? null,
            ]
        );

        return $state['classrooms'][$name] = $classroom->id;
    }

    protected static function pushSkip(array &$state, int $line, string $reason): void
    {
        if (count($state['skips']) < static::MAX_SKIP_SAMPLES) {
            $state['skips'][] = ['baris' => $line, 'alasan' => $reason];
        }
    }

    public static function normalizeJk(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strtoupper(trim((string) $value));
        $value = (string) preg_replace('/[\.\-_]+/', ' ', $value);
        $value = (string) preg_replace('/\s+/', ' ', $value);

        if (in_array($value, ['L', 'LAKI', 'LAKI LAKI'], true)) {
            return 'L';
        }

        if (in_array($value, ['P', 'PEREMPUAN'], true)) {
            return 'P';
        }

        return null;
    }

    public static function stringValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public static function numberString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            // Angka asli Excel (mis. NIPD 11987) → string bulat.
            $value = sprintf('%.0f', $value);
        } else {
            // String dibiarkan apa adanya agar nol depan awet
            // (mis. NISN Dapodik '0091298678').
            $value = trim((string) $value);

            if (preg_match('/^(\d+)\.0+$/', $value, $m)) {
                $value = $m[1];
            }
        }

        return $value === '' ? null : $value;
    }
}

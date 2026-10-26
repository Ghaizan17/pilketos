<?php

namespace Tests\Feature;

use App\Imports\StudentsImport;
use App\Models\Student;
use App\Services\StudentImportRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private string $stubPath = '';

    protected function tearDown(): void
    {
        if ($this->stubPath !== '') {
            @unlink($this->stubPath);
            @unlink(substr($this->stubPath, 0, -5)); // empty tempnam stub (path + '.xlsx')
        }

        parent::tearDown();
    }

    private function fixtureTemplate(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'students').'.xlsx';
        $this->stubPath = $path;

        $sheet = (new Spreadsheet)->getActiveSheet();
        $sheet->fromArray([
            ['nama', 'nipd', 'nisn', 'jk', 'kelas'],
            ['Siswa A', '1001', '111', 'L', 'XII RPL A'],
            ['Siswa B', '1002', '222', 'P', 'XII RPL A'],
            ['', '1003', '333', 'L', 'XII RPL A'], // invalid: nama kosong
        ], null, 'A1');

        (new Xlsx($sheet->getParent()))->save($path);

        return $path;
    }

    /**
     * Fixture mini format Dapodik: 4 baris metadata, header di baris 5,
     * sub-header grup di baris 6, data dari baris 7. Kolom lain
     * (Tempat Lahir, NIK, ...) ikut ditulis agar mirip file asli —
     * semuanya harus diabaikan.
     */
    private function fixtureDapodik(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'dapodik').'.xlsx';
        $this->stubPath = $path;

        $sheet = (new Spreadsheet)->getActiveSheet();
        $sheet->fromArray([
            ['Daftar Peserta Didik'],
            ['SMKN 2 BANJARMASIN'],
            ['Kecamatan Kec. Banjarmasin Utara'],
            ['Tanggal Unduh: 2026-08-28 08:42:37'],
            ['No', 'Nama', 'NIPD', 'JK', 'NISN', 'Tempat Lahir', 'Rombel Saat Ini'],
            [null, null, null, null, null, null, null],
            [1, 'Ahmad Yani', 11987, 'L', '0091298678', 'Banjarmasin', 'XII DKV B'],
            [2, 'Siti Aminah', 11988, 'P', '0091298679', 'Banjarbaru', 'XII DKV B'],
            [3, '', 11989, 'L', '0091298680', 'Martapura', 'XII DKV B'], // invalid: nama kosong
            [4, 'Budi Santoso', 11987, 'L', '0091298681', 'Banjarmasin', 'XII DKV A'], // duplikat NIPD → upsert
        ], null, 'A1');

        // Pindahkan Rombel ke kolom AQ (index 42) seperti file Dapodik asli.
        foreach ([7, 8, 9, 10] as $row) {
            $sheet->setCellValue("AQ{$row}", $sheet->getCell("G{$row}")->getValue());
            $sheet->setCellValue("G{$row}", null);
        }

        (new Xlsx($sheet->getParent()))->save($path);

        return $path;
    }

    public function test_import_upserts_without_changing_uuid(): void
    {
        $path = $this->fixtureTemplate();

        $import = new StudentsImport;
        Excel::import($import, $path);

        $this->assertSame(2, $import->getImportedCount());
        $this->assertSame(1, $import->getSkippedCount());
        $this->assertSame(2, Student::count());
        $this->assertTrue(Student::where('nipd', '1001')->exists());
        $this->assertTrue(Student::where('nipd', '1002')->exists());

        $uuidA = Student::where('nipd', '1001')->value('uuid');
        $uuidB = Student::where('nipd', '1002')->value('uuid');
        $this->assertNotEmpty($uuidA);
        $this->assertNotEmpty($uuidB);

        // Re-import file yang sama: count tetap, UUID lama tidak berubah.
        $reimport = new StudentsImport;
        Excel::import($reimport, $path);

        $this->assertSame(2, Student::count());
        $this->assertSame($uuidA, Student::where('nipd', '1001')->value('uuid'));
        $this->assertSame($uuidB, Student::where('nipd', '1002')->value('uuid'));
    }

    public function test_import_dapodik_uses_only_needed_columns(): void
    {
        $state = StudentImportRunner::importAll($this->fixtureDapodik());

        $this->assertSame('done', $state['status']);
        // Baris 7, 8 valid + baris 10 upsert NIPD 11987 → 2 murid unik.
        $this->assertSame(3, $state['imported']);
        $this->assertSame(1, $state['skipped']);
        $this->assertSame(2, Student::count());

        // Nol depan NISN awet (kolom string Dapodik tidak dipaksa float).
        $this->assertSame('0091298681', Student::where('nipd', '11987')->value('nisn'));
        $this->assertSame('0091298679', Student::where('nipd', '11988')->value('nisn'));

        // Upsert: nama terakhir menang, UUID pertama tidak berubah.
        $this->assertSame('Budi Santoso', Student::where('nipd', '11987')->value('name'));

        // Rombel dari kolom AQ masuk sebagai kelas.
        $this->assertSame('XII DKV A', Student::where('nipd', '11987')->first()->classroom->name);
        $this->assertSame('XII DKV B', Student::where('nipd', '11988')->first()->classroom->name);

        // Re-import: tidak ada duplikat, UUID stabil.
        $uuid = Student::where('nipd', '11988')->value('uuid');
        $again = StudentImportRunner::importAll($this->fixtureDapodik());
        $this->assertSame(2, Student::count());
        $this->assertSame($uuid, Student::where('nipd', '11988')->value('uuid'));
        $this->assertSame(3, $again['imported']);
    }

    public function test_import_rejects_unknown_format(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'bad').'.xlsx';
        $this->stubPath = $path;

        $sheet = (new Spreadsheet)->getActiveSheet();
        $sheet->fromArray([['foo', 'bar'], ['a', 'b']], null, 'A1');
        (new Xlsx($sheet->getParent()))->save($path);

        $this->expectException(\InvalidArgumentException::class);
        StudentImportRunner::importAll($path);
    }

    public function test_import_tick_processes_incrementally(): void
    {
        $runId = StudentImportRunner::start($this->fixtureDapodik());
        $first = StudentImportRunner::tick($runId, 1);

        $this->assertSame('processing', $first['status']);
        $this->assertSame(1, $first['processed']);

        $final = StudentImportRunner::importAll($this->fixtureDapodik(), 100);
        $this->assertSame('done', $final['status']);
        $this->assertSame($final['total'], $final['processed']);

        StudentImportRunner::forget($runId);
    }
}

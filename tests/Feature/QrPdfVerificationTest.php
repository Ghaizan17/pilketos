<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrPdfVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_pdf_download_by_classroom(): void
    {
        $this->actingAs(User::factory()->create());

        $classroom = Classroom::create(['name' => 'X RPL A', 'grade' => 'X', 'major' => 'RPL A']);
        Student::create(['name' => 'Siswa A', 'nipd' => 'PDF001', 'classroom_id' => $classroom->id]);
        Student::create(['name' => 'Siswa B', 'nipd' => 'PDF002', 'classroom_id' => $classroom->id]);

        $response = $this->get(route('students.qr-cards.pdf', [
            'scope' => 'classroom',
            'classroom_id' => $classroom->id,
        ]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertGreaterThan(1000, strlen($response->getContent()));
        // PDF binary harus diawali %PDF
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }


    public function test_qr_pdf_guest_cannot_download(): void
    {
        // Guest diarahkan ke login; di scaffold ini (tanpa route login bawaan)
        // menghasilkan error, bukan PDF — pastikan bukan PDF yang bocor.
        $response = $this->get(route('students.qr-cards.pdf', ['scope' => 'all']));

        $this->assertStringStartsWith('<!DOCTYPE html', $response->getContent());
    }
}

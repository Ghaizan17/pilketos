<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentQrCardController extends Controller
{
    public function show(Student $student): View
    {
        $student->loadMissing('classroom');

        return view('students.qr-card', ['student' => $student]);
    }

    public function bulk(Request $request): View
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn (string $id): int => (int) trim($id))
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->take(200)
            ->all();

        $students = Student::query()
            ->whereIn('id', $ids)
            ->with('classroom')
            ->orderBy('name')
            ->get();

        return view('students.qr-cards', ['students' => $students]);
    }

    /**
     * Unduh PDF kartu QR (kecil, 10 kartu per halaman A4).
     * Filter: scope=all | scope=classroom & classroom_id=..
     */
    public function pdf(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $students = $this->filteredStudents($request);

        // Cache SVG per murid (UUID tidak pernah berubah) agar unduhan
        // berikutnya jauh lebih cepat.
        $qrUris = [];

        foreach ($students as $student) {
            $qrUris[$student->id] = $this->qrDataUri($student);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('students.qr-cards-pdf', [
            'students' => $students,
            'scopeLabel' => $this->scopeLabel($request),
            'qrUris' => $qrUris,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('kartu-qr-'.$this->scopeSlug($request).'.pdf');
    }

    /**
     * Unduh file PDF hasil generate bertahap (lihat PrintQrCards).
     */
    public function file(string $job): \Symfony\Component\HttpFoundation\Response
    {
        if (! preg_match('/^[0-9a-f-]{36}$/', $job)) {
            abort(404);
        }

        $state = \Illuminate\Support\Facades\Cache::get("qr-pdf-job:{$job}");

        if (! is_array($state) || ($state['status'] ?? null) !== 'done' || empty($state['file'])) {
            abort(404, 'File belum siap atau sudah kedaluwarsa. Buat ulang PDF.');
        }

        if (! \Illuminate\Support\Facades\Storage::disk('local')->exists("qr-pdf/{$state['file']}")) {
            abort(404, 'File tidak ditemukan. Buat ulang PDF.');
        }

        return \Illuminate\Support\Facades\Storage::disk('local')->download(
            "qr-pdf/{$state['file']}",
            'kartu-qr-'.\Illuminate\Support\Str::slug($state['label'] ?? 'murid').'.pdf'
        );
    }

    protected function qrDataUri(\App\Models\Student $student): string
    {
        $key = "qr-svg/{$student->id}.svg";

        if (! \Illuminate\Support\Facades\Storage::disk('local')->exists($key)) {
            $svg = preg_replace(
                '/<\?xml[^?]*\?>\s*/',
                '',
                (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)->margin(0)->generate($student->uuid)
            );
            \Illuminate\Support\Facades\Storage::disk('local')->put($key, $svg);
        }

        return 'data:image/svg+xml;base64,'.base64_encode((string) \Illuminate\Support\Facades\Storage::disk('local')->get($key));
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Student> */
    public function filteredStudents(Request $request): \Illuminate\Database\Eloquent\Collection
    {
        $query = \App\Models\Student::query()->with('classroom')->orderBy('name');

        $scope = (string) $request->query('scope', 'all');

        if ($scope === 'classroom' && $request->filled('classroom_id')) {
            $query->where('classroom_id', $request->query('classroom_id'));
        }

        return $query->get();
    }

    protected function scopeLabel(Request $request): string
    {
        $scope = (string) $request->query('scope', 'all');

        if ($scope === 'classroom' && $request->filled('classroom_id')) {
            $name = \App\Models\Classroom::find($request->query('classroom_id'))?->name;

            return $name ? "Kelas {$name}" : 'Semua murid';
        }

        return 'Semua murid';
    }

    protected function scopeSlug(Request $request): string
    {
        $scope = (string) $request->query('scope', 'all');

        if ($scope === 'classroom' && $request->filled('classroom_id')) {
            $name = \App\Models\Classroom::find($request->query('classroom_id'))?->name ?? 'kelas';

            return \Illuminate\Support\Str::slug("kelas-{$name}");
        }

        return 'semua';
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentQrCardController extends Controller
{
    public function singlePdf(Student $student): \Symfony\Component\HttpFoundation\Response
    {
        $student->loadMissing('classroom');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('students.qr-card-pdf', [
            'student' => $student,
            'qrUri' => $this->qrDataUri($student),
        ])->setPaper('a4', 'portrait')->output();

        $slug = \Illuminate\Support\Str::slug($student->nipd ?: $student->name);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="kartu-qr-'.$slug.'.pdf"',
        ]);
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

        $scopeLabel = $this->scopeLabel($request);

        // Render bertahap per 100 murid lalu gabung murni-PHP (FPDI),
        // agar "semua murid" (ribuan kartu) tidak meledak di memori.
        $ids = $this->filteredStudentsQuery($request)->pluck('students.id')->all();

        if ($ids === []) {
            abort(404, 'Tidak ada murid pada cakupan ini.');
        }

        $merger = new \setasign\Fpdi\Fpdi();

        foreach (array_chunk($ids, 100) as $chunk) {
            // Ambil per ID sesuai urutan $ids (sudah urut kelas) — JANGAN
            // orderBy('name') karena akan mengacak urutan kelas tiap batch.
            $students = Student::query()
                ->whereIn('id', $chunk)
                ->with('classroom')
                ->get()
                ->sortBy(fn (Student $student): int => array_search($student->id, $chunk, true))
                ->values();

            $qrUris = [];

            foreach ($students as $student) {
                $qrUris[$student->id] = $this->qrDataUri($student);
            }

            $part = \Barryvdh\DomPDF\Facade\Pdf::loadView('students.qr-cards-pdf', [
                'students' => $students,
                'scopeLabel' => $scopeLabel,
                'qrUris' => $qrUris,
            ])->setPaper('a4', 'portrait')->output();

            $tmp = tempnam(sys_get_temp_dir(), 'qr-part-').'.pdf';
            file_put_contents($tmp, $part);
            unset($part);

            $count = $merger->setSourceFile($tmp);

            for ($page = 1; $page <= $count; $page++) {
                $tpl = $merger->importPage($page);
                $size = $merger->getTemplateSize($tpl);
                $merger->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $merger->useTemplate($tpl);
            }

            @unlink($tmp);
            unset($students, $qrUris);
        }

        return response($merger->Output('S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="kartu-qr-'.$this->scopeSlug($request).'.pdf"',
        ]);
    }

    /** Query murid sesuai filter — dipakai hitung/pluck tanpa memuat model. */
    protected function filteredStudentsQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = Student::query()->orderedForPrinting();

        $scope = (string) $request->query('scope', 'all');

        if ($scope === 'classroom' && $request->filled('classroom_id')) {
            $query->where('classroom_id', $request->query('classroom_id'));
        }

        return $query;
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

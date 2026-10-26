<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\ElectionSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BoothController extends Controller
{
    public function home(): View
    {
        return view('booth.home', [
            'isOpen' => ElectionSetting::isOpen(),
            'candidates' => Candidate::query()->orderBy('number')->get(),
        ]);
    }

    public function index(): View
    {
        return view('booth.scan', [
            'isOpen' => ElectionSetting::isOpen(),
            'candidatesPayload' => $this->candidatesPayload(),
        ]);
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    protected function candidatesPayload(): \Illuminate\Support\Collection
    {
        return Candidate::query()->orderBy('number')->get()->map(fn (Candidate $candidate) => [
            'id' => $candidate->id,
            'number' => $candidate->number,
            'ketua' => $candidate->ketua_name,
            'wakil' => $candidate->wakil_name,
            'tagline' => $candidate->tagline,
            'photo' => $candidate->photo_path ? Storage::url($candidate->photo_path) : null,
        ])->values();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\ElectionSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BoothController extends Controller
{
    public function index(): View
    {
        $candidates = Candidate::query()->orderBy('number')->get()->map(fn (Candidate $candidate) => [
            'id' => $candidate->id,
            'number' => $candidate->number,
            'ketua' => $candidate->ketua_name,
            'wakil' => $candidate->wakil_name,
            'tagline' => $candidate->tagline,
            'photo' => $candidate->photo_path ? Storage::url($candidate->photo_path) : null,
        ])->values();

        return view('booth.index', [
            'isOpen' => ElectionSetting::isOpen(),
            'candidatesPayload' => $candidates,
        ]);
    }
}

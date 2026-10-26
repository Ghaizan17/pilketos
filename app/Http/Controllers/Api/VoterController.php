<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ElectionSetting;
use App\Models\Student;
use App\Support\QrPayload;
use Illuminate\Http\JsonResponse;

class VoterController extends Controller
{
    public function show(string $uuid): JsonResponse
    {
        if (! ElectionSetting::isOpen()) {
            return response()->json(['closed' => true], 403);
        }

        $uuid = QrPayload::normalize($uuid);

        $student = Student::query()
            ->with('classroom')
            ->where('uuid', $uuid)
            ->first();

        if (! $student) {
            return response()->json(['found' => false], 404);
        }

        return response()->json([
            'found' => true,
            'already_voted' => $student->vote()->exists(),
            'student' => [
                'uuid' => $student->uuid,
                'name' => $student->name,
                'classroom' => $student->classroom?->name,
            ],
        ]);
    }
}

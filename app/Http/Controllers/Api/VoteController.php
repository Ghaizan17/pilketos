<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\ElectionSetting;
use App\Models\Student;
use App\Models\Vote;
use App\Support\QrPayload;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoteController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (! ElectionSetting::isOpen()) {
            return response()->json(['closed' => true], 403);
        }

        // student_uuid stays `string` (not `uuid|exists`): any unknown or
        // non-uuid payload must answer 404 per the booth contract, not 422.
        // Legacy payloads (PHP-serialized `s:...`, trailing `>...` suffix)
        // normalize to the canonical raw uuid below.
        $data = $request->validate([
            'student_uuid' => ['required', 'string'],
            'candidate_id' => ['required', 'integer', 'exists:candidates,id'],
        ]);

        $uuid = QrPayload::normalize($data['student_uuid']);

        if (! QrPayload::isValid($uuid)) {
            return response()->json(['found' => false], 404);
        }

        $student = Student::query()->where('uuid', $uuid)->first();

        if (! $student) {
            return response()->json(['found' => false], 404);
        }

        $candidate = Candidate::query()->findOrFail($data['candidate_id']);

        try {
            $duplicate = DB::transaction(function () use ($student, $candidate, $request) {
                $locked = Student::query()
                    ->whereKey($student->id)
                    ->lockForUpdate()
                    ->first();

                if ($locked->vote()->exists()) {
                    return true;
                }

                Vote::query()->create([
                    'student_id' => $locked->id,
                    'candidate_id' => $candidate->id,
                    'voted_at' => now(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return false;
            });
        } catch (QueryException $e) {
            // Unique(student_id) race: two booths, one ballot. Any other
            // DB error is a real 500, not a duplicate vote.
            if (Vote::query()->where('student_id', $student->id)->exists()) {
                return response()->json(['status' => 'already_voted'], 409);
            }

            throw $e;
        }

        if ($duplicate) {
            return response()->json(['status' => 'already_voted'], 409);
        }

        return response()->json([
            'status' => 'counted',
            'candidate_number' => $candidate->number,
        ], 201);
    }
}

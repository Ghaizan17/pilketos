<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Classroom;
use App\Models\ElectionSetting;
use App\Models\Student;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ElectionSetting::create(['key' => 'is_open', 'value' => 'true']);
    }

    /**
     * @return array{0: Student, 1: Candidate}
     */
    private function fixtures(): array
    {
        $classroom = Classroom::create(['name' => 'XII RPL A', 'grade' => 'XII', 'major' => 'RPL A']);
        $candidate = Candidate::create([
            'number' => 1,
            'ketua_name' => 'Ketua A',
            'wakil_name' => 'Wakil A',
        ]);
        $student = Student::create([
            'name' => 'Voter Satu',
            'nipd' => '111',
            'classroom_id' => $classroom->id,
        ]);

        return [$student, $candidate];
    }

    public function test_vote_success_returns_201_and_counts_once(): void
    {
        [$student, $candidate] = $this->fixtures();

        $this->getJson('/api/voters/'.$student->uuid)
            ->assertOk()
            ->assertJson(['found' => true, 'already_voted' => false]);

        $this->postJson('/api/votes', [
            'student_uuid' => $student->uuid,
            'candidate_id' => $candidate->id,
        ])
            ->assertStatus(201)
            ->assertJson(['status' => 'counted', 'candidate_number' => 1]);

        $this->assertSame(1, Vote::count());
    }

    public function test_second_vote_same_uuid_returns_409_and_count_stays_1(): void
    {
        [$student, $candidate] = $this->fixtures();

        $this->postJson('/api/votes', [
            'student_uuid' => $student->uuid,
            'candidate_id' => $candidate->id,
        ])->assertStatus(201);

        $this->postJson('/api/votes', [
            'student_uuid' => $student->uuid,
            'candidate_id' => $candidate->id,
        ])
            ->assertStatus(409)
            ->assertJson(['status' => 'already_voted']);

        $this->assertSame(1, Vote::count());
    }

    public function test_unknown_uuid_returns_404(): void
    {
        [, $candidate] = $this->fixtures();

        $this->getJson('/api/voters/'.(string) Str::uuid())
            ->assertNotFound()
            ->assertJson(['found' => false]);

        $this->postJson('/api/votes', [
            'student_uuid' => (string) Str::uuid(),
            'candidate_id' => $candidate->id,
        ])->assertNotFound();

        $this->assertSame(0, Vote::count());
    }

    public function test_closed_election_returns_403_on_both_endpoints(): void
    {
        [$student, $candidate] = $this->fixtures();

        ElectionSetting::where('key', 'is_open')->update(['value' => 'false']);

        $this->getJson('/api/voters/'.$student->uuid)
            ->assertStatus(403)
            ->assertJson(['closed' => true]);

        $this->postJson('/api/votes', [
            'student_uuid' => $student->uuid,
            'candidate_id' => $candidate->id,
        ])
            ->assertStatus(403)
            ->assertJson(['closed' => true]);

        $this->assertSame(0, Vote::count());
    }
}

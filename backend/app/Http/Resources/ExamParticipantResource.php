<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_session_id' => $this->exam_session_id,
            'student_id' => $this->student_id,
            'participant_order' => $this->participant_order,
            'nomor_peserta' => $this->when($this->participant_order !== null && $this->relationLoaded('examSession'), function () {
                $session = $this->examSession;
                $institution = $session->exam->institution ?? null;
                if (! $institution) {
                    return null;
                }
                $date = $session->scheduled_start_at ?? $session->started_at ?? now();

                return $institution->buildNomorPeserta((int) $this->participant_order, $date);
            }),
            'login_token' => $this->when($request->user()?->isAdminOrSuperAdmin() || $request->user()?->isInstitutionAdmin(), $this->login_token),
            'started_at' => $this->started_at?->toIso8601String(),
            'extra_seconds' => (int) ($this->extra_seconds ?? 0),
            'attempt_number' => (int) ($this->attempt_number ?? 1),
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'presence' => $this->status === \App\Models\ExamParticipant::STATUS_STARTED
                ? ($this->last_seen_at && $this->last_seen_at->greaterThan(now()->subSeconds(50)) ? 'online' : 'away')
                : null,
            'seconds_remaining' => $this->when(
                $this->status === \App\Models\ExamParticipant::STATUS_STARTED && $this->relationLoaded('examSession'),
                fn () => app(\App\Services\ExamService::class)->secondsRemaining($this->resource)
            ),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'score' => $this->when($this->score_released || $request->user()?->isAdminOrSuperAdmin() || $request->user()?->isInstitutionAdmin() || $request->user()?->isTeacher(), $this->score !== null ? (float) $this->score : null),
            'score_max' => $this->when($this->score_released || $request->user()?->isAdminOrSuperAdmin() || $request->user()?->isInstitutionAdmin() || $request->user()?->isTeacher(), $this->score_max !== null ? (float) $this->score_max : null),
            'score_released' => (bool) $this->score_released,
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
            'student' => $this->whenLoaded('student', function () {
                if (!$this->student) {
                    return null;
                }
                $class = null;
                if ($this->student->relationLoaded('class')) {
                    $related = $this->student->getRelation('class');
                    if ($related instanceof \App\Models\SchoolClass) {
                        $class = [
                            'id' => (int) $related->id,
                            'name' => $related->name,
                            'grade' => $related->grade !== null ? (int) $related->grade : null,
                        ];
                    }
                }

                return [
                    'id' => $this->student->id,
                    'name' => $this->student->name,
                    'nis' => $this->student->nis,
                    'nisn' => $this->student->nisn,
                    'email' => $this->student->email,
                    'class' => $class,
                ];
            }),
        ];
    }
}

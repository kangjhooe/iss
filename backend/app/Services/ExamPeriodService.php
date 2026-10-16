<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\ExamPeriod;
use App\Models\ExamSession;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ExamPeriodService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function listForInstitution(int $institutionId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = ExamPeriod::forInstitution($institutionId)
            ->withCount('exams')
            ->withCount(['exams as exams_ready_count' => function ($q) {
                $q->whereHas('examQuestions');
            }])
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $institutionId, array $data, int $userId): ExamPeriod
    {
        $data['institution_id'] = $institutionId;
        $data['created_by'] = $userId;
        $data['status'] = $data['status'] ?? ExamPeriod::STATUS_DRAFT;

        return ExamPeriod::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ExamPeriod $period, array $data): ExamPeriod
    {
        $apply = (bool) ($data['apply_schedule_to_exams'] ?? true);
        unset($data['apply_schedule_to_exams']);

        $period->update($data);

        if ($apply && (array_key_exists('window_start_at', $data) || array_key_exists('window_end_at', $data))) {
            $this->applySchedule($period->fresh());
        }

        return $period->fresh();
    }

    public function delete(ExamPeriod $period): void
    {
        $locked = Exam::query()
            ->where('exam_period_id', $period->id)
            ->whereHas('sessions', fn ($q) => $q->whereIn('status', ['started', 'ended']))
            ->exists();
        if ($locked) {
            throw new InvalidArgumentException('Tidak bisa dihapus: ada ujian mapel yang sesinya sudah berjalan/selesai.');
        }

        $period->delete();
    }

    /**
     * Tambah ujian mapel ke gelombang: 1 exam + 1 sesi utama per mapel.
     *
     * @param  list<int>  $subjectIds
     * @return list<Exam>
     */
    public function addSubjects(ExamPeriod $period, array $subjectIds, int $userId, int $durationMinutes = 90): array
    {
        $subjectIds = array_values(array_unique(array_map('intval', $subjectIds)));
        $subjects = Subject::query()
            ->where('institution_id', $period->institution_id)
            ->whereIn('id', $subjectIds)
            ->get()
            ->keyBy('id');

        $created = [];
        DB::transaction(function () use ($period, $subjectIds, $subjects, $userId, $durationMinutes, &$created) {
            foreach ($subjectIds as $sid) {
                $subject = $subjects->get($sid);
                if (! $subject) {
                    continue;
                }
                $exists = Exam::query()
                    ->where('exam_period_id', $period->id)
                    ->where('subject_id', $sid)
                    ->exists();
                if ($exists) {
                    continue;
                }

                $code = $this->uniqueExamCode($period, $subject);
                $exam = Exam::create([
                    'institution_id' => $period->institution_id,
                    'exam_period_id' => $period->id,
                    'subject_id' => $sid,
                    'code' => $code,
                    'name' => $period->name.' · '.$subject->name,
                    'description' => null,
                    'duration_minutes' => $durationMinutes,
                    'shuffle_questions' => true,
                    'shuffle_options' => true,
                    'start_type' => Exam::START_TYPE_MANUAL,
                    'scheduled_start_at' => $period->window_start_at,
                    'scheduled_end_at' => $period->window_end_at,
                    'created_by' => $userId,
                ]);

                ExamSession::create([
                    'exam_id' => $exam->id,
                    'name' => 'Sesi utama',
                    'scheduled_start_at' => $period->window_start_at,
                    'scheduled_end_at' => $period->window_end_at,
                    'status' => ExamSession::STATUS_DRAFT,
                ]);

                $created[] = $exam->fresh(['subject', 'sessions']);
            }
        });

        return $created;
    }

    /**
     * Terapkan jadwal gelombang ke semua ujian mapel + sesi utamanya.
     */
    public function applySchedule(ExamPeriod $period): void
    {
        $period->loadMissing('exams.sessions');
        foreach ($period->exams as $exam) {
            $exam->update([
                'scheduled_start_at' => $period->window_start_at,
                'scheduled_end_at' => $period->window_end_at,
            ]);
            $session = $exam->sessions->sortBy('id')->first();
            if ($session && in_array($session->status, [ExamSession::STATUS_DRAFT, ExamSession::STATUS_SCHEDULED], true)) {
                $session->update([
                    'scheduled_start_at' => $period->window_start_at,
                    'scheduled_end_at' => $period->window_end_at,
                    'status' => ($period->window_start_at && $period->window_end_at)
                        ? ExamSession::STATUS_SCHEDULED
                        : ExamSession::STATUS_DRAFT,
                ]);
            }
        }
    }

    /**
     * Tambah semua siswa dari kelas terpilih ke sesi utama tiap ujian mapel.
     *
     * @param  list<int>  $classIds
     * @param  list<int>|null  $examIds  null = semua mapel di gelombang
     */
    public function syncParticipantsByClasses(ExamPeriod $period, array $classIds, ?array $examIds = null): array
    {
        $classIds = array_values(array_unique(array_map('intval', $classIds)));
        $studentIds = Student::query()
            ->where('institution_id', $period->institution_id)
            ->whereIn('class_id', $classIds)
            ->pluck('id')
            ->all();

        $examsQuery = Exam::query()->where('exam_period_id', $period->id);
        if ($examIds !== null) {
            $examsQuery->whereIn('id', array_map('intval', $examIds));
        }
        $exams = $examsQuery->with('sessions')->get();

        $summary = ['students' => count($studentIds), 'exams' => 0, 'added' => 0];

        DB::transaction(function () use ($exams, $studentIds, &$summary) {
            foreach ($exams as $exam) {
                $session = $exam->sessions->sortBy('id')->first();
                if (! $session) {
                    $session = ExamSession::create([
                        'exam_id' => $exam->id,
                        'name' => 'Sesi utama',
                        'scheduled_start_at' => $exam->scheduled_start_at,
                        'scheduled_end_at' => $exam->scheduled_end_at,
                        'status' => ExamSession::STATUS_DRAFT,
                    ]);
                }
                $existing = $session->participants()->pluck('student_id')->all();
                $toAdd = array_diff($studentIds, $existing);
                foreach ($toAdd as $studentId) {
                    ExamParticipant::create([
                        'exam_session_id' => $session->id,
                        'student_id' => $studentId,
                        'login_token' => ExamParticipant::generateLoginToken(),
                        'status' => ExamParticipant::STATUS_REGISTERED,
                    ]);
                    $summary['added']++;
                }
                $summary['exams']++;
            }
        });

        return $summary;
    }

    protected function uniqueExamCode(ExamPeriod $period, Subject $subject): string
    {
        $base = Str::upper(Str::slug($period->code.'-'.($subject->code ?: $subject->name), ''));
        $base = preg_replace('/[^A-Z0-9\-]/', '', $base) ?: 'EXAM';
        $base = mb_substr($base, 0, 48);
        $code = $base;
        $i = 2;
        while (Exam::query()
            ->where('institution_id', $period->institution_id)
            ->where('code', $code)
            ->exists()) {
            $code = mb_substr($base, 0, 44).'-'.$i;
            $i++;
        }

        return $code;
    }
}

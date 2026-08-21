<?php

namespace Tests\Unit;

use App\Models\QuestionBank;
use App\Services\ExamQuestionSnapshot;
use PHPUnit\Framework\TestCase;

class ExamQuestionSnapshotTest extends TestCase
{
    public function test_accepted_isian_answers_include_aliases(): void
    {
        $keys = ExamQuestionSnapshot::acceptedIsianAnswers([
            'key_answer' => 'H2O',
            'key_answer_aliases' => ['h2o', 'air', ''],
        ]);

        $this->assertSame(['h2o', 'air'], $keys);
    }

    public function test_attempt_payload_strips_correct_flags(): void
    {
        $payload = ExamQuestionSnapshot::toAttemptPayload([
            'question_bank_id' => 9,
            'type' => QuestionBank::TYPE_PG,
            'body' => 'Hasil 3+4',
            'weight' => 1,
            'options' => [
                ['id' => 1, 'option_key' => 'A', 'body' => '6', 'is_correct' => false, 'option_weight' => 0, 'sort_order' => 0],
                ['id' => 2, 'option_key' => 'B', 'body' => '7', 'is_correct' => true, 'option_weight' => 1, 'sort_order' => 1],
            ],
            'stimulus' => null,
        ], 0, 1, false);

        $this->assertSame('Hasil 3+4', $payload['body']);
        $this->assertCount(2, $payload['options']);
        $this->assertArrayNotHasKey('is_correct', $payload['options'][0]);
        $this->assertArrayNotHasKey('option_weight', $payload['options'][1]);
        $this->assertSame(2, $payload['options'][1]['id']);
    }

    public function test_has_payload_requires_type(): void
    {
        $this->assertFalse(ExamQuestionSnapshot::hasPayload(null));
        $this->assertFalse(ExamQuestionSnapshot::hasPayload([]));
        $this->assertTrue(ExamQuestionSnapshot::hasPayload(['type' => 'pg', 'body' => 'x']));
    }
}

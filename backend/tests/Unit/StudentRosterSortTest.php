<?php

namespace Tests\Unit;

use App\Support\StudentRosterSort;
use Tests\TestCase;

class StudentRosterSortTest extends TestCase
{
    public function test_sorts_by_grade_then_class_name_then_student_name(): void
    {
        $rows = [
            ['name' => 'Budi', 'class' => ['id' => 2, 'name' => 'X-10', 'grade' => 10]],
            ['name' => 'Ani', 'class' => ['id' => 1, 'name' => 'X-2', 'grade' => 10]],
            ['name' => 'Citra', 'class' => ['id' => 3, 'name' => 'XI-1', 'grade' => 11]],
            ['name' => 'Dina', 'class' => ['id' => 1, 'name' => 'X-2', 'grade' => 10]],
            ['name' => 'Eko', 'class' => null],
        ];

        usort($rows, fn ($a, $b) => StudentRosterSort::compare(
            $a['class'] ?? null,
            $a['name'] ?? '',
            $b['class'] ?? null,
            $b['name'] ?? ''
        ));

        $this->assertSame(
            ['Ani', 'Dina', 'Budi', 'Citra', 'Eko'],
            array_column($rows, 'name')
        );
    }

    public function test_group_rows_keeps_class_blocks(): void
    {
        $rows = [
            ['name' => 'Ani', 'class' => ['id' => 1, 'name' => 'X-2']],
            ['name' => 'Dina', 'class' => ['id' => 1, 'name' => 'X-2']],
            ['name' => 'Budi', 'class' => ['id' => 2, 'name' => 'X-10']],
        ];

        $groups = StudentRosterSort::groupRows($rows);

        $this->assertCount(2, $groups);
        $this->assertSame('X-2', $groups[0]['name']);
        $this->assertCount(2, $groups[0]['rows']);
        $this->assertSame('X-10', $groups[1]['name']);
    }
}

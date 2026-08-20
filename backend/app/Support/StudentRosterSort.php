<?php

namespace App\Support;

use App\Models\SchoolClass;
use App\Models\Student;

class StudentRosterSort
{
    /**
     * @return array{id: int, name: string, grade: int|null}|null
     */
    public static function classArray(?Student $student): ?array
    {
        if (!$student) {
            return null;
        }

        $related = null;
        if ($student->relationLoaded('class')) {
            $related = $student->getRelation('class');
        } elseif ($student->relationLoaded('schoolClass')) {
            $related = $student->getRelation('schoolClass');
        }

        if (!$related instanceof SchoolClass) {
            return null;
        }

        return [
            'id' => (int) $related->id,
            'name' => (string) $related->name,
            'grade' => $related->grade !== null ? (int) $related->grade : null,
        ];
    }

    public static function compareStudents(?Student $a, ?Student $b): int
    {
        return self::compare(
            self::classArray($a),
            $a?->name,
            self::classArray($b),
            $b?->name
        );
    }

    /**
     * @param  array<string, mixed>|null  $classA
     * @param  array<string, mixed>|null  $classB
     */
    public static function compare(?array $classA, ?string $nameA, ?array $classB, ?string $nameB): int
    {
        $grade = self::gradeValue($classA) <=> self::gradeValue($classB);
        if ($grade !== 0) {
            return $grade;
        }

        $className = strnatcasecmp((string) ($classA['name'] ?? ''), (string) ($classB['name'] ?? ''));
        if ($className !== 0) {
            return $className;
        }

        return strcasecmp((string) $nameA, (string) $nameB);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{key: string, name: string, rows: list<array<string, mixed>>}>
     */
    public static function groupRows(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $class = is_array($row['class'] ?? null) ? $row['class'] : null;
            $key = (string) ($class['id'] ?? $class['name'] ?? '');
            $name = (string) ($class['name'] ?? 'Tanpa kelas');
            if ($name === '') {
                $name = 'Tanpa kelas';
            }
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'name' => $name,
                    'rows' => [],
                ];
            }
            $groups[$key]['rows'][] = $row;
        }

        return array_values($groups);
    }

    /**
     * @param  array<string, mixed>|null  $class
     */
    private static function gradeValue(?array $class): int
    {
        if ($class === null || !isset($class['grade']) || $class['grade'] === '' || $class['grade'] === null) {
            return PHP_INT_MAX;
        }

        return (int) $class['grade'];
    }
}

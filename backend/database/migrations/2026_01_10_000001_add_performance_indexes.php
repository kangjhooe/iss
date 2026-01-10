<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes for student table
        if (Schema::hasTable('student')) {
            Schema::table('student', function (Blueprint $table) {
                if (!$this->indexExists('student', 'idx_student_institution_status')) {
                    $table->index(['institution_id', 'status'], 'idx_student_institution_status');
                }
                if (!$this->indexExists('student', 'idx_student_class_status')) {
                    $table->index(['class', 'status'], 'idx_student_class_status');
                }
                if (!$this->indexExists('student', 'idx_student_gender_status')) {
                    $table->index(['gender', 'status'], 'idx_student_gender_status');
                }
                if (!$this->indexExists('student', 'idx_student_academic_year')) {
                    $table->index('academic_year_id', 'idx_student_academic_year');
                }
            });
        }

        // Add indexes for employee table
        if (Schema::hasTable('employee')) {
            Schema::table('employee', function (Blueprint $table) {
                if (!$this->indexExists('employee', 'idx_employee_institution_type_status')) {
                    $table->index(['institution_id', 'type', 'status'], 'idx_employee_institution_type_status');
                }
                if (!$this->indexExists('employee', 'idx_employee_employment_status')) {
                    $table->index(['employment_status', 'status'], 'idx_employee_employment_status');
                }
            });
        }

        // Add indexes for class table (note: table name is 'class', not 'school_class')
        if (Schema::hasTable('class')) {
            Schema::table('class', function (Blueprint $table) {
                if (!$this->indexExists('class', 'idx_class_institution_year_status')) {
                    $table->index(['institution_id', 'academic_year_id', 'status'], 'idx_class_institution_year_status');
                }
                if (!$this->indexExists('class', 'idx_class_grade_year')) {
                    $table->index(['grade', 'academic_year_id'], 'idx_class_grade_year');
                }
            });
        }

        // Add indexes for facility tables
        if (Schema::hasTable('land')) {
            Schema::table('land', function (Blueprint $table) {
                if (!$this->indexExists('land', 'idx_land_institution_status')) {
                    $table->index(['institution_id', 'status'], 'idx_land_institution_status');
                }
            });
        }

        if (Schema::hasTable('building')) {
            Schema::table('building', function (Blueprint $table) {
                // Building table doesn't have 'status' column, only 'condition'
                if (!$this->indexExists('building', 'idx_building_institution_land')) {
                    $table->index(['institution_id', 'land_id'], 'idx_building_institution_land');
                }
                if (!$this->indexExists('building', 'idx_building_institution_condition')) {
                    $table->index(['institution_id', 'condition'], 'idx_building_institution_condition');
                }
            });
        }

        if (Schema::hasTable('room')) {
            Schema::table('room', function (Blueprint $table) {
                // Room table doesn't have 'status' column, has 'type' and 'condition'
                if (!$this->indexExists('room', 'idx_room_institution_building_type')) {
                    $table->index(['institution_id', 'building_id', 'type'], 'idx_room_institution_building_type');
                }
                if (!$this->indexExists('room', 'idx_room_institution_type')) {
                    $table->index(['institution_id', 'type'], 'idx_room_institution_type');
                }
            });
        }

        // Add indexes for report queries
        if (Schema::hasTable('class_student_history')) {
            Schema::table('class_student_history', function (Blueprint $table) {
                if (!$this->indexExists('class_student_history', 'idx_history_year_class')) {
                    $table->index(['academic_year_id', 'class_id'], 'idx_history_year_class');
                }
                if (!$this->indexExists('class_student_history', 'idx_history_student_year')) {
                    $table->index(['student_id', 'academic_year_id'], 'idx_history_student_year');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('student')) {
            Schema::table('student', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'idx_student_institution_status');
                $this->dropIndexIfExists($table, 'idx_student_class_status');
                $this->dropIndexIfExists($table, 'idx_student_gender_status');
                $this->dropIndexIfExists($table, 'idx_student_academic_year');
            });
        }

        if (Schema::hasTable('employee')) {
            Schema::table('employee', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'idx_employee_institution_type_status');
                $this->dropIndexIfExists($table, 'idx_employee_employment_status');
            });
        }

        if (Schema::hasTable('class')) {
            Schema::table('class', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'idx_class_institution_year_status');
                $this->dropIndexIfExists($table, 'idx_class_grade_year');
            });
        }

        if (Schema::hasTable('land')) {
            Schema::table('land', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'idx_land_institution_status');
            });
        }

        if (Schema::hasTable('building')) {
            Schema::table('building', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'idx_building_institution_land');
                $this->dropIndexIfExists($table, 'idx_building_institution_condition');
            });
        }

        if (Schema::hasTable('room')) {
            Schema::table('room', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'idx_room_institution_building_type');
                $this->dropIndexIfExists($table, 'idx_room_institution_type');
            });
        }

        if (Schema::hasTable('class_student_history')) {
            Schema::table('class_student_history', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'idx_history_year_class');
                $this->dropIndexIfExists($table, 'idx_history_student_year');
            });
        }
    }

    /**
     * Check if index exists on table.
     */
    protected function indexExists(string $table, string $index): bool
    {
        try {
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            
            $result = DB::select(
                "SELECT COUNT(*) as count FROM information_schema.statistics 
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                [$databaseName, $table, $index]
            );
            
            return $result[0]->count > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Drop index if it exists.
     */
    protected function dropIndexIfExists(Blueprint $table, string $index): void
    {
        try {
            $table->dropIndex($index);
        } catch (\Exception $e) {
            // Index doesn't exist, ignore
        }
    }
};

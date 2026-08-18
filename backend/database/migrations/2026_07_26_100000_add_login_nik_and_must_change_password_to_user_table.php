<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user', function (Blueprint $table) {
            if (!Schema::hasColumn('user', 'login_nik')) {
                $table->string('login_nik', 16)->nullable()->unique()->after('email');
            }
            if (!Schema::hasColumn('user', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user', function (Blueprint $table) {
            if (Schema::hasColumn('user', 'login_nik')) {
                $table->dropUnique(['login_nik']);
                $table->dropColumn('login_nik');
            }
            if (Schema::hasColumn('user', 'must_change_password')) {
                $table->dropColumn('must_change_password');
            }
        });
    }
};

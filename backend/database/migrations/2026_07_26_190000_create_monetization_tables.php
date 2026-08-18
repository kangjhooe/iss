<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('app_branding')) {
            Schema::table('app_branding', function (Blueprint $table) {
                if (! Schema::hasColumn('app_branding', 'monetization_launched')) {
                    $table->boolean('monetization_launched')->default(false)->after('maintenance_message');
                }
                if (! Schema::hasColumn('app_branding', 'default_storage_quota_mb')) {
                    $table->unsignedInteger('default_storage_quota_mb')->default(5120)->after('monetization_launched');
                }
            });
        }

        if (Schema::hasTable('institution')) {
            Schema::table('institution', function (Blueprint $table) {
                if (! Schema::hasColumn('institution', 'storage_quota_mb')) {
                    $table->unsignedInteger('storage_quota_mb')->nullable()->after('is_demo');
                }
                if (! Schema::hasColumn('institution', 'storage_addon_mb')) {
                    $table->unsignedInteger('storage_addon_mb')->default(0)->after('storage_quota_mb');
                }
            });
        }

        if (! Schema::hasTable('subscription_plans')) {
            Schema::create('subscription_plans', function (Blueprint $table) {
                $table->id();
                $table->string('key', 64)->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedInteger('storage_quota_mb')->default(5120);
                $table->boolean('includes_online_exam')->default(false);
                $table->unsignedInteger('price_monthly')->default(0);
                $table->unsignedInteger('price_yearly')->default(0);
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('subscription_addons')) {
            Schema::create('subscription_addons', function (Blueprint $table) {
                $table->id();
                $table->string('key', 64)->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedInteger('storage_mb')->nullable();
                $table->unsignedInteger('price_monthly')->default(0);
                $table->unsignedInteger('price_yearly')->default(0);
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('institution_subscriptions')) {
            Schema::create('institution_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
                $table->foreignId('subscription_plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
                $table->string('status', 32)->default('manual'); // manual|trial|active|suspended
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique('institution_id');
            });
        }

        if (! Schema::hasTable('institution_addon_grants')) {
            Schema::create('institution_addon_grants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
                $table->string('addon_key', 64);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('storage_mb')->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->string('source', 32)->default('manual'); // manual|trial|paid
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['institution_id', 'addon_key']);
                $table->index('addon_key');
            });
        }

        $now = now();

        if (Schema::hasTable('subscription_plans') && DB::table('subscription_plans')->count() === 0) {
            DB::table('subscription_plans')->insert([
                [
                    'key' => 'starter',
                    'name' => 'Starter',
                    'description' => 'Paket dasar operasional sekolah',
                    'storage_quota_mb' => 5120,
                    'includes_online_exam' => false,
                    'price_monthly' => 0,
                    'price_yearly' => 0,
                    'is_active' => true,
                    'sort_order' => 10,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key' => 'standard',
                    'name' => 'Standard',
                    'description' => 'Termasuk ujian online dan kuota penyimpanan lebih besar',
                    'storage_quota_mb' => 20480,
                    'includes_online_exam' => true,
                    'price_monthly' => 0,
                    'price_yearly' => 0,
                    'is_active' => true,
                    'sort_order' => 20,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        if (Schema::hasTable('subscription_addons') && DB::table('subscription_addons')->count() === 0) {
            DB::table('subscription_addons')->insert([
                [
                    'key' => 'storage_upgrade',
                    'name' => 'Peningkatan Penyimpanan',
                    'description' => 'Tambah kuota penyimpanan institusi',
                    'storage_mb' => 10240,
                    'price_monthly' => 0,
                    'price_yearly' => 0,
                    'is_active' => true,
                    'sort_order' => 10,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key' => 'online_exam',
                    'name' => 'Modul Ujian Online',
                    'description' => 'Akses modul ujian online sebagai add-on',
                    'storage_mb' => null,
                    'price_monthly' => 0,
                    'price_yearly' => 0,
                    'is_active' => true,
                    'sort_order' => 20,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_addon_grants');
        Schema::dropIfExists('institution_subscriptions');
        Schema::dropIfExists('subscription_addons');
        Schema::dropIfExists('subscription_plans');

        if (Schema::hasTable('institution')) {
            Schema::table('institution', function (Blueprint $table) {
                if (Schema::hasColumn('institution', 'storage_addon_mb')) {
                    $table->dropColumn('storage_addon_mb');
                }
                if (Schema::hasColumn('institution', 'storage_quota_mb')) {
                    $table->dropColumn('storage_quota_mb');
                }
            });
        }

        if (Schema::hasTable('app_branding')) {
            Schema::table('app_branding', function (Blueprint $table) {
                if (Schema::hasColumn('app_branding', 'default_storage_quota_mb')) {
                    $table->dropColumn('default_storage_quota_mb');
                }
                if (Schema::hasColumn('app_branding', 'monetization_launched')) {
                    $table->dropColumn('monetization_launched');
                }
            });
        }
    }
};

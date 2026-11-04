<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organizational_unit_id')->nullable()->after('role_id')->constrained('organizational_units')->nullOnDelete();
        });

        // 2. activities
        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('organizational_unit_id')->nullable()->after('responsible_authority')->constrained('organizational_units')->nullOnDelete();
            $table->string('resolution_number', 100)->nullable()->after('academic_hours');
            $table->string('target_audience_mode', 30)->default('own_unit')->after('resolution_number'); // 'own_unit', 'all_faculty', 'specific_units'
        });

        // 3. activity_allowed_units (para resoluciones que habilitan varias carreras específicas)
        Schema::create('activity_allowed_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignId('organizational_unit_id')->constrained('organizational_units')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['activity_id', 'organizational_unit_id']);
        });

        // 4. certificate_templates
        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->foreignId('organizational_unit_id')->nullable()->after('category')->constrained('organizational_units')->nullOnDelete();
        });

        // 5. certificates
        Schema::table('certificates', function (Blueprint $table) {
            $table->foreignId('organizational_unit_id')->nullable()->after('activity_id')->constrained('organizational_units')->nullOnDelete();
        });

        // 6. beneficiaries
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->foreignId('primary_organizational_unit_id')->nullable()->after('institution_or_department')->constrained('organizational_units')->nullOnDelete();
        });

        // 7. activity_participants
        Schema::table('activity_participants', function (Blueprint $table) {
            $table->boolean('career_eligible')->default(true)->after('status');
            $table->string('ineligibility_reason', 255)->nullable()->after('career_eligible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_participants', function (Blueprint $table) {
            $table->dropColumn(['career_eligible', 'ineligibility_reason']);
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropForeign(['primary_organizational_unit_id']);
            $table->dropColumn('primary_organizational_unit_id');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign(['organizational_unit_id']);
            $table->dropColumn('organizational_unit_id');
        });

        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->dropForeign(['organizational_unit_id']);
            $table->dropColumn('organizational_unit_id');
        });

        Schema::dropIfExists('activity_allowed_units');

        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['organizational_unit_id']);
            $table->dropColumn(['organizational_unit_id', 'resolution_number', 'target_audience_mode']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organizational_unit_id']);
            $table->dropColumn('organizational_unit_id');
        });
    }
};

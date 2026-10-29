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
        // 1. Ampliar certificate_types con políticas de comportamiento, vigencia y evaluación
        Schema::table('certificate_types', function (Blueprint $table) {
            if (! Schema::hasColumn('certificate_types', 'validity_mode')) {
                $table->string('validity_mode', 30)->default('years')->after('description'); // 'permanent', 'years', 'custom'
            }
            if (! Schema::hasColumn('certificate_types', 'default_validity_years')) {
                $table->unsignedSmallInteger('default_validity_years')->nullable()->default(3)->after('validity_mode');
            }
            if (! Schema::hasColumn('certificate_types', 'has_score')) {
                $table->boolean('has_score')->default(false)->after('default_validity_years');
            }
            if (! Schema::hasColumn('certificate_types', 'score_scale')) {
                $table->string('score_scale', 50)->nullable()->default('Sobre 100 puntos')->after('has_score');
            }
            if (! Schema::hasColumn('certificate_types', 'min_passing_score')) {
                $table->decimal('min_passing_score', 5, 2)->nullable()->default(51.00)->after('score_scale');
            }
            if (! Schema::hasColumn('certificate_types', 'icon')) {
                $table->string('icon', 50)->default('📜')->after('min_passing_score');
            }
            if (! Schema::hasColumn('certificate_types', 'badge_color')) {
                $table->string('badge_color', 30)->default('blue')->after('icon');
            }
        });

        // 2. Vincular el Tipo de Certificado a la Actividad y sus configuraciones
        Schema::table('activities', function (Blueprint $table) {
            if (! Schema::hasColumn('activities', 'certificate_type_id')) {
                $table->foreignId('certificate_type_id')->nullable()->after('responsible_authority')->constrained('certificate_types')->onDelete('set null');
            }
        });

        Schema::table('activity_certificate_configs', function (Blueprint $table) {
            if (! Schema::hasColumn('activity_certificate_configs', 'validity_mode')) {
                $table->string('validity_mode', 30)->nullable()->after('certificate_template_id'); // 'permanent', 'years', 'custom'
            }
            if (! Schema::hasColumn('activity_certificate_configs', 'validity_years')) {
                $table->unsignedSmallInteger('validity_years')->nullable()->after('validity_mode');
            }
            if (! Schema::hasColumn('activity_certificate_configs', 'show_score_on_pdf')) {
                $table->boolean('show_score_on_pdf')->default(true)->after('validity_years');
            }
            if (! Schema::hasColumn('activity_certificate_configs', 'show_score_on_portal')) {
                $table->boolean('show_score_on_portal')->default(true)->after('show_score_on_pdf');
            }
        });

        // 3. Modificar certificates para soportar vigencia permanente (expiration_date nullable) y notas
        Schema::table('certificates', function (Blueprint $table) {
            $table->date('expiration_date')->nullable()->change();

            if (! Schema::hasColumn('certificates', 'validity_type')) {
                $table->string('validity_type', 30)->default('years')->after('expiration_date'); // 'permanent', 'years', 'custom_date'
            }
            if (! Schema::hasColumn('certificates', 'score')) {
                $table->decimal('score', 5, 2)->nullable()->after('integrity_hash');
            }
            if (! Schema::hasColumn('certificates', 'score_label')) {
                $table->string('score_label', 150)->nullable()->after('score');
            }
        });

        // 4. Modificar activity_participants para conservar la nota detectada desde Excel o manual
        Schema::table('activity_participants', function (Blueprint $table) {
            if (! Schema::hasColumn('activity_participants', 'score')) {
                $table->decimal('score', 5, 2)->nullable()->after('status');
            }
            if (! Schema::hasColumn('activity_participants', 'score_label')) {
                $table->string('score_label', 150)->nullable()->after('score');
            }
        });

        // 5. Configurar presets institucionales para los tipos de certificado existentes
        if (Schema::hasTable('certificate_types')) {
            DB::table('certificate_types')->where('code', 'aprobacion')->update([
                'has_score' => true,
                'validity_mode' => 'years',
                'default_validity_years' => 3,
                'min_passing_score' => 51.00,
                'score_scale' => 'Sobre 100 puntos',
                'icon' => '🎓',
                'badge_color' => 'emerald',
            ]);

            DB::table('certificate_types')->where('code', 'asistencia')->update([
                'has_score' => false,
                'validity_mode' => 'years',
                'default_validity_years' => 3,
                'icon' => '📜',
                'badge_color' => 'blue',
            ]);

            DB::table('certificate_types')->where('code', 'disertante')->update([
                'has_score' => false,
                'validity_mode' => 'permanent',
                'default_validity_years' => null,
                'icon' => '🎤',
                'badge_color' => 'purple',
            ]);

            DB::table('certificate_types')->where('code', 'organizador')->update([
                'has_score' => false,
                'validity_mode' => 'years',
                'default_validity_years' => 3,
                'icon' => '⚙️',
                'badge_color' => 'slate',
            ]);

            DB::table('certificate_types')->where('code', 'reconocimiento')->update([
                'has_score' => false,
                'validity_mode' => 'permanent',
                'default_validity_years' => null,
                'icon' => '🏆',
                'badge_color' => 'amber',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_participants', function (Blueprint $table) {
            $table->dropColumn(['score', 'score_label']);
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['validity_type', 'score', 'score_label']);
            $table->date('expiration_date')->nullable(false)->change();
        });

        Schema::table('activity_certificate_configs', function (Blueprint $table) {
            $table->dropColumn(['validity_mode', 'validity_years', 'show_score_on_pdf', 'show_score_on_portal']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['certificate_type_id']);
            $table->dropColumn(['certificate_type_id']);
        });

        Schema::table('certificate_types', function (Blueprint $table) {
            $table->dropColumn([
                'validity_mode',
                'default_validity_years',
                'has_score',
                'score_scale',
                'min_passing_score',
                'icon',
                'badge_color',
            ]);
        });
    }
};

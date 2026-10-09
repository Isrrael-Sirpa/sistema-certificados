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
        // 1. Biblioteca de Recursos Institucionales (Logos, Firmas, Sellos, Marcas de Agua)
        if (!Schema::hasTable('institutional_assets')) {
            Schema::create('institutional_assets', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('type', 30); // logo, signature, stamp, watermark, background, decorative
                $table->string('file_path', 255);
                $table->string('mime_type', 80)->nullable();
                $table->json('metadata')->nullable(); // Para firmas: authority_name, authority_title, institution. Para logos: default_width, etc.
                $table->boolean('is_mandatory')->default(false); // Elemento de identidad institucional obligatoria
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('display_order')->default(0);
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();

                $table->index(['type', 'is_active']);
            });
        }

        // 2. Extender certificate_templates con categoría, versión activa y flag predeterminado
        Schema::table('certificate_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificate_templates', 'category')) {
                $table->string('category', 60)->default('general')->after('slug');
            }
            if (!Schema::hasColumn('certificate_templates', 'current_version')) {
                $table->unsignedInteger('current_version')->default(1)->after('orientation');
            }
            if (!Schema::hasColumn('certificate_templates', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('certificate_templates', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()->after('is_default')->constrained('users')->onDelete('set null');
            }
        });

        // 3. Versiones de Plantillas de Certificados
        // Si la tabla quedó a medias del intento anterior, la recreamos limpiamente
        Schema::dropIfExists('certificate_template_versions');

        Schema::create('certificate_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_template_id')->constrained('certificate_templates')->onDelete('cascade');
            $table->unsignedInteger('version_number')->default(1);
            $table->string('name', 150);
            $table->string('orientation', 20)->default('landscape'); // landscape, portrait
            $table->json('design_schema'); // Elementos, coordenadas, jerarquía de textos, colores, bordes, assets vinculados
            $table->string('change_summary', 255)->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Nombre de índice explícito para cumplir con el límite de 64 caracteres en MySQL
            $table->unique(['certificate_template_id', 'version_number'], 'tpl_version_unique');
        });

        // 4. Configuración Personalizada de Certificado por Actividad Académica
        if (!Schema::hasTable('activity_certificate_configs')) {
            Schema::create('activity_certificate_configs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('activity_id')->unique()->constrained('activities')->onDelete('cascade');
                $table->foreignId('certificate_template_id')->nullable()->constrained('certificate_templates')->onDelete('set null');
                $table->string('custom_title', 255)->nullable();
                $table->text('custom_body_text')->nullable(); // Redacción personalizada con variables {{...}}
                $table->string('primary_color', 20)->nullable();
                $table->string('secondary_color', 20)->nullable();
                $table->json('selected_asset_ids')->nullable(); // IDs de logos, firmas co-firmantes, sellos habilitados
                $table->json('custom_signatures')->nullable(); // Firmas adicionales específicas de la actividad
                $table->json('custom_fields_schema')->nullable(); // Campos adicionales: resolución, convenio, etc.
                $table->timestamps();
            });
        }

        // 5. Extender tabla certificates con versionado y campos personalizados
        Schema::table('certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificates', 'template_version_id')) {
                $table->foreignId('template_version_id')->nullable()->after('certificate_template_id')->constrained('certificate_template_versions')->onDelete('set null');
            }
            if (!Schema::hasColumn('certificates', 'custom_fields_data')) {
                $table->json('custom_fields_data')->nullable()->after('snapshot_data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (Schema::hasColumn('certificates', 'template_version_id')) {
                $table->dropForeign(['template_version_id']);
                $table->dropColumn('template_version_id');
            }
            if (Schema::hasColumn('certificates', 'custom_fields_data')) {
                $table->dropColumn('custom_fields_data');
            }
        });

        Schema::dropIfExists('activity_certificate_configs');
        Schema::dropIfExists('certificate_template_versions');

        Schema::table('certificate_templates', function (Blueprint $table) {
            if (Schema::hasColumn('certificate_templates', 'created_by_user_id')) {
                $table->dropForeign(['created_by_user_id']);
                $table->dropColumn(['category', 'current_version', 'is_default', 'created_by_user_id']);
            }
        });

        Schema::dropIfExists('institutional_assets');
    }
};

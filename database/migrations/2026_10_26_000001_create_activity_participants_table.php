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
        Schema::create('activity_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');
            $table->foreignId('beneficiary_id')->constrained('beneficiaries')->onDelete('cascade');
            $table->string('role', 50)->default('Participante');
            $table->string('status', 30)->default('inscrito'); // 'inscrito', 'certificado'
            $table->timestamps();

            $table->unique(['activity_id', 'beneficiary_id']);
            $table->index(['activity_id', 'status']);
        });

        // Sincronizar automáticamente registros históricos de certificados ya emitidos
        if (Schema::hasTable('certificates')) {
            $existingCertificates = DB::table('certificates')
                ->select('activity_id', 'beneficiary_id')
                ->whereNotNull('activity_id')
                ->whereNotNull('beneficiary_id')
                ->distinct()
                ->get();

            $now = now();
            foreach ($existingCertificates as $cert) {
                DB::table('activity_participants')->insertOrIgnore([
                    'activity_id' => $cert->activity_id,
                    'beneficiary_id' => $cert->beneficiary_id,
                    'role' => 'Participante',
                    'status' => 'certificado',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_participants');
    }
};

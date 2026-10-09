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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_code', 50)->unique(); // eg: CERT-2026-000001
            $table->uuid('verification_token')->unique(); // UUID v4 for public QR verification URL
            $table->foreignId('beneficiary_id')->constrained('beneficiaries')->onDelete('restrict');
            $table->foreignId('activity_id')->constrained('activities')->onDelete('restrict');
            $table->foreignId('certificate_type_id')->constrained('certificate_types')->onDelete('restrict');
            $table->foreignId('certificate_template_id')->constrained('certificate_templates')->onDelete('restrict');
            $table->foreignId('generated_by_user_id')->constrained('users')->onDelete('restrict');
            
            $table->date('issue_date');
            $table->date('expiration_date'); // issue_date + 3 years
            
            $table->string('status', 20)->default('VIGENTE'); // VIGENTE, EXPIRADO, REVOCADO
            $table->timestamp('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->foreignId('revoked_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->string('pdf_path')->nullable();
            $table->string('qr_path')->nullable();
            $table->string('integrity_hash', 64)->nullable(); // SHA-256 integrity hash
            $table->json('snapshot_data')->nullable(); // frozen beneficiary, activity, authority info at generation time
            
            $table->timestamps();

            $table->index(['status', 'expiration_date']);
            $table->index('issue_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};

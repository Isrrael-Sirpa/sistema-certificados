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
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 30)->default('CI'); // CI, DNI, Pasaporte, Cédula de Extranjería
            $table->string('document_number', 50);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('academic_title', 50)->nullable(); // Univ., Lic., Ing., Dr., Mg., etc.
            $table->string('email', 150)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('institution_or_department', 150)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['document_type', 'document_number']);
            $table->index(['last_name', 'first_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};

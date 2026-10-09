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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('activity_type', 80); // Curso, Taller, Seminario, Congreso, Capacitación, etc.
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('academic_hours')->default(0);
            $table->string('location', 255)->nullable();
            $table->string('organizer', 255)->default('Vicedecanato');
            $table->string('responsible_authority', 255)->nullable(); // Nombre y cargo de la autoridad
            $table->string('status', 30)->default('activa'); // activa, finalizada, cancelada
            $table->timestamps();
            $table->softDeletes();

            $table->index(['activity_type', 'status']);
            $table->index('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};

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
        Schema::create('organizational_units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 50)->unique();
            $table->string('type', 30)->default('carrera'); // 'carrera', 'departamento', 'instituto', 'administrativo'
            $table->string('faculty_name', 150)->default('Facultad de Tecnología');
            $table->string('responsible_title', 100)->nullable(); // e.g. 'Director de Carrera', 'Vicedecano'
            $table->string('responsible_name', 150)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizational_units');
    }
};

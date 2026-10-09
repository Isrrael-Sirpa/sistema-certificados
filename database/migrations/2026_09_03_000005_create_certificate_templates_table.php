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
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->string('view_path', 150)->default('certificates.templates.default');
            $table->string('orientation', 20)->default('landscape'); // landscape, portrait
            $table->string('background_image')->nullable();
            $table->json('configuration')->nullable(); // margins, font choices, signature positions
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_templates');
    }
};

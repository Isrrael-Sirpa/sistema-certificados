<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('certificate_types')->where('code', 'aprobacion')->where('has_score', false)
            ->update(['has_score' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // La regla de aprobación también aplica después de revertir el código.
    }
};

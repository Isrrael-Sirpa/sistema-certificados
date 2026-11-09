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
        Schema::table('activities', function (Blueprint $table) {
            $table->decimal('min_passing_score', 5, 2)->nullable();
        });

        $legacyMinimum = 51.0;
        $typeMinimums = DB::table('certificate_types')->where('has_score', true)->pluck('min_passing_score', 'id');
        foreach ($typeMinimums as $typeId => $minimum) {
            DB::table('activities')->where('certificate_type_id', $typeId)
                ->update(['min_passing_score' => max($legacyMinimum, (float) $minimum)]);
        }

        DB::table('activities')->whereNull('min_passing_score')->update(['min_passing_score' => $legacyMinimum]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('min_passing_score');
        });
    }
};

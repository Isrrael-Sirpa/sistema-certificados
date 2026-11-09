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
        Schema::table('activities', function (Blueprint $table) {
            $table->boolean('requires_payment')->default(false);
            $table->decimal('cost_amount', 10, 2)->default(0);
        });
        Schema::table('activity_participants', function (Blueprint $table) {
            $table->boolean('payment_confirmed')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_participants', function (Blueprint $table) {
            $table->dropColumn('payment_confirmed');
        });
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['requires_payment', 'cost_amount']);
        });
    }
};

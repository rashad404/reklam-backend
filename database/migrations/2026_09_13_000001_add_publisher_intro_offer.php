<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publishers', function (Blueprint $table) {
            $table->timestamp('commission_free_started_at')->nullable();
            $table->timestamp('commission_free_until')->nullable();
        });

        // Give already approved launch partners the same six-month offer from launch.
        $start = now()->toImmutable();
        DB::table('publishers')->where('status', 'approved')->update([
            'commission_free_started_at' => $start,
            'commission_free_until' => $start->addMonthsNoOverflow(6),
        ]);
    }

    public function down(): void
    {
        Schema::table('publishers', function (Blueprint $table) {
            $table->dropColumn(['commission_free_started_at', 'commission_free_until']);
        });
    }
};

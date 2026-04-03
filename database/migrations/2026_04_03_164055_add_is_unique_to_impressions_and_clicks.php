<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('impressions', function (Blueprint $table) {
            $table->boolean('is_unique')->default(true)->after('os');
        });

        Schema::table('clicks', function (Blueprint $table) {
            $table->boolean('is_unique')->default(true)->after('os');
        });
    }

    public function down(): void
    {
        Schema::table('impressions', function (Blueprint $table) {
            $table->dropColumn('is_unique');
        });

        Schema::table('clicks', function (Blueprint $table) {
            $table->dropColumn('is_unique');
        });
    }
};

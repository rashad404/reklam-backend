<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('impressions', function (Blueprint $table) {
            $table->string('device_type', 20)->nullable()->after('country');
            $table->string('browser', 30)->nullable()->after('device_type');
            $table->string('os', 30)->nullable()->after('browser');
        });

        Schema::table('clicks', function (Blueprint $table) {
            $table->string('device_type', 20)->nullable()->after('country');
            $table->string('browser', 30)->nullable()->after('device_type');
            $table->string('os', 30)->nullable()->after('browser');
        });

        Schema::table('daily_stats', function (Blueprint $table) {
            $table->string('country', 2)->nullable()->after('advertiser_id');
            $table->string('device_type', 20)->nullable()->after('country');
        });

        Schema::table('daily_stats', function (Blueprint $table) {
            $table->dropUnique(['date', 'ad_id', 'ad_unit_id']);
            $table->unique(['date', 'ad_id', 'ad_unit_id', 'country', 'device_type'], 'daily_stats_unique');
        });
    }

    public function down(): void
    {
        Schema::table('impressions', function (Blueprint $table) {
            $table->dropColumn(['device_type', 'browser', 'os']);
        });

        Schema::table('clicks', function (Blueprint $table) {
            $table->dropColumn(['device_type', 'browser', 'os']);
        });

        Schema::table('daily_stats', function (Blueprint $table) {
            $table->dropUnique('daily_stats_unique');
            $table->dropColumn(['country', 'device_type']);
            $table->unique(['date', 'ad_id', 'ad_unit_id']);
        });
    }
};

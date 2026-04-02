<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('ad_id')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('ad_unit_id')->nullable();
            $table->unsignedBigInteger('publisher_id')->nullable();
            $table->unsignedBigInteger('advertiser_id')->nullable();
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            $table->decimal('spent', 12, 2)->default(0);
            $table->decimal('earned', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['date', 'campaign_id']);
            $table->index(['date', 'publisher_id']);
            $table->index(['date', 'advertiser_id']);
            $table->unique(['date', 'ad_id', 'ad_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_stats');
    }
};

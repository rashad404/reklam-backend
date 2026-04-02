<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained()->onDelete('cascade');
            $table->foreignId('ad_unit_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('advertiser_id');
            $table->unsignedBigInteger('publisher_id');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('country', 2)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['ad_id', 'created_at']);
            $table->index(['campaign_id', 'created_at']);
            $table->index(['publisher_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impressions');
    }
};

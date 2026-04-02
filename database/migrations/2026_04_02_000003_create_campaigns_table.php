<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advertiser_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['display', 'native', 'text'])->default('display');
            $table->decimal('budget', 12, 2)->default(0);
            $table->decimal('daily_budget', 12, 2)->nullable();
            $table->decimal('spent', 12, 2)->default(0);
            $table->decimal('cpc_bid', 8, 4)->nullable();
            $table->decimal('cpm_bid', 8, 4)->nullable();
            $table->enum('status', ['draft', 'active', 'paused', 'completed'])->default('draft');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('targeting_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};

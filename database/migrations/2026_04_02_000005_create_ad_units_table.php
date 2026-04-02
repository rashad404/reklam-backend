<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publisher_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('ad_format', ['banner_728x90', 'banner_300x250', 'banner_320x50', 'native', 'text'])->default('banner_300x250');
            $table->string('website_url');
            $table->string('page_url')->nullable();
            $table->enum('status', ['active', 'paused'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_units');
    }
};

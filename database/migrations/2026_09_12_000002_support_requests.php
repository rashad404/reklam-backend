<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->uuid('request_key');
            $table->string('subject', 120);
            $table->text('message');
            $table->string('status')->default('open');
            $table->text('reply')->nullable();
            $table->foreignId('replied_by')->nullable()->constrained('users');
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'request_key']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_requests');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $t) {
            $t->softDeletes();
            $t->uuid('request_key')->nullable()->unique();
        });
        Schema::table('ads', function (Blueprint $t) {
            $t->softDeletes();
            $t->text('review_reason')->nullable();
        });
        Schema::table('ad_units', function (Blueprint $t) {
            $t->softDeletes();
            $t->timestamp('last_seen_at')->nullable();
        });
        Schema::table('publishers', function (Blueprint $t) {
            $t->string('verification_token', 64)->nullable();
            $t->timestamp('verified_at')->nullable();
            $t->text('review_reason')->nullable();
        });
        Schema::create('moderation_decisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->constrained('users');
            $t->string('subject_type');
            $t->unsignedBigInteger('subject_id');
            $t->string('status');
            $t->text('reason')->nullable();
            $t->timestamps();
            $t->index(['subject_type', 'subject_id']);
        });
        Schema::create('delivery_events', function (Blueprint $t) {
            $t->id();
            $t->uuid('delivery_id');
            $t->string('kind', 20);
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['delivery_id', 'kind']);
        });
        Schema::table('impressions', function (Blueprint $t) {
            $t->index(['advertiser_id', 'created_at']);
            $t->index(['ad_unit_id', 'created_at']);
        });
        Schema::table('clicks', function (Blueprint $t) {
            $t->index(['advertiser_id', 'created_at']);
            $t->index(['ad_unit_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_events');
        Schema::dropIfExists('moderation_decisions');
        Schema::table('campaigns', function (Blueprint $t) {
            $t->dropSoftDeletes();
            $t->dropColumn('request_key');
        });
        Schema::table('ads', function (Blueprint $t) {
            $t->dropSoftDeletes();
            $t->dropColumn('review_reason');
        });
        Schema::table('ad_units', function (Blueprint $t) {
            $t->dropSoftDeletes();
            $t->dropColumn('last_seen_at');
        });
        Schema::table('publishers', function (Blueprint $t) {
            $t->dropColumn(['verification_token', 'verified_at', 'review_reason']);
        });
        foreach (['impressions', 'clicks'] as $name) {
            Schema::table($name, function (Blueprint $t) {
                $t->dropIndex(['advertiser_id', 'created_at']);
                $t->dropIndex(['ad_unit_id', 'created_at']);
            });
        }
    }
};

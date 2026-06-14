<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('workspace_id')->index();
            $table->string('central_user_id')->index();
            $table->unsignedBigInteger('workspace_member_id')->nullable()->index();
            $table->string('actor_name');
            $table->string('action')->index();
            $table->string('subject_type')->index();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};

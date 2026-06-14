<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('group')->default('general');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('workspace_id')->index();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['workspace_id', 'slug']);
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();

            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('workspace_members', function (Blueprint $table) {
            $table->id();
            $table->string('workspace_id')->index();
            $table->string('central_user_id')->index();
            $table->string('name');
            $table->string('email')->nullable();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['workspace_id', 'central_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_members');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};

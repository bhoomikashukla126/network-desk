<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cable_segments', function (Blueprint $table) {
            $table->id();
            $table->string('workspace_id')->index();
            $table->foreignId('from_point_id')->constrained('network_points')->cascadeOnDelete();
            $table->foreignId('to_point_id')->constrained('network_points')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('cable_type', 32)->default('fiber');
            $table->string('status', 32)->default('active');
            $table->decimal('length_m', 10, 2)->nullable();
            $table->json('path')->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cable_segments');
    }
};

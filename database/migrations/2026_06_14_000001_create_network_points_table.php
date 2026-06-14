<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_points', function (Blueprint $table) {
            $table->id();
            $table->string('workspace_id')->index();
            $table->string('name');
            $table->string('type', 32)->default('junction');
            $table->string('status', 32)->default('active');
            $table->string('area')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->unsignedSmallInteger('port_count')->nullable();
            $table->json('metadata')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'type']);
            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'area']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_points');
    }
};

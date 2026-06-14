<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_cable_types', function (Blueprint $table) {
            $table->id();
            $table->string('workspace_id')->index();
            $table->string('key', 32);
            $table->string('label', 80);
            $table->string('color', 7)->default('#64748b');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['workspace_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_cable_types');
    }
};

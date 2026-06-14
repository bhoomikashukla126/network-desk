<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_point_id')->constrained('network_points')->cascadeOnDelete();
            $table->string('path');
            $table->string('url');
            $table->string('caption')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_images');
    }
};

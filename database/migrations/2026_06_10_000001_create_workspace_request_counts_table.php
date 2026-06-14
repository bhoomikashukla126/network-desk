<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_request_counts', function (Blueprint $table) {
            $table->string('workspace_id')->primary();
            $table->unsignedBigInteger('request_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_request_counts');
    }
};

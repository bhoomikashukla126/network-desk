<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cable_core_ends', function (Blueprint $table) {
            $table->unique('network_point_port_id');
        });
    }

    public function down(): void
    {
        Schema::table('cable_core_ends', function (Blueprint $table) {
            $table->dropUnique(['network_point_port_id']);
        });
    }
};

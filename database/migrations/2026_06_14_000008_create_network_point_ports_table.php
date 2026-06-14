<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_point_ports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_point_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('direction', 16);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['network_point_id', 'label', 'direction']);
        });

        Schema::table('cable_core_ends', function (Blueprint $table) {
            $table->foreignId('network_point_port_id')
                ->nullable()
                ->after('network_point_id')
                ->constrained('network_point_ports')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cable_core_ends', function (Blueprint $table) {
            $table->dropConstrainedForeignId('network_point_port_id');
        });

        Schema::dropIfExists('network_point_ports');
    }
};

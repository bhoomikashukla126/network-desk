<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cable_segments', function (Blueprint $table) {
            $table->unsignedSmallInteger('core_count')->nullable()->after('length_m');
        });

        Schema::create('cable_cores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cable_segment_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('core_number');
            $table->string('color', 16);
            $table->string('label')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();

            $table->unique(['cable_segment_id', 'core_number']);
        });

        Schema::create('cable_core_ends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cable_core_id')->constrained()->cascadeOnDelete();
            $table->string('side', 8);
            $table->foreignId('network_point_id')->constrained();
            $table->string('connection_type', 16)->nullable();
            $table->foreignId('connected_core_end_id')->nullable()->constrained('cable_core_ends')->nullOnDelete();
            $table->string('device_port_label')->nullable();
            $table->string('device_port_direction', 16)->nullable();
            $table->timestamps();

            $table->unique(['cable_core_id', 'side']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cable_core_ends');
        Schema::dropIfExists('cable_cores');

        Schema::table('cable_segments', function (Blueprint $table) {
            $table->dropColumn('core_count');
        });
    }
};

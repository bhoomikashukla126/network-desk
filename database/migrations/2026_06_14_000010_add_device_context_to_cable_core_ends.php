<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cable_core_ends', function (Blueprint $table) {
            $table->foreignId('network_point_device_id')
                ->nullable()
                ->after('network_point_port_id')
                ->constrained('network_point_devices')
                ->nullOnDelete();
            $table->string('device_type', 32)->nullable()->after('network_point_device_id');
            $table->string('device_label')->nullable()->after('device_type');
        });
    }

    public function down(): void
    {
        Schema::table('cable_core_ends', function (Blueprint $table) {
            $table->dropConstrainedForeignId('network_point_device_id');
            $table->dropColumn(['device_type', 'device_label']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_point_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_point_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('type', 32);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['network_point_id', 'label']);
        });

        Schema::table('network_point_ports', function (Blueprint $table) {
            $table->foreignId('network_point_device_id')
                ->nullable()
                ->after('network_point_id')
                ->constrained('network_point_devices')
                ->cascadeOnDelete();
        });

        if (Schema::hasTable('network_point_ports')) {
            $pointIds = DB::table('network_point_ports')
                ->distinct()
                ->pluck('network_point_id');

            foreach ($pointIds as $pointId) {
                $point = DB::table('network_points')->where('id', $pointId)->first();

                if (! $point) {
                    continue;
                }

                $types = json_decode($point->types ?? '[]', true);
                $type = is_array($types) && $types !== [] ? $types[0] : ($point->type ?? 'junction');

                $deviceId = DB::table('network_point_devices')->insertGetId([
                    'network_point_id' => $pointId,
                    'label' => 'Device 1',
                    'type' => $type,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('network_point_ports')
                    ->where('network_point_id', $pointId)
                    ->update(['network_point_device_id' => $deviceId]);
            }
        }

        Schema::table('network_point_ports', function (Blueprint $table) {
            $table->dropUnique(['network_point_id', 'label', 'direction']);
            $table->unique(['network_point_device_id', 'label', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::table('network_point_ports', function (Blueprint $table) {
            $table->dropUnique(['network_point_device_id', 'label', 'direction']);
            $table->unique(['network_point_id', 'label', 'direction']);
            $table->dropConstrainedForeignId('network_point_device_id');
        });

        Schema::dropIfExists('network_point_devices');
    }
};

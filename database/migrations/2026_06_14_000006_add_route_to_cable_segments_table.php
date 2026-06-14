<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cable_segments', function (Blueprint $table) {
            $table->json('route')->nullable()->after('path');
        });

        DB::table('cable_segments')->orderBy('id')->lazyById()->each(function ($cable) {
            $route = [
                ['type' => 'point', 'point_id' => (int) $cable->from_point_id],
            ];

            $path = json_decode($cable->path ?? '[]', true);

            if (is_array($path)) {
                foreach ($path as $pair) {
                    if (is_array($pair) && count($pair) === 2) {
                        $route[] = [
                            'type' => 'bend',
                            'lat' => (float) $pair[0],
                            'lng' => (float) $pair[1],
                        ];
                    }
                }
            }

            $route[] = ['type' => 'point', 'point_id' => (int) $cable->to_point_id];

            DB::table('cable_segments')->where('id', $cable->id)->update([
                'route' => json_encode($route),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('cable_segments', function (Blueprint $table) {
            $table->dropColumn('route');
        });
    }
};

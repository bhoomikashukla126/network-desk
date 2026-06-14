<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('network_points', function (Blueprint $table) {
            $table->json('types')->nullable()->after('type');
        });

        DB::table('network_points')->orderBy('id')->lazyById()->each(function ($point) {
            $type = $point->type ?: 'junction';

            DB::table('network_points')->where('id', $point->id)->update([
                'types' => json_encode([$type]),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('network_points', function (Blueprint $table) {
            $table->dropColumn('types');
        });
    }
};

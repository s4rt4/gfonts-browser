<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('font_families', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('display_name');
        });

        // Backfill existing rows
        DB::table('font_families')->orderBy('id')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('font_families')
                    ->where('id', $row->id)
                    ->update(['slug' => strtolower(str_replace(' ', '-', $row->family))]);
            }
        });

        // Now safe to make it unique + indexed
        Schema::table('font_families', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('font_families', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};

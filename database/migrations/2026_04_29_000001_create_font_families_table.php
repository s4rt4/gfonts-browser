<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('font_families', function (Blueprint $table) {
            $table->id();
            $table->string('family')->unique();
            $table->string('display_name')->nullable();
            $table->string('category')->index();
            $table->string('stroke')->nullable();

            $table->json('subsets')->nullable();
            $table->json('axes')->nullable();
            $table->json('designers')->nullable();
            $table->json('languages')->nullable();
            $table->json('classifications')->nullable();
            $table->json('color_capabilities')->nullable();

            $table->integer('popularity')->nullable()->index();
            $table->integer('trending')->nullable()->index();
            $table->integer('default_sort')->nullable();

            $table->boolean('is_noto')->default(false);
            $table->boolean('is_brand_font')->default(false);
            $table->boolean('is_open_source')->default(true);
            $table->boolean('is_variable')->default(false)->index();

            $table->date('date_added')->nullable();
            $table->date('last_modified')->nullable();

            $table->unsignedInteger('file_count')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('font_families');
    }
};

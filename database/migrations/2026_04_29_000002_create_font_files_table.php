<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('font_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('font_family_id')
                ->constrained('font_families')
                ->cascadeOnDelete();

            $table->string('filename')->unique();
            $table->string('subfamily')->nullable();
            $table->unsignedSmallInteger('weight')->nullable()->index();
            $table->string('style', 16)->default('normal');

            $table->boolean('is_variable')->default(false);
            $table->string('axes_in_filename')->nullable();

            $table->unsignedInteger('file_size')->nullable();

            $table->timestamps();

            $table->index(['font_family_id', 'weight', 'style']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('font_files');
    }
};

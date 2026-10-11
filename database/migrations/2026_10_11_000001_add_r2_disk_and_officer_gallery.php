<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('disk')->default('public')->after('id');
        });

        Schema::create('officer_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('officer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique(['officer_id', 'media_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('officer_media');
        Schema::table('media', fn (Blueprint $table) => $table->dropColumn('disk'));
    }
};

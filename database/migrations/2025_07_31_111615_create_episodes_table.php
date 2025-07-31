<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('podcast_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('audio_url');
            $table->string('guid')->unique();
            $table->string('thumbnail_url')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->string('duration_formatted')->nullable(); // e.g., "1:23:45"
            $table->timestamp('published_at');
            $table->timestamps();
            
            $table->index(['podcast_id', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};

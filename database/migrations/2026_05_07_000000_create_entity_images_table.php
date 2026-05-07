<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_images', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // 'Person', 'Planet', 'Film', 'Starship', 'Vehicle', 'Species'
            $table->unsignedBigInteger('entity_id');
            $table->text('image_url')->nullable();
            $table->string('source')->default('pexels'); // 'pexels'
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->unique(['entity_type', 'entity_id']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_images');
    }
};

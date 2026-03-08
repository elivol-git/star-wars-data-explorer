<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up():void
    {
        if (!Schema::hasTable('films')) {

            Schema::create('films', function (Blueprint $table) {
                $table->id();
                $table->string('title')->default('')->index();
                $table->unsignedTinyInteger('episode_id')->nullable()->index();
                $table->text('opening_crawl')->nullable();
                $table->string('director')->default('')->nullable();
                $table->string('producer')->default('');
                $table->date('release_date')->nullable();
                $table->timestamp('created')->nullable();
                $table->timestamp('edited')->nullable();
                $table->string('url')->unique();
                $table->timestamps();
                $table->index('title', 'idx_film_title');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down():void
    {
        Schema::dropIfExists('films');
    }
};

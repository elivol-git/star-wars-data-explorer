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
        if (!Schema::hasTable('planets')) {
            Schema::create('planets', function (Blueprint $table) {
                $table->id();
                $table->string('name')->index();
                $table->unsignedInteger('rotation_period')->nullable();
                $table->unsignedInteger('orbital_period')->nullable();
                $table->unsignedInteger('diameter')->nullable();
                $table->string('climate')->default('');
                $table->string('gravity')->default('');
                $table->string('terrain')->default('');
                $table->decimal('surface_water', 5, 2)->unsigned()->nullable();
                $table->unsignedBigInteger('population')->nullable();
                $table->timestamp('created')->nullable();
                $table->timestamp('edited')->nullable();
                $table->string('url')->unique();
                $table->timestamps();
                $table->index('name', 'idx_planets_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('planets');
    }
};

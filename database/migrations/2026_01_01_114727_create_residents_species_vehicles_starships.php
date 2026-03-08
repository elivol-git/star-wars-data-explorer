<?php

use App\Models\Pivots\PivotTables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        if (!Schema::hasTable('people')) {
            Schema::create('people', function (Blueprint $table) {
                $table->id();
                $table->string('name')->index();
                $table->unsignedInteger('height')->nullable();
                $table->unsignedInteger('mass')->nullable();
                $table->string('hair_color')->nullable();
                $table->string('skin_color')->nullable();
                $table->string('eye_color')->nullable();
                $table->string('birth_year')->nullable();
                $table->string('gender')->nullable();
                $table->string('homeworld')->nullable();
                $table->timestamp('created')->nullable();
                $table->timestamp('edited')->nullable();
                $table->string('url')->unique();
                $table->foreignId('homeworld_id')->nullable()->constrained('planets');
                $table->index('name', 'idx_name_people');
            });
        }

        if (!Schema::hasTable('species')) {
            Schema::create('species', function (Blueprint $table) {
                $table->id();
                $table->string('name')->index();
                $table->string('classification')->nullable();
                $table->string('designation')->nullable();
                $table->unsignedInteger('average_height')->nullable();
                $table->string('skin_colors')->nullable();
                $table->string('hair_colors')->nullable();
                $table->string('eye_colors')->nullable();
                $table->unsignedInteger('average_lifespan')->nullable();
                $table->string('homeworld')->nullable();
                $table->string('language')->nullable();
                $table->timestamp('created')->nullable();
                $table->timestamp('edited')->nullable();
                $table->string('url')->unique();
                $table->foreignId('homeworld_id')->nullable()->index()->constrained('planets');
                $table->index('name', 'idx_name_species');
            });
        }

        if (!Schema::hasTable('vehicles')) {
            Schema::create('vehicles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->index();
                $table->string('model')->nullable();
                $table->string('manufacturer')->nullable();
                $table->unsignedBigInteger('cost_in_credits')->nullable();
                $table->float('length')->unsigned()->nullable();
                $table->unsignedInteger('max_atmosphering_speed')->nullable();
                $table->unsignedInteger('crew')->nullable();
                $table->unsignedInteger('passengers')->nullable();
                $table->unsignedBigInteger('cargo_capacity')->nullable();
                $table->string('consumables')->nullable();
                $table->string('vehicle_class')->nullable();
                $table->timestamp('created')->nullable();
                $table->timestamp('edited')->nullable();
                $table->string('url')->unique();
                $table->index('name', 'idx_vehicle_name');
            });
        }

        if (!Schema::hasTable('starships')) {
            Schema::create('starships', function (Blueprint $table) {
                $table->id();
                $table->string('name')->index();
                $table->string('model')->nullable();
                $table->string('manufacturer')->nullable();
                $table->unsignedBigInteger('cost_in_credits')->nullable();
                $table->decimal('length', 10, 2)->unsigned()->nullable();
                $table->unsignedInteger('max_atmosphering_speed')->nullable();
                $table->unsignedInteger('crew')->nullable();
                $table->unsignedInteger('passengers')->nullable();
                $table->unsignedBigInteger('cargo_capacity')->nullable();
                $table->string('consumables')->nullable();
                $table->decimal('hyperdrive_rating', 3, 1)->unsigned()->nullable();
                $table->unsignedInteger('MGLT')->nullable();
                $table->string('starship_class')->nullable();
                $table->timestamp('created')->nullable();
                $table->timestamp('edited')->nullable();
                $table->string('url')->unique();
                $table->index('name', 'idx_starship_name');
            });
        }

        //Pivot tables
        if (!Schema::hasTable(PivotTables::PLANET_PERSON)) {
            Schema::create(PivotTables::PLANET_PERSON, function (Blueprint $table) {
                $table->foreignId('planet_id')->constrained()->cascadeOnDelete();
                $table->foreignId('person_id')->constrained()->cascadeOnDelete();
                $table->primary(['planet_id', 'person_id']);
                $table->index(['person_id', 'planet_id']);
            });
        }

        if (!Schema::hasTable(PivotTables::FILM_PLANET)) {
            Schema::create(PivotTables::FILM_PLANET, function (Blueprint $table) {
                $table->foreignId('film_id')->constrained()->cascadeOnDelete();
                $table->foreignId('planet_id')->constrained()->cascadeOnDelete();
                $table->primary(['film_id', 'planet_id']);
                $table->index(['planet_id', 'film_id']);
            });
        }

        if (!Schema::hasTable(PivotTables::FILM_PERSON)) {
            Schema::create(PivotTables::FILM_PERSON, function (Blueprint $table) {
                $table->foreignId('film_id')->constrained()->cascadeOnDelete();
                $table->foreignId('person_id')->constrained()->cascadeOnDelete();
                $table->primary(['film_id', 'person_id']);
                $table->index(['person_id', 'film_id']);
            });
        }

        if (!Schema::hasTable(PivotTables::FILM_SPECIES)) {
            Schema::create(PivotTables::FILM_SPECIES, function (Blueprint $table) {
                $table->foreignId('film_id')->constrained()->cascadeOnDelete();
                $table->foreignId('species_id')->constrained()->cascadeOnDelete();
                $table->primary(['film_id', 'species_id']);
                $table->index(['species_id', 'film_id']);
            });
        }

        if (!Schema::hasTable(PivotTables::FILM_VEHICLE)) {
            Schema::create(PivotTables::FILM_VEHICLE, function (Blueprint $table) {
                $table->foreignId('film_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
                $table->primary(['film_id', 'vehicle_id']);
                $table->index(['vehicle_id', 'film_id']);
            });
        }

        if (!Schema::hasTable(PivotTables::FILM_STARSHIP)) {
            Schema::create(PivotTables::FILM_STARSHIP, function (Blueprint $table) {
                $table->foreignId('film_id')->constrained()->cascadeOnDelete();
                $table->foreignId('starship_id')->constrained()->cascadeOnDelete();

                $table->primary(['film_id', 'starship_id']);
            });
        }

        if (!Schema::hasTable(PivotTables::PERSON_SPECIES)) {
            Schema::create(PivotTables::PERSON_SPECIES, function (Blueprint $table) {
                $table->foreignId('person_id')->constrained()->cascadeOnDelete();
                $table->foreignId('species_id')->constrained()->cascadeOnDelete();
                $table->primary(['person_id', 'species_id']);
                $table->index(['species_id', 'person_id']);
            });
        }

        if (!Schema::hasTable(PivotTables::PERSON_VEHICLE)) {
            Schema::create(PivotTables::PERSON_VEHICLE, function (Blueprint $table) {
                $table->foreignId('person_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
                $table->primary(['person_id', 'vehicle_id']);
                $table->index(['vehicle_id', 'person_id']);
            });
        }

        if (!Schema::hasTable(PivotTables::PERSON_STARSHIP)) {
            Schema::create(PivotTables::PERSON_STARSHIP, function (Blueprint $table) {
                $table->foreignId('person_id')->constrained()->cascadeOnDelete();
                $table->foreignId('starship_id')->constrained()->cascadeOnDelete();
                $table->primary(['person_id', 'starship_id']);
                $table->index(['starship_id', 'person_id']);
            });
        }

    }

    public function down(): void
    {
        Schema::dropIfExists(PivotTables::PLANET_PERSON);
        Schema::dropIfExists(PivotTables::PERSON_STARSHIP);
        Schema::dropIfExists(PivotTables::PERSON_VEHICLE);
        Schema::dropIfExists(PivotTables::PERSON_SPECIES);
        Schema::dropIfExists(PivotTables::PERSON_STARSHIP);

        Schema::dropIfExists(PivotTables::FILM_VEHICLE);
        Schema::dropIfExists(PivotTables::FILM_SPECIES);
        Schema::dropIfExists(PivotTables::FILM_PERSON);
        Schema::dropIfExists(PivotTables::FILM_PLANET);
        Schema::dropIfExists(PivotTables::FILM_STARSHIP);

        Schema::dropIfExists('starships');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('species');
        Schema::dropIfExists('people');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dogs', function (Blueprint $table) {
            $table->id();
            $table->string('bg_dog_id')->unique()->nullable();
            $table->string('registered_name')->nullable();
            $table->string('call_name')->nullable();
            $table->string('sex')->nullable();
            $table->date('birth_date')->nullable()->index();
            $table->date('death_date')->nullable();
            $table->integer('age_years')->nullable()->index();
            $table->string('color')->nullable();

            // Breeder link
            $table->foreignId('breeder_id')->nullable()->constrained()->nullOnDelete();
            $table->string('breeder_name')->nullable();

            // Health clearances
            $table->string('hip_rating')->nullable();
            $table->string('elbow_rating')->nullable();
            $table->string('heart_status')->nullable();
            $table->string('eye_status')->nullable();
            $table->string('dna_status')->nullable();
            $table->boolean('ofa_certified')->default(false);
            $table->json('health_data')->nullable();

            // Pedigree
            $table->string('sire_id')->nullable();
            $table->string('dam_id')->nullable();
            $table->string('sire_name')->nullable();
            $table->string('dam_name')->nullable();

            // Images
            $table->string('primary_image')->nullable();
            $table->json('images')->nullable();

            // Grading
            $table->decimal('grade', 5, 2)->nullable()->index();
            $table->decimal('health_score', 5, 2)->nullable();
            $table->decimal('longevity_score', 5, 2)->nullable();
            $table->decimal('breeder_score', 5, 2)->nullable();

            $table->timestamps();

            $table->fulltext(['registered_name', 'call_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dogs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breeders', function (Blueprint $table) {
            $table->id();
            $table->string('bg_person_id')->unique()->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('kennel_name')->nullable()->index();
            $table->string('city')->nullable();
            $table->string('state')->nullable()->index();
            $table->string('country')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->integer('dogs_bred_count')->default(0);
            $table->integer('litters_count')->default(0);
            $table->decimal('grade', 5, 2)->nullable()->index();
            $table->json('health_stats')->nullable();
            $table->timestamps();

            $table->fulltext(['first_name', 'last_name', 'kennel_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breeders');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('litters', function (Blueprint $table) {
            $table->id();
            $table->string('bg_litter_id')->unique()->nullable();
            $table->date('birth_date')->nullable()->index();
            $table->integer('birth_year')->nullable()->index();

            // Parents
            $table->string('sire_id')->nullable();
            $table->string('dam_id')->nullable();
            $table->string('sire_name')->nullable();
            $table->string('dam_name')->nullable();

            // Breeder
            $table->foreignId('breeder_id')->nullable()->constrained()->nullOnDelete();
            $table->string('breeder_name')->nullable();

            // Litter stats
            $table->integer('puppies_count')->nullable();
            $table->integer('males_count')->nullable();
            $table->integer('females_count')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('litters');
    }
};

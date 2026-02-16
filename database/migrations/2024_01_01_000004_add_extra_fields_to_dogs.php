<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dogs', function (Blueprint $table) {
            // Registration & identification
            $table->string('registration_number')->nullable();
            $table->string('dna_number')->nullable();
            $table->string('microchip')->nullable();

            // Titles and achievements
            $table->text('titles')->nullable();

            // Owner information
            $table->string('owner_name')->nullable();
            $table->string('owner_id')->nullable();

            // Physical characteristics
            $table->string('weight')->nullable();
            $table->string('height')->nullable();
            $table->string('bite')->nullable();
            $table->string('tail')->nullable();
            $table->string('eye_color')->nullable();

            // Health - DM (Degenerative Myelopathy)
            $table->string('dm_status')->nullable();

            // Breeding info
            $table->string('stud_book')->nullable();
            $table->boolean('frozen_semen')->default(false);

            // Rescue
            $table->string('rescue_type')->nullable();

            // Litter link
            $table->string('litter_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('dogs', function (Blueprint $table) {
            $table->dropColumn([
                'registration_number', 'dna_number', 'microchip',
                'titles', 'owner_name', 'owner_id',
                'weight', 'height', 'bite', 'tail', 'eye_color',
                'dm_status', 'stud_book', 'frozen_semen',
                'rescue_type', 'litter_id'
            ]);
        });
    }
};

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
        Schema::create('cell_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cell_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->integer('age')->nullable();

            // Celugrama boolean flags
            $table->boolean('is_new')->default(true); // N.C.
            $table->boolean('went_to_encounter')->default(false); // EN
            $table->boolean('is_baptized')->default(false); // BA
            $table->boolean('attends_church')->default(false); // A.I.
            $table->boolean('attends_school')->default(false); // A.E.

            $table->string('ministry')->nullable(); // S.M.

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cell_members');
    }
};

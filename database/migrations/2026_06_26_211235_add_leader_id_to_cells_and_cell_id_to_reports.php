<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add leader_id to cells
        Schema::table('cells', function (Blueprint $table) {
            $table->foreignId('leader_id')->nullable()->constrained('users')->nullOnDelete();
        });

        // 2. Add cell_id to reports
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('cell_id')->nullable()->constrained('cells')->cascadeOnDelete();
        });

        // 3. Data Migration
        // Set cells.leader_id from users.id where user is the leader of the cell
        DB::statement("UPDATE cells SET leader_id = (SELECT id FROM users WHERE users.cell_id = cells.id AND users.role = 'leader' LIMIT 1)");
        
        // Set reports.cell_id from users.cell_id
        DB::statement("UPDATE reports SET cell_id = (SELECT cell_id FROM users WHERE users.id = reports.user_id LIMIT 1)");

        // 4. Drop cell_id from users
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['cell_id']);
            $table->dropColumn('cell_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Add cell_id back to users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('cell_id')->nullable()->constrained('cells')->nullOnDelete();
        });

        // 2. Data Migration back
        DB::statement("UPDATE users SET cell_id = (SELECT id FROM cells WHERE cells.leader_id = users.id LIMIT 1) WHERE role = 'leader'");

        // 3. Drop columns
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['cell_id']);
            $table->dropColumn('cell_id');
        });

        Schema::table('cells', function (Blueprint $table) {
            $table->dropForeign(['leader_id']);
            $table->dropColumn('leader_id');
        });
    }
};

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
        if (!Schema::hasColumn('books', 'cover_path')) {
            Schema::table('books', function (Blueprint $table) {
                $table->string('cover_path')->nullable()->after('file_path');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('books', 'cover_path')) {
            Schema::table('books', function (Blueprint $table) {
                $table->dropColumn('cover_path');
            });
        }
    }
};

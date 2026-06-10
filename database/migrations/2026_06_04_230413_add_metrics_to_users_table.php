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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('rating', 3, 1)->default(0.0)->after('status');
            $table->integer('reports_count')->default(0)->after('rating');
            $table->integer('compliance_percentage')->default(0)->after('reports_count');
            $table->json('compliments')->nullable()->after('compliance_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rating', 'reports_count', 'compliance_percentage', 'compliments']);
        });
    }
};

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
        Schema::table('projects', function (Blueprint $table) {
            $table->text('initial_budget')->nullable()->after('budget');
        });

        // Copy existing budget to initial_budget for old projects
        \Illuminate\Support\Facades\DB::statement('UPDATE projects SET initial_budget = budget');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('initial_budget');
        });
    }
};

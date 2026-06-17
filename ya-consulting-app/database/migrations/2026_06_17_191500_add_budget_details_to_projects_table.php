<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('budget_labor', 15, 2)->default(0)->after('budget');
            $table->decimal('budget_material', 15, 2)->default(0)->after('budget_labor');
            $table->decimal('budget_transport', 15, 2)->default(0)->after('budget_material');
            $table->decimal('budget_other', 15, 2)->default(0)->after('budget_transport');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['budget_labor', 'budget_material', 'budget_transport', 'budget_other']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('budget')->change();
            $table->text('budget_labor')->change();
            $table->text('budget_material')->change();
            $table->text('budget_transport')->change();
            $table->text('budget_other')->change();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->text('amount')->change();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('budget', 15, 2)->change();
            $table->decimal('budget_labor', 15, 2)->change();
            $table->decimal('budget_material', 15, 2)->change();
            $table->decimal('budget_transport', 15, 2)->change();
            $table->decimal('budget_other', 15, 2)->change();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->change();
        });
    }
};

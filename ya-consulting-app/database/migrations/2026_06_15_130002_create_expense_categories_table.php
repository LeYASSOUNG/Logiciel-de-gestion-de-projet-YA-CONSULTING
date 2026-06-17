<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('parent_type', ['main_oeuvre', 'materiel', 'transport', 'autres'])
                  ->default('autres');
            $table->boolean('is_custom')->default(false);
            $table->string('color', 7)->default('#6366f1'); // hex color for UI
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('planned_end_date');
            $table->date('actual_end_date')->nullable();
            $table->decimal('budget', 15, 2);
            $table->enum('status', ['en_cours', 'termine', 'en_pause'])->default('en_cours');
            $table->string('supplier_contact')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};

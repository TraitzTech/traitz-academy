<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_instalment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('name');                               // e.g. "3-Month Plan"
            $table->unsignedTinyInteger('number_of_instalments');
            $table->decimal('amount_per_instalment', 10, 2);
            $table->unsignedTinyInteger('interval_in_days')->default(30); // days between instalments
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_instalment_plans');
    }
};

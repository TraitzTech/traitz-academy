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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 5)->default('XAF');
            $table->string('payment_method')->nullable();
            $table->string('receipt_reference')->nullable();
            $table->string('vendor')->nullable();
            $table->date('expense_date');
            $table->string('status')->default('approved');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['expense_date', 'status']);
            $table->index('recorded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};

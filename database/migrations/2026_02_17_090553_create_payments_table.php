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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('receipt_number')->nullable()->unique();
            $table->string('mesomb_transaction_id')->nullable()->index();
            $table->string('payer_phone');
            $table->string('provider', 20);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('XAF');
            $table->string('payment_type', 20)->default('full');
            $table->unsignedInteger('installment_number')->default(1);
            $table->unsignedInteger('total_installments')->default(1);
            $table->string('status', 20)->default('pending')->index();
            $table->text('failure_reason')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

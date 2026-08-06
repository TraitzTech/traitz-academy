<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('receipt_number')->nullable()->unique();
            $table->string('mesomb_transaction_id')->nullable()->index();
            $table->string('payer_phone');
            $table->string('provider', 20);
            $table->decimal('amount', 10, 2);
            $table->decimal('base_amount', 10, 2);
            $table->decimal('surcharge_amount', 10, 2)->default(0);
            $table->decimal('surcharge_percentage', 5, 2)->default(0);
            $table->string('currency', 10)->default('XAF');
            $table->string('payment_type', 20)->default('full');
            $table->string('status', 20)->default('pending')->index();
            $table->text('failure_reason')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_payments');
    }
};

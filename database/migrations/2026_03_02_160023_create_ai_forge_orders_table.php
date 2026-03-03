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
        Schema::create('ai_forge_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_forge_event_id')->constrained('ai_forge_events')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');
            $table->string('country')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('surcharge_amount', 10, 2)->default(0);
            $table->decimal('surcharge_percentage', 5, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('currency')->default('XAF');
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('pending');
            $table->string('payment_provider')->nullable();
            $table->string('payer_phone')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('receipt_number')->nullable();
            $table->string('failure_reason')->nullable();
            $table->json('raw_response')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_forge_orders');
    }
};

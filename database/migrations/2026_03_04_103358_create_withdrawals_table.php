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
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('service');
            $table->string('receiver');
            $table->string('receiver_name')->nullable();
            $table->string('currency')->default('XAF');
            $table->string('country')->default('CM');
            $table->string('status')->default('pending');
            $table->string('mesomb_transaction_id')->nullable();
            $table->string('reference')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};

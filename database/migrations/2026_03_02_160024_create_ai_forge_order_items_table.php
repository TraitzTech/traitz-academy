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
        Schema::create('ai_forge_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_forge_order_id')->constrained('ai_forge_orders')->cascadeOnDelete();
            $table->foreignId('ai_forge_swag_id')->constrained('ai_forge_swags')->cascadeOnDelete();
            $table->string('variation')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_forge_order_items');
    }
};

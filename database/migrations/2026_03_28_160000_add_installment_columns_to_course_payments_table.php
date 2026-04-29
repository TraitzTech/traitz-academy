<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_payments', function (Blueprint $table) {
            $table->unsignedInteger('installment_number')->default(1)->after('payment_type');
            $table->unsignedInteger('total_installments')->default(1)->after('installment_number');
        });
    }

    public function down(): void
    {
        Schema::table('course_payments', function (Blueprint $table) {
            $table->dropColumn(['installment_number', 'total_installments']);
        });
    }
};

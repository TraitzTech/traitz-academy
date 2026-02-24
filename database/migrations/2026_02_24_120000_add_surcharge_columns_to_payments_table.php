<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('base_amount', 10, 2)->nullable()->after('amount');
            $table->decimal('surcharge_amount', 10, 2)->default(0)->after('base_amount');
            $table->decimal('surcharge_percentage', 5, 2)->default(0)->after('surcharge_amount');
        });

        DB::table('payments')->update([
            'base_amount' => DB::raw('amount'),
            'surcharge_amount' => 0,
            'surcharge_percentage' => 0,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['base_amount', 'surcharge_amount', 'surcharge_percentage']);
        });
    }
};

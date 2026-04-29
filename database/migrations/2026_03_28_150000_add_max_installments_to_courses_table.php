<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedInteger('max_installments')->default(1)->after('sale_price');
        });

        if (Schema::hasTable('course_instalment_plans')) {
            $aggregated = DB::table('course_instalment_plans')
                ->selectRaw('course_id, MAX(number_of_instalments) as n')
                ->where('is_active', true)
                ->groupBy('course_id');

            foreach ($aggregated->get() as $row) {
                DB::table('courses')
                    ->where('id', $row->course_id)
                    ->update(['max_installments' => max(1, (int) $row->n)]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('max_installments');
        });
    }
};

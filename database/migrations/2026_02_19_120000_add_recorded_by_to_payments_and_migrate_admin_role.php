<?php

use App\Models\User;
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
            $table->foreignId('recorded_by')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('payments')
            ->where('manual_entry', true)
            ->whereNull('recorded_by')
            ->update([
                'recorded_by' => DB::raw('updated_by'),
            ]);

        DB::table('users')
            ->where('role', User::ROLE_ADMIN_LEGACY)
            ->update(['role' => User::ROLE_CTO]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by');
        });
    }
};

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
        Schema::table('payments', function (Blueprint $table) {
            $table->boolean('manual_entry')->default(false)->after('status');
            $table->string('payment_channel', 30)->nullable()->after('provider');
            $table->text('admin_notes')->nullable()->after('failure_reason');
            $table->foreignId('updated_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();

            $table->index(['manual_entry', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn(['manual_entry', 'payment_channel', 'admin_notes']);
        });
    }
};

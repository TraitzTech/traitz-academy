<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets a supervisor attach a resource to a Program they oversee (interns-only,
     * never public). attachable_id stays null for the existing admin-authored,
     * publicly listed library resources.
     */
    public function up(): void
    {
        Schema::table('learning_resources', function (Blueprint $table) {
            $table->string('attachable_type')->nullable()->after('id');
            $table->unsignedBigInteger('attachable_id')->nullable()->after('attachable_type');
            $table->foreignId('created_by')->nullable()->after('attachable_id')->constrained('users')->nullOnDelete();
            $table->string('audience')->default('all_program_interns')->after('created_by');
            $table->index(['attachable_type', 'attachable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('learning_resources', function (Blueprint $table) {
            $table->dropIndex(['attachable_type', 'attachable_id']);
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['attachable_type', 'attachable_id', 'audience']);
        });
    }
};

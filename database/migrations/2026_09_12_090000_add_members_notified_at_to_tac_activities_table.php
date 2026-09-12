<?php

use App\Models\TacActivity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tac_activities', function (Blueprint $table) {
            // When the "new activity" email run was kicked off for this
            // activity — set at dispatch time, not once every recipient has
            // actually been sent (a large run can spill into the next day if
            // the mailer's rate limit kicks in). Guards against notifying
            // members twice for the same activity.
            $table->timestamp('members_notified_at')->nullable()->after('published_at');
        });

        // Without this, every activity published before this feature shipped
        // has a blank members_notified_at — so the next time someone flips
        // its status (even draft -> published again), the whole track (or
        // community) would get emailed about an activity they've known about
        // for months. Backfill existing published activities as already
        // notified so only genuinely new publishes trigger the mail run.
        TacActivity::query()
            ->where('status', TacActivity::STATUS_PUBLISHED)
            ->update(['members_notified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('tac_activities', function (Blueprint $table) {
            $table->dropColumn('members_notified_at');
        });
    }
};

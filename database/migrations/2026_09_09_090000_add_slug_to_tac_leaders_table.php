<?php

use App\Models\TacLeader;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tac_leaders', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        // Backfill existing leaders so nobody ends up without a public URL.
        TacLeader::query()->whereNull('slug')->get(['id', 'name'])->each(function (TacLeader $leader) {
            $base = Str::slug($leader->name) ?: 'leader';
            $slug = $base;
            $suffix = 2;

            while (TacLeader::query()->where('slug', $slug)->exists()) {
                $slug = "{$base}-{$suffix}";
                $suffix++;
            }

            $leader->forceFill(['slug' => $slug])->saveQuietly();
        });

        Schema::table('tac_leaders', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });

        Schema::table('tac_leaders', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('tac_leaders', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};

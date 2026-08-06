<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Categories were originally seeded with raw emoji icons; the picker now
     * stores a lucide-icon key instead (see resources/js/utils/categoryIcons.ts).
     * Keyed by slug (ASCII-safe) rather than matching the old emoji bytes
     * directly — multi-byte emoji comparisons are unreliable across
     * migration-file encodings/DB collations.
     */
    private const SLUG_TO_KEY = [
        'programming-development' => 'code',
        'data-science-ai' => 'bot',
        'uiux-design' => 'palette',
        'business-management' => 'bar-chart',
        'cybersecurity' => 'shield',
        'cloud-devops' => 'cloud',
        'digital-marketing' => 'megaphone',
        'finance-accounting' => 'wallet',
    ];

    public function up(): void
    {
        foreach (self::SLUG_TO_KEY as $slug => $key) {
            DB::table('course_categories')->where('slug', $slug)->update(['icon' => $key]);
        }
    }

    public function down(): void
    {
        // Not reversible to the original emoji — icon keys are the new format.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussion_upvotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('upvotable'); // supports both discussions and discussion_replies
            $table->timestamps();

            $table->unique(['user_id', 'upvotable_id', 'upvotable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_upvotes');
    }
};

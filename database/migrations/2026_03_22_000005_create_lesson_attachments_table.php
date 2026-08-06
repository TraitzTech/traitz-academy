<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_lesson_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('file_url');
            $table->string('file_type')->nullable(); // e.g. pdf, docx, zip, png
            $table->unsignedBigInteger('file_size')->nullable(); // in bytes
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_attachments');
    }
};

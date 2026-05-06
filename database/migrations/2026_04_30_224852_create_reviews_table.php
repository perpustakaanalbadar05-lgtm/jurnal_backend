<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paper_id')->constrained('papers')->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->text('comment');
            $table->text('private_comment')->nullable();
            $table->enum('decision', ['accept', 'minor_revision', 'major_revision', 'reject']);
            $table->enum('status', ['pending', 'completed'])->default('completed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};

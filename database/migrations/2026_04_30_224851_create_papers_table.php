<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('papers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('abstract');
            $table->string('keywords')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'under_review', 'accepted', 'revision', 'rejected', 'published'])->default('pending');
            $table->integer('version')->default(1);
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('papers');
    }
};

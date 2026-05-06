<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paper_authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paper_id')->constrained('papers')->onDelete('cascade');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('institution')->nullable();
            $table->boolean('is_corresponding')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paper_authors');
    }
};

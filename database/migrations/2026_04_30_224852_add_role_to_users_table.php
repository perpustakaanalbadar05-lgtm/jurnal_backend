<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Role is already in users table from the initial migration
        // This migration is intentionally empty (handled in create_users_table)
    }

    public function down(): void
    {
        //
    }
};

<?php

declare(strict_types=1);

use Slenix\Database\Migrations\Migration;
use Slenix\Database\Migrations\Schema;
use Slenix\Database\Migrations\Blueprint;

return new class extends Migration
{
    /**
     * Run the migration — creates the users table.
     *
     * Compatible with MySQL, PostgreSQL and SQLite.
     * Column types are automatically translated by Schema/Grammar.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('fname', 100);
            $table->string('lname', 100);
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(false);
            $table->enum('status', ['online','offline'])->default('online');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migration — drops the users table.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
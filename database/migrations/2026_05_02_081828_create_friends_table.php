<?php

declare(strict_types=1);

use Slenix\Database\Migrations\Migration;
use Slenix\Database\Migrations\Schema;
use Slenix\Database\Migrations\Blueprint;

return new class extends Migration
{
    /**
     * Run the migration — creates the friends table.
     *
     * Compatible with MySQL, PostgreSQL and SQLite.
     * Column types are automatically translated by Schema/Grammar.
     */
    public function up(): void
    {
        Schema::create('friends', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'blocked'])->default('pending');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migration — drops the friends table.
     */
    public function down(): void
    {
        Schema::dropIfExists('friends');
    }
};
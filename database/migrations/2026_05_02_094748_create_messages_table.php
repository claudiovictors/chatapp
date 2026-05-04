<?php

declare(strict_types=1);

use Slenix\Database\Migrations\Migration;
use Slenix\Database\Migrations\Schema;
use Slenix\Database\Migrations\Blueprint;

return new class extends Migration {
    /**
     * Run the migration — creates the messages table.
     *
     * Compatible with MySQL, PostgreSQL and SQLite.
     * Column types are automatically translated by Schema/Grammar.
     */
    public function up(): void
{
    Schema::create('messages', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
        $table->text('body')->nullable();
        $table->enum('type', ['text', 'sticker', 'gif'])->default('text'); 
        $table->string('sticker_url', 500)->nullable();
        $table->boolean('is_read')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migration — drops the messages table.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
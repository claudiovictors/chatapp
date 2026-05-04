<?php

declare(strict_types=1);

namespace App\Models;

use Slenix\Database\Model;
use Slenix\Database\Relations\BelongsTo;

class Message extends Model
{
    protected string $table = 'messages';
    protected string $primaryKey = 'id';
    protected array  $fillable   = ['sender_id', 'receiver_id', 'body', 'type', 'sticker_url', 'is_read'];

    protected array  $casts      = [
        'id'          => 'integer',
        'sender_id'   => 'integer',
        'receiver_id' => 'integer',
        'is_read'     => 'boolean',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
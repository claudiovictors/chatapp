<?php

declare(strict_types=1);

namespace App\Models;

use Slenix\Database\Model;
use Slenix\Database\Relations\BelongsTo;

class Friend extends Model
{
    protected string $table = 'friends';
    protected string $primaryKey = 'id';
    protected array $fillable = ['sender_id', 'receiver_id', 'status'];

    protected array $casts = [
        'id'         => 'integer',
        'from_id'    => 'integer',
        'to_id'      => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
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
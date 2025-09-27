<?php

declare(strict_types=1);

namespace App\Models;

use Slenix\Database\Model;

class Message extends Model
{
    protected string $table = 'messages';
    protected string $primaryKey = 'id';
    protected array $fillable = ['user_id', 'friend_id', 'message'];

    protected bool $timestamps = true;

    protected array $casts = [
        'id' => 'int',
        'created_at' => 'datatime',
        'updated_at' => 'datatime'
    ];
}
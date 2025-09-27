<?php

declare(strict_types=1);

namespace App\Models;

use Slenix\Database\Model;

class Friend extends Model
{
    protected string $table = 'friends';
    protected string $primaryKey = 'id';
    protected array $fillable = ['user_id', 'friend_id', 'status'];

    protected array $casts = [
        'id' => 'int',
        'created_at' => 'datatime',
        'updated_at' => 'datatime'
    ];
}
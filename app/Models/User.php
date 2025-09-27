<?php

declare(strict_types=1);

namespace App\Models;

use Slenix\Database\Model;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    protected array $fillable = ['fname', 'lname', 'email', 'password', 'status', 'image'];

    protected array $hidden = ['password'];

    protected bool $timestamps = true;

    protected array $casts = [
        'created_at' => 'datatime',
        'updated_at' => 'datatime'
    ];
}
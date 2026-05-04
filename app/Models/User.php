<?php

declare(strict_types=1);

namespace App\Models;

use Slenix\Database\Model;
use Slenix\Database\Relations\BelongsToMany;
use Slenix\Database\Collection; // <-- a tua Collection
use Slenix\Supports\Auth\Traits\Authenticatable;

class User extends Model
{
    use Authenticatable;

    protected string $table = 'users';
    protected string $primaryKey = 'id';
    protected array $fillable = ['fname', 'lname', 'email', 'password', 'is_active', 'status'];
    protected array $hidden = ['password'];
    protected array $casts = [
        'id' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function friends(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'friends',
            'sender_id',
            'receiver_id'
        )->wherePivot('status', 'accepted');
    }

    public function friendsReceived(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'friends',
            'receiver_id',
            'sender_id'
        )->wherePivot('status', 'accepted');
    }

    public function pendingRequests(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'friends',
            'receiver_id',
            'sender_id'
        )->wherePivot('status', 'pending')
            ->withPivot('id'); // <-- ID do registo na tabela friends
    }

    public function allFriends(): Collection
{
    $sent = $this->friends()->get();
    $received = $this->friendsReceived()->get();

    $friends = new Collection(
        array_merge($sent->all(), $received->all())
    );

    $userId = $this->getKey();

    foreach ($friends as $friend) {

        $friend->last_message = \App\Models\Message::query(
            "SELECT * FROM messages 
             WHERE (sender_id = ? AND receiver_id = ?) 
                OR (sender_id = ? AND receiver_id = ?) 
             ORDER BY id DESC
             LIMIT 1",
            [$userId, $friend->id, $friend->id, $userId]
        )->first();
    }

    return $friends;
}

    public function suggestions(int $limit = 10): Collection
    {
        $related = \App\Models\Friend::newQuery()
            ->where('sender_id', $this->getKey())
            ->orWhere('receiver_id', $this->getKey())
            ->get();

        $relatedIds = [];
        foreach ($related->all() as $f) {
            $relatedIds[] = $f->sender_id;
            $relatedIds[] = $f->receiver_id;
        }

        $relatedIds = array_values(array_unique(
            array_filter($relatedIds, fn($id) => $id !== $this->getKey())
        ));

        $query = static::newQuery()
            ->select(['id', 'fname', 'lname', 'status', 'created_at']) // <-- explícito, sem sender_id
            ->where('id', '!=', $this->getKey())
            ->limit($limit);

        if (!empty($relatedIds)) {
            $query->whereNotIn('id', $relatedIds);
        }

        return $query->get();
    }
}
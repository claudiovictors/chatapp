<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Friend;
use Slenix\Http\Message\Request;
use Slenix\Http\Message\Response;
use Slenix\Libraries\Session;

class FriendController
{
    public function addFriend(Request $request, Response $response)
    {
        $user_id = Session::get('user_id');
        $friend_id = $request->input('friend_id');
        $status = 'pendente';

        $addFriend = Friend::create([
            'user_id' => $user_id,
            'friend_id' => $friend_id,
            'status' => $status
        ]);

        if ($addFriend) {
            return $response->redirect('/~');
        }
    }

    public function accept(Request $request, Response $response)
    {
        $user_id = Session::get('user_id');
        $friendship_id = $request->input('id');

        $friendship = Friend::find($friendship_id);

        if ($friendship && $friendship->friend_id == $user_id) {
            $friendship->status = 'aceito';
            $friendship->update();

            return $response->redirect('/~');
        }

        return $response->redirect('/~');
    }
}

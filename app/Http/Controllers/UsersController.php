<?php

namespace App\Http\Controllers;

use App\Handlers\ImageUploadHandler;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UsersController extends Controller
{
    public function show(User $user)
    {
        return view('users.show')->with([
            'user' => $user
        ]);
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(UserRequest $request, User $user, ImageUploadHandler $handler)
    {
        $data = $request->only([
            'name', 'email', 'introduction'
        ]);
        if ($request->avatar) {
            $result = $handler->save($request->avatar, 'avatars', $user
            ->id, 416);
            if ($result) {
                $data['avatar'] = $result['path'];
            }
        }
        $user->update($data);
        return redirect()->route('users.show', [$user])->with('success', '编辑成功');
    }
}

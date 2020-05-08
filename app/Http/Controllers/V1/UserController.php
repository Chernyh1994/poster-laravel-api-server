<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Http\Requests\V1\User\UpdateUserRequest;

class UserController extends Controller
{
    /**
     * Handle an authorized user profile.
     *
     * @return ResponseJson
     */
    public function myProfile()
    {
        $user = User::with('images')->findOrFail(Auth::id());
        return response()->json(compact('user'));
    }

    /**
     * Handle an user profile.
     * 
     * @param  int  $id
     * 
     * @return ResponseJson
     */
    public function userProfile($id)
    {
        $user = User::with('images')->findOrFail($id);
        return response()->json(compact('user'));
    }

    /**
     * Update user.
     *
     * @param UpdateUserRequest $request
     * 
     * @return ResponseJson
     */
    public function update(UpdateUserRequest $request)
    {        
        if($request->file('avatar')) {
            $request->file('avatar')->store('upload/avatars', 'public');
            $name = $request->file('avatar')->hashName();
            $path = asset('storage/upload/avatars/'.$name);

            Auth::user()->images()->create([
                'path' => $path,
                'name' => $name,
                'mime' => $request->file('avatar')->getMimeType(),
                'size' => $request->file('avatar')->getSize(),
            ]);
        };
        $user = User::with('images')->findOrFail(Auth::id());
        $data = $request->validated();
        $user->fill($data)->save();
        return response()->json(compact('user'));
    }
}

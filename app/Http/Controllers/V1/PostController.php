<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\V1\Post\CreatePostRequest;
use App\Http\Requests\V1\Post\UpdatePostRequest;
use App\Models\Post;

class PostController extends Controller
{
    /**
     * Display a listing post.
     *
     * @return ResponseJson
     */
    public function index()
    {
        $posts = Post::with(['author.avatar', 'images', 'video'])->withCount(['comments', 'likes'])->latest()->paginate(10);

        return response()->json(compact('posts'));
    }

    /**
     * Display a lists post for user.
     *
     * @return ResponseJson
     */
    public function showMyPosts()
    {
        $posts = Auth::user()->posts()->with(['author.avatar', 'images', 'video'])->withCount(['comments', 'likes'])->latest()->paginate(10);
        
        return response()->json(compact('posts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  CreatePostRequest $request
     * 
     * @return ResponseJson
     */
    public function store(CreatePostRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $arr_images = $request->file('media');
            $video_url = isset($data['video_url']);

            $post = Auth::user()->posts()->create($data);

            if($arr_images) {
                foreach($arr_images as $image) {
                    $image->store('upload/postImages', 'public');

                    $name = $image->hashName();
                    $path = asset('storage/upload/postImages/'.$name);

                    $post->images()->create([
                        'path' => $path,
                        'name' => $name,
                        'mime' => $image->getMimeType(),
                        'size' => $image->getSize(),
                    ]);
                };
            }

            if($video_url) {
                $post->video()->create(['url' => $data['video_url']]);
            }
            
            DB::commit();
            return response()->json(compact('post'));
        } catch (\Throwable $e) {
            DB::rollback();
            return response()->json(['message' => 'Something went wrong try again.'], 422);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return ResponseJson
     */
    public function show($id)
    {
        $post = Post::with(['author.avatar', 'images', 'video'])->findOrFail($id);

        return response()->json(compact('post'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdatePostRequest  $request
     * 
     * @param  int  $id
     * @return Response
     */
    public function update(UpdatePostRequest $request, $id)
    {
        $data = $request->validated();
        Gate::authorize('update', $post);

        $post = Post::findOrFail($id);
        $post->update($data);

        return response()->json(compact('post'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return ResponseJson
     */
    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        Gate::authorize('delete', $post);

        $post->delete();

        return response()->json(['message' => 'Successful']);
    }
}

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
use App\Models\Like;

class PostController extends Controller
{
    /**
     * Display a listing post.
     *
     * @return ResponseJson
     */
    public function index()
    {
        $posts = Post::with(['author.profile', 'images'])
            ->withCount(['comments', 'likes'])
            ->latest()
            ->paginate(10);

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
        $createTransaction = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $images = $request->file('images');
            $post = Auth::user()->posts()->create($data);

            if($images) {
                foreach($images as $image) {
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
            $post->refresh()
                ->loadCount(['comments', 'likes'])
                ->load(['author.profile', 'images', 'likes']);
            
            return response()->json(compact('post'));
        });

        return $createTransaction;
    }

    /**
     * Display a listing post.
     *
     * @param  int  $created_at
     * @return ResponseJson
     */
    public function show($created_at)
    {
        $posts = Post::with(['author.profile', 'images'])
        ->withCount(['comments', 'likes'])
        ->where('created_at', '<', $created_at)
        ->with(['likes' => function ($query) {
            if ($query->where('author_id', Auth::id())) {
                return true;
            } 
        }])
        ->latest()
        ->limit(10)
        ->get();

        $has_more = (boolean)count($posts);

        return response()->json(compact('posts', 'has_more'));
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
        $updateTransaction = DB::transaction(function () use ($request, $id) {
            $data = $request->validated();
            $images = $request->file('images');
            $post = Post::findOrFail($id);
    
            Gate::authorize('update', $post);

            if($images) {
                foreach($images as $image) {
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
            $post->refresh()->load(['author.profile', 'images']);
            
            return response()->json(compact('post'));
        });

        return $updateTransaction;
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

    /**
     * Display a lists post for user.
     *
     * @param  int  $created_at
     * @return ResponseJson
     */
    public function showMyPosts($created_at)
    {
        $posts = Auth::user()->posts()
            ->with(['author.profile', 'images'])
            ->with(['likes' => function ($query) {
                if ($query->where('author_id', Auth::id())) {
                    return true;
                } 
            }])
            ->withCount(['comments', 'likes'])
            ->where('created_at', '<', $created_at)
            ->latest()
            ->limit(10)
            ->get();

        $has_more = (boolean)count($posts);
        
        return response()->json(compact('posts', 'has_more'));
    }

    /**
     * Display a lists favorite post for user.
     *
     * @param  int  $created_at
     * @return ResponseJson
     */
    public function showFavoritePosts($created_at)
    {
       //TODO:
    }

    /**
     * Add a like for post.
     *
     * @param  int $id
     * @return ResponseJson
     */
    public function postLike($id)
    {
        $post = Post::findOrFail($id);
        $user = Auth::user();
        $like = $post->likes()->where('author_id', $user->id)->first();

        if($like) {
           return response()->json(['message' => 'Post already has like by you']);
        }
        
        $like = new Like;
        $like->author_id = $user->id;

        $post->likes()->save($like);

        return response()->json(compact('like'));
    }

    /**
     * Add unlike for post.
     *
     * @param  int $id
     * @return ResponseJson
     */
    public function postUnlike($id)
    {
        $user = Auth::user();
        $like = Post::findOrFail($id)->likes()->where('author_id', $user->id)->first();

        Gate::authorize('unlike', $like);

        $like->delete();

        return response()->json(['message' => 'Unlike successful']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Post;

class NewsController extends Controller
{
    public function index()
    {
        return view('news.index', [
            'posts' => Post::published()->with('product')->orderByDesc('published_at')->paginate(10),
        ]);
    }

    public function show(Post $post)
    {
        abort_unless(Post::published()->whereKey($post->id)->exists(), 404);

        return view('news.show', ['post' => $post->load('product')]);
    }
}

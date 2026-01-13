<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class PostController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a paginated list of active posts.
     * Excludes drafts and scheduled posts.
     */
    public function index(): JsonResponse
    {
        $posts = Post::active()
            ->with('user')
            ->latest('published_at')
            ->paginate(20);

        return response()->json($posts);
    }

    /**
     * Show the form for creating a new post.
     * Only authenticated users can access.
     */
    public function create(): string
    {
        return 'posts.create';
    }

    /**
     * Store a newly created post.
     * Only authenticated users can create posts.
     */
    public function store(StorePostRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();
        
        // Default to draft if not specified
        if (!isset($validated['is_draft'])) {
            $validated['is_draft'] = true;
        }

        Post::create($validated);

        return redirect()->route('posts.index')->with('success', 'Post created successfully.');
    }

    /**
     * Display a single active post.
     * Returns 404 if post is draft or scheduled.
     */
    public function show(Post $post): JsonResponse
    {
        // Return 404 if post is not published (draft or scheduled)
        if (!$post->isPublished()) {
            abort(404);
        }

        $post->load('user');

        return response()->json($post);
    }

    /**
     * Show the form for editing a post.
     * Only the post author can access.
     */
    public function edit(Post $post): string
    {
        $this->authorize('update', $post);

        return 'posts.edit';
    }

    /**
     * Update the specified post.
     * Only the post author can update.
     */
    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $post->update($request->validated());

        return redirect()->route('posts.index')->with('success', 'Post updated successfully.');
    }

    /**
     * Remove the specified post.
     * Only the post author can delete.
     */
    public function destroy(Post $post): Response
    {
        $this->authorize('delete', $post);

        $post->delete();

        return response()->noContent();
    }
}

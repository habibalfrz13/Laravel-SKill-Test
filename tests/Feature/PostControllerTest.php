<?php

use App\Models\Post;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

describe('posts.index', function () {
    it('returns paginated active posts with user data', function () {
        // Create active posts (published)
        Post::factory()->count(25)->create([
            'user_id' => $this->user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson(route('posts.index'));

        $response->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'content', 'user_id', 'published_at', 'user']
                ],
                'per_page',
                'total',
            ]);
    });

    it('excludes draft posts', function () {
        Post::factory()->create([
            'user_id' => $this->user->id,
            'is_draft' => true,
            'published_at' => null,
            'title' => 'Draft Post',
        ]);

        Post::factory()->create([
            'user_id' => $this->user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
            'title' => 'Published Post',
        ]);

        $response = $this->getJson(route('posts.index'));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Published Post');
    });

    it('excludes scheduled posts', function () {
        Post::factory()->create([
            'user_id' => $this->user->id,
            'is_draft' => false,
            'published_at' => now()->addDay(),
            'title' => 'Scheduled Post',
        ]);

        Post::factory()->create([
            'user_id' => $this->user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
            'title' => 'Published Post',
        ]);

        $response = $this->getJson(route('posts.index'));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Published Post');
    });
});

describe('posts.create', function () {
    it('requires authentication', function () {
        $response = $this->get(route('posts.create'));

        $response->assertRedirect(route('login'));
    });

    it('returns posts.create string for authenticated users', function () {
        $response = $this->actingAs($this->user)->get(route('posts.create'));

        $response->assertOk()
            ->assertSee('posts.create');
    });
});

describe('posts.store', function () {
    it('requires authentication', function () {
        $response = $this->post(route('posts.store'), [
            'title' => 'Test Post',
            'content' => 'Test content',
        ]);

        $response->assertRedirect(route('login'));
    });

    it('validates required fields', function () {
        $response = $this->actingAs($this->user)->post(route('posts.store'), []);

        $response->assertSessionHasErrors(['title', 'content']);
    });

    it('creates a post with valid data', function () {
        $response = $this->actingAs($this->user)->post(route('posts.store'), [
            'title' => 'New Post Title',
            'content' => 'New post content here',
            'published_at' => now()->toDateTimeString(),
        ]);

        $response->assertRedirect(route('posts.index'));

        $this->assertDatabaseHas('posts', [
            'title' => 'New Post Title',
            'content' => 'New post content here',
            'user_id' => $this->user->id,
        ]);
    });

    it('defaults to draft if is_draft not specified', function () {
        $this->actingAs($this->user)->post(route('posts.store'), [
            'title' => 'Draft Post',
            'content' => 'Content',
        ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Draft Post',
            'is_draft' => true,
        ]);
    });
});

describe('posts.show', function () {
    it('returns active post as JSON', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson(route('posts.show', $post));

        $response->assertOk()
            ->assertJsonPath('id', $post->id)
            ->assertJsonPath('title', $post->title)
            ->assertJsonStructure(['id', 'title', 'content', 'user']);
    });

    it('returns 404 for draft posts', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'is_draft' => true,
            'published_at' => null,
        ]);

        $response = $this->getJson(route('posts.show', $post));

        $response->assertNotFound();
    });

    it('returns 404 for scheduled posts', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'is_draft' => false,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->getJson(route('posts.show', $post));

        $response->assertNotFound();
    });
});

describe('posts.edit', function () {
    it('requires authentication', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->get(route('posts.edit', $post));

        $response->assertRedirect(route('login'));
    });

    it('allows post author to access', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get(route('posts.edit', $post));

        $response->assertOk()
            ->assertSee('posts.edit');
    });

    it('denies access to non-author', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->otherUser)->get(route('posts.edit', $post));

        $response->assertForbidden();
    });
});

describe('posts.update', function () {
    it('requires authentication', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->put(route('posts.update', $post), [
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ]);

        $response->assertRedirect(route('login'));
    });

    it('allows post author to update', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->put(route('posts.update', $post), [
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ]);

        $response->assertRedirect(route('posts.index'));

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ]);
    });

    it('denies update to non-author', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->otherUser)->put(route('posts.update', $post), [
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ]);

        $response->assertForbidden();
    });

    it('validates required fields', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->put(route('posts.update', $post), []);

        $response->assertSessionHasErrors(['title', 'content']);
    });
});

describe('posts.destroy', function () {
    it('requires authentication', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->delete(route('posts.destroy', $post));

        $response->assertRedirect(route('login'));
    });

    it('allows post author to delete', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->delete(route('posts.destroy', $post));

        $response->assertNoContent();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    });

    it('denies delete to non-author', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->otherUser)->delete(route('posts.destroy', $post));

        $response->assertForbidden();

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    });
});

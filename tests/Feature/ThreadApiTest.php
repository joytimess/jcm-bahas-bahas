<?php

use App\Models\Comment;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('creates a thread with images and enforces the 5 image limit', function () {
    $img = fn () => UploadedFile::fake()->image('a.jpg');

    $this->postJson('/api/threads', ['body' => 'hello', 'images' => [$img(), $img()]])
        ->assertCreated()
        ->assertJsonCount(2, 'data.images');

    $this->postJson('/api/threads', ['body' => 'hello', 'images' => array_map($img, range(1, 6))])
        ->assertUnprocessable();
});

it('supports nested replies and cascades soft delete', function () {
    $thread = $this->user->threads()->create(['body' => 't']);

    $c1 = $this->postJson("/api/threads/{$thread->id}/comments", ['body' => 'c1'])->assertCreated()->json('data.id');
    $c2 = $this->postJson("/api/threads/{$thread->id}/comments", ['body' => 'c2', 'parent_id' => $c1])->assertCreated()->json('data.id');
    $this->postJson("/api/threads/{$thread->id}/comments", ['body' => 'c3', 'parent_id' => $c2])->assertCreated();

    $this->getJson("/api/threads/{$thread->id}/comments")
        ->assertJsonPath('data.0.replies.0.replies.0.body', 'c3');

    $this->deleteJson("/api/comments/{$c1}")->assertOk();
    expect(Comment::count())->toBe(0);
    expect(Comment::withoutGlobalScopes()->where('is_deleted', true)->count())->toBe(3);

    $this->deleteJson("/api/threads/{$thread->id}")->assertOk();
    $this->getJson("/api/threads/{$thread->id}")->assertNotFound();
    expect(Thread::withoutGlobalScopes()->find($thread->id)->is_deleted)->toBeTrue();
});

it('toggles likes and blocks editing others posts', function () {
    $thread = $this->user->threads()->create(['body' => 't']);

    $this->postJson("/api/threads/{$thread->id}/like")->assertJson(['liked' => true, 'likes_count' => 1]);
    $this->postJson("/api/threads/{$thread->id}/like")->assertJson(['liked' => false, 'likes_count' => 0]);

    Sanctum::actingAs(User::factory()->create());
    $this->putJson("/api/threads/{$thread->id}", ['body' => 'x'])->assertForbidden();
    $this->deleteJson("/api/threads/{$thread->id}")->assertForbidden();
});

it('renders the thread pages for a logged in user', function () {
    $thread = $this->user->threads()->create(['body' => 't']);

    $this->actingAs($this->user)->get('/dashboard')->assertOk();
    $this->actingAs($this->user)->get("/threads/{$thread->id}")->assertOk();
});

it('hides api endpoints from direct browser navigation', function () {
    $this->get('/api/threads', ['Accept' => 'text/html'])->assertNotFound();
    $this->get('/api/threads', ['Accept' => 'application/json', 'Sec-Fetch-Mode' => 'navigate'])->assertNotFound();
    $this->getJson('/api/threads')->assertOk();
});

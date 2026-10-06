<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->me = User::factory()->create();
    $this->other = User::factory()->create();
    Sanctum::actingAs($this->me);
});

function commentOn($thread, User $by, string $body)
{
    $c = $thread->comments()->make(['body' => $body]);
    $c->user_id = $by->id;
    $c->save();

    return $c;
}

it('renders the My Threads and My Likes pages with sidebar links', function () {
    $this->actingAs($this->me)->get('/my/threads')
        ->assertOk()
        ->assertSee('My Threads')
        ->assertSee('/api/users/'.$this->me->id.'/threads', false)
        ->assertSee(route('my.likes'), false);

    $this->actingAs($this->me)->get('/my/likes')->assertOk()->assertSee('My Likes');
});

it('lists liked threads and liked comments with their parent thread, newest first', function () {
    $thread = $this->other->threads()->create(['body' => 'thread induk']);
    $comment = commentOn($thread, $this->other, 'komentar bagus');

    $this->postJson("/api/threads/{$thread->id}/like")->assertOk();
    $this->postJson("/api/comments/{$comment->id}/like")->assertOk();

    $this->getJson('/api/me/likes')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.type', 'comment')
        ->assertJsonPath('data.0.comment.body', 'komentar bagus')
        ->assertJsonPath('data.0.thread.id', $thread->id)
        ->assertJsonPath('data.0.thread.body', 'thread induk')
        ->assertJsonPath('data.1.type', 'thread')
        ->assertJsonPath('data.1.comment', null);
});

it('skips likes on deleted or no-longer-visible threads and other users likes', function () {
    $gone = $this->other->threads()->create(['body' => 'akan dihapus']);
    $this->postJson("/api/threads/{$gone->id}/like");
    $gone->softDelete();

    $private = User::factory()->create(['is_private' => true]);
    $secret = $private->threads()->create(['body' => 'rahasia']);
    $this->me->following()->attach($private->id);
    $this->postJson("/api/threads/{$secret->id}/like")->assertOk();
    $this->me->following()->detach($private->id);

    $mine = $this->other->threads()->create(['body' => 'like orang lain']);
    Sanctum::actingAs($this->other);
    $this->postJson("/api/threads/{$mine->id}/like");

    Sanctum::actingAs($this->me);
    $this->getJson('/api/me/likes')->assertOk()->assertJsonCount(0, 'data');
});

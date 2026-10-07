<?php

use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->author = User::factory()->create();
    $this->original = $this->author->threads()->create(['body' => 'asli']);
    $this->me = User::factory()->create();
    Sanctum::actingAs($this->me);
});

it('toggles a repost and reports counts', function () {
    $this->postJson("/api/threads/{$this->original->id}/repost")
        ->assertOk()->assertJson(['reposted' => true, 'reposts_count' => 1]);

    $this->getJson("/api/threads/{$this->original->id}")
        ->assertJsonPath('data.reposts_count', 1)
        ->assertJsonPath('data.reposted_by_me', true);

    $this->postJson("/api/threads/{$this->original->id}/repost")
        ->assertJson(['reposted' => false, 'reposts_count' => 0]);

    expect(Thread::where('repost_of_id', $this->original->id)->count())->toBe(0);
});

it('reposting a repost targets the original thread', function () {
    $other = User::factory()->create();
    $repost = $other->threads()->create(['repost_of_id' => $this->original->id, 'body' => null]);

    $this->postJson("/api/threads/{$repost->id}/repost")->assertJson(['reposted' => true, 'reposts_count' => 2]);

    expect($this->me->threads()->first()->repost_of_id)->toBe($this->original->id);
});

it('creates a quote with the original embedded', function () {
    $this->postJson('/api/threads', ['body' => 'pendapatku', 'quote_of' => $this->original->id])
        ->assertCreated()
        ->assertJsonPath('data.type', 'quote')
        ->assertJsonPath('data.repost_of.id', $this->original->id)
        ->assertJsonPath('data.repost_of.body', 'asli');

    $this->getJson("/api/threads/{$this->original->id}")->assertJsonPath('data.quotes_count', 1);
});

it('rejects reposting or quoting private, hidden or deleted threads', function () {
    $this->author->forceFill(['is_private' => true])->save();

    // Tidak mengikuti akun privat → thread tidak terlihat.
    $this->postJson("/api/threads/{$this->original->id}/repost")->assertNotFound();

    // Mengikuti akun privat → terlihat, tapi tetap tidak boleh dibagikan.
    $this->me->following()->attach($this->author->id, ['status' => 'accepted']);
    $this->postJson("/api/threads/{$this->original->id}/repost")->assertUnprocessable();
    $this->postJson('/api/threads', ['body' => 'q', 'quote_of' => $this->original->id])->assertUnprocessable();

    $this->author->forceFill(['is_private' => false])->save();
    $this->original->softDelete();
    $this->postJson("/api/threads/{$this->original->id}/repost")->assertNotFound();
    $this->postJson('/api/threads', ['body' => 'q', 'quote_of' => $this->original->id])->assertUnprocessable();
});

it('shows reposts in the following feed', function () {
    $friend = User::factory()->create();
    $this->me->following()->attach($friend->id, ['status' => 'accepted']);
    $friend->threads()->create(['repost_of_id' => $this->original->id, 'body' => null]);

    $this->getJson('/api/threads?feed=following')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'repost')
        ->assertJsonPath('data.0.user.id', $friend->id)
        ->assertJsonPath('data.0.repost_of.id', $this->original->id);
});

it('cannot edit a repost and deleting it undoes the repost', function () {
    $this->postJson("/api/threads/{$this->original->id}/repost");
    $repost = $this->me->threads()->first();

    $this->putJson("/api/threads/{$repost->id}", ['body' => 'x'])->assertForbidden();
    $this->deleteJson("/api/threads/{$repost->id}")->assertOk();

    $this->getJson("/api/threads/{$this->original->id}")->assertJsonPath('data.reposts_count', 0);
});

it('hides reposts of deleted threads but keeps quotes with a placeholder', function () {
    $this->postJson("/api/threads/{$this->original->id}/repost");
    $quoteId = $this->postJson('/api/threads', ['body' => 'pendapatku', 'quote_of' => $this->original->id])->json('data.id');

    $this->original->softDelete();

    $this->getJson('/api/threads')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $quoteId)
        ->assertJsonPath('data.0.repost_of', null)
        ->assertJsonPath('data.0.repost_unavailable', true);
});

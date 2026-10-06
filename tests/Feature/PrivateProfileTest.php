<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->me = User::factory()->create();
    $this->owner = User::factory()->create(['is_private' => true]);
    $this->secret = $this->owner->threads()->create(['body' => 'rahasia']);
    Sanctum::actingAs($this->me);
});

it('creates a pending request for private accounts and accepted for public ones', function () {
    $public = User::factory()->create();

    $this->postJson("/api/users/{$public->id}/follow")->assertJson(['status' => 'following']);
    $this->postJson("/api/users/{$this->owner->id}/follow")
        ->assertJson(['status' => 'pending', 'followers_count' => 0]);

    expect($this->owner->followRequests()->count())->toBe(1)
        ->and($this->owner->followers()->count())->toBe(0);

    // toggle lagi = batalkan permintaan
    $this->postJson("/api/users/{$this->owner->id}/follow")->assertJson(['status' => 'none']);
    expect($this->owner->followRequests()->count())->toBe(0);
});

it('lets the owner accept requests', function () {
    $this->postJson("/api/users/{$this->owner->id}/follow");

    // bukan pemilik: tidak ada permintaan yang menunggu dirinya
    $this->postJson("/api/follow-requests/{$this->owner->id}/accept")->assertNotFound();

    Sanctum::actingAs($this->owner);
    $this->getJson('/api/follow-requests')->assertOk()->assertJsonPath('data.0.id', $this->me->id);

    $this->postJson("/api/follow-requests/{$this->me->id}/accept")
        ->assertOk()->assertJson(['followers_count' => 1]);

    // status follow $me terhadap $owner
    expect($this->owner->followStatusFor($this->me))->toBe('following')
        ->and($this->owner->followers()->count())->toBe(1)
        ->and($this->owner->threadsVisibleTo($this->me))->toBeTrue();
});

it('lets the owner reject requests only once', function () {
    $this->postJson("/api/users/{$this->owner->id}/follow");

    Sanctum::actingAs($this->owner);
    $this->deleteJson("/api/follow-requests/{$this->me->id}")->assertOk();
    $this->deleteJson("/api/follow-requests/{$this->me->id}")->assertNotFound();

    expect($this->owner->followRequests()->count())->toBe(0)
        ->and($this->owner->followers()->count())->toBe(0);
});

it('hides private threads from everyone who is not approved', function () {
    $this->postJson("/api/users/{$this->owner->id}/follow"); // pending saja

    $this->getJson('/api/threads')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson("/api/threads/{$this->secret->id}")->assertNotFound();
    $this->getJson("/api/threads/{$this->secret->id}/comments")->assertNotFound();
    $this->postJson("/api/threads/{$this->secret->id}/comments", ['body' => 'x'])->assertNotFound();
    $this->postJson("/api/threads/{$this->secret->id}/like")->assertNotFound();
    $this->getJson("/api/users/{$this->owner->id}/threads")->assertForbidden();
    $this->actingAs($this->me)->get("/threads/{$this->secret->id}")->assertNotFound();
});

it('also hides comment likes of a hidden thread', function () {
    $comment = $this->secret->comments()->make(['body' => 'c']);
    $comment->user_id = $this->owner->id;
    $comment->save();

    $this->postJson("/api/comments/{$comment->id}/like")->assertNotFound();
});

it('shows private threads to the owner and approved followers, and hides again after unfollow', function () {
    $this->me->following()->attach($this->owner->id);

    $this->getJson('/api/threads')->assertJsonCount(1, 'data');
    $this->getJson("/api/threads/{$this->secret->id}")->assertOk();
    $this->getJson("/api/users/{$this->owner->id}/threads")->assertOk()->assertJsonCount(1, 'data');
    $this->postJson("/api/threads/{$this->secret->id}/like")->assertOk();

    // pemilik selalu bisa melihat
    Sanctum::actingAs($this->owner);
    $this->getJson("/api/threads/{$this->secret->id}")->assertOk();

    // unfollow → hilang lagi
    Sanctum::actingAs($this->me);
    $this->postJson("/api/users/{$this->owner->id}/follow")->assertJson(['status' => 'none']);
    $this->getJson("/api/threads/{$this->secret->id}")->assertNotFound();
});

it('keeps public account threads visible to everyone', function () {
    $publicUser = User::factory()->create();
    $thread = $publicUser->threads()->create(['body' => 'terbuka']);

    $this->getJson("/api/threads/{$thread->id}")->assertOk();
    $this->getJson("/api/users/{$publicUser->id}/threads")->assertOk()->assertJsonCount(1, 'data');
});

it('exposes the public profile with counts even when private, without email', function () {
    User::factory()->create()->following()->attach($this->owner->id);

    $res = $this->getJson("/api/users/{$this->owner->id}")->assertOk();

    expect($res->json('data.followers_count'))->toBe(1)
        ->and($res->json('data.is_private'))->toBeTrue()
        ->and($res->json('data.can_view_threads'))->toBeFalse()
        ->and($res->json('data.follow_status'))->toBe('none')
        ->and($res->json('data'))->not->toHaveKey('email');
});

it('counts only accepted follows', function () {
    $this->postJson("/api/users/{$this->owner->id}/follow"); // pending

    expect($this->me->following()->count())->toBe(0)
        ->and($this->owner->followers()->count())->toBe(0);

    $this->getJson('/api/user')->assertJson(['following_count' => 0]);
});

it('auto-accepts pending requests when the account becomes public', function () {
    $this->postJson("/api/users/{$this->owner->id}/follow");

    $this->actingAs($this->owner)
        ->patch('/profile', ['name' => $this->owner->name, 'email' => $this->owner->email, 'is_private' => '0'])
        ->assertSessionHasNoErrors();

    expect($this->owner->fresh()->is_private)->toBeFalse()
        ->and($this->owner->followers()->count())->toBe(1)
        ->and($this->owner->followRequests()->count())->toBe(0);
});

it('can switch an account to private from the profile form', function () {
    $this->actingAs($this->me)
        ->patch('/profile', ['name' => $this->me->name, 'email' => $this->me->email, 'is_private' => '1'])
        ->assertSessionHasNoErrors();

    expect($this->me->fresh()->is_private)->toBeTrue();
});

it('renders user profile pages and redirects to /profile for yourself', function () {
    $this->actingAs($this->me)->get("/users/{$this->owner->id}")->assertOk();
    $this->actingAs($this->me)->get("/users/{$this->me->id}")->assertRedirect('/profile');
});

it('renders the follow-request section and private toggle on the profile page', function () {
    $this->actingAs($this->owner)->get('/profile')
        ->assertOk()
        ->assertSee('Permintaan mengikuti')
        ->assertSee('Akun privat');
});

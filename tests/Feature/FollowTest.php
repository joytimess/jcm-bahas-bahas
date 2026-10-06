<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->me = User::factory()->create();
    Sanctum::actingAs($this->me);
});

it('follows and unfollows by toggling', function () {
    $other = User::factory()->create();

    $this->postJson("/api/users/{$other->id}/follow")
        ->assertOk()
        ->assertJson(['status' => 'following', 'followers_count' => 1]);

    expect($this->me->following()->count())->toBe(1);

    $this->postJson("/api/users/{$other->id}/follow")
        ->assertOk()
        ->assertJson(['status' => 'none', 'followers_count' => 0]);

    expect($this->me->following()->count())->toBe(0);
});

it('does not allow following yourself', function () {
    $this->postJson("/api/users/{$this->me->id}/follow")->assertUnprocessable();

    expect($this->me->following()->count())->toBe(0);
});

it('rejects guests', function () {
    $this->app['auth']->forgetGuards();

    $this->postJson('/api/users/1/follow')->assertUnauthorized();
    $this->getJson('/api/users/suggestions')->assertUnauthorized();
});

it('counts followers and following in both directions', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();

    $this->me->following()->attach([$a->id, $b->id]);
    $a->following()->attach($this->me->id);

    $this->getJson('/api/user')
        ->assertOk()
        ->assertJson(['followers_count' => 1, 'following_count' => 2]);

    expect($a->followers()->count())->toBe(1)
        ->and($b->followers()->count())->toBe(1)
        ->and($a->following()->count())->toBe(1);
});

it('lists the 5 newest other users with follow state and no email', function () {
    $users = User::factory()->count(6)->create();
    $this->me->following()->attach($users->last()->id);

    $res = $this->getJson('/api/users/suggestions')->assertOk()->assertJsonCount(5, 'data');

    $ids = collect($res->json('data'))->pluck('id');

    expect($ids->first())->toBe($users->last()->id)
        ->and($ids)->not->toContain($this->me->id)
        ->and($ids)->not->toContain($users->first()->id)
        ->and($res->json('data.0.follow_status'))->toBe('following')
        ->and($res->json('data.1.follow_status'))->toBe('none')
        ->and($res->json('data.0'))->not->toHaveKey('email');
});

it('includes the author avatar url in thread resources', function () {
    $this->me->forceFill(['avatar' => 'avatars/me.jpg'])->save();
    $this->me->threads()->create(['body' => 'halo']);

    $this->getJson('/api/threads')
        ->assertOk()
        ->assertJsonPath('data.0.user.avatar_url', Storage::disk('public')->url('avatars/me.jpg'));
});

it('uploads, replaces and removes the profile avatar', function () {
    $this->actingAs($this->me)
        ->patch('/profile', [
            'name' => 'Rudi',
            'email' => $this->me->email,
            'avatar' => UploadedFile::fake()->image('a.jpg'),
        ])
        ->assertSessionHasNoErrors();

    $first = $this->me->refresh()->avatar;
    Storage::disk('public')->assertExists($first);

    $this->actingAs($this->me)
        ->patch('/profile', [
            'name' => 'Rudi',
            'email' => $this->me->email,
            'avatar' => UploadedFile::fake()->image('b.png'),
        ]);

    $second = $this->me->refresh()->avatar;
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);

    $this->actingAs($this->me)
        ->patch('/profile', ['name' => 'Rudi', 'email' => $this->me->email, 'remove_avatar' => '1']);

    expect($this->me->refresh()->avatar)->toBeNull();
    Storage::disk('public')->assertMissing($second);
});

it('rejects a non-image avatar', function () {
    $this->actingAs($this->me)
        ->patch('/profile', [
            'name' => 'Rudi',
            'email' => $this->me->email,
            'avatar' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        ])
        ->assertSessionHasErrors('avatar');
});

it('renders the sidebar layout with suggestions on dashboard and thread pages', function () {
    $thread = $this->me->threads()->create(['body' => 'halo']);

    $this->actingAs($this->me)->get('/dashboard')
        ->assertOk()
        ->assertSee('Navigasi utama', false)
        ->assertSee('Pengguna baru')
        ->assertSee('Log Out')
        ->assertSee('cropper-title', false);

    $this->actingAs($this->me)->get("/threads/{$thread->id}")
        ->assertOk()
        ->assertSee('Pengguna baru');
});

it('shows follower counts on the profile page without the suggestions column', function () {
    $other = User::factory()->create();
    $other->following()->attach($this->me->id);

    $this->actingAs($this->me)->get('/profile')
        ->assertOk()
        ->assertSee('pengikut')
        ->assertSee('mengikuti')
        ->assertDontSee('Pengguna baru');
});

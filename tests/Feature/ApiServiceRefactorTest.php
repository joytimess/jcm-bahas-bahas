<?php

use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

describe('API authentication', function () {
    it('registers a user and returns an API token without exposing the password', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'Ayu', 'email' => 'ayu@example.test',
            'password' => 'secret-pass', 'password_confirmation' => 'secret-pass',
        ]);

        $response->assertCreated()->assertJsonPath('user.name', 'Ayu')
            ->assertJsonPath('user.email', 'ayu@example.test')->assertJsonMissingPath('user.password');
        $user = User::where('email', 'ayu@example.test')->firstOrFail();
        expect(Hash::check('secret-pass', $user->password))->toBeTrue();
        expect($response->json('token'))->toBeString()->not->toBeEmpty();
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id, 'name' => 'api']);
    });

    it('returns 422 for duplicate registration without creating a user or token', function () {
        User::factory()->create(['email' => 'ayu@example.test']);

        $this->postJson('/api/register', [
            'name' => 'Ayu', 'email' => 'ayu@example.test',
            'password' => 'secret-pass', 'password_confirmation' => 'secret-pass',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    });

    it('returns 422 for missing authentication input', function (string $endpoint, array $fields) {
        $this->postJson($endpoint, [])->assertUnprocessable()->assertJsonValidationErrors($fields);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    })->with([
        'registration' => ['/api/register', ['name', 'email', 'password']],
        'login' => ['/api/login', ['email', 'password']],
    ]);

    it('returns 422 for invalid credentials without issuing a token', function (bool $existingUser) {
        if ($existingUser) {
            User::factory()->create(['email' => 'ayu@example.test', 'password' => 'secret-pass']);
        }

        $this->postJson('/api/login', ['email' => 'ayu@example.test', 'password' => 'wrong-pass'])
            ->assertUnprocessable()->assertJsonPath('errors.email.0', 'Email atau password salah.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    })->with(['unknown email' => false, 'wrong password' => true]);

    it('logs in with valid credentials and issues a token', function () {
        $user = User::factory()->create(['email' => 'ayu@example.test', 'password' => 'secret-pass']);

        $response = $this->postJson('/api/login', ['email' => 'ayu@example.test', 'password' => 'secret-pass']);

        $response->assertOk()->assertJsonPath('user.id', $user->id)->assertJsonMissingPath('user.password');
        expect($response->json('token'))->toBeString()->not->toBeEmpty();
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id, 'name' => 'api']);
    });

    it('logs out by revoking only the current bearer token', function () {
        $user = User::factory()->create();
        $active = $user->createToken('api');
        $other = $user->createToken('other');

        $this->withToken($active->plainTextToken)->postJson('/api/logout')
            ->assertOk()->assertExactJson(['message' => 'Logged out.']);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $active->accessToken->id]);
        $this->assertModelExists($other->accessToken);
    });
});

describe('comment access and validation', function () {
    it('allows owners to update comments', function () {
        $user = User::factory()->create();
        $thread = $user->threads()->create(['body' => 'thread']);
        $comment = Comment::forceCreate(['user_id' => $user->id, 'thread_id' => $thread->id, 'body' => 'old']);
        Sanctum::actingAs($user);

        $this->putJson('/api/comments/'.$comment->id, ['body' => 'updated'])
            ->assertOk()->assertJsonPath('data.body', 'updated');

        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => 'updated', 'is_deleted' => false]);
    });

    it('returns 403 before validation when another user edits a comment', function () {
        $owner = User::factory()->create();
        $thread = $owner->threads()->create(['body' => 'thread']);
        $comment = Comment::forceCreate(['user_id' => $owner->id, 'thread_id' => $thread->id, 'body' => 'old']);
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/comments/'.$comment->id, ['body' => ''])->assertForbidden();

        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => 'old']);
    });

    it('returns 403 when another user deletes a comment', function () {
        $owner = User::factory()->create();
        $thread = $owner->threads()->create(['body' => 'thread']);
        $comment = Comment::forceCreate(['user_id' => $owner->id, 'thread_id' => $thread->id, 'body' => 'old']);
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson('/api/comments/'.$comment->id)->assertForbidden();

        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'is_deleted' => false]);
    });

    it('returns 404 before validation when posting to a private thread', function () {
        $owner = User::factory()->create();
        $owner->forceFill(['is_private' => true])->save();
        $thread = $owner->threads()->create(['body' => 'private']);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/threads/'.$thread->id.'/comments', [])->assertNotFound();

        $this->assertDatabaseCount('comments', 0);
    });

    it('returns 422 for a parent from another thread or a deleted parent', function (bool $deleted) {
        $user = User::factory()->create();
        $thread = $user->threads()->create(['body' => 'target']);
        $parentThread = $deleted ? $thread : $user->threads()->create(['body' => 'other']);
        $parent = Comment::forceCreate(['user_id' => $user->id, 'thread_id' => $parentThread->id, 'body' => 'parent']);
        if ($deleted) {
            $parent->softDelete();
        }
        Sanctum::actingAs($user);

        $this->postJson('/api/threads/'.$thread->id.'/comments', ['body' => 'reply', 'parent_id' => $parent->id])
            ->assertUnprocessable()->assertJsonValidationErrors('parent_id');

        $this->assertDatabaseCount('comments', 1);
    })->with(['different thread' => false, 'deleted parent' => true]);
});

describe('image transactions', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('replaces a removed image while keeping the total at five', function (string $type) {
        $user = User::factory()->create();
        $thread = $user->threads()->create(['body' => 'thread']);
        $model = $type === 'threads' ? $thread : Comment::forceCreate(['user_id' => $user->id, 'thread_id' => $thread->id, 'body' => 'comment']);
        for ($i = 0; $i < 5; $i++) {
            $model->images()->create(['path' => 'old-'.$i.'.jpg', 'order' => $i]);
        }
        $removed = $model->images()->first();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/'.$type.'/'.$model->id, [
            'remove_image_ids' => [$removed->id], 'images' => [UploadedFile::fake()->image('new.jpg')],
        ]);

        $response->assertOk()->assertJsonCount(5, 'data.images');
        $this->assertDatabaseHas('images', ['id' => $removed->id, 'is_deleted' => true]);
        $this->assertDatabaseCount('images', 6);
        $uploaded = $model->images()->reorder('id', 'desc')->first();
        Storage::disk('public')->assertExists($uploaded->path);
    })->with(['thread' => 'threads', 'comment' => 'comments']);

    it('rolls back body edits and image removals when the total exceeds five', function (string $type) {
        $user = User::factory()->create();
        $thread = $user->threads()->create(['body' => 'original']);
        $model = $type === 'threads' ? $thread : Comment::forceCreate(['user_id' => $user->id, 'thread_id' => $thread->id, 'body' => 'original']);
        for ($i = 0; $i < 5; $i++) {
            $model->images()->create(['path' => 'old-'.$i.'.jpg', 'order' => $i]);
        }
        $removed = $model->images()->first();
        Sanctum::actingAs($user);

        $this->putJson('/api/'.$type.'/'.$model->id, [
            'body' => 'changed', 'remove_image_ids' => [$removed->id],
            'images' => [UploadedFile::fake()->image('one.jpg'), UploadedFile::fake()->image('two.jpg')],
        ])->assertUnprocessable()->assertJsonPath('errors.images.0', 'Maksimal 5 gambar per post.');

        $this->assertDatabaseHas($model->getTable(), ['id' => $model->id, 'body' => 'original']);
        $this->assertDatabaseHas('images', ['id' => $removed->id, 'is_deleted' => false]);
        $this->assertDatabaseCount('images', 5);
        expect(Storage::disk('public')->allFiles())->toBe([]);
    })->with(['thread' => 'threads', 'comment' => 'comments']);
});

describe('response compatibility', function () {
    it('keeps the authorization error message when another user edits a thread', function () {
        $owner = User::factory()->create();
        $thread = $owner->threads()->create(['body' => 'original']);
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/threads/'.$thread->id, ['body' => ''])
            ->assertForbidden()->assertJsonPath('message', 'This action is unauthorized.');

        $this->assertDatabaseHas('threads', ['id' => $thread->id, 'body' => 'original']);
    });

    it('keeps raw paginator metadata and only brief users in connection lists', function () {
        $user = User::factory()->create();
        $follower = User::factory()->create(['name' => 'Ayu']);
        $user->followers()->attach($follower->id, ['status' => 'accepted']);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/users/'.$user->id.'/followers');

        $response->assertOk()->assertJsonPath('current_page', 1)->assertJsonPath('per_page', 20)
            ->assertJsonPath('total', 1)->assertJsonMissingPath('meta')
            ->assertJsonPath('data', [['id' => $follower->id, 'name' => 'Ayu', 'avatar_url' => null]])
            ->assertJsonStructure(['first_page_url', 'last_page_url', 'links', 'path', 'from', 'to']);
    });

    it('keeps raw paginator metadata and the history item shape in my likes', function () {
        $user = User::factory()->create();
        $thread = $user->threads()->create(['body' => 'liked thread']);
        $like = $thread->likes()->make();
        $like->user_id = $user->id;
        $like->save();
        Sanctum::actingAs($user);

        $this->getJson('/api/me/likes')->assertOk()->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 15)->assertJsonPath('total', 1)->assertJsonMissingPath('meta')
            ->assertJsonPath('data.0.id', $like->id)->assertJsonPath('data.0.type', 'thread')
            ->assertJsonPath('data.0.thread.body', 'liked thread')->assertJsonPath('data.0.comment', null)
            ->assertJsonPath('data.0.thread.user', ['id' => $user->id, 'name' => $user->name, 'avatar_url' => null]);
    });
});

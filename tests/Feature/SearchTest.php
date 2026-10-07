<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->viewer = User::factory()->create(['name' => 'Viewer']);
    Sanctum::actingAs($this->viewer);
});

it('searches visible thread bodies by default with the existing resource contract', function () {
    $owner = User::factory()->create(['name' => 'Laravel Author']);
    $thread = $owner->threads()->create(['body' => 'Belajar Laravel hari ini']);
    $thread->images()->create(['path' => 'images/threads/example.jpg', 'order' => 0]);
    $thread->images()->create(['path' => 'deleted.jpg', 'order' => 1])->softDelete();
    $thread->comments()->make(['body' => 'nice'])->forceFill(['user_id' => $this->viewer->id])->save();
    $thread->likes()->create(['user_id' => $this->viewer->id]);
    $owner->threads()->create(['body' => 'unrelated'])->comments()
        ->make(['body' => 'laravel comment only'])->forceFill(['user_id' => $this->viewer->id])->save();
    $deleted = $owner->threads()->create(['body' => 'laravel deleted']);
    $deleted->softDelete();
    User::factory()->create(['is_private' => true])->threads()->create(['body' => 'laravel secret']);

    $response = $this->getJson('/api/search?q=%20LARAVEL%20')->assertOk()
        ->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $thread->id)
        ->assertJsonPath('data.0.comments_count', 1)->assertJsonPath('data.0.likes_count', 1)
        ->assertJsonPath('data.0.liked_by_me', true)->assertJsonCount(1, 'data.0.images')
        ->assertJsonStructure(['data' => [['id', 'body', 'user' => ['id', 'name', 'avatar_url'],
            'images' => [['id', 'url', 'order']], 'comments_count', 'likes_count', 'liked_by_me',
            'created_at', 'updated_at']], 'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'links', 'path', 'per_page', 'to', 'total']]);

    expect($response->json('data.0.user'))->not->toHaveKeys(['email', 'password', 'remember_token']);
    parse_str(parse_url($response->json('links.first'), PHP_URL_QUERY), $params);
    expect($params['q'])->toBe('LARAVEL');
});

it('updates search visibility after approval unfollow and privacy changes', function () {
    $owner = User::factory()->create(['is_private' => true]);
    $thread = $owner->threads()->create(['body' => 'laravel secret']);
    $this->viewer->threads()->create(['body' => 'laravel mine']);
    $this->postJson("/api/users/{$owner->id}/follow")->assertOk();
    $this->getJson('/api/search?q=laravel')->assertJsonPath('meta.total', 1);

    Sanctum::actingAs($owner);
    $this->getJson('/api/search?q=secret')->assertJsonPath('data.0.id', $thread->id);
    $this->postJson("/api/follow-requests/{$this->viewer->id}/accept")->assertOk();
    Sanctum::actingAs($this->viewer);
    $this->getJson('/api/search?q=laravel')->assertJsonPath('meta.total', 2);
    $this->postJson("/api/users/{$owner->id}/follow")->assertOk();
    $this->getJson('/api/search?q=secret')->assertJsonPath('meta.total', 0)->assertJsonPath('data', []);
    $this->getJson("/api/threads/{$thread->id}")->assertNotFound();
    $owner->forceFill(['is_private' => false])->save();
    $this->getJson('/api/search?q=secret')->assertJsonPath('meta.total', 1);
});

it('returns only public user fields including private accounts self and follow states', function () {
    $this->viewer->forceFill(['name' => 'Andi A'])->save();
    $accepted = User::factory()->create(['name' => 'Andi B', 'avatar' => 'avatars/example.jpg']);
    $pending = User::factory()->create(['name' => 'Andi C', 'is_private' => true]);
    $none = User::factory()->create(['name' => 'Andi D', 'is_private' => true]);
    $this->viewer->following()->attach($accepted->id);
    $this->viewer->following()->attach($pending->id, ['status' => 'pending']);
    $none->threads()->create(['body' => 'secret']);
    User::factory()->create(['name' => 'Other', 'email' => 'andi@example.com']);

    $response = $this->getJson('/api/search?q=ANDI&type=users')->assertOk()->assertJsonCount(4, 'data');
    expect(array_column($response->json('data'), 'id'))->toBe([$this->viewer->id, $accepted->id, $pending->id, $none->id]);
    expect(array_column($response->json('data'), 'follow_status'))->toBe(['none', 'following', 'pending', 'none']);
    $response->assertJsonPath('data.0.is_me', true)->assertJsonPath('data.1.is_me', false)
        ->assertJsonPath('data.2.is_private', true)->assertJsonPath('data.3.avatar_url', null);
    expect($response->json('data.1.avatar_url'))->toBeString()->toEndWith('/avatars/example.jpg');
    foreach ($response->json('data') as $item) {
        expect(array_keys($item))->toBe(['id', 'name', 'avatar_url', 'is_private', 'is_me', 'follow_status']);
        expect($item['id'])->toBeInt()->and($item['is_private'])->toBeBool()->and($item['is_me'])->toBeBool();
    }
});

it('treats search characters literally with case insensitive ASCII matching', function (string $term, string $body, string $decoy) {
    $match = $this->viewer->threads()->create(['body' => $body]);
    $this->viewer->threads()->create(['body' => $decoy]);
    $user = User::factory()->create(['name' => $body]);
    User::factory()->create(['name' => $decoy]);
    $q = rawurlencode($term);

    $this->getJson('/api/search?q='.$q)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $match->id);
    $this->getJson('/api/search?type=users&q='.$q)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $user->id);
})->with([
    ['50%', 'Diskon 50% sekarang', 'Diskon 500 sekarang'],
    ['a_b', 'Ada a_b di sini', 'Ada axb di sini'],
    ['hi!', 'Say hi! now', 'Say hi now'],
    ["O'Reilly", "Baca O'Reilly", 'Baca OReilly'],
    ["' OR 1=1 --", "literal ' OR 1=1 -- text", 'unrelated'],
    ['!%_', 'literal !%_ text', 'literal !xx text'],
    ['LARAVEL', 'Belajar Laravel', 'Belajar PHP'],
]);

it('rejects invalid search query parameters', function (array $params, string $field) {
    $this->getJson('/api/search?'.http_build_query($params))->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    [[], 'q'], [['q' => ''], 'q'], [['q' => '   '], 'q'], [['q' => 'a'], 'q'],
    [['q' => str_repeat('a', 101)], 'q'], [['q' => ['ab']], 'q'],
    [['q' => 'valid', 'type' => 'all'], 'type'], [['q' => 'valid', 'type' => ''], 'type'],
    [['q' => 'valid', 'type' => ['users']], 'type'],
    [['q' => 'valid', 'page' => 0], 'page'], [['q' => 'valid', 'page' => -1], 'page'],
    [['q' => 'valid', 'page' => 1.5], 'page'], [['q' => 'valid', 'page' => ''], 'page'],
    [['q' => 'valid', 'page' => [1]], 'page'],
]);

it('accepts boundary length search terms after trimming', function (int $length) {
    $term = str_repeat('a', $length);
    $this->viewer->threads()->create(['body' => $term]);
    $this->getJson('/api/search?q='.rawurlencode('  '.$term.'  '))->assertOk()->assertJsonCount(1, 'data');
})->with([2, 100]);

it('paginates each search type deterministically with only validated parameters', function (string $type) {
    $this->freezeTime();
    $ids = collect(range(1, 17))->map(fn () => $type === 'users'
        ? User::factory()->create(['name' => 'same name'])->id
        : $this->viewer->threads()->create(['body' => 'same text'])->id);
    $first = $this->getJson("/api/search?q=same&type=$type&per_page=100&user_id=999")
        ->assertOk()->assertJsonCount(15, 'data')->assertJsonPath('meta.total', 17)->assertJsonPath('meta.per_page', 15);
    $second = $this->getJson($first->json('links.next'))->assertOk()->assertJsonCount(2, 'data');
    $expected = $type === 'users' ? $ids->all() : $ids->reverse()->values()->all();
    expect(array_merge(array_column($first->json('data'), 'id'), array_column($second->json('data'), 'id')))->toBe($expected);
    parse_str(parse_url($first->json('links.next'), PHP_URL_QUERY), $params);
    expect($params)->toBe(['q' => 'same', 'type' => $type, 'page' => '2']);
    $this->getJson("/api/search?q=same&type=$type&page=3")->assertOk()->assertJsonPath('data', [])
        ->assertJsonPath('meta.total', 17)->assertJsonPath('meta.last_page', 2)->assertJsonPath('meta.from', null);
})->with(['threads', 'users']);

it('returns empty search results with standard pagination metadata', function (string $type) {
    $this->getJson("/api/search?q=missing&type=$type")->assertOk()->assertJsonPath('data', [])
        ->assertJsonPath('meta.total', 0)->assertJsonPath('meta.last_page', 1)
        ->assertJsonPath('meta.from', null)->assertJsonPath('meta.to', null);
})->with(['threads', 'users']);

it('uses query parameters rather than a GET JSON body for search', function () {
    $thread = $this->viewer->threads()->create(['body' => 'laravel']);
    $this->json('GET', '/api/search', ['q' => 'laravel'])->assertUnprocessable()->assertJsonValidationErrors('q');
    $this->json('GET', '/api/search?q=laravel', ['q' => 'missing', 'type' => 'users', 'page' => 9])
        ->assertOk()->assertJsonPath('data.0.id', $thread->id)->assertJsonPath('meta.current_page', 1);
});

it('requires authentication and retains browser navigation blocking for search', function () {
    $this->get('/api/search?q=laravel', ['Accept' => 'text/html'])->assertNotFound();
    $this->get('/api/search?q=laravel', ['Accept' => 'application/json', 'Sec-Fetch-Mode' => 'navigate'])->assertNotFound();
    $this->app['auth']->forgetGuards();
    $this->getJson('/api/search?q=laravel')->assertUnauthorized();
});

it('loads search results without one extra query per author or follow status', function (string $type) {
    $addUser = function () {
        $user = User::factory()->create(['name' => 'Search match']);
        $this->viewer->following()->attach($user->id);
        $user->threads()->create(['body' => 'search match']);
    };
    $countQueries = function () use ($type) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $response = $this->getJson("/api/search?q=match&type=$type")->assertOk();

            return [count(DB::getQueryLog()), $response->json('data')];
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    };

    $addUser();
    [$singleCount] = $countQueries();
    foreach (range(1, 14) as $i) {
        $addUser();
    }
    [$manyCount, $data] = $countQueries();
    expect($data)->toHaveCount(15)->and($manyCount)->toBeLessThanOrEqual($singleCount);
})->with(['threads', 'users']);

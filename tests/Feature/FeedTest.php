<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->viewer = User::factory()->create();
    Sanctum::actingAs($this->viewer);
});

it('defaults to all visible threads while following includes only accepted other authors', function () {
    $public = User::factory()->create();
    $private = User::factory()->create(['is_private' => true]);
    $pending = User::factory()->create(['is_private' => true]);
    $stranger = User::factory()->create();
    $hidden = User::factory()->create(['is_private' => true]);
    $this->viewer->following()->attach([$public->id, $private->id]);
    $this->viewer->following()->attach($pending->id, ['status' => 'pending']);

    $mine = $this->viewer->threads()->create(['body' => 'mine']);
    $publicThread = $public->threads()->create(['body' => 'public']);
    $privateThread = $private->threads()->create(['body' => 'private accepted']);
    $strangerThread = $stranger->threads()->create(['body' => 'public stranger']);
    $pending->threads()->create(['body' => 'private pending']);
    $hidden->threads()->create(['body' => 'private stranger']);
    $deleted = $public->threads()->create(['body' => 'deleted']);
    $deleted->softDelete();

    $all = $this->getJson('/api/threads')->assertOk()->assertJsonPath('meta.per_page', 15);
    expect(collect($all->json('data'))->pluck('id')->all())
        ->toEqualCanonicalizing([$mine->id, $publicThread->id, $privateThread->id, $strangerThread->id]);
    expect($this->getJson('/api/threads?feed=all')->json('data'))->toBe($all->json('data'));

    $following = $this->getJson('/api/threads?feed=following')->assertOk()
        ->assertJsonPath('meta.total', 2);
    expect(collect($following->json('data'))->pluck('id')->all())
        ->toEqualCanonicalizing([$publicThread->id, $privateThread->id]);
});

it('returns an empty following feed before following anyone or when followed accounts have no threads', function () {
    User::factory()->create()->threads()->create(['body' => 'unfollowed']);

    $this->getJson('/api/threads?feed=following')->assertOk()
        ->assertJsonPath('data', [])->assertJsonPath('meta.total', 0)
        ->assertJsonPath('meta.last_page', 1)->assertJsonPath('meta.from', null)
        ->assertJsonPath('meta.to', null);

    $this->viewer->following()->attach(User::factory()->create()->id);
    $this->getJson('/api/threads?feed=following')->assertOk()->assertJsonPath('data', []);
});

it('reflects follow approval unfollow and privacy changes on the next feed request', function () {
    $owner = User::factory()->create(['is_private' => true]);
    $thread = $owner->threads()->create(['body' => 'private thread']);
    $this->postJson("/api/users/{$owner->id}/follow")->assertOk();
    $this->getJson('/api/threads?feed=following')->assertJsonPath('meta.total', 0);

    Sanctum::actingAs($owner);
    $this->postJson("/api/follow-requests/{$this->viewer->id}/accept")->assertOk();
    Sanctum::actingAs($this->viewer);
    $this->getJson('/api/threads?feed=following')->assertJsonPath('data.0.id', $thread->id);

    $this->postJson("/api/users/{$owner->id}/follow")->assertOk();
    $this->getJson('/api/threads?feed=following')->assertJsonPath('meta.total', 0);
    $this->getJson('/api/threads')->assertJsonPath('meta.total', 0);

    $owner->forceFill(['is_private' => false])->save();
    $this->getJson('/api/threads')->assertJsonPath('meta.total', 1);
    $this->getJson('/api/threads?feed=following')->assertJsonPath('meta.total', 0);
    $this->postJson("/api/users/{$owner->id}/follow")->assertOk();
    $this->getJson('/api/threads?feed=following')->assertJsonPath('meta.total', 1);
});

it('paginates following deterministically and retains only validated filters in links', function () {
    $this->freezeTime();
    $author = User::factory()->create();
    $this->viewer->following()->attach($author->id);
    $ids = collect(range(1, 17))->map(fn ($i) => $author->threads()->create(['body' => "thread $i"])->id);

    $first = $this->getJson('/api/threads?feed=following&per_page=100&user_id=999')
        ->assertOk()->assertJsonCount(15, 'data')->assertJsonPath('meta.total', 17);
    $second = $this->getJson($first->json('links.next'))->assertOk()->assertJsonCount(2, 'data');
    expect(array_merge(array_column($first->json('data'), 'id'), array_column($second->json('data'), 'id')))
        ->toBe($ids->reverse()->values()->all());
    parse_str(parse_url($first->json('links.next'), PHP_URL_QUERY), $params);
    expect($params)->toBe(['feed' => 'following', 'page' => '2']);

    $this->getJson('/api/threads?feed=following&page=3')->assertOk()
        ->assertJsonPath('data', [])->assertJsonPath('meta.total', 17)
        ->assertJsonPath('meta.last_page', 2)->assertJsonPath('meta.from', null);
});

it('rejects invalid feed query parameters', function (string $query, string $field) {
    $this->getJson('/api/threads?'.$query)->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    ['feed=unknown', 'feed'], ['feed=', 'feed'], ['feed[]=all', 'feed'],
    ['page=0', 'page'], ['page=-1', 'page'], ['page=1.5', 'page'],
    ['page=', 'page'], ['page[]=1', 'page'], ['page=abc', 'page'],
]);

it('uses only query parameters even when a GET JSON body requests another feed', function () {
    $this->viewer->threads()->create(['body' => 'own thread']);
    $this->json('GET', '/api/threads', ['feed' => 'following', 'page' => 99])->assertOk()
        ->assertJsonPath('meta.total', 1)->assertJsonPath('meta.current_page', 1);
});

it('requires authentication and retains the browser navigation block for feeds', function () {
    $this->get('/api/threads?feed=following', ['Accept' => 'text/html'])->assertNotFound();
    $this->get('/api/threads?feed=following', [
        'Accept' => 'application/json', 'Sec-Fetch-Mode' => 'navigate',
    ])->assertNotFound();
    $this->app['auth']->forgetGuards();
    $this->getJson('/api/threads?feed=following')->assertUnauthorized();
});

it('loads feed authors and images without a query per item', function () {
    $addAuthor = function () {
        $author = User::factory()->create();
        $this->viewer->following()->attach($author->id);
        $author->threads()->create(['body' => 'thread'])->images()->create(['path' => 'example.jpg', 'order' => 0]);
    };
    $countQueries = function () {
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $response = $this->getJson('/api/threads?feed=following')->assertOk();

            return [count(DB::getQueryLog()), $response->json('data')];
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    };

    $addAuthor();
    [$singleCount] = $countQueries();
    foreach (range(1, 14) as $i) {
        $addAuthor();
    }
    [$manyCount, $data] = $countQueries();
    expect($data)->toHaveCount(15)->and($manyCount)->toBeLessThanOrEqual($singleCount);
});

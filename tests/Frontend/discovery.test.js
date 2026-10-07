import test, { beforeEach } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { listState, discoveryFeed, discoverySearch, registerDiscovery } from '../../resources/js/discovery.js';

const response = (data, page = 1, last = 1) => ({ data, meta: { current_page: page, last_page: last, total: data.length } });
const deferred = () => {
    let resolve, reject;
    const promise = new Promise((yes, no) => { resolve = yes; reject = no; });
    return { promise, resolve, reject };
};

beforeEach(() => {
    globalThis.window = {
        location: { href: 'http://localhost/dashboard', search: '' },
        history: { pushState(_state, _title, url) { window.lastUrl = String(url); } },
        imagePicker: () => ({ files: [], reset() { this.files = []; } }),
        dispatchEvent(event) { window.lastEvent = event; },
    };
    registerDiscovery();
    globalThis.CustomEvent = class { constructor(type, options) { this.type = type; this.detail = options.detail; } };
});

test('ignores stale feed responses and preserves the latest loading state', async () => {
    const old = deferred(), latest = deferred();
    let calls = 0;
    window.api = () => (++calls === 1 ? old.promise : latest.promise);
    const feed = discoveryFeed('/api/threads', true);
    feed.body = 'draft';
    const first = feed.load(true);
    const second = feed.selectFeed('following');
    old.resolve(response([{ id: 1 }])); await first;
    assert.equal(feed.loading, true); assert.deepEqual(feed.threads, []);
    latest.resolve(response([{ id: 2 }])); await second;
    assert.deepEqual(feed.threads, [{ id: 2 }]); assert.equal(feed.body, 'draft');
    assert.equal(feed.loading, false); assert.match(window.lastUrl, /feed=following/);
});

test('does not advance a failed page and retries without duplicating results', async () => {
    const state = listState(); state.requestUrl = page => `/list?page=${page}`;
    window.api = async () => response([{ id: 1 }], 1, 2);
    await state.load(true);
    window.api = async () => { throw new Error('offline'); };
    await state.load();
    assert.equal(state.page, 1); assert.deepEqual(state.threads, [{ id: 1 }]);
    window.api = async (_method, url) => { assert.match(url, /page=2/); return response([{ id: 1 }, { id: 2 }], 2, 2); };
    await state.retry();
    assert.deepEqual(state.threads, [{ id: 1 }, { id: 2 }]); assert.equal(state.hasMore, false);
});

test('blocks concurrent pagination requests', async () => {
    const pending = deferred(); let calls = 0;
    const state = listState(); state.requestUrl = () => '/list';
    window.api = () => { calls++; return pending.promise; };
    const first = state.load(); await state.load();
    assert.equal(calls, 1); pending.resolve(response([])); await first;
});

test('old request failure cannot overwrite the latest search result', async () => {
    const old = deferred(); let calls = 0;
    const search = discoverySearch(); search.draftQuery = 'old';
    window.api = () => ++calls === 1 ? old.promise : Promise.resolve(response([{ id: 2 }]));
    const first = search.submit(); search.draftQuery = 'new'; await search.submit();
    old.reject(new Error('offline')); await first;
    assert.equal(search.loadError, ''); assert.deepEqual(search.threads, [{ id: 2 }]);
});

test('changing search type uses submitted query rather than unsubmitted text', async () => {
    const search = discoverySearch(); const urls = [];
    window.api = async (_method, url) => { urls.push(url); return response([]); };
    search.draftQuery = 'a_b%'; await search.submit(); search.draftQuery = 'different';
    await search.selectType('users');
    const params = new URL(urls.at(-1), 'http://localhost').searchParams;
    assert.equal(params.get('q'), 'a_b%'); assert.equal(params.get('type'), 'users');
    assert.equal(search.draftQuery, 'different');
});

test('restoring an empty search invalidates an outstanding result', async () => {
    const search = discoverySearch(), pending = deferred();
    window.api = () => pending.promise; search.draftQuery = 'valid';
    const first = search.submit(); window.location.search = '?type=users';
    search.restoreSearch(); pending.resolve(response([{ id: 1 }])); await first;
    assert.equal(search.submittedQuery, ''); assert.equal(search.type, 'users'); assert.deepEqual(search.users, []);
});

test('invalid queries do not call the API', async () => {
    const search = discoverySearch(); let calls = 0;
    window.api = async () => { calls++; return response([]); };
    for (const q of ['', 'a', ' '.repeat(3), 'a'.repeat(101)]) { search.draftQuery = q; await search.submit(); }
    assert.equal(calls, 0);
});

test('own new post never enters the following feed', async () => {
    const feed = discoveryFeed('/api/threads', true); feed.activeFeed = 'following'; feed.body = 'post';
    window.api = async () => ({ data: { id: 1 } });
    await feed.post();
    assert.deepEqual(feed.threads, []); assert.equal(feed.body, ''); assert.ok(feed.postNotice);
});

test('My Threads uses its original API source without global feed filter', () => {
    const feed = discoveryFeed('/api/users/10/threads', false);
    window.location.search = '?feed=following';
    assert.equal(feed.readFeed(), 'all'); assert.equal(feed.requestUrl(2), '/api/users/10/threads?page=2');
});

test('follow and like busy states block duplicates and release on failure', async () => {
    const state = listState(), pending = deferred(); let calls = 0;
    const user = { id: 1, follow_status: 'none' };
    window.api = () => { calls++; return pending.promise; };
    const follow = state.toggleFollow(user); await state.toggleFollow(user);
    assert.equal(calls, 1); pending.reject(new Error('offline')); await follow;
    assert.equal(user.follow_status, 'none'); assert.equal(state.followBusy[1], false);
    const thread = { id: 2, liked_by_me: false, likes_count: 0 };
    await state.like(thread); assert.equal(thread.likes_count, 0); assert.equal(state.likeBusy[2], false);
});

test('successful follow broadcasts the server state and self follow is skipped', async () => {
    const state = listState();
    window.api = async () => ({ status: 'pending' });
    await state.toggleFollow({ id: 1, is_me: false, follow_status: 'none' });
    assert.deepEqual(window.lastEvent.detail, { userId: 1, status: 'pending' });
    window.api = () => { throw new Error('must not call'); };
    await state.toggleFollow({ id: 2, is_me: true });
});

test('session errors are distinct from empty results', async () => {
    const state = listState(); state.requestUrl = () => '/list';
    window.api = async () => { const error = new Error(); error.status = 419; throw error; };
    await state.load(true);
    assert.equal(state.sessionExpired, true); assert.match(state.loadError, /Sesi berakhir/); assert.equal(state.page, 0);
});

test('like and follow button bindings always produce booleans before any interaction', () => {
    for (const [view, map, item] of [
        ['../../resources/views/threads/_card.blade.php', 'likeBusy', 't'],
        ['../../resources/views/search/index.blade.php', 'followBusy', 'u'],
    ]) {
        const markup = readFileSync(new URL(view, import.meta.url), 'utf8');
        const expression = markup.match(new RegExp(`:disabled="(${map}[^"\\n]+)"`))[1];
        const evaluate = new Function(map, item, `return (${expression});`);
        // Alpine turns undefined expressions containing a dot into an empty string;
        // its boolean attribute binder then adds the disabled attribute for that string.
        assert.equal(evaluate({}, { id: 1 }), false);
        assert.equal(evaluate({ 1: true }, { id: 1 }), true);
        assert.equal(evaluate({ 1: false }, { id: 1 }), false);
    }
});

test('like toggles successfully and the button is released after each request', async () => {
    const state = listState();
    const thread = { id: 12, liked_by_me: false, likes_count: 0 };
    let requests = 0;
    window.api = async (method, url) => {
        assert.equal(method, 'POST'); assert.equal(url, '/api/threads/12/like');
        requests++;
        return { liked: requests === 1, likes_count: requests === 1 ? 1 : 0 };
    };
    await state.like(thread);
    assert.equal(thread.liked_by_me, true); assert.equal(thread.likes_count, 1); assert.equal(state.likeBusy[12], false);
    await state.like(thread);
    assert.equal(thread.liked_by_me, false); assert.equal(thread.likes_count, 0); assert.equal(state.likeBusy[12], false);
});

export function listState() {
    return {
        threads: [], users: [], page: 0, hasMore: false, total: 0,
        loading: false, loadingMore: false, loadError: '', sessionExpired: false, generation: 0,
        likeBusy: {}, actionErrors: {}, followBusy: {}, followErrors: {},
        editingId: null, editBody: '', editBusy: false,
        async load(reset = false) {
            if (!reset && (this.loading || this.loadingMore)) return;
            const generation = reset ? ++this.generation : this.generation;
            const page = reset ? 1 : this.page + 1;
            if (reset) {
                this.threads = []; this.users = []; this.page = 0; this.hasMore = false;
                this.total = 0; this.loading = true; this.loadingMore = false;
            } else this.loadingMore = true;
            this.loadError = ''; this.sessionExpired = false;
            try {
                const res = await window.api('GET', this.requestUrl(page));
                if (generation !== this.generation) return;
                const key = this.type === 'users' ? 'users' : 'threads';
                const seen = new Set(this[key].map(item => item.id));
                this[key].push(...res.data.filter(item => !seen.has(item.id)));
                this.page = res.meta.current_page;
                this.total = res.meta.total;
                this.hasMore = res.meta.current_page < res.meta.last_page;
            } catch (error) {
                if (generation !== this.generation) return;
                this.sessionExpired = [401, 419].includes(error.status);
                this.loadError = window.discoveryError(error);
                if (error.status === 422 && 'validationError' in this) this.validationError = this.loadError;
            } finally {
                if (generation === this.generation) { this.loading = false; this.loadingMore = false; }
            }
        },
        retry() { return this.load(this.page === 0); },
        async like(t) {
            if (this.likeBusy[t.id]) return;
            this.likeBusy[t.id] = true; this.actionErrors[t.id] = '';
            try {
                const res = await window.api('POST', `/api/threads/${t.id}/like`);
                t.liked_by_me = res.liked; t.likes_count = res.likes_count;
            } catch (error) { this.sessionExpired = [401, 419].includes(error.status); this.actionErrors[t.id] = window.discoveryError(error); }
            finally { this.likeBusy[t.id] = false; }
        },
        openImage(images, index) {
            window.GLightbox({ elements: images.map(i => ({ href: i.url, type: 'image' })), startAt: index, loop: true }).open();
        },
        startEdit(t) { this.editingId = t.id; this.editBody = t.body; },
        async saveEdit(t) {
            if (this.editBusy) return;
            this.editBusy = true; this.actionErrors[t.id] = '';
            try {
                Object.assign(t, (await window.api('PUT', `/api/threads/${t.id}`, { body: this.editBody })).data);
                this.editingId = null;
            } catch (error) { this.sessionExpired = [401, 419].includes(error.status); this.actionErrors[t.id] = window.discoveryError(error); }
            finally { this.editBusy = false; }
        },
        async remove(t) {
            if (this.likeBusy[t.id] || !window.confirm('Hapus thread ini?')) return;
            this.likeBusy[t.id] = true; this.actionErrors[t.id] = '';
            try {
                await window.api('DELETE', `/api/threads/${t.id}`);
                this.threads = this.threads.filter(item => item.id !== t.id);
            } catch (error) { this.sessionExpired = [401, 419].includes(error.status); this.actionErrors[t.id] = window.discoveryError(error); }
            finally { this.likeBusy[t.id] = false; }
        },
        followLabel(user) { return { none: 'Ikuti', pending: 'Diminta', following: 'Mengikuti' }[user.follow_status]; },
        syncFollow(detail) {
            for (const user of this.users) if (user.id === detail.userId) user.follow_status = detail.status;
        },
        async toggleFollow(user) {
            if (user.is_me || this.followBusy[user.id]) return;
            this.followBusy[user.id] = true; this.followErrors[user.id] = '';
            try {
                const res = await window.api('POST', `/api/users/${user.id}/follow`);
                user.follow_status = res.status;
                window.dispatchEvent(new CustomEvent('follow-changed', { detail: { userId: user.id, status: res.status } }));
            } catch (error) { this.sessionExpired = [401, 419].includes(error.status); this.followErrors[user.id] = window.discoveryError(error); }
            finally { this.followBusy[user.id] = false; }
        },
    };
}

export function discoveryFeed(source, dashboard = false) {
    return {
        ...listState(), ...window.imagePicker(), source, dashboard, activeFeed: 'all',
        body: '', posting: false, error: '', postNotice: '',
        init() { this.activeFeed = this.readFeed(); return this.load(true); },
        readFeed() { return this.dashboard && new URLSearchParams(window.location.search).get('feed') === 'following' ? 'following' : 'all'; },
        requestUrl(page) {
            const params = new URLSearchParams({ page });
            if (this.dashboard) params.set('feed', this.activeFeed);
            return `${this.source}?${params}`;
        },
        selectFeed(feed, history = true) {
            this.activeFeed = feed; this.editingId = null;
            if (history) {
                const url = new URL(window.location.href);
                url.searchParams.set('feed', feed);
                window.history.pushState({}, '', url);
            }
            return this.load(true);
        },
        restoreFeed() { if (this.dashboard) return this.selectFeed(this.readFeed(), false); },
        followChanged() { if (this.dashboard && this.activeFeed === 'following') return this.load(true); },
        async post() {
            if (this.posting || !this.body.trim()) return;
            this.posting = true; this.error = ''; this.postNotice = '';
            const data = new FormData();
            data.append('body', this.body); this.files.forEach(file => data.append('images[]', file));
            try {
                const res = await window.api('POST', '/api/threads', data);
                this.body = ''; this.reset();
                if (this.dashboard && this.activeFeed === 'following') {
                    this.postNotice = 'Postingan berhasil dibuat. Lihat di tab Semua.';
                } else if (!this.threads.some(item => item.id === res.data.id)) this.threads.unshift(res.data);
            } catch (error) { this.sessionExpired = [401, 419].includes(error.status); this.error = window.discoveryError(error); }
            finally { this.posting = false; }
        },
    };
}

export function discoverySearch() {
    return {
        ...listState(), draftQuery: '', submittedQuery: '', type: 'threads', validationError: '',
        init() { return this.restoreSearch(); },
        requestUrl(page) { return `/api/search?${new URLSearchParams({ q: this.submittedQuery, type: this.type, page })}`; },
        restoreSearch() {
            const params = new URLSearchParams(window.location.search);
            this.draftQuery = params.get('q') || ''; this.type = params.get('type') === 'users' ? 'users' : 'threads';
            return this.submit(false);
        },
        validQuery(value) {
            const length = Array.from(value).length;
            return length >= 2 && length <= 100;
        },
        submit(history = true) {
            const query = this.draftQuery.trim();
            this.validationError = '';
            if (!this.validQuery(query)) {
                this.validationError = query ? 'Masukkan kata pencarian sepanjang 2–100 karakter.' : '';
                if (!history) {
                    ++this.generation; this.submittedQuery = ''; this.threads = []; this.users = [];
                    this.loading = false; this.loadingMore = false; this.loadError = ''; this.hasMore = false; this.page = 0;
                }
                return;
            }
            this.submittedQuery = query; this.draftQuery = query;
            if (history) this.updateUrl();
            return this.load(true);
        },
        selectType(type) {
            this.type = type;
            this.updateUrl();
            if (this.submittedQuery) return this.load(true);
        },
        updateUrl() {
            const params = new URLSearchParams({ type: this.type });
            if (this.submittedQuery) params.set('q', this.submittedQuery);
            window.history.pushState({}, '', `/search?${params}`);
        },
    };
}

export function searchShortcut() {
    return {
        query: '', error: '',
        submit() {
            const query = this.query.trim();
            if (Array.from(query).length < 2 || Array.from(query).length > 100) {
                this.error = 'Masukkan kata pencarian sepanjang 2–100 karakter.'; return;
            }
            window.location.assign(`/search?${new URLSearchParams({ q: query, type: 'threads' })}`);
        },
    };
}

export function registerDiscovery() {
    window.discoveryFeed = discoveryFeed;
    window.discoverySearch = discoverySearch;
    window.searchShortcut = searchShortcut;
    window.discoveryError = error => [401, 419].includes(error.status)
        ? 'Sesi berakhir. Silakan masuk kembali.'
        : error.status === 422 ? Object.values(error.errors || {}).flat()[0] || error.message
            : 'Gagal memuat data. Coba lagi.';
}

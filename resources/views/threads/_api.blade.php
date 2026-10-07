<script>
    window.ME = {{ auth()->id() }};

    // Wrapper fetch ke /api memakai session cookie (Sanctum stateful) + CSRF.
    window.api = async function (method, url, data = null) {
        const headers = {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        };
        const options = { method, headers, credentials: 'same-origin' };

        if (data instanceof FormData) {
            if (method !== 'POST') {
                data.append('_method', method);
                options.method = 'POST';
            }
            options.body = data;
        } else if (data) {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }

        const res = await fetch(url, options);
        const json = await res.json().catch(() => ({}));

        if (!res.ok) {
            const err = new Error(json.message || 'Terjadi kesalahan.');
            err.errors = json.errors || {};
            err.status = res.status;
            throw err;
        }

        return json;
    };

    // Profil sendiri → /profile, orang lain → /users/{id}.
    window.profileUrl = function (id) {
        return id === window.ME ? '/profile' : `/users/${id}`;
    };

    // Inisial (maks. 2 huruf) untuk avatar cadangan.
    window.initials = function (name) {
        return (name || '').trim().split(/\s+/).filter(Boolean).slice(0, 2)
            .map(w => w[0].toUpperCase()).join('');
    };

    window.timeAgo = function (iso) {
        const s = Math.floor((Date.now() - new Date(iso)) / 1000);
        if (s < 60) return 'baru saja';
        if (s < 3600) return Math.floor(s / 60) + ' menit lalu';
        if (s < 86400) return Math.floor(s / 3600) + ' jam lalu';
        return new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    };

    // Helper pemilih gambar (maksimal 5) untuk form compose / edit.
    window.imagePicker = function () {
        return {
            files: [],
            previews: [],
            // Pilih gambar → atur (crop) satu per satu → masuk ke daftar lampiran.
            async pick(event) {
                const incoming = Array.from(event.target.files).slice(0, Math.max(5 - this.files.length, 0));
                event.target.value = '';
                const cropped = await cropImages(incoming);
                this.files = this.files.concat(cropped).slice(0, 5);
                this.previews = this.files.map(f => URL.createObjectURL(f));
            },
            removeFile(i) {
                this.files.splice(i, 1);
                this.previews.splice(i, 1);
            },
            reset() { this.files = []; this.previews = []; },
        };
    };
</script>

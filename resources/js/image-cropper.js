// Crop gambar sebelum diunggah (Cropper.js dimuat lazy saat pertama dipakai).
//
//   const file = await window.cropImage(file, { aspect: 1, round: true, ratios: false });
//   // → File hasil crop, 'original' bila memilih "Pakai asli", atau null bila dibatalkan.
//   const files = await window.cropImages(fileList, opts);   // berurutan, mengabaikan yang dibatalkan

const RATIOS = [
    { label: 'Bebas', value: NaN },
    { label: '1:1', value: 1 },
    { label: '4:3', value: 4 / 3 },
    { label: '16:9', value: 16 / 9 },
    { label: '3:4', value: 3 / 4 },
];

const MAX_SIDE = 2048;

const EXT = { 'image/png': 'png', 'image/webp': 'webp', 'image/jpeg': 'jpg' };

export default function registerImageCropper(Alpine) {
    Alpine.data('imageCropper', () => ({
        open: false,
        src: '',
        title: 'Atur gambar',
        round: false,
        showRatios: true,
        allowOriginal: true,
        ratios: RATIOS,
        current: NaN,
        position: '',
        busy: false,
        cropper: null,
        file: null,
        resolve: null,

        init() {
            window.cropImage = (file, opts) => this.start(file, opts);

            window.cropImages = async (files, opts = {}) => {
                const list = Array.from(files).filter(f => f.type.startsWith('image/'));
                const out = [];
                for (let i = 0; i < list.length; i++) {
                    const res = await this.start(list[i], {
                        ...opts,
                        position: list.length > 1 ? `Gambar ${i + 1} dari ${list.length}` : '',
                    });
                    if (res === 'original') out.push(list[i]);
                    else if (res) out.push(res);
                }
                return out;
            };

            this.$watch('open', v => document.documentElement.classList.toggle('overflow-hidden', v));
        },

        start(file, opts = {}) {
            // GIF: canvas membuang animasi, jadi dipakai apa adanya.
            if (file.type === 'image/gif') return Promise.resolve('original');

            // Hanya satu sesi crop pada satu waktu.
            if (this.resolve) this.finish(null);

            return new Promise(resolve => {
                this.resolve = resolve;
                this.file = file;
                this.title = opts.title || 'Atur gambar';
                this.round = !!opts.round;
                this.showRatios = opts.ratios !== false;
                this.allowOriginal = opts.original !== false;
                this.current = opts.aspect === undefined ? NaN : opts.aspect;
                this.position = opts.position || '';
                this.busy = true;
                this.src = URL.createObjectURL(file);
                this.open = true;
            });
        },

        async setup() {
            const { default: Cropper } = await import('cropperjs');

            this.cropper?.destroy();
            this.cropper = new Cropper(this.$refs.image, {
                viewMode: 1,
                dragMode: 'move',
                aspectRatio: this.current,
                autoCropArea: 1,
                background: false,
                checkOrientation: true,
                toggleDragModeOnDblclick: false,
            });
            this.busy = false;
            this.$nextTick(() => this.$refs.confirm?.focus());
        },

        setRatio(value) {
            this.current = value;
            this.cropper?.setAspectRatio(value);
        },

        rotate(deg) {
            this.cropper?.rotate(deg);
        },

        confirm() {
            if (! this.cropper || this.busy) return;
            this.busy = true;

            const type = EXT[this.file.type] ? this.file.type : 'image/jpeg';
            const canvas = this.cropper.getCroppedCanvas({
                maxWidth: MAX_SIDE,
                maxHeight: MAX_SIDE,
                imageSmoothingQuality: 'high',
            });

            canvas.toBlob(blob => {
                if (! blob) return this.finish(null);
                const base = this.file.name.replace(/\.[^.]+$/, '') || 'gambar';
                this.finish(new File([blob], `${base}.${EXT[type]}`, { type }));
            }, type, 0.92);
        },

        useOriginal() {
            this.finish('original');
        },

        cancel() {
            if (this.open) this.finish(null);
        },

        finish(result) {
            const resolve = this.resolve;
            this.resolve = null;
            this.cropper?.destroy();
            this.cropper = null;
            if (this.src) URL.revokeObjectURL(this.src);
            this.src = '';
            this.file = null;
            this.busy = false;
            this.open = false;
            resolve?.(result);
        },
    }));
}

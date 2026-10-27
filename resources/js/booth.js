import Alpine from 'alpinejs';
import { readBarcodes } from 'zxing-wasm/reader';

const SCAN_SIZE = 960;
const SCAN_DELAY_MS = 120;

Alpine.data('booth', (config = {}) => ({
    // ---- state machine ----
    // scanning → resolving → selecting → confirm_popup → confirming → (toast + scanning)
    // error branches: unknown_qr | already_voted | closed | camera_error
    state: config.closed ? 'closed' : 'scanning',

    // ---- data ----
    candidates: config.candidates ?? [],
    student: null,
    selectedId: null,
    countedNumber: null,
    notice: '',
    toasts: [],
    toastSeq: 0,

    // ---- camera ----
    cameraOn: false,
    devices: [],
    selectedDeviceId: '',
    cameraError: '',
    stream: null,
    decoding: false,
    locked: false,
    scanCanvas: null,

    csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    },

    candidateNumber(id) {
        return this.candidates.find((c) => c.id === id)?.number ?? '?';
    },

    async init() {
        if (this.state === 'closed') return;
        await this.listDevices();
        // Kamera otomatis menyala saat halaman dibuka — tanpa tombol.
        await this.startCamera();
    },

    // ---- camera controls ----

    async listDevices() {
        try {
            if (!navigator.mediaDevices?.enumerateDevices) return;
            const all = await navigator.mediaDevices.enumerateDevices();
            this.devices = all.filter((d) => d.kind === 'videoinput');
            if (!this.selectedDeviceId && this.devices.length > 0) {
                const rear = this.devices.find((d) => /back|rear|environment|belakang/i.test(d.label));
                this.selectedDeviceId = (rear ?? this.devices[this.devices.length - 1]).deviceId;
            }
        } catch {
            // Labels need permission; dropdown simply stays empty.
        }
    },

    async startCamera() {
        this.cameraError = '';
        if (!navigator.mediaDevices?.getUserMedia) {
            this.cameraError = 'Kamera tidak tersedia di browser ini (butuh HTTPS/localhost). Hubungi panitia.';
            this.state = 'camera_error';
            return;
        }
        try {
            if (this.stream) this.stopTracks();
            const base = this.selectedDeviceId
                ? { deviceId: { exact: this.selectedDeviceId } }
                : { facingMode: { ideal: 'environment' } };
            // Minta resolusi HD agar QR kecil/jauh tetap terbaca; crop persegi mengikuti viewfinder.
            const video = { ...base, width: { ideal: 1280 }, height: { ideal: 720 } };
            this.stream = await navigator.mediaDevices.getUserMedia({ video, audio: false });
            const el = this.$refs.video;
            el.srcObject = this.stream;
            await el.play();
            this.cameraOn = true;
            await this.listDevices();
            this.scanLoop();
        } catch {
            this.cameraOn = false;
            this.cameraError = 'Kamera gagal dibuka (izin ditolak/tidak ada kamera). Hubungi panitia.';
            this.state = 'camera_error';
        }
    },

    stopTracks() {
        this.stream?.getTracks().forEach((t) => t.stop());
        this.stream = null;
        const el = this.$refs.video;
        if (el) el.srcObject = null;
    },

    stopCamera() {
        this.cameraOn = false;
        this.stopTracks();
    },

    async restartCamera() {
        this.stopCamera();
        if (this.state === 'camera_error') this.state = 'scanning';
        await this.startCamera();
    },

    scanLoop() {
        if (!this.cameraOn) return;
        // Scanner active ONLY in `scanning`; paused while any modal is open.
        if (this.state === 'scanning' && !this.locked && !this.decoding) {
            const video = this.$refs.video;
            if (video && video.readyState >= 2) {
                this.decoding = true;
                this.decodeFrame(video).finally(() => {
                    this.decoding = false;
                });
            }
        }
        setTimeout(() => this.scanLoop(), SCAN_DELAY_MS);
    },

    async decodeFrame(video) {
        try {
            if (!this.scanCanvas) {
                this.scanCanvas = document.createElement('canvas');
                this.scanCanvas.width = SCAN_SIZE;
                this.scanCanvas.height = SCAN_SIZE;
            }
            // Crop persegi tengah (seperti object-cover di viewfinder yang kini aspect-square).
            const vw = video.videoWidth || SCAN_SIZE;
            const vh = video.videoHeight || SCAN_SIZE;
            const side = Math.min(vw, vh);
            const sx = (vw - side) / 2;
            const sy = (vh - side) / 2;
            const ctx = this.scanCanvas.getContext('2d', { willReadFrequently: true });
            ctx.drawImage(video, sx, sy, side, side, 0, 0, SCAN_SIZE, SCAN_SIZE);
            const imageData = ctx.getImageData(0, 0, SCAN_SIZE, SCAN_SIZE);
            const results = await readBarcodes(imageData, {
                tryHarder: true,
                formats: ['QRCode'],
                maxNumberOfSymbols: 1,
            });
            const text = results?.[0]?.text;
            // locked=true stops double-decode until back to `scanning`.
            if (text && this.state === 'scanning' && !this.locked) {
                this.resolveUuid(text);
            }
        } catch {
            // Transient decode failure; next tick retries.
        }
    },

    // ---- resolve → select → confirm ----

    async resolveUuid(raw) {
        const uuid = (typeof raw === 'string' ? raw : '').trim();
        if (this.state !== 'scanning' && this.state !== 'camera_error') return;
        this.locked = true;
        this.notice = '';
        this.state = 'resolving';
        try {
            const res = await fetch(`/api/voters/${encodeURIComponent(uuid)}`, {
                headers: { Accept: 'application/json' },
            });
            if (res.status === 403) {
                this.state = 'closed';
                return;
            }
            if (res.status === 404) {
                this.student = null;
                this.state = 'unknown_qr';
                return;
            }
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            this.student = data.student;
            this.selectedId = null;
            this.state = data.already_voted ? 'already_voted' : 'selecting';
        } catch {
            this.locked = false;
            this.notice = 'Jaringan bermasalah, coba lagi.';
            this.state = 'scanning';
        }
    },

    askConfirm() {
        if (!this.selectedId || this.confirming) return;
        this.state = 'confirm_popup';
    },

    voterInitial() {
        return (this.student?.name ?? '?').trim().charAt(0).toUpperCase() || '?';
    },

    pushToast(message) {
        const id = ++this.toastSeq;
        this.toasts.push({ id, message });
        setTimeout(() => this.dismissToast(id), 3000);
    },

    dismissToast(id) {
        this.toasts = this.toasts.filter((t) => t.id !== id);
    },

    async confirmVote() {
        if (!this.selectedId || this.confirming) return;
        this.confirming = true;
        this.state = 'confirming';
        try {
            const res = await fetch('/api/votes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrf(),
                },
                body: JSON.stringify({
                    student_uuid: this.student.uuid,
                    candidate_id: this.selectedId,
                }),
            });
            if (res.status === 201) {
                const data = await res.json();
                this.countedNumber = data.candidate_number;
                this.pushToast(`Suara ${this.student?.name ?? ''} tercatat untuk Paslon No. ${data.candidate_number}`);
                this.resetToScanning();
                return;
            }
            if (res.status === 409) {
                this.state = 'already_voted';
                return;
            }
            if (res.status === 403) {
                this.state = 'closed';
                return;
            }
            if (res.status === 404) {
                this.state = 'unknown_qr';
                return;
            }
            this.notice = 'Suara tidak dapat dicatat, coba lagi.';
            this.state = 'selecting';
        } catch {
            this.notice = 'Jaringan bermasalah, coba lagi.';
            this.state = 'selecting';
        } finally {
            this.confirming = false;
        }
    },

    confirming: false,

    resetToScanning() {
        this.student = null;
        this.selectedId = null;
        this.countedNumber = null;
        this.notice = '';
        this.confirming = false;
        this.locked = false;
        this.state = 'scanning';
    },
}));

Alpine.start();

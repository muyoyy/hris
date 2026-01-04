<x-filament-panels::page>
    <div x-data="attendanceWidget()" x-init="init()" class="space-y-6">
        <div class="mx-auto w-full max-w-6xl px-3 sm:px-0 space-y-5">

            {{-- Actions --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <x-filament::button
                    icon="heroicon-o-arrow-down-left"
                    color="success"
                    class="w-full justify-center"
                    x-on:click="openModal('check-in')"
                >
                    Absen Masuk
                </x-filament::button>

                <x-filament::button
                    icon="heroicon-o-arrow-up-right"
                    color="primary"
                    class="w-full justify-center"
                    x-on:click="openModal('check-out')"
                >
                    Absen Pulang
                </x-filament::button>
            </div>

        </div>

        {{-- Modal --}}
        <template x-if="showModal">
            <div
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-gray-900/60 p-2 sm:p-6"
                x-on:keydown.escape.window="closeModal()"
            >
                <div class="w-full max-w-6xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800 max-h-[92vh] flex flex-col">

                    {{-- Header --}}
                    <div class="flex flex-col gap-2 border-b px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                        <div class="min-w-0">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Form Absensi</p>
                            <h3 class="truncate text-lg font-semibold capitalize text-gray-900 dark:text-gray-100" x-text="modalTitle"></h3>
                        </div>

                        <div class="flex gap-2">
                            <x-filament::button color="gray" size="sm" class="w-full sm:w-auto" x-on:click="closeModal">
                                Tutup
                            </x-filament::button>
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="px-4 py-4 overflow-y-auto">
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

                            {{-- Camera / Preview --}}
                            <div class="space-y-3 order-1">
                                <div class="rounded-2xl border border-gray-200 bg-black/70 dark:border-gray-700 overflow-hidden">
                                    <div class="relative w-full">
                                        <div class="h-[200px] sm:h-[260px] lg:h-[360px]">
                                            <video
                                                x-ref="video"
                                                class="h-full w-full object-cover"
                                                autoplay
                                                playsinline
                                                muted
                                                x-show="!photoDataUrl"
                                            ></video>

                                            <template x-if="photoDataUrl">
                                                <img :src="photoDataUrl" class="h-full w-full object-cover" alt="Selfie preview">
                                            </template>
                                        </div>

                                        <div class="pointer-events-none absolute inset-0 ring-1 ring-white/10"></div>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                                    <x-filament::button
                                        color="gray"
                                        icon="heroicon-o-camera"
                                        class="w-full sm:w-auto"
                                        x-on:click="startCamera"
                                    >
                                        Nyalakan Kamera
                                    </x-filament::button>

                                    <x-filament::button
                                        color="primary"
                                        icon="heroicon-o-photo"
                                        class="w-full sm:w-auto"
                                        x-on:click="takePhoto"
                                        x-bind:disabled="!cameraActive"
                                    >
                                        Ambil Foto
                                    </x-filament::button>

                                    <x-filament::button
                                        color="gray"
                                        icon="heroicon-o-trash"
                                        class="w-full sm:w-auto"
                                        x-show="photoDataUrl"
                                        x-on:click="photoDataUrl = null"
                                    >
                                        Ulangi Foto
                                    </x-filament::button>
                                </div>
                            </div>

                            {{-- Location --}}
                            <div class="space-y-3 order-2">
                                <div class="rounded-2xl border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-800/60">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0 text-sm text-gray-700 dark:text-gray-200">
                                            <div class="font-medium">Lokasi</div>
                                            <div class="mt-1 break-words text-xs text-gray-600 dark:text-gray-300" x-text="locationText"></div>
                                        </div>

                                        <x-filament::button
                                            color="gray"
                                            icon="heroicon-o-map-pin"
                                            size="sm"
                                            class="w-full justify-center sm:w-auto"
                                            x-on:click="getLocation"
                                        >
                                            Ambil Lokasi
                                        </x-filament::button>
                                    </div>

                                    <div class="mt-3 rounded-xl border border-dashed border-gray-300 bg-white p-3 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                        Pastikan GPS menyala dan akurasi muncul sebelum mengirim.
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-gray-200 bg-white/80 p-4 dark:border-gray-800 dark:bg-gray-900/50">
                                    <div class="flex items-center gap-2">
                                        <x-filament::icon icon="heroicon-o-information-circle" class="h-5 w-5 text-primary-500" />
                                        <p class="text-sm text-gray-700 dark:text-gray-200">
                                            Foto selfie & lokasi akan tersimpan sebagai bukti absensi.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="border-t px-4 py-4 dark:border-gray-800">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-h-[20px] text-sm text-red-600 dark:text-red-400" x-text="errorMessage"></div>

                            <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                                <x-filament::button color="gray" class="w-full sm:w-auto" x-on:click="closeModal">
                                    Batal
                                </x-filament::button>

                                <x-filament::button
                                    color="success"
                                    class="w-full sm:w-auto"
                                    x-on:click="submit"
                                    x-bind:disabled="loading"
                                >
                                    <span x-show="!loading">Kirim</span>
                                    <span x-show="loading">Mengirim...</span>
                                </x-filament::button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </template>
    </div>

    <script>
        function attendanceWidget() {
            return {
                action: null,
                showModal: false,
                modalTitle: '',
                location: { lat: null, lng: null, accuracy: null },
                locationText: 'Lokasi belum diambil.',
                photoDataUrl: null,
                stream: null,
                cameraActive: false,
                loading: false,
                errorMessage: '',
                init() {},
                openModal(type) {
                    this.action = type;
                    this.modalTitle = type === 'check-in' ? 'Absen Masuk' : 'Absen Pulang';
                    this.showModal = true;
                    this.errorMessage = '';
                    this.photoDataUrl = null;
                    this.stopCamera();
                },
                closeModal() {
                    this.showModal = false;
                    this.stopCamera();
                },
                async getLocation() {
                    this.errorMessage = '';
                    if (!navigator.geolocation) {
                        this.errorMessage = 'Browser tidak mendukung geolokasi.';
                        return;
                    }
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            this.location.lat = pos.coords.latitude;
                            this.location.lng = pos.coords.longitude;
                            this.location.accuracy = Math.round(pos.coords.accuracy || 0);
                            this.locationText = `Lat: ${this.location.lat.toFixed(5)}, Lng: ${this.location.lng.toFixed(5)}, +/-${this.location.accuracy}m`;
                        },
                        (err) => {
                            this.errorMessage = 'Gagal ambil lokasi: ' + err.message;
                        },
                        { enableHighAccuracy: true, timeout: 10000 }
                    );
                },
                async startCamera() {
                    this.errorMessage = '';
                    this.stopCamera();
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: 'user' },
                            audio: false,
                        });
                        this.$refs.video.srcObject = this.stream;
                        this.cameraActive = true;
                    } catch (e) {
                        this.errorMessage = 'Gagal mengakses kamera: ' + e.message;
                    }
                },
                takePhoto() {
                    if (!this.stream) {
                        this.errorMessage = 'Kamera belum aktif.';
                        return;
                    }
                    const video = this.$refs.video;
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth || 640;
                    canvas.height = video.videoHeight || 480;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    this.photoDataUrl = canvas.toDataURL('image/png');
                    this.stopCamera();
                },
                stopCamera() {
                    if (this.stream) {
                        this.stream.getTracks().forEach((t) => t.stop());
                        this.stream = null;
                    }
                    this.cameraActive = false;
                },
                async submit() {
                    this.errorMessage = '';
                    if (!this.location.lat || !this.photoDataUrl) {
                        this.errorMessage = 'Lokasi dan foto wajib diambil.';
                        return;
                    }
                    this.loading = true;
                    try {
                        await this.$wire.submitAttendance(this.action, {
                            lat: this.location.lat,
                            lng: this.location.lng,
                            accuracy: this.location.accuracy,
                            photo: this.photoDataUrl,
                        });
                        this.closeModal();
                    } catch (e) {
                        this.errorMessage = 'Gagal mengirim: ' + (e?.message ?? 'unknown');
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
</x-filament-panels::page>

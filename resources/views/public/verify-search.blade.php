@extends('layouts.guest')

@push('styles')
<style>
    @keyframes laserScan {
        0% { top: 5%; opacity: 0.8; }
        50% { top: 90%; opacity: 1; }
        100% { top: 5%; opacity: 0.8; }
    }
    .animate-laser {
        animation: laserScan 2.2s ease-in-out infinite;
    }
</style>
@endpush

@section('content')
<div class="max-w-2xl mx-auto" x-data="qrCameraScanner()">
    <!-- Hero / Verification Card -->
    <div class="bg-white rounded-3xl shadow-2xl border border-slate-200/80 overflow-hidden mb-8">
        
        <div class="bg-gradient-to-b from-[#024966] via-[#023f59] to-[#01354a] px-8 py-10 text-center text-white relative">
            @php
                $showUmsa = ($settings['portal_verify_show_umsa_logo'] ?? '1') == '1';
                $showFt = ($settings['portal_verify_show_ft_logo'] ?? '1') == '1';
            @endphp

            @if($showUmsa || $showFt)
                <div class="inline-flex items-center gap-3.5 p-3 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 mb-3 shadow-lg">
                    @if($showUmsa)
                        <img src="{{ asset('assets/institutional/logo_umsa.svg') }}" class="w-11 h-16 object-contain drop-shadow" alt="Escudo Oficial UMSA" onerror="this.onerror=null; this.src='{{ asset('assets/institutional/logo_umsa.png') }}';">
                    @endif

                    @if($showUmsa && $showFt)
                        <div class="h-12 w-px bg-white/20"></div>
                    @endif

                    @if($showFt)
                        <img src="{{ asset('assets/institutional/logo_ft_umsa.svg') }}" class="w-16 h-16 object-contain drop-shadow" alt="Emblema Facultad de Tecnología" onerror="this.onerror=null; this.src='{{ asset('assets/institutional/logo_ft_umsa.png') }}';">
                    @endif
                </div>
            @endif

            <div class="text-[10px] font-extrabold uppercase tracking-widest text-red-200">
                {{ $settings['portal_verify_tagline'] ?? 'Universidad Mayor de San Andrés • Facultad de Tecnología' }}
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white mt-1">
                {{ $settings['portal_verify_title'] ?? 'Verificación Oficial de Certificados' }}
            </h1>
            <p class="text-xs sm:text-sm text-sky-100/90 mt-2 max-w-lg mx-auto">
                {{ $settings['portal_verify_subtitle'] ?? 'Consulte la autenticidad, vigencia reglamentaria (3 años) y respaldo institucional de certificados emitidos por el Vicedecanato.' }}
            </p>
        </div>

        <div class="p-8 sm:p-10 space-y-6">
            @if(session('error'))
                <div class="p-4 bg-red-50 text-red-800 rounded-2xl border border-red-200 text-sm flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div class="font-medium">{{ session('error') }}</div>
                </div>
            @endif

            <!-- Botón Destacado: Escanear con Cámara -->
            <div>
                <button type="button" 
                        @click="openScannerModal()" 
                        class="w-full py-4 px-6 bg-gradient-to-r from-[#024966] via-[#023f59] to-[#01354a] hover:from-[#01354a] hover:to-[#012534] text-white text-sm font-bold rounded-2xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-3 cursor-pointer border border-sky-400/25 active:scale-98 group">
                    <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center group-hover:scale-110 transition-transform shadow-xs">
                        <svg class="w-5 h-5 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <span class="tracking-wide text-sm sm:text-base">Escanear Código QR con Cámara</span>
                    <span class="ml-1 text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">Cámara / Móvil</span>
                </button>
            </div>

            <!-- Divisor elegante -->
            <div class="relative flex py-1 items-center">
                <div class="grow border-t border-slate-200"></div>
                <span class="shrink mx-4 text-[11px] font-bold text-slate-400 uppercase tracking-wider">o digite el código manual</span>
                <div class="grow border-t border-slate-200"></div>
            </div>

            <!-- Formulario de Consulta Manual por Código -->
            <form method="POST" action="{{ route('verification.search') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="certificate_code" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Código Único de Certificado
                    </label>
                    <div class="relative rounded-2xl shadow-xs">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-6 w-6 text-[#32639E]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <input type="text" name="certificate_code" id="certificate_code" x-ref="certificateCodeInput"
                               value="{{ old('certificate_code') }}" required autofocus
                               class="block w-full pl-12 pr-4 py-3.5 text-base sm:text-lg font-mono tracking-wider uppercase border-2 border-slate-200 rounded-2xl focus:ring-2 focus:ring-[#024966] focus:border-[#024966] placeholder-slate-400 transition-colors"
                               placeholder="{{ $settings['portal_verify_code_placeholder'] ?? 'EJ: CERT-2026-000001' }}">
                    </div>
                    <p class="text-xs text-slate-500 mt-2 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-sky-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Encuentre este código al pie de su certificado impreso o bajo el código QR institucional.</span>
                    </p>
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="w-full py-4 px-6 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-base font-bold rounded-2xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-98">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <span>Verificar Autenticidad Registrada</span>
                    </button>
                </div>
            </form>

            <!-- Tarjetas Informativas Inferiores (Configurables) -->
            @php
                $showQrCard = ($settings['portal_verify_show_qr_card'] ?? '1') == '1';
                $showValCard = ($settings['portal_verify_show_validity_card'] ?? '1') == '1';
            @endphp

            @if($showQrCard || $showValCard)
                <div class="mt-8 pt-8 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-slate-500">
                    @if($showQrCard)
                        <div @click="openScannerModal()" class="flex items-start gap-3.5 p-4 bg-slate-50 hover:bg-slate-100/80 rounded-2xl border border-slate-200/80 cursor-pointer transition-all group">
                            <div class="w-10 h-10 rounded-xl bg-sky-50 text-[#024966] group-hover:bg-[#024966] group-hover:text-white flex items-center justify-center shrink-0 border border-sky-100 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                            </div>
                            <div>
                                <span class="font-bold text-slate-800 block text-xs group-hover:text-[#024966]">
                                    {{ $settings['portal_verify_qr_card_title'] ?? 'Escaneo QR Instantáneo' }}
                                </span>
                                {{ $settings['portal_verify_qr_card_desc'] ?? 'Escanee con la cámara de su dispositivo móvil o computadora para validación directa e inmediata sin teclear.' }}
                            </div>
                        </div>
                    @endif

                    @if($showValCard)
                        <div class="flex items-start gap-3.5 p-4 bg-slate-50 rounded-2xl border border-slate-200/80">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <span class="font-bold text-slate-800 block text-xs">
                                    {{ $settings['portal_verify_validity_card_title'] ?? 'Vigencia Institucional' }}
                                </span>
                                {{ $settings['portal_verify_validity_card_desc'] ?? 'Certificados con validez curricular de 3 años calendario reglamentarios según normativa facultativa.' }}
                            </div>
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>

    <!-- MODAL INTERACTIVO DE ESCÁNER DE CÁMARA QR -->
    <div x-show="scannerModalOpen" 
         x-cloak 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-md">
        
        <div @click.away="closeScannerModal()" 
             class="bg-white rounded-3xl max-w-md w-full overflow-hidden shadow-2xl border border-slate-200 flex flex-col max-h-[90vh]">
            
            <!-- Modal Header -->
            <div class="p-5 bg-gradient-to-r from-[#024966] to-[#01354a] text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center border border-white/20">
                        <svg class="w-5 h-5 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white">Lector QR de Certificados</h3>
                        <p class="text-[11px] text-sky-200">Enfoque el código QR impreso en el documento</p>
                    </div>
                </div>

                <button type="button" @click="closeScannerModal()" class="text-white/80 hover:text-white p-2 rounded-xl hover:bg-white/10 transition-colors cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Modal Body / Scanner Viewport -->
            <div class="p-6 space-y-4 overflow-y-auto">

                <!-- Estado: Mensaje de Éxito / Redireccionando -->
                <div x-show="scanSuccess" x-cloak class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl flex items-center gap-3">
                    <svg class="w-6 h-6 text-emerald-600 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div>
                        <span class="font-bold text-xs block">¡Código QR Validado!</span>
                        <span class="text-[11px]" x-text="scanSuccessMessage">Redirigiendo a la verificación oficial...</span>
                    </div>
                </div>

                <!-- Estado: Mensaje de Error -->
                <div x-show="scanError" x-cloak class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-2xl flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div>
                        <span class="font-bold text-xs block">Aviso del Lector</span>
                        <span class="text-[11px]" x-text="scanErrorMessage"></span>
                    </div>
                </div>

                <!-- Visor de Cámara con Marco y Mira Láser -->
                <div class="relative bg-black rounded-2xl overflow-hidden aspect-square w-full max-w-xs mx-auto shadow-inner flex items-center justify-center">
                    
                    <!-- Elemento destino de Html5Qrcode -->
                    <div id="interactive-qr-reader" class="w-full h-full object-cover"></div>

                    <!-- Mira de Enfoque y Láser Animado -->
                    <div class="absolute inset-4 pointer-events-none border-2 border-white/40 rounded-2xl flex items-center justify-center">
                        <!-- Esquinas destacadas -->
                        <div class="absolute -top-1 -left-1 w-6 h-6 border-t-4 border-l-4 border-red-500 rounded-tl-lg"></div>
                        <div class="absolute -top-1 -right-1 w-6 h-6 border-t-4 border-r-4 border-red-500 rounded-tr-lg"></div>
                        <div class="absolute -bottom-1 -left-1 w-6 h-6 border-b-4 border-l-4 border-red-500 rounded-bl-lg"></div>
                        <div class="absolute -bottom-1 -right-1 w-6 h-6 border-b-4 border-r-4 border-red-500 rounded-br-lg"></div>

                        <!-- Línea láser animada -->
                        <div class="absolute inset-x-2 h-0.5 bg-gradient-to-r from-transparent via-red-500 to-transparent shadow-[0_0_12px_#ef4444] animate-laser"></div>
                    </div>

                    <!-- Indicador de Carga de Cámara -->
                    <div x-show="loadingCamera" class="absolute inset-0 bg-slate-900/90 flex flex-col items-center justify-center text-white gap-2 z-10">
                        <svg class="animate-spin h-8 w-8 text-sky-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span class="text-xs font-semibold">Activando cámara...</span>
                    </div>
                </div>

                <!-- Opciones y Alternativas de Cámara -->
                <div class="space-y-3 pt-2">
                    <div class="flex items-center justify-between text-xs text-slate-500">
                        <button type="button" 
                                @click="switchCamera()" 
                                x-show="hasMultipleCameras" 
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition-colors cursor-pointer">
                            <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            <span>Cambiar Cámara</span>
                        </button>

                        <span class="text-[11px] text-slate-400 ml-auto">Enfoque el QR con buena iluminación</span>
                    </div>

                    <!-- Opción alternativa: Cargar foto o imagen de certificado -->
                    <div class="pt-2 border-t border-slate-100">
                        <input type="file" id="qr-file-input" accept="image/*" class="hidden" @change="handleFileScan($event)">
                        <label for="qr-file-input" 
                               class="w-full py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl flex items-center justify-center gap-2 cursor-pointer transition-colors">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span>📁 ¿Tiene una foto o archivo del QR? Subir imagen</span>
                        </label>
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end">
                <button type="button" @click="closeScannerModal()" class="px-5 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl transition-colors cursor-pointer">
                    Cerrar Lector
                </button>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<!-- Librería Universal de Detección QR con Cámara Web/Móvil -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
    function qrCameraScanner() {
        return {
            scannerModalOpen: false,
            loadingCamera: false,
            scanSuccess: false,
            scanSuccessMessage: '',
            scanError: false,
            scanErrorMessage: '',
            html5QrCode: null,
            cameras: [],
            currentCameraIndex: 0,
            hasMultipleCameras: false,

            async openScannerModal() {
                this.scannerModalOpen = true;
                this.scanSuccess = false;
                this.scanError = false;
                this.loadingCamera = true;

                // Esperar a que el modal termine de renderizar el contenedor en el DOM
                await this.$nextTick();

                this.initScanner();
            },

            async initScanner() {
                try {
                    if (typeof Html5Qrcode === 'undefined') {
                        throw new Error('La librería del escáner no se pudo cargar. Verifique su conexión.');
                    }

                    if (!this.html5QrCode) {
                        this.html5QrCode = new Html5Qrcode('interactive-qr-reader');
                    }

                    const devices = await Html5Qrcode.getCameras();
                    if (devices && devices.length > 0) {
                        this.cameras = devices;
                        this.hasMultipleCameras = devices.length > 1;
                        
                        // Preferir cámara trasera ('environment') en smartphones
                        let selectedCameraId = devices[0].id;
                        const backCamera = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('trasera') || d.label.toLowerCase().includes('environment'));
                        if (backCamera) {
                            selectedCameraId = backCamera.id;
                            this.currentCameraIndex = devices.indexOf(backCamera);
                        }

                        this.startCameraStream(selectedCameraId);
                    } else {
                        // Fallback usando facingMode estándar
                        this.startCameraStream({ facingMode: "environment" });
                    }
                } catch (err) {
                    console.error('Error al inicializar cámara:', err);
                    this.loadingCamera = false;
                    this.scanError = true;
                    this.scanErrorMessage = 'No se pudo acceder a la cámara. Asegúrese de otorgar permisos de cámara en su navegador o seleccione "Subir imagen".';
                }
            },

            async startCameraStream(cameraConfig) {
                this.loadingCamera = true;
                this.scanError = false;

                try {
                    const config = {
                        fps: 15,
                        qrbox: { width: 220, height: 220 },
                        aspectRatio: 1.0,
                    };

                    await this.html5QrCode.start(
                        cameraConfig,
                        config,
                        (decodedText) => this.onQrCodeSuccess(decodedText),
                        (errorMsg) => {
                            // Ignorar errores normales de frame sin QR
                        }
                    );
                    this.loadingCamera = false;
                } catch (err) {
                    console.error('Error al iniciar stream de cámara:', err);
                    this.loadingCamera = false;
                    this.scanError = true;
                    this.scanErrorMessage = 'Permiso denegado o cámara no disponible en este dispositivo.';
                }
            },

            async switchCamera() {
                if (!this.hasMultipleCameras || this.cameras.length <= 1) return;

                this.currentCameraIndex = (this.currentCameraIndex + 1) % this.cameras.length;
                const nextCameraId = this.cameras[this.currentCameraIndex].id;

                if (this.html5QrCode && this.html5QrCode.isScanning) {
                    await this.html5QrCode.stop();
                }

                this.startCameraStream(nextCameraId);
            },

            onQrCodeSuccess(decodedText) {
                console.log('Código QR detectado:', decodedText);
                this.playBeep();

                this.scanSuccess = true;
                this.scanSuccessMessage = '¡Certificado reconocido!';

                // Detener escaneo inmediatamente para evitar múltiples ejecuciones
                if (this.html5QrCode && this.html5QrCode.isScanning) {
                    this.html5QrCode.stop().catch(() => {});
                }

                // 1. Caso A: Es una URL oficial de verificación (ej: .../verificar/{token})
                if (decodedText.includes('/verificar/')) {
                    const parts = decodedText.split('/verificar/');
                    const token = parts[1] ? parts[1].split('?')[0].trim() : '';

                    if (token) {
                        this.scanSuccessMessage = 'Token criptográfico verificado. Cargando detalles oficiales...';
                        setTimeout(() => {
                            window.location.href = `/verificar/${token}?method=qr_camera`;
                        }, 600);
                        return;
                    }
                }

                // 2. Caso B: Es un código directo de certificado (ej: CERT-2026-000001)
                const certMatch = decodedText.match(/CERT-\d{4}-\d{5,8}/i);
                if (certMatch) {
                    const code = certMatch[0].toUpperCase();
                    this.scanSuccessMessage = `Código detectado: ${code}. Buscando registro...`;
                    setTimeout(() => {
                        this.$refs.certificateCodeInput.value = code;
                        this.closeScannerModal();
                        // Enviar formulario
                        this.$refs.certificateCodeInput.closest('form').submit();
                    }, 700);
                    return;
                }

                // 3. Caso C: Texto no reconocido
                this.scanError = true;
                this.scanErrorMessage = 'El código QR detectado (' + decodedText.substring(0, 30) + '...) no corresponde a un certificado del Vicedecanato.';
                this.scanSuccess = false;

                // Reanudar escaneo después de 3 segundos si no fue reconocido
                setTimeout(() => {
                    this.scanError = false;
                    if (this.scannerModalOpen && (!this.html5QrCode || !this.html5QrCode.isScanning)) {
                        this.initScanner();
                    }
                }, 3000);
            },

            async handleFileScan(event) {
                const file = event.target.files[0];
                if (!file) return;

                this.loadingCamera = true;
                this.scanError = false;

                try {
                    if (!this.html5QrCode) {
                        this.html5QrCode = new Html5Qrcode('interactive-qr-reader');
                    }

                    // Detener cámara en vivo si estaba corriendo
                    if (this.html5QrCode.isScanning) {
                        await this.html5QrCode.stop();
                    }

                    const decodedText = await this.html5QrCode.scanFile(file, true);
                    this.loadingCamera = false;
                    this.onQrCodeSuccess(decodedText);
                } catch (err) {
                    console.error('Error al decodificar imagen:', err);
                    this.loadingCamera = false;
                    this.scanError = true;
                    this.scanErrorMessage = 'No se encontró ningún código QR legible en la imagen subida. Intente con una foto más clara o enfoque el código.';
                }
            },

            async closeScannerModal() {
                if (this.html5QrCode && this.html5QrCode.isScanning) {
                    try {
                        await this.html5QrCode.stop();
                    } catch (e) {}
                }
                this.scannerModalOpen = false;
                this.loadingCamera = false;
                this.scanSuccess = false;
                this.scanError = false;
            },

            playBeep() {
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(880, ctx.currentTime); // Tono A5 agudo agradable
                    gain.gain.setValueAtTime(0.15, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.18);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.18);
                } catch (e) {
                    // AudioContext puede no estar disponible o requerir interacción de usuario previa
                }
            }
        };
    }
</script>
@endpush


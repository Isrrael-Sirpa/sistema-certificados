<!DOCTYPE html>
<html lang="es" class="h-full bg-[#F5F7F8]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Verificación Institucional' }} - Vicedecanato</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-full font-sans antialiased text-slate-800 bg-[#F5F7F8] flex flex-col justify-between">

    <!-- Guest Topbar -->
    <header class="bg-[#024966] border-b border-white/10 text-white py-3.5 px-6 shadow-lg sticky top-0 z-40 backdrop-blur-md">
        <div class="max-w-6xl mx-auto flex items-center justify-between gap-4">
            <a href="{{ route('verification.index') }}" class="flex items-center gap-3.5 group">
                <div class="w-10 h-10 rounded-xl bg-white/10 p-1 flex items-center justify-center border border-white/20 shadow-sm group-hover:scale-105 transition-transform">
                    <img src="{{ asset('assets/institutional/logo_ft_umsa.svg') }}" class="w-full h-full object-contain" alt="Escudo FT-UMSA" onerror="this.onerror=null; this.src='{{ asset('assets/institutional/logo_ft_umsa.png') }}';">
                </div>
                <div>
                    <div class="text-[9px] font-extrabold uppercase tracking-widest text-red-200">FACULTAD DE TECNOLOGÍA • UMSA</div>
                    <div class="text-sm font-black tracking-tight uppercase leading-tight text-white">Centro de Verificación Oficial</div>
                    <div class="text-[10px] text-sky-200 font-medium">Validación Criptográfica y Consulta QR</div>
                </div>
            </a>

            <div class="flex items-center gap-3">
                <a href="{{ route('verification.index') }}" class="hidden sm:inline-flex items-center gap-1.5 text-xs text-slate-200 hover:text-white px-3 py-1.5 rounded-lg hover:bg-white/10 transition-colors">
                    <svg class="w-4 h-4 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <span>Consultar Código</span>
                </a>

                @if(auth()->check())
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-xs font-bold rounded-xl shadow-md transition-all">
                        <span>Panel Administrativo</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-white/10 hover:bg-white/20 text-white text-xs font-medium rounded-xl transition-all border border-white/20 shadow-xs">
                        <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                        <span>Acceso Vicedecanato</span>
                    </a>
                @endif
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 py-10 px-4 sm:px-6">
        @yield('content')
    </main>

    <!-- Guest Footer -->
    <footer class="bg-white border-t border-slate-200 py-8 text-center text-xs text-slate-500">
        <div class="max-w-6xl mx-auto px-4 space-y-2">
            <div class="flex items-center justify-center gap-4 mb-2">
                <img src="{{ asset('assets/institutional/logo_umsa.svg') }}" class="h-8 w-auto object-contain opacity-85" alt="UMSA">
                <span class="text-slate-300 font-light">|</span>
                <img src="{{ asset('assets/institutional/logo_ft_umsa.svg') }}" class="h-8 w-auto object-contain opacity-85" alt="Facultad de Tecnología">
            </div>
            <p class="font-bold text-[#024966] uppercase tracking-wider text-xs">Universidad Mayor de San Andrés • Facultad de Tecnología</p>
            <p class="text-slate-500 text-[11px]">Vicedecanato — Sistema Centralizado de Emisión, Registro y Verificación de Certificados Académicos</p>
            <p class="text-slate-400 text-[11px]">Av. Arce No. 2295 • La Paz - Bolivia • <em>"Ciencia, cálculo y jerarquía Adelante Tecnología"</em></p>
            <p class="pt-2 text-[10px] text-slate-400">&copy; {{ date('Y') }} Todos los derechos reservados.</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>

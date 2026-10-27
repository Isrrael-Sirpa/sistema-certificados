<!DOCTYPE html>
<html lang="es" class="h-full bg-brand-deep">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Verificación Institucional' }} - Vicedecanato</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="ft-public min-h-screen font-sans antialiased text-ink flex flex-col">
    <a class="ft-skip-link" href="#public-content">Ir al contenido</a>
    <header class="ft-public-header px-6 flex items-center">
        <div class="w-full max-w-[1320px] mx-auto flex items-center justify-between gap-4">
            <a href="{{ route('verification.index') }}" class="flex items-center gap-3 min-w-0">
                <img src="{{ asset('assets/institutional/logo_ft_umsa.svg') }}" width="40" height="44" class="w-10 h-11 object-contain shrink-0" alt="Escudo FT-UMSA" onerror="this.onerror=null; this.src='{{ asset('assets/institutional/logo_ft_umsa.png') }}';">
                <div class="min-w-0">
                    <p class="ft-public-brand-title text-xs font-bold tracking-wide text-white">FT / CERTIFICACIÓN <span class="hidden sm:inline">DIGITAL</span></p>
                    <p class="ft-public-brand-subtitle text-[10px] text-white/70 mt-1">Facultad de Tecnología · Universidad Mayor de San Andrés</p>
                </div>
            </a>
            <nav class="flex items-center gap-3 shrink-0" aria-label="Navegación pública">
                <a href="{{ route('verification.index') }}" class="hidden sm:inline-flex items-center text-xs font-medium px-3 py-2 rounded-xl hover:bg-white/10">Consultar código</a>
                @if(auth()->check())
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2.5 bg-action hover:bg-action-hover text-white text-xs font-semibold rounded-xl">Panel <span class="hidden sm:inline ml-1">Administrativo</span><span class="ml-2" aria-hidden="true">→</span></a>
                @else
                    <a href="{{ route('login') }}" class="ft-public-access inline-flex items-center px-3.5 py-2.5 text-xs font-semibold hover:bg-white/10">Acceso <span class="hidden sm:inline ml-1">Vicedecanato</span><span class="ml-2" aria-hidden="true">→</span></a>
                @endif
            </nav>
        </div>
    </header>
    <main id="public-content" class="ft-public-main flex-1" tabindex="-1">
        @yield('content')
    </main>
    <footer class="ft-public-footer border-t text-center">
        <div class="max-w-6xl mx-auto space-y-2">
            <p class="text-xs font-semibold text-white/90">Universidad Mayor de San Andrés · Facultad de Tecnología</p>
            <p class="text-[11px] text-white/70">Vicedecanato — Sistema Centralizado de Emisión, Registro y Verificación de Certificados Académicos</p>
            <p class="text-[10px] text-white/60">Av. Arce No. 2295 · La Paz, Bolivia · &copy; {{ date('Y') }}</p>
        </div>
    </footer>
    @stack('scripts')
</body>
</html>

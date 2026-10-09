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
</head>
<body class="min-h-full font-sans antialiased text-slate-800 bg-[#F5F7F8] flex flex-col justify-between">

    <!-- Guest Topbar -->
    <header class="bg-[#024966] border-b border-white/10 text-white py-4 px-6 shadow-md">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <a href="{{ route('verification.index') }}" class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#BB2026] flex items-center justify-center text-white font-bold text-lg shadow-md shadow-red-900/30">
                    🏛️
                </div>
                <div>
                    <div class="text-sm font-bold tracking-tight uppercase leading-none">Portal Institucional de Verificación</div>
                    <div class="text-[11px] text-red-200 font-medium">Vicedecanato - Validación de Certificados QR</div>
                </div>
            </a>

            <div>
                @if(auth()->check())
                    <a href="{{ route('dashboard') }}" class="px-3.5 py-1.5 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-xs font-bold rounded-lg shadow-sm transition-all">
                        Ir al Panel Administrativo &rarr;
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-3.5 py-1.5 bg-white/10 hover:bg-white/20 text-white text-xs font-medium rounded-lg transition-all border border-white/10">
                        Acceso Administrativo
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
    <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500">
        <div class="max-w-6xl mx-auto px-4">
            <p class="font-bold text-[#024966] uppercase tracking-wider mb-1">Universidad Mayor de San Andrés - Vicedecanato</p>
            <p>Sistema Oficial de Validación y Verificación de Autenticidad de Certificados Académicos</p>
            <p class="mt-2 text-[11px] text-slate-400">&copy; {{ date('Y') }} Todos los derechos reservados.</p>
        </div>
    </footer>

</body>
</html>

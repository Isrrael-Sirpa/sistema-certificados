<!DOCTYPE html>
<html lang="es" class="h-full bg-[#F5F7F8]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Sistema de Certificados' }} - Vicedecanato</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">


    <!-- Vite Assets / Tailwind -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
    @stack('styles')
</head>
<body class="h-full font-sans antialiased text-slate-800 bg-[#F5F7F8] flex flex-col md:flex-row min-h-screen" x-data="{ sidebarOpen: false }">

    <!-- Mobile Sidebar Overlay -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-[#013449]/80 backdrop-blur-xs md:hidden" 
         @click="sidebarOpen = false"
         style="display: none;"></div>

    <!-- Sidebar Navigation -->
    <aside class="fixed inset-y-0 left-0 z-50 w-72 bg-[#024966] text-white flex flex-col transition-transform duration-300 ease-in-out md:translate-x-0 md:static md:inset-auto md:min-h-screen shadow-2xl"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">
        
        <!-- Institutional Brand Header -->
        <div class="p-5 border-b border-white/10 flex items-center justify-between bg-black/10">
            <div class="flex items-center space-x-3">
                <div class="w-11 h-11 rounded-xl bg-white/10 p-1.5 flex items-center justify-center backdrop-blur-xs border border-white/20 shadow-md">
                    <img src="{{ asset('assets/institutional/logo_ft_umsa.svg') }}" class="w-full h-full object-contain" alt="Escudo FT-UMSA" onerror="this.onerror=null; this.src='{{ asset('assets/institutional/logo_ft_umsa.png') }}';">
                </div>
                <div>
                    <div class="text-[9px] font-extrabold uppercase tracking-widest text-red-200">UMSA • TECNOLOGÍA</div>
                    <h1 class="text-sm font-black tracking-tight text-white leading-tight">VICEDECANATO</h1>
                    <p class="text-[10px] text-slate-300 font-medium tracking-wide">Certificación Digital</p>
                </div>
            </div>
            <button @click="sidebarOpen = false" class="md:hidden text-slate-300 hover:text-white p-1 rounded-lg hover:bg-white/10">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
            
            <div class="px-3 pb-2 text-[10px] font-semibold tracking-wider text-slate-300/80 uppercase">Gestión Principal</div>

            <a href="{{ route('dashboard') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('dashboard') ? 'bg-white/15 text-white shadow-inner font-semibold border-l-4 border-[#BB2026]' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('dashboard') ? 'text-[#BB2026]' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('certificates.index') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('certificates.*') ? 'bg-white/15 text-white shadow-inner font-semibold border-l-4 border-[#BB2026]' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('certificates.*') ? 'text-[#BB2026]' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span>Certificados</span>
            </a>

            <a href="{{ route('beneficiaries.index') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('beneficiaries.*') ? 'bg-white/15 text-white shadow-inner font-semibold border-l-4 border-[#BB2026]' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('beneficiaries.*') ? 'text-[#BB2026]' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span>Beneficiarios</span>
            </a>

            <a href="{{ route('activities.index') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('activities.*') ? 'bg-white/15 text-white shadow-inner font-semibold border-l-4 border-[#BB2026]' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('activities.*') ? 'text-[#BB2026]' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span>Actividades Académicas</span>
            </a>

            <a href="{{ route('reports.index') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('reports.*') ? 'bg-white/15 text-white shadow-inner font-semibold border-l-4 border-[#BB2026]' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('reports.*') ? 'text-[#BB2026]' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <span>Reportes</span>
            </a>

            @if(auth()->user()->isAdmin())
                <div class="pt-4 px-3 pb-2 text-[10px] font-semibold tracking-wider text-slate-300/80 uppercase">Administración</div>

                <a href="{{ route('templates.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('templates.*') ? 'bg-white/15 text-white shadow-inner font-semibold border-l-4 border-[#BB2026]' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('templates.*') ? 'text-[#BB2026]' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path></svg>
                    <span>Plantillas y Diseñador</span>
                </a>

                <a href="{{ route('assets.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('assets.*') ? 'bg-white/15 text-white shadow-inner font-semibold border-l-4 border-[#BB2026]' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('assets.*') ? 'text-[#BB2026]' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>Recursos Institucionales</span>
                </a>

                <a href="{{ route('users.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('users.*') ? 'bg-white/15 text-white shadow-inner font-semibold border-l-4 border-[#BB2026]' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('users.*') ? 'text-[#BB2026]' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <span>Usuarios y Roles</span>
                </a>

                <a href="{{ route('audit.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('audit.*') ? 'bg-white/15 text-white shadow-inner font-semibold border-l-4 border-[#BB2026]' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('audit.*') ? 'text-[#BB2026]' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    <span>Auditoría de Acciones</span>
                </a>

                <a href="{{ route('settings.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('settings.*') ? 'bg-white/15 text-white shadow-inner font-semibold border-l-4 border-[#BB2026]' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('settings.*') ? 'text-[#BB2026]' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span>Configuración</span>
                </a>
            @endif

            <div class="pt-4 px-3 pb-2 text-[10px] font-semibold tracking-wider text-slate-300/80 uppercase">Portal Público</div>

            <a href="{{ route('verification.index') }}" target="_blank"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-red-200 hover:bg-white/10 hover:text-white transition-all">
                <svg class="w-5 h-5 text-[#BB2026]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                <span>Verificador Público QR</span>
            </a>

        </nav>

        <!-- User Profile & Sign Out Footer -->
        <div class="p-4 border-t border-white/10 bg-black/20 flex items-center justify-between">
            <div class="flex items-center space-x-3 overflow-hidden">
                <div class="w-9 h-9 rounded-full bg-[#BB2026] text-white font-bold flex items-center justify-center text-sm border border-red-400/40 shadow-sm">
                    {{ substr(auth()->user()->name, 0, 2) }}
                </div>
                <div class="truncate">
                    <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium {{ auth()->user()->isAdmin() ? 'bg-red-500/20 text-red-200 border border-red-500/40' : 'bg-blue-500/20 text-blue-200 border border-blue-500/40' }}">
                        {{ auth()->user()->role->display_name ?? 'Usuario' }}
                    </span>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="ml-2">
                @csrf
                <button type="submit" title="Cerrar sesión" class="p-2 text-slate-300 hover:text-red-400 hover:bg-white/10 rounded-lg transition-colors cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Container -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-[#F5F7F8]">
        
        <!-- Topbar Header -->
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between shadow-xs sticky top-0 z-30">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = true" class="md:hidden text-slate-600 hover:text-slate-900 focus:outline-hidden">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div>
                    <h2 class="text-lg font-bold text-[#024966] leading-tight">@yield('page-title', 'Panel de Control')</h2>
                    <p class="text-xs text-slate-500">@yield('page-subtitle', 'Vicedecanato - Gestión y Verificación Institucional')</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 text-xs text-[#024966] bg-slate-100 px-3 py-1.5 rounded-full border border-slate-200">
                    <svg class="w-3.5 h-3.5 text-[#32639E]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span class="font-medium">{{ now()->translatedFormat('l, d \d\e F \d\e Y') }}</span>
                </div>

                <a href="{{ route('certificates.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-xs font-bold rounded-xl shadow-sm hover:shadow-md transition-all active:scale-95 focus:ring-2 focus:ring-[#BB2026]/40 cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span>Emitir Certificado</span>
                </a>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="px-6 pt-4">
            @if(session('success'))
                <div class="flex items-center p-4 mb-3 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 shadow-xs" role="alert">
                    <svg class="w-5 h-5 text-emerald-600 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div class="font-medium flex-1">{{ session('success') }}</div>
                </div>
            @endif

            @if(session('error'))
                <div class="flex items-center p-4 mb-3 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 shadow-xs" role="alert">
                    <svg class="w-5 h-5 text-red-600 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div class="font-medium flex-1">{{ session('error') }}</div>
                </div>
            @endif

            @if(session('warning'))
                <div class="flex items-center p-4 mb-3 text-sm text-amber-800 rounded-xl bg-amber-50 border border-amber-200 shadow-xs" role="alert">
                    <svg class="w-5 h-5 text-amber-600 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <div class="font-medium flex-1">{{ session('warning') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 mb-3 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 shadow-xs">
                    <div class="font-bold mb-1 flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Por favor corrija los siguientes errores:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 ml-6">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Main Body Content -->
        <main class="flex-1 p-6 overflow-y-auto">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 px-6 py-3 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
            <div>
                <span class="font-bold text-[#024966]">Facultad de Tecnología</span> • Universidad Mayor de San Andrés (Av. Arce No. 2295)
            </div>
            <div class="text-[11px] text-slate-400">
                Sistema Oficial de Certificación Digital &copy; {{ date('Y') }} Vicedecanato
            </div>
        </footer>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>

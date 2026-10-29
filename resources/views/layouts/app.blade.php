<!DOCTYPE html>
<html lang="es" class="h-full bg-background">
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
<body class="ft-app {{ request()->routeIs('templates.designer') ? 'ft-editor-page' : '' }} font-sans antialiased text-slate-800 bg-background flex flex-col lg:flex-row min-h-dvh"
      x-data="{
          sidebarOpen: false,
          isDesktop: window.matchMedia('(min-width: 1024px)').matches,
          openSidebar() {
              if (this.isDesktop) { return; }
              this.sidebarOpen = true;
              this.$nextTick(() => this.$refs.sidebarClose.focus());
          },
          closeSidebar(restoreFocus = true) {
              if (!this.sidebarOpen) { return; }
              this.sidebarOpen = false;
              if (restoreFocus && !this.isDesktop) {
                  this.$nextTick(() => this.$refs.sidebarToggle.focus());
              }
          },
          syncViewport() {
              this.isDesktop = window.matchMedia('(min-width: 1024px)').matches;
              if (this.isDesktop) { this.closeSidebar(false); }
          },
          trapSidebarFocus(event) {
              if (!this.sidebarOpen || this.isDesktop) { return; }
              const controls = [...this.$refs.sidebar.querySelectorAll('a[href], button:not([disabled]), [tabindex]')]
                  .filter(element => element.tabIndex >= 0 && element.getClientRects().length);
              const first = controls[0];
              const last = controls[controls.length - 1];
              if (event.shiftKey && document.activeElement === first) {
                  event.preventDefault();
                  last.focus();
              } else if (!event.shiftKey && document.activeElement === last) {
                  event.preventDefault();
                  first.focus();
              }
          }
      }"
      :class="{ 'overflow-hidden': sidebarOpen && !isDesktop }"
      x-effect="document.documentElement.style.overflow = sidebarOpen && !isDesktop ? 'hidden' : ''"
      @resize.window.debounce.100ms="syncViewport()"
      @keydown.escape.window="if (sidebarOpen) { $event.preventDefault(); closeSidebar(); }">
    <a class="ft-skip-link" href="#main-content">Ir al contenido</a>

    <!-- Mobile Sidebar Overlay -->
    <div x-show="sidebarOpen && !isDesktop"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-brand-deep/80 backdrop-blur-xs lg:hidden"
         aria-hidden="true"
         @click="closeSidebar()"
         style="display: none;"></div>

    <!-- Sidebar Navigation -->
    <aside id="main-navigation" x-ref="sidebar" aria-label="Navegación principal"
           :role="isDesktop ? null : 'dialog'"
           :aria-modal="sidebarOpen && !isDesktop ? 'true' : null"
           :aria-hidden="!isDesktop && !sidebarOpen"
           :inert="!isDesktop && !sidebarOpen"
           @keydown.tab="trapSidebarFocus($event)"
           class="ft-sidebar fixed inset-y-0 left-0 z-50 h-dvh w-72 max-w-[calc(100vw-2rem)] bg-brand-deep text-white flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

        <!-- Institutional Brand Header -->
        <div class="ft-sidebar-brand p-5 border-b border-white/10 flex items-center justify-between shrink-0">
            <div class="flex items-center space-x-3">
                <div class="w-11 h-11 rounded-xl bg-white/10 p-1.5 flex items-center justify-center backdrop-blur-xs border border-white/20 shadow-md">
                    <img src="{{ asset('assets/institutional/logo_ft_umsa.svg') }}" class="w-full h-full object-contain" alt="Escudo FT-UMSA" onerror="this.onerror=null; this.src='{{ asset('assets/institutional/logo_ft_umsa.png') }}';">
                </div>
                <div>
                    <div class="text-[9px] font-bold uppercase tracking-widest text-accent">UMSA • TECNOLOGÍA</div>
                    <h1 class="text-sm font-black tracking-tight text-white leading-tight">VICEDECANATO</h1>
                    <p class="text-[10px] text-slate-300 font-medium tracking-wide">Certificación Digital</p>
                </div>
            </div>
            <button type="button" x-ref="sidebarClose" @click="closeSidebar()" aria-label="Cerrar menú" class="lg:hidden min-h-11 min-w-11 shrink-0 inline-flex items-center justify-center text-slate-300 hover:text-white rounded-lg hover:bg-white/10">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 min-h-0 px-4 py-6 space-y-1.5 overflow-y-auto overscroll-y-contain" aria-label="Módulos del sistema" @click="if ($event.target.closest('a')) { closeSidebar(); }">

            <div class="px-3 pb-2 text-[10px] font-semibold tracking-wider text-slate-300/80 uppercase">Gestión Principal</div>

            <a href="{{ route('dashboard') }}"
               aria-current="{{ request()->routeIs('dashboard') ? 'page' : 'false' }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('dashboard') ? 'bg-white/15 text-white shadow-inner font-semibold border-l-4 border-accent' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('dashboard') ? 'text-accent' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('certificates.index') }}"
               aria-current="{{ request()->routeIs('certificates.*') ? 'page' : 'false' }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('certificates.*') ? 'bg-white/15 text-white shadow-inner font-semibold border-l-4 border-accent' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('certificates.*') ? 'text-accent' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span>Certificados</span>
            </a>

            <a href="{{ route('beneficiaries.index') }}"
               aria-current="{{ request()->routeIs('beneficiaries.*') ? 'page' : 'false' }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('beneficiaries.*') ? 'bg-white/15 text-white shadow-inner font-semibold border-l-4 border-accent' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('beneficiaries.*') ? 'text-accent' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span>Beneficiarios</span>
            </a>

            <a href="{{ route('activities.index') }}"
               aria-current="{{ request()->routeIs('activities.*') ? 'page' : 'false' }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('activities.*') ? 'bg-white/15 text-white shadow-inner font-semibold border-l-4 border-accent' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('activities.*') ? 'text-accent' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span>Actividades Académicas</span>
            </a>

            <a href="{{ route('reports.index') }}"
               aria-current="{{ request()->routeIs('reports.*') ? 'page' : 'false' }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('reports.*') ? 'bg-white/15 text-white shadow-inner font-semibold border-l-4 border-accent' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('reports.*') ? 'text-accent' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <span>Reportes</span>
            </a>

            @if(auth()->user()->isAdmin())
                <div class="pt-4 px-3 pb-2 text-[10px] font-semibold tracking-wider text-slate-300/80 uppercase">Administración</div>

                <a href="{{ route('templates.index') }}"
                   aria-current="{{ request()->routeIs('templates.*') ? 'page' : 'false' }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('templates.*') ? 'bg-white/15 text-white shadow-inner font-semibold border-l-4 border-accent' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('templates.*') ? 'text-accent' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path></svg>
                    <span>Plantillas y Diseñador</span>
                </a>

                <a href="{{ route('assets.index') }}"
                   aria-current="{{ request()->routeIs('assets.*') ? 'page' : 'false' }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('assets.*') ? 'bg-white/15 text-white shadow-inner font-semibold border-l-4 border-accent' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('assets.*') ? 'text-accent' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>Recursos Institucionales</span>
                </a>

                <a href="{{ route('users.index') }}"
                   aria-current="{{ request()->routeIs('users.*') ? 'page' : 'false' }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('users.*') ? 'bg-white/15 text-white shadow-inner font-semibold border-l-4 border-accent' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('users.*') ? 'text-accent' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <span>Usuarios y Roles</span>
                </a>

                <a href="{{ route('audit.index') }}"
                   aria-current="{{ request()->routeIs('audit.*') ? 'page' : 'false' }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('audit.*') ? 'bg-white/15 text-white shadow-inner font-semibold border-l-4 border-accent' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('audit.*') ? 'text-accent' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    <span>Auditoría de Acciones</span>
                </a>

                <a href="{{ route('settings.index') }}"
                   aria-current="{{ request()->routeIs('settings.*') ? 'page' : 'false' }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('settings.*') ? 'bg-white/15 text-white shadow-inner font-semibold border-l-4 border-accent' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('settings.*') ? 'text-accent' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span>Configuración</span>
                </a>
            @endif

            <div class="pt-4 px-3 pb-2 text-[10px] font-semibold tracking-wider text-slate-300/80 uppercase">Portal Público</div>

            <a href="{{ route('verification.index') }}" target="_blank" rel="noopener" aria-label="Verificador Público QR (se abre en otra pestaña)"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-red-200 hover:bg-white/10 hover:text-white transition-all">
                <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                <span>Verificador Público QR</span>
            </a>

        </nav>

        <!-- User Profile & Sign Out Footer -->
        <div class="ft-sidebar-profile p-4 border-t border-white/10 flex items-center justify-between shrink-0">
            <div class="flex items-center space-x-3 overflow-hidden">
                <div class="w-9 h-9 rounded-full bg-action text-white font-bold flex items-center justify-center text-sm border border-red-400/40 shadow-sm">
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
                <button type="submit" title="Cerrar sesión" aria-label="Cerrar sesión" class="min-h-11 min-w-11 inline-flex items-center justify-center p-2 text-slate-300 hover:text-red-400 hover:bg-white/10 rounded-lg transition-colors cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Container -->
    <div class="ft-content-shell flex-1 flex flex-col min-w-0 bg-background" :inert="sidebarOpen && !isDesktop">

        <!-- Topbar Header -->
        <header class="ft-topbar bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between gap-3 sticky top-0 z-30">
            <div class="flex items-center gap-2 sm:gap-4 min-w-0">
                <button type="button" x-ref="sidebarToggle" @click="openSidebar()" aria-label="Abrir menú" aria-controls="main-navigation" :aria-expanded="sidebarOpen" class="lg:hidden min-h-11 min-w-11 shrink-0 inline-flex items-center justify-center rounded-xl text-slate-600 hover:text-brand hover:bg-brand-soft">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="min-w-0">
                    <p class="ft-topbar-label">CERTIFICACIÓN ACADÉMICA / FT UMSA</p>
                    <p class="ft-topbar-subtitle">Espacio de gestión institucional</p>
                </div>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <div class="hidden xl:flex items-center gap-2 text-xs text-brand bg-slate-100 px-3 py-1.5 rounded-full border border-slate-200">
                    <svg class="w-3.5 h-3.5 text-brand-hover" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span class="font-medium">{{ now()->translatedFormat('l, d \d\e F \d\e Y') }}</span>
                </div>

                <a href="{{ route('activities.create') }}" aria-label="Nueva Actividad" class="min-h-11 inline-flex items-center gap-2 px-4 py-2 bg-brand hover:bg-brand-deep text-white text-xs font-bold rounded-xl shadow-xs hover:shadow-md transition-all active:scale-95 focus:ring-2 focus:ring-brand/40 cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span class="hidden sm:inline">Nueva Actividad</span><span class="sm:hidden">Nuevo</span>
                </a>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="ft-flash-messages px-6 pt-4" @if(request()->routeIs('templates.designer')) x-data="{ visible: true }" x-show="visible" @endif>
            @if(request()->routeIs('templates.designer') && (session('success') || session('error') || session('warning') || session('info') || $errors->any()))
                <button type="button" @click="visible = false" class="ft-editor-flash-close" aria-label="Cerrar notificación">✕</button>
            @endif
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

            @if(session('info'))
                <div class="flex items-center p-4 mb-3 text-sm text-blue-800 rounded-xl bg-blue-50 border border-blue-200 shadow-xs" role="alert">
                    <svg class="w-5 h-5 text-blue-600 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div class="font-medium flex-1">{{ session('info') }}</div>
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
        <main id="main-content" class="ft-workspace {{ request()->routeIs('templates.designer') ? 'ft-designer' : '' }} flex-1 min-w-0" tabindex="-1">
            <div class="ft-page-heading">
                <p class="ft-eyebrow">VICEDECANATO / GESTIÓN INSTITUCIONAL</p>
                <h1>@yield('page-title', 'Panel de Control')</h1>
                <p>@yield('page-subtitle', 'Gestión y verificación de certificados académicos')</p>
            </div>
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 px-6 py-3 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
            <div>
                <span class="font-bold text-brand">Facultad de Tecnología</span> • Universidad Mayor de San Andrés (Av. Arce No. 2295)
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

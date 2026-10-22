@extends('layouts.guest')

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-3xl shadow-2xl border border-slate-200/80 overflow-hidden">
        
        <!-- Header Banner with FT Crest -->
        <div class="bg-gradient-to-b from-[#024966] to-[#01354a] px-8 py-8 text-center text-white relative">
            <div class="inline-flex items-center gap-3.5 p-3 rounded-2xl bg-white/10 backdrop-blur-xs border border-white/20 mb-3 shadow-lg">
                <img src="{{ asset('assets/institutional/logo_umsa.svg') }}" class="w-10 h-14 object-contain drop-shadow" alt="Escudo Oficial UMSA" onerror="this.onerror=null; this.src='{{ asset('assets/institutional/logo_umsa.png') }}';">
                <div class="h-10 w-px bg-white/20"></div>
                <img src="{{ asset('assets/institutional/logo_ft_umsa.svg') }}" class="w-14 h-14 object-contain drop-shadow" alt="Emblema Facultad de Tecnología" onerror="this.onerror=null; this.src='{{ asset('assets/institutional/logo_ft_umsa.png') }}';">
            </div>
            <div class="text-[10px] font-extrabold uppercase tracking-widest text-red-200">Facultad de Tecnología • UMSA</div>
            <h2 class="text-xl font-black tracking-tight text-white mt-1">Acceso Administrativo</h2>
            <p class="text-xs text-sky-200/90 mt-1">Vicedecanato — Sistema de Emisión y Control</p>
        </div>

        <!-- Form Area -->
        <div class="p-8">
            @if(session('status'))
                <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 text-xs rounded-xl border border-emerald-200 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-3.5 bg-red-50 text-red-700 text-xs rounded-xl border border-red-200">
                    <div class="font-bold mb-1 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Error de acceso</span>
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 ml-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Correo Institucional</label>
                    <div class="relative rounded-xl shadow-xs">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path></svg>
                        </div>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                                class="block w-full pl-10 pr-3.5 py-2.5 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966] focus:border-[#024966] placeholder-slate-400 transition-colors"
                                placeholder="admin@universidad.edu">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Contraseña de Seguridad</label>
                    <div class="relative rounded-xl shadow-xs">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                        <input type="password" name="password" id="password" required
                                class="block w-full pl-10 pr-3.5 py-2.5 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966] focus:border-[#024966] placeholder-slate-400 transition-colors"
                                placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center text-xs text-slate-600 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-[#BB2026] border-slate-300 rounded-sm focus:ring-[#BB2026]">
                        <span class="ml-2">Recordar sesión en este equipo</span>
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="w-full py-3 px-4 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-sm font-bold rounded-xl shadow-md hover:shadow-lg transition-all focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-[#BB2026] flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                        <span>Iniciar Sesión Segura</span>
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-100 text-center space-y-2">
                <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100 text-[11px] text-slate-500">
                    <span class="font-bold text-[#024966] block">Acceso predeterminado:</span>
                    <code class="text-slate-700 font-mono">admin@universidad.edu</code> / <code class="text-slate-700 font-mono">Admin*2026</code>
                </div>

                <p class="text-xs text-slate-500 pt-1">
                    ¿Desea validar un certificado emitido?
                    <a href="{{ route('verification.index') }}" class="text-[#32639E] font-bold hover:underline block sm:inline">Verificador Público QR &rarr;</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

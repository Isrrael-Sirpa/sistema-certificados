@extends('layouts.guest')

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200/80 overflow-hidden">
        
        <!-- Header Banner -->
        <div class="bg-[#024966] px-8 py-7 text-center text-white relative">
            <div class="inline-flex p-3 rounded-2xl bg-white/10 backdrop-blur-xs border border-white/20 mb-3 shadow-inner">
                <span class="text-3xl">🔐</span>
            </div>
            <h2 class="text-xl font-bold tracking-tight">Acceso Administrativo</h2>
            <p class="text-xs text-red-200 mt-1">Ingrese sus credenciales de Vicedecanato</p>
        </div>

        <!-- Form Area -->
        <div class="p-8">
            @if(session('status'))
                <div class="mb-4 p-3 bg-green-50 text-green-700 text-xs rounded-lg border border-green-200">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 text-red-700 text-xs rounded-lg border border-red-200">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Correo Institucional</label>
                    <div class="relative rounded-lg shadow-xs">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path></svg>
                        </div>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                                class="block w-full pl-10 pr-3 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#024966] focus:border-[#024966] placeholder-slate-400"
                                placeholder="usuario@universidad.edu">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Contraseña</label>
                    <div class="relative rounded-lg shadow-xs">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                        <input type="password" name="password" id="password" required
                                class="block w-full pl-10 pr-3 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#024966] focus:border-[#024966] placeholder-slate-400"
                                placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center text-xs text-slate-600 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-[#BB2026] border-slate-300 rounded-sm focus:ring-[#BB2026]">
                        <span class="ml-2">Recordar mi sesión</span>
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="w-full py-3 px-4 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-sm font-semibold rounded-lg shadow-md hover:shadow-lg transition-all focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-[#BB2026]">
                        Iniciar Sesión Segura &rarr;
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-500">
                    ¿Es un beneficiario o empleador?
                    <a href="{{ route('verification.index') }}" class="text-[#32639E] font-semibold hover:underline">Verificar certificado con QR o código</a>
                </p>
            </div> </p>
            </div>
        </div>
    </div>
</div>
@endsection

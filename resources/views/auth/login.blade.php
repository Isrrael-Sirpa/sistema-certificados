@extends('layouts.guest')

@section('content')
<div class="ft-login">
    <section class="ft-login-story">
        <div class="ft-login-logos">
            <img src="{{ asset('assets/institutional/logo_umsa.svg') }}" width="50" height="72" class="h-[72px] w-[50px] object-contain" alt="Escudo oficial UMSA">
            <span class="h-12 w-px bg-white/25" aria-hidden="true"></span>
            <img src="{{ asset('assets/institutional/logo_ft_umsa.svg') }}" width="64" height="64" class="h-16 w-16 object-contain" alt="Facultad de Tecnología">
        </div>
        <p class="ft-eyebrow">VICEDECANATO / FT UMSA</p>
        <h1>El mérito académico merece un gran respaldo.</h1>
        <p class="ft-login-description">Gestión, emisión y verificación de certificados académicos.</p>
        <p class="ft-login-note mt-6 pt-6 border-t border-white/15">Identidad institucional. Información organizada.</p>
    </section>

    <section class="ft-login-card" aria-labelledby="login-title">
        <p class="ft-eyebrow mb-4">Bienvenido al Vicedecanato</p>
        <h2 id="login-title">Acceso Administrativo</h2>
        <p class="ft-login-intro">Ingresa con tu cuenta institucional.</p>

        @if(session('status'))
            <div class="mt-5 p-4 bg-emerald-50 text-emerald-800 text-xs rounded-xl border border-emerald-200" role="status">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="mt-5 p-4 bg-red-50 text-red-800 text-xs rounded-xl border border-red-200" role="alert">
                <p class="font-semibold mb-2">Error de acceso</p>
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div>
                <label for="email" class="block mb-2">Correo institucional</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="username"
                       class="block w-full border border-border rounded-xl focus:ring-2 focus:ring-brand/15" placeholder="correo@umsa.bo">
            </div>
            <div x-data="{ passwordVisible: false }">
                <label for="password" class="block mb-2">Contraseña de seguridad</label>
                <div class="relative">
                    <input type="password" :type="passwordVisible ? 'text' : 'password'" name="password" id="password" required autocomplete="current-password"
                           class="block w-full border border-border rounded-xl focus:ring-2 focus:ring-brand/15 !pr-20" placeholder="Tu contraseña">
                    <button type="button" @click="passwordVisible = !passwordVisible" :aria-pressed="passwordVisible"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-brand px-1 py-2"
                            x-text="passwordVisible ? 'Ocultar' : 'Mostrar'" aria-label="Mostrar u ocultar contraseña">Mostrar</button>
                </div>
            </div>
            <div>
                <label class="flex items-center gap-2 !font-normal cursor-pointer">
                    <input type="checkbox" name="remember" class="h-4 w-4 accent-action border-border rounded">
                    <span class="text-xs text-muted">Recordar sesión en este equipo</span>
                </label>
            </div>
            <div>
                <button type="submit">Iniciar Sesión Segura <span aria-hidden="true" class="ml-2">→</span></button>
            </div>
        </form>

        <div class="mt-6 pt-5 border-t border-border">
            <p class="text-xs text-muted">¿Desea validar un certificado emitido?</p>
            <a href="{{ route('verification.index') }}" class="inline-block mt-2 text-sm font-semibold text-action hover:underline">Verificador Público QR <span aria-hidden="true">→</span></a>
        </div>
    </section>
</div>
@endsection

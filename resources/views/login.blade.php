<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inicio de sesión</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite([
            'resources/css/main.css',
            'resources/css/login.css',
            'resources/css/alertas.css',
            'resources/js/alertas.js',
            'resources/js/login.js',
        ])
    @else
        <link rel="stylesheet" href="{{ asset('css/main.css') }}">
        <link rel="stylesheet" href="{{ asset('css/login.css') }}">
        <link rel="stylesheet" href="{{ asset('css/alertas.css') }}">
        <script defer src="{{ asset('js/alertas.js') }}"></script>
        <script defer src="{{ asset('js/login.js') }}"></script>
    @endif
    <script>
        window.AppConfig = {
            baseUrl: "{{ url('/') }}",
            loginGoogle: "{{ url('/login/google') }}",
            allowedDomains: "{{ env('ALLOWED_EMAIL_DOMAINS', 'socya.org.co') }}"
        };
    </script>
    <script src="https://www.gstatic.com/firebasejs/10.13.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.13.0/firebase-auth-compat.js"></script>
</head>
<body>
    <div class="login-shell">
        <div class="login-box">
            <div class="login-left">
                @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
                    <img class="login-illustration" src="{{ \Illuminate\Support\Facades\Vite::asset('resources/img/Captura de pantalla 2026-02-09 103112.png') }}" alt="Ilustración">
                @else
                    <img class="login-illustration" src="{{ route('asset.img', ['path' => 'Captura de pantalla 2026-02-09 103112.png']) }}" alt="Ilustración">
                @endif
            </div>
            <div class="login-right">
                <div class="login-card">
                    <div class="login-header">
                        <img class="login-logo" src="https://socya.org.co/wp-content/uploads/ImagenesTI/LogoSocya.jpg" alt="Socya">
                    </div>
                    <div class="login-center">
                        <div class="login-title">Bienvenido a la mesa de servicio</div>
                        @php
                            $domains = array_filter(array_map('trim', explode(',', env('ALLOWED_EMAIL_DOMAINS', 'socya.org.co'))));
                            $domainDisplay = count($domains) ? $domains[0] : 'socya.org.co';
                        @endphp
                        <div class="login-sub">Inicia sesión con tu cuenta socya ({{ '@' . $domainDisplay }})</div>
                    </div>
                    <button id="googleLoginBtn" type="button" class="google-btn">
                        <span class="google-spinner" aria-hidden="true"></span>
                        <span class="google-logo">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                                <path fill="#FFC107" d="M43.6 20.5h-2.1V20H24v8h11.3c-1.6 4.6-6 8-11.3 8-6.9 0-12.5-5.6-12.5-12.5S17.1 11 24 11c3.2 0 6 1.2 8.2 3.1l5.7-5.7C34.9 5.4 29.8 3 24 3 12.3 3 3 12.3 3 24s9.3 21 21 21c10.7 0 20-8.7 20-21 0-1.2-.1-2.4-.4-3.5z"/>
                                <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.9 15 19.2 11 24 11c3.2 0 6 1.2 8.2 3.1l5.7-5.7C34.9 5.4 29.8 3 24 3 15.5 3 8.1 8.2 6.3 14.7z"/>
                                <path fill="#4CAF50" d="M24 45c5.4 0 10.4-2.1 14.1-5.5l-6.5-5.3c-2 1.4-4.6 2.2-7.6 2.2-5.3 0-9.7-3.4-11.3-8H6.3l-6.5 5.1C8.1 39.8 15.5 45 24 45z"/>
                                <path fill="#1976D2" d="M43.6 20.5h-2.1V20H24v8h11.3c-1.2 3.5-4.1 6.2-7.6 7.2l6.5 5.3C38.3 38 43 31.6 43.6 20.5z"/>
                            </svg>
                        </span>
                        <span class="google-text">Iniciar con Google</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

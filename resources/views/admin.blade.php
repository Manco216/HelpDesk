<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel Administrativo</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite([
            'resources/css/main.css',
            'resources/css/topbar.css',
            'resources/css/admin.css',
            'resources/css/alertas.css',
            'resources/js/main.js',
            'resources/js/admin.js',
            'resources/js/alertas.js',
        ])
    @else
        <link rel="stylesheet" href="{{ asset('css/main.css') }}">
        <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
        <link rel="stylesheet" href="{{ asset('css/alertas.css') }}">
        <script defer src="{{ asset('js/main.js') }}"></script>
        <script defer src="{{ asset('js/admin.js') }}"></script>
        <script defer src="{{ asset('js/alertas.js') }}"></script>
    @endif
</head>
<body>
    <div class="page-content admin-page">
        <div class="contenedor">
            <h1 class="titulo">Panel Administrativo</h1>
            <section class="grid">
                <button type="button" class="boton-panel" data-key="configurar-sitio">Configurar sitio</button>
                <button type="button" class="boton-panel" data-key="configurar-correo">Configurar mensajes de correo electrónico</button>
                <button type="button" class="boton-panel" data-key="cambiar-clave">Cambiar contraseña de administrador</button>
                <button type="button" class="boton-panel" data-key="usuarios">Administrar usuarios</button>
                <button type="button" class="boton-panel" data-key="categorias">Administrar categorías</button>
                <button type="button" class="boton-panel" data-key="departamentos">Administrar departamentos</button>
                <button type="button" class="boton-panel" data-key="prioridades">Administrar prioridades</button>
                <button type="button" class="boton-panel" data-key="estados">Administrar estados</button>
                <button type="button" class="boton-panel" data-key="tareas">Administrar tareas</button>
            </section>
        </div>
    </div>
</body>
</html>

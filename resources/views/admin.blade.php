<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Panel Administrativo</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite([
            'resources/css/main.css',
            'resources/css/topbar.css',
            'resources/css/admin.css',
            'resources/css/alertas.css',
            'resources/js/main.js',
            'resources/js/topbar.js',
            'resources/js/admin.js',
            'resources/js/alertas.js',
        ])
        <script defer src="{{ asset('js/categoria.js') }}"></script>
        <script defer src="{{ asset('js/usuarios.js') }}"></script>
        <script defer src="{{ asset('js/estados.js') }}"></script>
        <script defer src="{{ asset('js/prioridades.js') }}"></script>
        <script defer src="{{ asset('js/ans.js') }}"></script>
        <script defer src="{{ asset('js/proceso.js') }}"></script>
        <script defer src="{{ asset('js/departamento.js') }}"></script>
        <script defer src="{{ asset('js/tarea.js') }}"></script>
    @else
        <link rel="stylesheet" href="{{ asset('css/main.css') }}">
        <link rel="stylesheet" href="{{ asset('css/topbar.css') }}">
        <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
        <link rel="stylesheet" href="{{ asset('css/alertas.css') }}">
        <script defer src="{{ asset('js/main.js') }}"></script>
        <script defer src="{{ asset('js/topbar.js') }}"></script>
        <script defer src="{{ asset('js/admin.js') }}"></script>
        <script defer src="{{ asset('js/alertas.js') }}"></script>
        <script defer src="{{ asset('js/categoria.js') }}"></script>
        <script defer src="{{ asset('js/usuarios.js') }}"></script>
        <script defer src="{{ asset('js/estados.js') }}"></script>
        <script defer src="{{ asset('js/prioridades.js') }}"></script>
        <script defer src="{{ asset('js/ans.js') }}"></script>
        <script defer src="{{ asset('js/proceso.js') }}"></script>
        <script defer src="{{ asset('js/departamento.js') }}"></script>
        <script defer src="{{ asset('js/tarea.js') }}"></script>
    @endif
    @php
        $prefix = env('DB_PREFIX', '');
        $t = function(string $nm) use ($prefix) {
            return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
        };
        $usuario = $t('tbl_usuario');
        $departamento = $t('tbl_departamento');
        $deptName = '';
        $uid = (int)(session('user_id') ?? 0);
        if ($uid > 0 && \Illuminate\Support\Facades\Schema::hasTable($usuario)) {
            $user = \Illuminate\Support\Facades\DB::table($usuario)->where('id_usuario', $uid)->first();
            if ($user) {
                $deptId = null;
                if (\Illuminate\Support\Facades\Schema::hasColumn($usuario, 'fk_id_departamento')) $deptId = $user->fk_id_departamento;
                elseif (\Illuminate\Support\Facades\Schema::hasColumn($usuario, 'id_departamento')) $deptId = $user->id_departamento;
                if ($deptId && \Illuminate\Support\Facades\Schema::hasTable($departamento)) {
                    $deptNameCol = \Illuminate\Support\Facades\Schema::hasColumn($departamento, 'nombre_departamento') ? 'nombre_departamento' : (\Illuminate\Support\Facades\Schema::hasColumn($departamento, 'nombre') ? 'nombre' : null);
                    if ($deptNameCol) {
                        $d = \Illuminate\Support\Facades\DB::table($departamento)->where('id_departamento', $deptId)->first();
                        if ($d && isset($d->$deptNameCol)) $deptName = (string)$d->$deptNameCol;
                    }
                }
            }
        }
    @endphp
    <script>
        window.AppConfig = Object.assign(window.AppConfig || {}, {
            baseUrl: "{{ url('/') }}",
            loginGoogle: "{{ url('/login/google') }}",
            userName: "{{ (string)session('user_name', '') }}",
            userEmail: "{{ (string)session('user_email', '') }}",
            userDeptName: "{{ $deptName }}"
        });
    </script>
</head>
<body>
    <div class="content">
        <div class="page-content admin-page">
            <div class="contenedor">
            <h1 class="titulo">Panel Administrativo</h1>
            <section class="grid">
                <button type="button" class="boton-panel" data-key="configurar-sitio">Configurar sitio</button>
                <button type="button" class="boton-panel" data-key="procesos">Administrar procesos</button>
                <button type="button" class="boton-panel" data-key="usuarios">Administrar usuarios</button>
                <button type="button" class="boton-panel" data-key="categorias">Administrar categorías</button>
                <button type="button" class="boton-panel" data-key="departamentos">Administrar departamentos</button>
                <button type="button" class="boton-panel" data-key="prioridades">Administrar prioridades</button>
                <button type="button" class="boton-panel" data-key="estados">Administrar estados</button>
                <button type="button" class="boton-panel" data-key="tareas">Administrar tareas</button>
                <button type="button" class="boton-panel" data-key="ans">Administrar ANS</button>
            </section>
            </div>
        </div>
    </div>
</body>
</html>

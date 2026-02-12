<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@10/dist/gridstack.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@10/dist/gridstack-extra.min.css">
    <script src="https://cdn.jsdelivr.net/npm/gridstack@10/dist/gridstack-all.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite([
            'resources/css/main.css',
            'resources/css/topbar.css',
            'resources/css/dashboard.css',
            'resources/css/alertas.css',
            'resources/js/main.js',
            'resources/js/topbar.js',
            'resources/js/dashboard.js',
            'resources/js/alertas.js',
        ])
    @else
        <link rel="stylesheet" href="{{ asset('css/main.css') }}">
        <link rel="stylesheet" href="{{ asset('css/topbar.css') }}">
        <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
        <link rel="stylesheet" href="{{ asset('css/alertas.css') }}">
        <script defer src="{{ asset('js/main.js') }}"></script>
        <script defer src="{{ asset('js/dashboard.js') }}"></script>
        <script defer src="{{ asset('js/topbar.js') }}"></script>
        <script defer src="{{ asset('js/alertas.js') }}"></script>

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

    <div class="page-content">
        <div class="dashboard-shell">
            <div class="dashboard-header-card">
                <div class="dashboard-toolbar">
                    <div class="dashboard-title-group">
                        <h1 class="dashboard-title">Panel Analítico</h1>
                        <p class="dashboard-subtitle">Arrastra, redimensiona y organiza tus gráficas en tiempo real.</p>
                    </div>
                    <button id="add-widget-btn" class="primary-btn">Añadir Gráfica</button>
                </div>
            </div>
            <div id="dashboard-grid" class="grid-stack"></div>
        </div>
        <div id="widget-modal" class="modal-overlay hidden" aria-hidden="true">
            <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
                <div class="modal-header">
                    <h2 id="modal-title" class="modal-title">Selecciona una gráfica</h2>
                    <button type="button" class="modal-close" aria-label="Cerrar">×</button>
                </div>
                <div class="modal-body">
                    <button type="button" class="widget-option" data-type="bar" data-title="solicitud x mes x año actual">
                        <div class="widget-option-title">solicitud x mes x año actual</div>
                        <div class="widget-option-preview" data-preview-type="bar"></div>
                    </button>
                    <button type="button" class="widget-option" data-type="bar" data-title="solucitud x tarea x mes">
                        <div class="widget-option-title">solucitud x tarea x mes</div>
                        <div class="widget-option-preview" data-preview-type="bar"></div>
                    </button>
                    <button type="button" class="widget-option" data-type="bar" data-title="solicitud x tarea x año">
                        <div class="widget-option-title">solicitud x tarea x año</div>
                        <div class="widget-option-preview" data-preview-type="bar"></div>
                    </button>
                    <button type="button" class="widget-option" data-type="donut" data-title="cumplimiento x año">
                        <div class="widget-option-title">cumplimiento x año</div>
                        <div class="widget-option-preview" data-preview-type="donut"></div>
                    </button>
                    <button type="button" class="widget-option" data-type="donut" data-title="cumplimiento x dia">
                        <div class="widget-option-title">cumplimiento x dia</div>
                        <div class="widget-option-preview" data-preview-type="donut"></div>
                    </button>
                    <button type="button" class="widget-option" data-type="donut" data-title="cumplimiento x mes">
                        <div class="widget-option-title">cumplimiento x mes</div>
                        <div class="widget-option-preview" data-preview-type="donut"></div>
                    </button>
                    <button type="button" class="widget-option" data-type="line" data-title="solucitudes x dia x mes actual">
                        <div class="widget-option-title">solucitudes x dia x mes actual</div>
                        <div class="widget-option-preview" data-preview-type="line"></div>
                    </button>
                    <button type="button" class="widget-option" data-type="thermo" data-title="cumplimiento">
                        <div class="widget-option-title">cumplimiento</div>
                        <div class="widget-option-preview" data-preview-type="thermo"></div>
                    </button>
                    <button type="button" class="widget-option" data-type="radar" data-title="desempeño">
                        <div class="widget-option-title">desempeño</div>
                        <div class="widget-option-preview" data-preview-type="radar"></div>
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" id="clear-dashboard-btn" class="danger-btn">Limpiar dashboard</button>
                    <button type="button" id="add-selected-btn" class="primary-btn">Agregar seleccionadas</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

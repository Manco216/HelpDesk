<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Planificación</title>

    <script src="https://cdn.jsdelivr.net/npm/@interactjs/interactjs/index.min.js"></script>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite([
            'resources/css/main.css',
            'resources/css/topbar.css',
            'resources/css/planificacion.css',
            'resources/css/alertas.css',
            'resources/js/main.js',
            'resources/js/topbar.js',
            'resources/js/planificacion.js',
            'resources/js/alertas.js',
        ])
    @else
        <link rel="stylesheet" href="{{ asset('css/main.css') }}">
        <link rel="stylesheet" href="{{ asset('css/topbar.css') }}">
        <link rel="stylesheet" href="{{ asset('css/planificacion.css') }}">
        <link rel="stylesheet" href="{{ asset('css/alertas.css') }}">
        <script defer src="{{ asset('js/main.js') }}"></script>
        <script defer src="{{ asset('js/topbar.js') }}"></script>
        <script defer src="{{ asset('js/planificacion.js') }}"></script>
        <script defer src="{{ asset('js/alertas.js') }}"></script>
    @endif
</head>
<body>
    <div class="page-content layout">
        <main class="calendar">
            <header class="calendar-header">
                <button class="nav-btn" id="prevMonth" aria-label="Mes anterior">◀</button>
                <h1 class="month-label" id="monthLabel"></h1>
                <button class="nav-btn" id="nextMonth" aria-label="Mes siguiente">▶</button>
            </header>
            <section class="weekdays" id="weekdays"></section>
            <section class="days" id="days"></section>
        </main>
        <aside class="schedule">
            <div class="schedule-header">
                <h2>Tareas pendientes</h2>
                <div class="hint">Arrastra una tarea sobre un día</div>
            </div>
            <ul class="todo-list" id="todoList"></ul>
            <form class="search-form" id="searchForm">
                <input type="search" id="searchInput" placeholder="Buscar tarea">
                <button type="button" class="clear-btn" id="clearSearch">Limpiar</button>
            </form>
            <div class="actions">
                <button type="button" class="primary-btn" id="loadSamples">Cargar pruebas</button>
                <button type="button" class="danger-btn" id="resetAll">Borrar todo</button>
            </div>
        </aside>
        <div class="modal" id="dayModal" aria-hidden="true">
            <div class="modal-backdrop" id="modalBackdrop"></div>
            <div class="modal-card">
                <div class="modal-header">
                    <div class="modal-title" id="modalTitle"></div>
                    <button type="button" class="close-btn" id="closeModal">✕</button>
                </div>
                <ul class="events" id="modalEvents"></ul>
                <div class="modal-empty" id="modalEmpty">Sin tareas asignadas</div>
            </div>
        </div>
    </div>
</body>
</html>

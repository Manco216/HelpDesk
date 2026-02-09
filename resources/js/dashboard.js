window.addEventListener('load', function () {
    var gridElement = document.getElementById('dashboard-grid');
    var grid = null;
    var STORAGE_KEY = 'dashboard_widgets_v1';
    var widgets = [];

    function ensureGrid() {
        if (grid) {
            return true;
        }
        if (gridElement && typeof GridStack !== 'undefined') {
            grid = GridStack.init({
                float: true,
                margin: 10,
                cellHeight: 140,
                column: 12,
                draggable: { handle: '.widget-header' },
                resizable: { handles: 'e, se, s' }
            }, gridElement);
            grid.on('change', function () {
                syncWidgetsFromGrid();
            });
            return true;
        }
        return false;
    }

    var chartMap = new WeakMap();
    var resizeObserver = new ResizeObserver(function (entries) {
        entries.forEach(function (entry) {
            var chart = chartMap.get(entry.target);
            if (chart) {
                chart.resize();
            }
        });
    });

    function clearDashboard() {
        if (!gridElement) {
            return;
        }
        var items = gridElement.querySelectorAll('.grid-stack-item');
        items.forEach(function (item) {
            var chartContainer = item.querySelector('.widget-chart');
            if (chartContainer) {
                var chart = chartMap.get(chartContainer);
                if (chart) {
                    chart.dispose();
                    chartMap.delete(chartContainer);
                }
                resizeObserver.unobserve(chartContainer);
            }
        });
        if (grid) {
            grid.removeAll();
        } else {
            gridElement.innerHTML = '';
        }
        widgets = [];
        persistWidgets();
    }

    function randomSeries(count, min, max) {
        var result = [];
        for (var i = 0; i < count; i++) {
            var v = Math.floor(min + Math.random() * (max - min));
            result.push(v);
        }
        return result;
    }

    function hexToRgb(hex) {
        var h = hex.replace('#', '');
        var r = parseInt(h.slice(0, 2), 16);
        var g = parseInt(h.slice(2, 4), 16);
        var b = parseInt(h.slice(4, 6), 16);
        return [r, g, b];
    }
    function rgbToHex(r, g, b) {
        var s = function (n) { var x = n.toString(16); return x.length < 2 ? '0' + x : x; };
        return '#' + s(r) + s(g) + s(b);
    }
    function mixColor(c1, c2, t) {
        var r = Math.round(c1[0] + (c2[0] - c1[0]) * t);
        var g = Math.round(c1[1] + (c2[1] - c1[1]) * t);
        var b = Math.round(c1[2] + (c2[2] - c1[2]) * t);
        return rgbToHex(r, g, b);
    }
    function gaugeStops(base, mid, end, n1, n2) {
        var stops = [];
        var b = hexToRgb(base);
        var m = hexToRgb(mid);
        var e = hexToRgb(end);
        stops.push([0, base]);
        for (var i = 1; i <= n1; i++) {
            var t1 = i / n1;
            var v1 = 0.6 * t1;
            stops.push([v1, mixColor(b, m, t1)]);
        }
        for (var j = 1; j <= n2; j++) {
            var t2 = j / n2;
            var v2 = 0.6 + 0.4 * t2;
            stops.push([v2, mixColor(m, e, t2)]);
        }
        stops[stops.length - 1] = [1, end];
        return stops;
    }

    function setWidgetState(overlay, mode, message) {
        if (!overlay) {
            return;
        }
        var textNode = overlay.querySelector('.widget-state-text');
        overlay.classList.remove('error');
        if (mode === 'loading') {
            overlay.classList.add('visible');
            if (textNode) {
                textNode.textContent = 'Cargando gráfica...';
            }
        } else if (mode === 'error') {
            overlay.classList.add('visible');
            overlay.classList.add('error');
            if (textNode) {
                textNode.textContent = message || 'Ocurrió un error al cargar la gráfica';
            }
        } else if (mode === 'success') {
            overlay.classList.add('visible');
            if (textNode) {
                textNode.textContent = 'Listo';
            }
            setTimeout(function () {
                overlay.classList.remove('visible');
            }, 400);
        } else if (mode === 'hidden') {
            overlay.classList.remove('visible');
        }
    }

    function loadWidgetsFromStorage() {
        try {
            var raw = window.localStorage.getItem(STORAGE_KEY);
            if (!raw) {
                widgets = [];
                return;
            }
            var parsed = JSON.parse(raw);
            if (!Array.isArray(parsed)) {
                widgets = [];
                return;
            }
            widgets = parsed;
        } catch (e) {
            widgets = [];
        }
    }

    function persistWidgets() {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(widgets));
        } catch (e) {
        }
    }

    function swapPositionsByTitle(titleA, titleB) {
        if (!Array.isArray(widgets)) {
            return;
        }
        var ta = (titleA || '').toLowerCase();
        var tb = (titleB || '').toLowerCase();
        var a = widgets.find(function (w) { return ((w.title || '').toLowerCase() === ta); });
        var b = widgets.find(function (w) { return ((w.title || '').toLowerCase() === tb); });
        if (a && b) {
            var ax = a.x || 0, ay = a.y || 0;
            a.x = b.x || 0; a.y = b.y || 0;
            b.x = ax; b.y = ay;
            persistWidgets();
        }
    }

    function syncWidgetsFromGrid() {
        if (!gridElement || !grid) {
            return;
        }
        var items = gridElement.querySelectorAll('.grid-stack-item');
        items.forEach(function (el) {
            var id = el.dataset.widgetId;
            if (!id || !el.gridstackNode) {
                return;
            }
            var cfg = widgets.find(function (w) { return w.id === id; });
            if (!cfg) {
                return;
            }
            cfg.x = el.gridstackNode.x;
            cfg.y = el.gridstackNode.y;
            cfg.w = el.gridstackNode.w;
            cfg.h = el.gridstackNode.h;
        });
        persistWidgets();
    }

    function createChart(type, el) {
        if (typeof echarts === 'undefined') {
            return null;
        }
        var chart = echarts.init(el);
        var baseColor = '#00A0DF';
        var option;

        if (type === 'bar') {
            var dataBar = randomSeries(6, 40, 180);
            option = {
                grid: { left: 32, right: 16, top: 32, bottom: 32 },
                xAxis: {
                    type: 'category',
                    data: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                    axisLine: { lineStyle: { color: '#94a3b8' } },
                    axisLabel: { color: '#64748b' }
                },
                yAxis: {
                    type: 'value',
                    axisLine: { lineStyle: { color: '#94a3b8' } },
                    splitLine: { lineStyle: { color: 'rgba(148, 163, 184, 0.3)' } },
                    axisLabel: { color: '#64748b' }
                },
                tooltip: { trigger: 'axis' },
                series: [
                    {
                        type: 'bar',
                        data: dataBar,
                        itemStyle: { color: baseColor, borderRadius: [4, 4, 0, 0] },
                        barMaxWidth: 28
                    }
                ]
            };
        } else if (type === 'line') {
            var data = randomSeries(6, 80, 260);
            option = {
                grid: { left: 32, right: 16, top: 32, bottom: 32 },
                xAxis: {
                    type: 'category',
                    data: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                    axisLine: { lineStyle: { color: '#94a3b8' } },
                    axisLabel: { color: '#64748b' }
                },
                yAxis: {
                    type: 'value',
                    axisLine: { lineStyle: { color: '#94a3b8' } },
                    splitLine: { lineStyle: { color: 'rgba(148, 163, 184, 0.3)' } },
                    axisLabel: { color: '#64748b' }
                },
                tooltip: { trigger: 'axis' },
                series: [
                    {
                        type: 'line',
                        data: data,
                        smooth: true,
                        lineStyle: { color: baseColor, width: 3 },
                        areaStyle: {
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                { offset: 0, color: 'rgba(0, 160, 223, 0.35)' },
                                { offset: 1, color: 'rgba(0, 160, 223, 0)' }
                            ])
                        },
                        symbolSize: 6,
                        itemStyle: { color: baseColor }
                    }
                ]
            };
        } else if (type === 'donut') {
            var a = Math.floor(40 + Math.random() * 40);
            var b = Math.floor(20 + Math.random() * 40);
            var c = Math.max(10, 100 - a - b);
            option = {
                tooltip: { trigger: 'item' },
                legend: {
                    bottom: 0,
                    textStyle: { color: '#64748b', fontSize: 12 }
                },
                series: [
                    {
                        name: 'Usuarios',
                        type: 'pie',
                        radius: ['40%', '70%'],
                        avoidLabelOverlap: false,
                        itemStyle: {
                            borderRadius: 10,
                            borderColor: '#ffffff',
                            borderWidth: 2
                        },
                        label: { show: false, position: 'center' },
                        emphasis: {
                            label: { show: true, fontSize: 16, fontWeight: 'bold', color: '#0f172a' }
                        },
                        labelLine: { show: false },
                        data: [
                            { value: a, name: 'Web' },
                            { value: b, name: 'Mobile' },
                            { value: c, name: 'Otros' }
                        ],
                        color: [
                            baseColor,
                            '#38bdf8',
                            '#a5b4fc',
                            '#e5e7eb'
                        ]
                    }
                ]
            };
        } else if (type === 'radar') {
            var values = randomSeries(5, 40, 95);
            option = {
                tooltip: {},
                radar: {
                    indicator: [
                        { name: 'Ventas', max: 100 },
                        { name: 'Marketing', max: 100 },
                        { name: 'Operaciones', max: 100 },
                        { name: 'Soporte', max: 100 },
                        { name: 'Desarrollo', max: 100 }
                    ],
                    splitLine: { lineStyle: { color: 'rgba(148, 163, 184, 0.6)' } },
                    splitArea: { areaStyle: { color: ['#ffffff', '#f1f5f9'] } },
                    axisLine: { lineStyle: { color: 'rgba(148, 163, 184, 0.8)' } }
                },
                series: [
                    {
                        type: 'radar',
                        data: [
                            {
                                value: values,
                                areaStyle: {
                                    color: 'rgba(0, 160, 223, 0.35)'
                                },
                                lineStyle: {
                                    color: baseColor
                                },
                                itemStyle: {
                                    color: baseColor
                                }
                            }
                        ]
                    }
                ]
            };
        } else if (type === 'thermo') {
            var value = Math.floor(10 + Math.random() * 85);
            option = {
                tooltip: { formatter: '{a}<br/>{b} : {c}%' },
                series: [
                    {
                        name: 'Termómetro',
                        type: 'gauge',
                        startAngle: 180,
                        endAngle: 0,
                        min: 0,
                        max: 100,
                        splitNumber: 5,
                        axisLine: {
                            lineStyle: {
                                width: 12,
                                color: gaugeStops('#00A0DF', '#a5b4fc', '#ef4444', 20, 20)
                            }
                        },
                        pointer: {
                            show: true,
                            length: '60%',
                            width: 4
                        },
                        axisTick: { show: false },
                        splitLine: { show: false },
                        axisLabel: { show: false },
                        detail: {
                            valueAnimation: true,
                            formatter: '{value}%',
                            color: '#0f172a',
                            fontSize: 16
                        },
                        data: [{ value: value, name: 'Nivel' }]
                    }
                ]
            };
        }

        if (option) {
            chart.setOption(option);
        }

        return chart;
    }

    function createWidget(type, customTitle) {
        if (!gridElement) {
            return;
        }

        var useGrid = ensureGrid();

        var titles = {
            bar: 'Gráfica de Barras',
            line: 'solucitudes x dia x mes actual',
            donut: 'Gráfica de Dona',
            radar: 'desempeño',
            thermo: 'cumplimiento'
        };

        var id = 'w_' + Date.now().toString(36) + '_' + Math.floor(Math.random() * 100000).toString(36);

        var item = document.createElement('div');
        item.className = 'grid-stack-item';
        item.dataset.widgetId = id;
        item.innerHTML =
            '<div class="grid-stack-item-content widget-card">' +
            '<div class="widget-header">' +
            '<span class="widget-title">' + (customTitle || titles[type] || '') + '</span>' +
            '<button type="button" class="widget-close" aria-label="Cerrar">×</button>' +
            '</div>' +
            '<div class="widget-body">' +
            '<div class="widget-chart"></div>' +
            '<div class="widget-state-overlay">' +
            '<div class="widget-state-content">' +
            '<div class="widget-state-spinner"></div>' +
            '<div class="widget-state-text">Cargando gráfica...</div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>';

        var config = {
            id: id,
            type: type,
            x: 0,
            y: 0,
            w: 4,
            h: 3,
            title: customTitle || titles[type] || ''
        };
        widgets.push(config);
        persistWidgets();

        if (useGrid && grid) {
            grid.addWidget(item, { w: config.w, h: config.h, minW: 3, minH: 2, autoPosition: true });
            syncWidgetsFromGrid();
        } else {
            gridElement.appendChild(item);
        }

        var chartContainer = item.querySelector('.widget-chart');
        var overlay = item.querySelector('.widget-state-overlay');
        setWidgetState(overlay, 'loading');

        var chart = createChart(type, chartContainer);
        if (!chart) {
            setWidgetState(overlay, 'error');
        } else {
            chartMap.set(chartContainer, chart);
            resizeObserver.observe(chartContainer);
            setWidgetState(overlay, 'success');
        }

        var closeBtn = item.querySelector('.widget-close');
        closeBtn.addEventListener('click', function () {
            resizeObserver.unobserve(chartContainer);
            if (chart) {
                chart.dispose();
            }
            var idx = widgets.findIndex(function (w) { return w.id === id; });
            if (idx !== -1) {
                widgets.splice(idx, 1);
                persistWidgets();
            }
            if (grid && useGrid) {
                grid.removeWidget(item);
            } else if (item.parentNode) {
                item.parentNode.removeChild(item);
            }
        });
    }

    var modal = document.getElementById('widget-modal');
    var openBtn = document.getElementById('add-widget-btn');
    var closeBtn = modal ? modal.querySelector('.modal-close') : null;
    var optionButtons = modal ? modal.querySelectorAll('.widget-option') : [];
    var previewContainers = modal ? modal.querySelectorAll('.widget-option-preview') : [];
    var addSelectedBtn = modal ? modal.querySelector('#add-selected-btn') : null;
    var selectedItems = [];

    function createPreviewChart(type, el) {
        if (typeof echarts === 'undefined') {
            return null;
        }
        var chart = echarts.init(el);
        var baseColor = '#00A0DF';
        var option;

        if (type === 'bar') {
            option = {
                grid: { left: 6, right: 6, top: 6, bottom: 6 },
                xAxis: {
                    type: 'category',
                    data: ['A', 'B', 'C', 'D'],
                    axisLine: { show: false },
                    axisTick: { show: false },
                    axisLabel: { show: false }
                },
                yAxis: {
                    type: 'value',
                    axisLine: { show: false },
                    axisTick: { show: false },
                    axisLabel: { show: false },
                    splitLine: { show: false }
                },
                series: [
                    {
                        type: 'bar',
                        data: [12, 18, 10, 16],
                        itemStyle: { color: baseColor, borderRadius: [3, 3, 0, 0] },
                        barMaxWidth: 18
                    }
                ]
            };
        } else if (type === 'line') {
            option = {
                grid: { left: 8, right: 8, top: 8, bottom: 8 },
                xAxis: {
                    type: 'category',
                    data: ['L', 'M', 'X', 'J', 'V'],
                    axisLine: { show: false },
                    axisTick: { show: false },
                    axisLabel: { show: false }
                },
                yAxis: {
                    type: 'value',
                    axisLine: { show: false },
                    axisTick: { show: false },
                    axisLabel: { show: false },
                    splitLine: { show: false }
                },
                series: [
                    {
                        type: 'line',
                        data: [20, 40, 30, 60, 45],
                        smooth: true,
                        lineStyle: { color: baseColor, width: 2 },
                        areaStyle: {
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                { offset: 0, color: 'rgba(0, 160, 223, 0.4)' },
                                { offset: 1, color: 'rgba(0, 160, 223, 0)' }
                            ])
                        },
                        symbolSize: 0
                    }
                ]
            };
        } else if (type === 'donut') {
            option = {
                tooltip: { show: false },
                series: [
                    {
                        type: 'pie',
                        radius: ['55%', '80%'],
                        avoidLabelOverlap: false,
                        label: { show: false },
                        labelLine: { show: false },
                        data: [
                            { value: 45, name: 'A' },
                            { value: 30, name: 'B' },
                            { value: 25, name: 'C' }
                        ],
                        color: [
                            baseColor,
                            '#38bdf8',
                            '#a5b4fc'
                        ]
                    }
                ]
            };
        } else if (type === 'radar') {
            option = {
                tooltip: { show: false },
                radar: {
                    indicator: [
                        { name: 'A', max: 100 },
                        { name: 'B', max: 100 },
                        { name: 'C', max: 100 },
                        { name: 'D', max: 100 },
                        { name: 'E', max: 100 }
                    ],
                    splitNumber: 3,
                    splitLine: { lineStyle: { color: 'rgba(148, 163, 184, 0.6)' } },
                    splitArea: { areaStyle: { color: ['#ffffff', '#e5f3fb'] } },
                    axisLine: { lineStyle: { color: 'rgba(148, 163, 184, 0.6)' } },
                    axisName: { show: false }
                },
                series: [
                    {
                        type: 'radar',
                        data: [
                            {
                                value: [60, 40, 80, 50, 70],
                                areaStyle: {
                                    color: 'rgba(0, 160, 223, 0.4)'
                                },
                                lineStyle: {
                                    color: baseColor
                                },
                                itemStyle: {
                                    color: baseColor
                                }
                            }
                        ]
                    }
                ]
            };
        } else if (type === 'thermo') {
            var value = Math.floor(20 + Math.random() * 70);
            option = {
                tooltip: { show: false },
                series: [
                    {
                        type: 'gauge',
                        startAngle: 180,
                        endAngle: 0,
                        min: 0,
                        max: 100,
                        axisLine: {
                            lineStyle: {
                                width: 8,
                                color: gaugeStops('#00A0DF', '#a5b4fc', '#ef4444', 16, 16)
                            }
                        },
                        pointer: { show: false },
                        axisTick: { show: false },
                        splitLine: { show: false },
                        axisLabel: { show: false },
                        detail: { show: false },
                        data: [{ value: value }]
                    }
                ]
            };
        }

        if (option) {
            chart.setOption(option);
        }

        return chart;
    }

    function renderStoredWidgets() {
        loadWidgetsFromStorage();
        if (!widgets.length || !gridElement) {
            return;
        }
        var useGrid = ensureGrid();
        swapPositionsByTitle('cumplimiento x año', 'solucitudes x dia x mes actual');
        widgets.forEach(function (cfg) {
            var titles = {
                bar: 'Gráfica de Barras',
                line: 'solucitudes x dia x mes actual',
                donut: 'Gráfica de Dona',
                radar: 'desempeño',
                thermo: 'cumplimiento'
            };
            var item = document.createElement('div');
            item.className = 'grid-stack-item';
            item.dataset.widgetId = cfg.id;
            item.innerHTML =
                '<div class="grid-stack-item-content widget-card">' +
                '<div class="widget-header">' +
                '<span class="widget-title">' + (cfg.title || titles[cfg.type] || '') + '</span>' +
                '<button type="button" class="widget-close" aria-label="Cerrar">×</button>' +
                '</div>' +
                '<div class="widget-body">' +
                '<div class="widget-chart"></div>' +
                '<div class="widget-state-overlay">' +
                '<div class="widget-state-content">' +
                '<div class="widget-state-spinner"></div>' +
                '<div class="widget-state-text">Cargando gráfica...</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>';

            if (useGrid && grid) {
                grid.addWidget(item, {
                    x: cfg.x || 0,
                    y: cfg.y || 0,
                    w: cfg.w || 4,
                    h: cfg.h || 3,
                    minW: 3,
                    minH: 2
                });
            } else {
                gridElement.appendChild(item);
            }

            var chartContainer = item.querySelector('.widget-chart');
            var overlay = item.querySelector('.widget-state-overlay');
            setWidgetState(overlay, 'loading');
            var chart = createChart(cfg.type, chartContainer);
            if (!chart) {
                setWidgetState(overlay, 'error');
            } else {
                chartMap.set(chartContainer, chart);
                resizeObserver.observe(chartContainer);
                setWidgetState(overlay, 'success');
            }

            var closeBtn = item.querySelector('.widget-close');
            closeBtn.addEventListener('click', function () {
                resizeObserver.unobserve(chartContainer);
                if (chart) {
                    chart.dispose();
                }
                var idx = widgets.findIndex(function (w) { return w.id === cfg.id; });
                if (idx !== -1) {
                    widgets.splice(idx, 1);
                    persistWidgets();
                }
                if (grid && useGrid) {
                    grid.removeWidget(item);
                } else if (item.parentNode) {
                    item.parentNode.removeChild(item);
                }
            });
        });
    }

    function openModal() {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
    }

    if (previewContainers && previewContainers.length && typeof echarts !== 'undefined') {
        previewContainers.forEach(function (preview) {
            var previewType = preview.getAttribute('data-preview-type');
            if (previewType) {
                createPreviewChart(previewType, preview);
            }
        });
    }

    function ensureDefaultWidgets() {
        loadWidgetsFromStorage();
        if (widgets.length) {
            return;
        }
        var defaults = [];
        var makeId = function () { return 'w_' + Date.now().toString(36) + '_' + Math.floor(Math.random() * 100000).toString(36); };
        defaults.push({ id: makeId(), type: 'bar', title: 'solicitud x mes x año actual', x: 0, y: 0, w: 4, h: 3 });
        defaults.push({ id: makeId(), type: 'bar', title: 'solucitud x tarea x mes', x: 4, y: 0, w: 4, h: 3 });
        defaults.push({ id: makeId(), type: 'bar', title: 'solicitud x tarea x año', x: 8, y: 0, w: 4, h: 3 });
        defaults.push({ id: makeId(), type: 'donut', title: 'cumplimiento x dia', x: 0, y: 3, w: 4, h: 3 });
        defaults.push({ id: makeId(), type: 'donut', title: 'cumplimiento x mes', x: 4, y: 3, w: 4, h: 3 });
        defaults.push({ id: makeId(), type: 'donut', title: 'cumplimiento x año', x: 8, y: 3, w: 4, h: 3 });
        widgets = defaults;
        persistWidgets();
    }

    ensureDefaultWidgets();
    renderStoredWidgets();

    if (addSelectedBtn) {
        addSelectedBtn.addEventListener('click', function () {
            if (!selectedItems.length) {
                closeModal();
                return;
            }
            selectedItems.forEach(function (it) {
                createWidget(it.type, it.title);
            });
            selectedItems = [];
            optionButtons.forEach(function (b) { b.classList.remove('selected'); });
            closeModal();
        });
    }
    var clearBtn = addSelectedBtn ? modal.querySelector('#clear-dashboard-btn') : null;
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            clearDashboard();
            selectedTypes.clear();
            optionButtons.forEach(function (b) { b.classList.remove('selected'); });
            closeModal();
        });
    }
    if (openBtn) {
        openBtn.addEventListener('click', function () {
            openModal();
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            closeModal();
        });
    }

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    if (optionButtons && optionButtons.length) {
        optionButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var action = button.getAttribute('data-action');
                var type = button.getAttribute('data-type');
                var title = button.getAttribute('data-title') || '';
                if (action === 'clear-dashboard') {
                    clearDashboard();
                    return;
                }
                if (!type) {
                    return;
                }
                if (button.classList.contains('selected')) {
                    button.classList.remove('selected');
                    selectedItems = selectedItems.filter(function (it) { return !(it.type === type && it.title === title); });
                } else {
                    button.classList.add('selected');
                    selectedItems.push({ type: type, title: title });
                }
            });
        });
    }

    if (addSelectedBtn) {
        addSelectedBtn.addEventListener('click', function () {
            if (!selectedItems.length) {
                closeModal();
                return;
            }
            selectedItems.forEach(function (it) {
                createWidget(it.type, it.title);
            });
            selectedItems = [];
            optionButtons.forEach(function (b) { b.classList.remove('selected'); });
            closeModal();
        });
    }
});

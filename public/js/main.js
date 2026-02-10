document.addEventListener('DOMContentLoaded', () => {
    const initLayout = () => {
        // ============================================
        // 1. CREAR O VALIDAR SIDEBAR
        // ============================================
        let sidebar = document.querySelector('.sidebar');
        if (!sidebar) {
            sidebar = document.createElement('aside');
            sidebar.className = 'sidebar';
            
            // Logos
            const logoCollapsed = document.createElement('img');
            logoCollapsed.className = 'sidebar-logo logo-collapsed';
            logoCollapsed.src = 'https://socya.org.co/wp-content/uploads/ImagenesTI/Icono_verde.png';
            logoCollapsed.alt = 'Socya Icon';
            
            const logoExpanded = document.createElement('img');
            logoExpanded.className = 'sidebar-logo logo-expanded';
            logoExpanded.src = 'https://socya.org.co/wp-content/uploads/ImagenesTI/LogoSocya.jpg';
            logoExpanded.alt = 'Socya Logo';
            
            // Navigation - configuración de items con iconos
            const nav = document.createElement('nav');
            nav.className = 'nav';
            const items = [
                {
                    text: 'Dashboard',
                    key: 'dashboard',
                    href: '/dashboard',
                    icon: `
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                    `,
                },
                {
                    text: 'Planificacion',
                    key: 'planificacion',
                    href: '/planificacion',
                    icon: `
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    `,
                },
                {
                    text: 'Administracion',
                    key: 'administracion',
                    href: '/admin',
                    icon: `
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 8.6 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 8.6a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 8.6 4.6 1.65 1.65 0 0 0 10.11 3H10a2 2 0 0 1 4 0v.09A1.65 1.65 0 0 0 15.4 4.6a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 8.6 1.65 1.65 0 0 0 20.91 10H21a2 2 0 0 1 0 4h-.09A1.65 1.65 0 0 0 19.4 15z"></path>
                        </svg>
                    `,
                },
            ];
            
            const currentPath = window.location.pathname || '/';

            // Render de items de navegación con iconos
            items.forEach((item, idx) => {
                const a = document.createElement('a');
                a.href = item.href || '#';
                let isActive = false;
                if (item.key === 'dashboard') {
                    isActive = currentPath === '/' || currentPath.startsWith('/dashboard');
                } else if (item.href) {
                    isActive = currentPath.startsWith(item.href);
                }
                a.className = 'nav-item' + (isActive ? ' active' : '');
                const icon = document.createElement('span');
                icon.className = 'icon';
                const label = document.createElement('span');
                label.className = 'label';
                label.textContent = item.text;
                if (item.icon) {
                    icon.innerHTML = item.icon;
                }
                a.appendChild(icon);
                a.appendChild(label);
                nav.appendChild(a);
            });
            
            // Footer
            const footer = document.createElement('div');
            footer.className = 'sidebar-footer';
            ['Help Centre', 'Contact us', 'Log out'].forEach((text) => {
                const a = document.createElement('a');
                a.href = '#';
                a.className = 'nav-item';
                const icon = document.createElement('span');
                icon.className = 'icon';
                const label = document.createElement('span');
                label.className = 'label';
                label.textContent = text;
                a.appendChild(icon);
                a.appendChild(label);
                footer.appendChild(a);
            });
            
            sidebar.appendChild(logoCollapsed);
            sidebar.appendChild(logoExpanded);
            sidebar.appendChild(nav);
            sidebar.appendChild(footer);
            document.body.insertBefore(sidebar, document.body.firstChild);

            const footerLinks = Array.from(footer.querySelectorAll('.nav-item'));
            const logoutLink = footerLinks.find((a) => {
                const t = (a.querySelector('.label') && a.querySelector('.label').textContent) || a.textContent || '';
                return t.toLowerCase().includes('log out') || t.toLowerCase().includes('logout') || t.toLowerCase().includes('cerrar sesión');
            });
            if (logoutLink) {
                logoutLink.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const base = (window.AppConfig && window.AppConfig.baseUrl) ? window.AppConfig.baseUrl : '';
                    const url = base + '/logout';
                    try {
                        await fetch(url, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin'
                        });
                    } catch (err) {}
                    try { localStorage.removeItem('HelpdeskSearchState'); } catch (_) {}
                    try { localStorage.removeItem('HelpdeskCatalogsV1'); } catch (_) {}
                    const loginUrl = (window.AppConfig && window.AppConfig.baseUrl) ? (window.AppConfig.baseUrl + '/login') : '/login';
                    window.location.assign(loginUrl);
                });
            }
        }

        // ============================================
        // 2. CREAR O VALIDAR CONTENT WRAPPER
        // ============================================
        let content = document.querySelector('.content');
        if (!content) {
            content = document.createElement('main');
            content.className = 'content';
            
            // Mover todo el contenido existente dentro de .content
            const pageContent = document.querySelector('.page-content');
            if (pageContent) {
                content.appendChild(pageContent);
            } else {
                // Si no hay .page-content, mover todos los elementos que no sean sidebar
                const children = Array.from(document.body.children).filter(
                    (el) => !el.classList.contains('sidebar')
                );
                children.forEach((el) => content.appendChild(el));
            }
            
            document.body.appendChild(content);
        }

        // ============================================
        // 6. FLOATING ACTION BUTTON & CHAT
        // ============================================
        // Remove old wrapper if exists to avoid duplicates
        const oldWrapper = document.querySelector('.fab-wrapper');
        if (oldWrapper) oldWrapper.remove();

        let fabContainer = document.querySelector('.fab-container');
        if (!fabContainer) {
            fabContainer = document.createElement('div');
            fabContainer.className = 'fab-container';
            
            // --- MAIN FAB ELEMENTS (Flat Structure) ---
            // Menu Items Wrapper
            const fabMenu = document.createElement('div');
            fabMenu.className = 'fab-menu';
            
            // Function to create FAB items
            const createFabItem = (text, iconHtml) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'fab-item';
                
                const label = document.createElement('span');
                label.className = 'fab-label';
                label.textContent = text;
                
                const btn = document.createElement('button');
                btn.className = 'fab-button-mini';
                btn.innerHTML = iconHtml;
                
                wrapper.appendChild(label);
                wrapper.appendChild(btn);
                return wrapper;
            };
            
            const item1 = createFabItem('Agregar una nueva solicitud', `
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            `);
            const item2 = createFabItem('Buscar solicitudes', `
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            `);
            const item3 = createFabItem('Mis solicitudes', `
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"></line><line x1="4" y1="12" x2="16" y2="12"></line><line x1="4" y1="18" x2="14" y2="18"></line></svg>
            `);
            fabMenu.appendChild(item3);
            fabMenu.appendChild(item2);
            fabMenu.appendChild(item1);

            const requestModalBackdrop = document.createElement('div');
            requestModalBackdrop.className = 'request-modal-backdrop';
            const requestModal = document.createElement('div');
            requestModal.className = 'request-modal';
            requestModal.innerHTML = `
                <form class="request-form">
                    <div class="request-modal-header">
                        <h3>Nueva solicitud</h3>
                        <button type="button" class="request-modal-close">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>
                    <div class="field">
                        <label for="request-process">Proceso</label>
                        <select id="request-process" name="process">
                            <option value="">Selecciona un proceso</option>
                        </select>
                        <div class="field-help process-required-msg">Selecciona un proceso para habilitar el formulario</div>
                    </div>
                    <div class="request-form-body">
                    <div class="field">
                        <label for="request-category">Categoría</label>
                        <select id="request-category" name="category">
                            <option value="">Selecciona una categoría</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="request-description">Descripción</label>
                        <textarea id="request-description" name="description" rows="4" placeholder="Describe brevemente tu petición"></textarea>
                    </div>
                    <div class="field">
                        <label for="request-files">Adjuntar archivos</label>
                        <div class="file-dropzone" tabindex="0">
                            <div class="file-dropzone-icon">+</div>
                            <div class="file-dropzone-text">Haz clic o arrastra archivos (máx. 10)</div>
                            <div class="file-dropzone-files"></div>
                        </div>
                        <input id="request-files" name="files" type="file" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,image/*" hidden>
                        <div class="field-help">PDF, Word, Excel e imágenes · Máx. 10 archivos</div>
                    </div>
                    <div class="request-modal-actions">
                        <button type="button" class="request-cancel">Cancelar</button>
                        <button type="submit" class="request-submit">Enviar petición</button>
                    </div>
                    </div>
                </form>
            `;

            const searchModalBackdrop = document.createElement('div');
            searchModalBackdrop.className = 'search-modal-backdrop';
            const searchModal = document.createElement('div');
            searchModal.className = 'search-modal';
            searchModal.innerHTML = `
                <form class="search-form">
                    <div class="search-modal-header">
                        <h3>Buscar solicitudes</h3>
                        <button type="button" class="search-modal-close">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>
                    <div class="search-section">
                        <div class="search-section-title">Especificaciones del problema</div>
                        <div class="search-grid">
                            <div class="field">
                                <label for="search-reported-by">Reportado por</label>
                                <input id="search-reported-by" name="reported_by" type="text" autocomplete="off">
                            </div>
                            <div class="field">
                                <label for="search-problem-id">ID de problema</label>
                                <input id="search-problem-id" name="problem_id" type="text" autocomplete="off">
                            </div>
                            <div class="field">
                                <label for="search-assigned-to">Asignado a</label>
                                <select id="search-assigned-to" name="assigned_to">
                                    <option value="">Cualquiera</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="search-category">Categoría</label>
                                <select id="search-category" name="category">
                                    <option value="">Cualquiera</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="search-department">Departamento</label>
                                <select id="search-department" name="department">
                                    <option value="">Cualquiera</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="search-status">Estado</label>
                                <select id="search-status" name="status">
                                    <option value="">Cualquiera</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="search-priority">Prioridad</label>
                                <select id="search-priority" name="priority">
                                    <option value="">Cualquiera</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="search-section">
                        <div class="search-section-title">Contenido</div>
                        <div class="field">
                            <label for="search-keywords">Palabras clave</label>
                            <input id="search-keywords" name="keywords" type="text" placeholder="Título, descripción, solución..." autocomplete="off">
                        </div>
                    </div>
                    <div class="search-section">
                        <div class="search-section-title">Ordenar por</div>
                        <div class="search-order-grid">
                            <label class="search-order-option">
                                <input type="radio" name="order_by" value="id" checked>
                                <span>ID de problema</span>
                            </label>
                            <label class="search-order-option">
                                <input type="radio" name="order_by" value="created_at">
                                <span>Fecha de creación</span>
                            </label>
                            <label class="search-order-option">
                                <input type="radio" name="order_by" value="priority">
                                <span>Prioridad</span>
                            </label>
                            <label class="search-order-option">
                                <input type="radio" name="order_by" value="status">
                                <span>Estado</span>
                            </label>
                        </div>
                    </div>
                    <div class="search-section">
                        <div class="search-section-title">Fechas</div>
                        <div class="search-dates-grid">
                            <div class="field">
                                <label for="search-date-from">Desde</label>
                                <input id="search-date-from" name="date_from" type="date">
                            </div>
                            <div class="field">
                                <label for="search-date-to">Hasta</label>
                                <input id="search-date-to" name="date_to" type="date">
                            </div>
                        </div>
                    </div>
                    <div class="search-modal-actions">
                        <button type="button" class="search-cancel">Cancelar</button>
                        <button type="submit" class="search-submit">Buscar</button>
                    </div>
                </form>
            `;
            const searchResults = document.createElement('div');
            searchResults.className = 'search-results';
            searchResults.innerHTML = `
                <div class="search-results-header">
                    <h4>Resultados</h4>
                    <button type="button" class="search-results-close" title="Cerrar">&times;</button>
                </div>
                <div class="search-results-body"></div>
            `;
            searchModal.appendChild(searchResults);
            const sf = searchModal.querySelector('.search-form');
            if (sf && searchResults) {
                searchModal.insertBefore(searchResults, sf);
            }
            const myRequestsModalBackdrop = document.createElement('div');
            myRequestsModalBackdrop.className = 'my-requests-modal-backdrop';
            const myRequestsModal = document.createElement('div');
            myRequestsModal.className = 'my-requests-modal';
            myRequestsModal.innerHTML = `
                <div class="my-requests-inner">
                    <div class="my-requests-header">
                        <div class="my-requests-title-row">
                            <h3>Mis solicitudes</h3>
                            <button type="button" class="my-requests-close">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </button>
                        </div>
                        <div class="my-requests-tabs-row">
                            <div class="my-requests-tabs">
                                <button type="button" class="my-requests-tab active" data-role="done">Hechas</button>
                                <button type="button" class="my-requests-tab" data-role="received">Recibidas</button>
                            </div>
                            <div class="my-requests-filter-wrapper">
                                <button type="button" class="my-requests-filter" title="Filtros">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12 10 19 14 21 14 12 22 3"></polygon></svg>
                                </button>
                                <div class="my-requests-filter-bar">
                                    <button type="button" class="my-requests-filter-option active" data-status="all">Todas</button>
                                    <button type="button" class="my-requests-filter-option" data-status="pending">Pendientes</button>
                                    <button type="button" class="my-requests-filter-option" data-status="closed">Cerradas</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="my-requests-body">
                        <p class="my-requests-empty">Aquí se mostrarán tus solicitudes.</p>
                    </div>
                </div>
            `;
            
            // Main Button
            const fabMain = document.createElement('button');
            fabMain.className = 'fab-main';
            fabMain.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            `;
            
            // Toggle Menu Logic (Click based for better functionality)
            let isMenuOpen = false;
            const toggleMenu = (e) => {
                e.stopPropagation();
                isMenuOpen = !isMenuOpen;
                if (isMenuOpen) {
                    fabMenu.classList.add('active');
                    fabMain.classList.add('active');
                } else {
                    fabMenu.classList.remove('active');
                    fabMain.classList.remove('active');
                }
            };
            fabMain.addEventListener('click', toggleMenu);

            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (isMenuOpen && !fabMenu.contains(e.target) && !fabMain.contains(e.target)) {
                    isMenuOpen = false;
                    fabMenu.classList.remove('active');
                    fabMain.classList.remove('active');
                }
            });
            
            // --- CHAT BOT BUTTON ---
            const fabChat = document.createElement('button');
            fabChat.className = 'fab-chat';
            fabChat.title = 'Abrir Chat';
            fabChat.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2c-5.5 0-10 3.6-10 8 0 2.3 1.2 4.4 3.2 5.9.1.5-.7 1.9-2.2 3.1 3.3.3 5.5-1.1 6.3-1.8.9.3 1.8.5 2.7.5 5.5 0 10-3.6 10-8s-4.5-8-10-8z"></path>
                    <circle cx="8" cy="9" r="1" fill="currentColor"></circle>
                    <circle cx="16" cy="9" r="1" fill="currentColor"></circle>
                </svg>
            `;
            
            // --- CHAT MODAL ---
            const chatModal = document.createElement('div');
            chatModal.className = 'chat-modal';
            chatModal.innerHTML = `
                <div class="chat-header">
                    <h3>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2c-5.5 0-10 3.6-10 8 0 2.3 1.2 4.4 3.2 5.9.1.5-.7 1.9-2.2 3.1 3.3.3 5.5-1.1 6.3-1.8.9.3 1.8.5 2.7.5 5.5 0 10-3.6 10-8s-4.5-8-10-8z"></path></svg>
                        Asistente Virtual
                    </h3>
                    <button class="chat-close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                <div class="chat-body">
                    <div class="chat-message bot">
                        Hola, ¿en qué puedo ayudarte hoy?
                    </div>
                </div>
                <div class="chat-footer">
                    <input type="text" class="chat-input" placeholder="Escribe un mensaje...">
                    <button class="chat-send">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    </button>
                </div>
            `;
            
            // Toggle Chat Logic
            fabChat.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent closing immediately
                chatModal.classList.toggle('active');
                if (chatModal.classList.contains('active')) {
                    document.body.style.overflow = 'hidden'; // Prevent scroll
                } else {
                    document.body.style.overflow = '';
                }
            });
            
            // Close Chat Logic
            const closeChat = () => {
                chatModal.classList.remove('active');
                document.body.style.overflow = '';
            };

            chatModal.querySelector('.chat-close').addEventListener('click', closeChat);

            // Close chat when clicking outside
            document.addEventListener('click', (e) => {
                if (chatModal.classList.contains('active') && 
                    !chatModal.contains(e.target) && 
                    !fabChat.contains(e.target)) {
                    closeChat();
                }
            });

            // Input Logic (simple echo for demo)
            const sendBtn = chatModal.querySelector('.chat-send');
            const input = chatModal.querySelector('.chat-input');
            const body = chatModal.querySelector('.chat-body');

            const sendMessage = () => {
                const text = input.value.trim();
                if (text) {
                    // User msg
                    const userMsg = document.createElement('div');
                    userMsg.className = 'chat-message user';
                    userMsg.textContent = text;
                    body.appendChild(userMsg);
                    input.value = '';
                    body.scrollTop = body.scrollHeight;

                    // Fake bot response
                    setTimeout(() => {
                        const botMsg = document.createElement('div');
                        botMsg.className = 'chat-message bot';
                        botMsg.textContent = "Recibido: " + text;
                        body.appendChild(botMsg);
                        body.scrollTop = body.scrollHeight;
                    }, 1000);
                }
            };

            sendBtn.addEventListener('click', sendMessage);
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') sendMessage();
            });
            
            // Append flat structure to container
            // Order: Menu (Top), Main Button (Middle), Chat Button (Bottom)
            fabContainer.appendChild(fabMenu);
            fabContainer.appendChild(fabMain);
            fabContainer.appendChild(fabChat);
            
            const openRequestModal = () => {
                requestModalBackdrop.classList.add('active');
                requestModal.classList.add('active');
                document.body.style.overflow = 'hidden';
                if (typeof updateFormEnabled === 'function') updateFormEnabled();
            };
            const closeRequestModal = () => {
                requestModalBackdrop.classList.remove('active');
                requestModal.classList.remove('active');
                document.body.style.overflow = '';
            };
            const openSearchModal = () => {
                searchModalBackdrop.classList.add('active');
                searchModal.classList.add('active');
                document.body.style.overflow = 'hidden';
                const body = searchModal.querySelector('.search-results-body');
                if (body) body.innerHTML = '';
                searchResults.classList.remove('active');
                const resetEl = (sel) => { const el = searchForm.querySelector(sel); if (el) el.value = ''; };
                resetEl('#search-reported-by');
                resetEl('#search-problem-id');
                resetEl('#search-assigned-to');
                resetEl('#search-category');
                resetEl('#search-department');
                resetEl('#search-status');
                resetEl('#search-priority');
                resetEl('#search-keywords');
                resetEl('#search-date-from');
                resetEl('#search-date-to');
                const ordEl = searchForm.querySelector('input[name=\"order_by\"][value=\"id\"]');
                if (ordEl) ordEl.checked = true;
                if (typeof loadSearchDropdowns === 'function') {
                    const p = loadSearchDropdowns();
                    if (p && typeof p.then === 'function') { p.then(() => {}); } else { }
                } else { }
            };
            const closeSearchModal = () => {
                searchModalBackdrop.classList.remove('active');
                searchModal.classList.remove('active');
                document.body.style.overflow = '';
            };
            const openMyRequestsModal = () => {
                myRequestsModalBackdrop.classList.add('active');
                myRequestsModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            };
            const closeMyRequestsModal = () => {
                myRequestsModalBackdrop.classList.remove('active');
                myRequestsModal.classList.remove('active');
                document.body.style.overflow = '';
            };
            const requestBtn = item1.querySelector('.fab-button-mini');
            if (requestBtn) {
                requestBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    openRequestModal();
                });
            }
            const searchBtn = item2.querySelector('.fab-button-mini');
            if (searchBtn) {
                searchBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    openSearchModal();
                });
            }
            const myRequestsBtn = item3.querySelector('.fab-button-mini');
            if (myRequestsBtn) {
                myRequestsBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    openMyRequestsModal();
                });
            }
            requestModalBackdrop.addEventListener('click', closeRequestModal);
            const requestClose = requestModal.querySelector('.request-modal-close');
            if (requestClose) {
                requestClose.addEventListener('click', (e) => {
                    e.preventDefault();
                    closeRequestModal();
                });
            }
            const requestCancel = requestModal.querySelector('.request-cancel');
            if (requestCancel) {
                requestCancel.addEventListener('click', (e) => {
                    e.preventDefault();
                    closeRequestModal();
                });
            }
            searchModalBackdrop.addEventListener('click', closeSearchModal);
            const searchClose = searchModal.querySelector('.search-modal-close');
            if (searchClose) {
                searchClose.addEventListener('click', (e) => {
                    e.preventDefault();
                    closeSearchModal();
                });
            }
            const searchCancel = searchModal.querySelector('.search-cancel');
            if (searchCancel) {
                searchCancel.addEventListener('click', (e) => {
                    e.preventDefault();
                    closeSearchModal();
                });
            }
            const searchCategorySelect = searchModal.querySelector('#search-category');
            const searchDepartmentSelect = searchModal.querySelector('#search-department');
            const searchStatusSelect = searchModal.querySelector('#search-status');
            const searchPrioritySelect = searchModal.querySelector('#search-priority');
            const searchAssignedToSelect = searchModal.querySelector('#search-assigned-to');
            const initSearchableSelect = (selectEl, items, idKey, nameKey) => {
                if (!selectEl || selectEl.dataset.searchableInitialized === '1') return;
                const field = selectEl.closest('.field') || selectEl.parentElement;
                if (!field) return;
                const wrapper = document.createElement('div');
                wrapper.className = 'searchable-select';
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'searchable-input';
                input.placeholder = 'Escribe para buscar...';
                const list = document.createElement('div');
                list.className = 'searchable-list';
                wrapper.appendChild(input);
                wrapper.appendChild(list);
                field.insertBefore(wrapper, selectEl);
                try { selectEl.style.display = 'none'; } catch {}
                const render = (q) => {
                    const query = (q || '').toLowerCase();
                    const arr = Array.isArray(items) ? items : [];
                    const filtered = arr.filter((it) => {
                        const nm = String(it[nameKey] ?? '').toLowerCase();
                        return query === '' ? true : nm.includes(query);
                    }).slice(0, 50);
                    const anyItem = { [idKey]: '', [nameKey]: 'Cualquiera' };
                    const final = query === '' ? [anyItem].concat(filtered) : filtered;
                    list.innerHTML = final.map((it) => {
                        const id = String(it[idKey] ?? '');
                        const nm = String(it[nameKey] ?? '');
                        return `<div class="searchable-item" data-id="${id}">${nm}</div>`;
                    }).join('');
                };
                input.addEventListener('focus', () => {
                    render(input.value);
                    list.style.display = 'block';
                });
                input.addEventListener('input', () => {
                    render(input.value);
                    list.style.display = 'block';
                });
                document.addEventListener('click', (e) => {
                    if (!wrapper.contains(e.target)) {
                        list.style.display = 'none';
                    }
                });
                list.addEventListener('click', (e) => {
                    const item = e.target.closest('.searchable-item');
                    if (!item) return;
                    const id = item.getAttribute('data-id') || '';
                    selectEl.value = id;
                    const nm = item.textContent || '';
                    input.value = id ? nm : '';
                    list.style.display = 'none';
                });
                const applyCurrent = () => {
                    const cur = selectEl.value;
                    if (!cur) { input.value = ''; return; }
                    const hit = (Array.isArray(items) ? items : []).find((it) => String(it[idKey] ?? '') === String(cur));
                    input.value = hit ? String(hit[nameKey] ?? '') : '';
                };
                applyCurrent();
                selectEl.addEventListener('change', applyCurrent);
                selectEl.dataset.searchableInitialized = '1';
            };
            const initSearchableSelectRemoteUsers = (selectEl) => {
                if (!selectEl || selectEl.dataset.searchableInitialized === '1') return;
                const field = selectEl.closest('.field') || selectEl.parentElement;
                if (!field) return;
                const wrapper = document.createElement('div');
                wrapper.className = 'searchable-select';
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'searchable-input';
                input.placeholder = 'Escribe para buscar usuarios...';
                const list = document.createElement('div');
                list.className = 'searchable-list';
                wrapper.appendChild(input);
                wrapper.appendChild(list);
                field.insertBefore(wrapper, selectEl);
                try { selectEl.style.display = 'none'; } catch {}
                let ac = null;
                let timer = null;
                const base = (window.AppConfig && window.AppConfig.baseUrl) ? window.AppConfig.baseUrl.replace(/\/+$/, '') : '';
                const renderItems = (items) => {
                    const anyItem = { id_usuario: '', nombre_usuario: 'Cualquiera' };
                    const final = (input.value.trim() === '' ? [anyItem] : []).concat((Array.isArray(items) ? items : []).slice(0, 50));
                    list.innerHTML = final.map((it) => {
                        const id = String(it.id_usuario ?? '');
                        const nm = String(it.nombre_usuario ?? '');
                        return `<div class="searchable-item" data-id="${id}">${nm}</div>`;
                    }).join('');
                };
                const fetchUsers = async (q) => {
                    if (ac) try { ac.abort(); } catch {}
                    ac = typeof AbortController !== 'undefined' ? new AbortController() : null;
                    const url = (base ? base : '') + '/api/catalog/users' + (q ? ('?q=' + encodeURIComponent(q)) : '');
                    try {
                        const res = await fetch(url, ac ? { signal: ac.signal } : {});
                        const data = res.ok ? await res.json() : [];
                        renderItems(data);
                        list.style.display = 'block';
                    } catch (_) {}
                };
                input.addEventListener('focus', () => {
                    fetchUsers('');
                });
                input.addEventListener('input', () => {
                    if (timer) clearTimeout(timer);
                    const q = input.value.trim();
                    timer = setTimeout(() => fetchUsers(q), 250);
                });
                document.addEventListener('click', (e) => {
                    if (!wrapper.contains(e.target)) {
                        list.style.display = 'none';
                    }
                });
                list.addEventListener('click', (e) => {
                    const item = e.target.closest('.searchable-item');
                    if (!item) return;
                    const id = item.getAttribute('data-id') || '';
                    selectEl.value = id;
                    const nm = item.textContent || '';
                    input.value = id ? nm : '';
                    list.style.display = 'none';
                });
                selectEl.dataset.searchableInitialized = '1';
            };
            const loadSearchDropdowns = async () => {
                const base = (window.AppConfig && window.AppConfig.baseUrl) ? window.AppConfig.baseUrl.replace(/\/+$/, '') : '';
                const setOptions = (el, items, idKey, nameKey, anyLabel) => {
                    if (!el) return;
                    const prev = el.value;
                    const opts = [`<option value=\"\">${anyLabel}</option>`].concat(
                        (Array.isArray(items) ? items : []).map((it) => {
                            const id = String(it[idKey] ?? '');
                            const name = String(it[nameKey] ?? '');
                            return `<option value=\"${id}\">${name}</option>`;
                        })
                    );
                    el.innerHTML = opts.join('');
                    if (prev && Array.isArray(items) && items.some((it) => String(it[idKey] ?? '') === prev)) {
                        el.value = prev;
                    }
                };
                const prime = (el) => { if (el) el.innerHTML = '<option value=\"\">Cualquiera</option>'; };
                prime(searchCategorySelect);
                prime(searchDepartmentSelect);
                prime(searchStatusSelect);
                prime(searchPrioritySelect);
                prime(searchAssignedToSelect);
                const cacheKey = 'HelpdeskCatalogsV1';
                const ttl = 5 * 60 * 1000;
                const now = Date.now();
                let cached = null;
                try { const raw = localStorage.getItem(cacheKey); cached = raw ? JSON.parse(raw) : null; } catch {}
                const fresh = cached && cached.ts && (now - cached.ts) < ttl;
                if (fresh) {
                    setOptions(searchCategorySelect, cached.cats || [], 'id_categoria', 'nombre_categoria', 'Cualquiera');
                    setOptions(searchDepartmentSelect, cached.deps || [], 'id_departamento', 'nombre_departamento', 'Cualquiera');
                    setOptions(searchStatusSelect, cached.sts || [], 'id_estado', 'nombre_estado', 'Cualquiera');
                    setOptions(searchPrioritySelect, cached.pri || [], 'id_prioridad', 'nombre_prioridad', 'Cualquiera');
                    setOptions(searchAssignedToSelect, cached.usr || [], 'id_usuario', 'nombre_usuario', 'Cualquiera');
                    initSearchableSelect(searchCategorySelect, cached.cats || [], 'id_categoria', 'nombre_categoria');
                    initSearchableSelect(searchAssignedToSelect, cached.usr || [], 'id_usuario', 'nombre_usuario');
                    initSearchableSelect(searchDepartmentSelect, cached.deps || [], 'id_departamento', 'nombre_departamento');
                }
                const fetchAndUpdate = async () => {
                    const ac = typeof AbortController !== 'undefined' ? new AbortController() : null;
                    const t = setTimeout(() => { if (ac) ac.abort(); }, 10000);
                    try {
                        const [catsRes, depsRes, stsRes, priRes, usrRes] = await Promise.all([
                            fetch((base ? base : '') + '/api/catalog/categories', ac ? { signal: ac.signal } : {}),
                            fetch((base ? base : '') + '/api/catalog/departments', ac ? { signal: ac.signal } : {}),
                            fetch((base ? base : '') + '/api/catalog/statuses', ac ? { signal: ac.signal } : {}),
                            fetch((base ? base : '') + '/api/catalog/priorities', ac ? { signal: ac.signal } : {}),
                            fetch((base ? base : '') + '/api/catalog/users', ac ? { signal: ac.signal } : {}),
                        ]);
                        const cats = catsRes.ok ? await catsRes.json() : [];
                        const deps = depsRes.ok ? await depsRes.json() : [];
                        const sts = stsRes.ok ? await stsRes.json() : [];
                        const pri = priRes.ok ? await priRes.json() : [];
                        const usr = usrRes.ok ? await usrRes.json() : [];
                        setOptions(searchCategorySelect, cats, 'id_categoria', 'nombre_categoria', 'Cualquiera');
                        setOptions(searchDepartmentSelect, deps, 'id_departamento', 'nombre_departamento', 'Cualquiera');
                        setOptions(searchStatusSelect, sts, 'id_estado', 'nombre_estado', 'Cualquiera');
                        setOptions(searchPrioritySelect, pri, 'id_prioridad', 'nombre_prioridad', 'Cualquiera');
                        setOptions(searchAssignedToSelect, usr, 'id_usuario', 'nombre_usuario', 'Cualquiera');
                        initSearchableSelect(searchCategorySelect, cats, 'id_categoria', 'nombre_categoria');
                        initSearchableSelect(searchAssignedToSelect, usr, 'id_usuario', 'nombre_usuario');
                        initSearchableSelect(searchDepartmentSelect, deps, 'id_departamento', 'nombre_departamento');
                        try { localStorage.setItem(cacheKey, JSON.stringify({ ts: Date.now(), cats, deps, sts, pri, usr })); } catch {}
                    } catch (_) {} finally { clearTimeout(t); }
                };
                if (!fresh) {
                    await fetchAndUpdate();
                } else {
                    fetchAndUpdate();
                }
            };
            const restoreSearchFormState = () => {
                const raw = localStorage.getItem('HelpdeskSearchState');
                if (!raw) return;
                try {
                    const st = JSON.parse(raw);
                    const form = st && st.form ? st.form : null;
                    const results = st && Array.isArray(st.results) ? st.results : null;
                    if (form) {
                        const setVal = (sel, v) => {
                            const el = searchForm.querySelector(sel);
                            if (!el) return;
                            const val = v ?? '';
                            if (el.tagName && el.tagName.toLowerCase() === 'select') {
                                const ok = Array.from(el.options || []).some((o) => o.value === val);
                                el.value = ok ? val : '';
                            } else {
                                el.value = val;
                            }
                        };
                        setVal('#search-reported-by', form.reported_by);
                        setVal('#search-problem-id', form.problem_id);
                        setVal('#search-assigned-to', form.assigned_to);
                        setVal('#search-category', form.category_id);
                        setVal('#search-department', form.department_id);
                        setVal('#search-status', form.status_id);
                        setVal('#search-priority', form.priority_id);
                        setVal('#search-keywords', form.keywords);
                        setVal('#search-date-from', form.date_from);
                        setVal('#search-date-to', form.date_to);
                        const ordEl = searchForm.querySelector(`input[name=\"order_by\"][value=\"${form.order_by || 'id'}\"]`);
                        if (ordEl) ordEl.checked = true;
                    }
                    if (results) {
                        const body = searchModal.querySelector('.search-results-body');
                        const fmtDate = (ds) => {
                            const d = new Date(ds);
                            if (isNaN(d.getTime())) return String(ds || '');
                            const dd = d.getDate();
                            const mm = d.getMonth() + 1;
                            const yyyy = d.getFullYear();
                            return `${dd}/${mm}/${yyyy}`;
                        };
                        const rowsHtml = results.map((row) => {
                            const id = String(row.id ?? '');
                            const title = String(row.descripcion ?? '').trim();
                            const userName = String(row.usuario ?? '');
                            const assignedTo = String(row.area ?? '');
                            const dateSubmitted = fmtDate(String(row.fecha_creacion ?? ''));
                            const status = String(row.estado ?? '');
                            return `<tr>
                                <td>${id}</td>
                                <td>${title}</td>
                                <td>${userName}</td>
                                <td>${assignedTo}</td>
                                <td>${dateSubmitted}</td>
                                <td>${status}</td>
                                <td><button type=\"button\" class=\"result-view-btn\" data-id=\"${id}\">Ver</button></td>
                            </tr>`;
                        }).join('');
                        if (body) {
                            body.innerHTML = `
                                <table class=\"search-results-table\">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Título</th>
                                            <th>Usuario</th>
                                            <th>Asignado a</th>
                                            <th>Fecha</th>
                                            <th>Estado</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>${rowsHtml}</tbody>
                                </table>
                            `;
                        }
                        searchResults.classList.add('active');
                    }
                } catch (_) {}
            };
            myRequestsModalBackdrop.addEventListener('click', closeMyRequestsModal);
            const myRequestsClose = myRequestsModal.querySelector('.my-requests-close');
            if (myRequestsClose) {
                myRequestsClose.addEventListener('click', (e) => {
                    e.preventDefault();
                    closeMyRequestsModal();
                });
            }
            const requestForm = requestModal.querySelector('.request-form');
            const processSelect = requestModal.querySelector('#request-process');
            const categorySelect = requestModal.querySelector('#request-category');
            const descriptionInput = requestModal.querySelector('#request-description');
            const filesInput = requestModal.querySelector('#request-files');
            const requestSubmit = requestModal.querySelector('.request-submit');
            const fileDropzoneRoot = requestModal.querySelector('.file-dropzone');
            const requestFormBody = requestModal.querySelector('.request-form-body');
            const processRequiredMsg = requestModal.querySelector('.process-required-msg');
            let requestSubmitLock = false;
            let requestSubmitLastTs = 0;
            const populateProcesses = async () => {
                try {
                    const base = (window.AppConfig && window.AppConfig.baseUrl) ? window.AppConfig.baseUrl.replace(/\/+$/, '') : '';
                    const url = (base ? base : '') + '/api/processes';
                    const res = await fetch(url, { method: 'GET' });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (!Array.isArray(data)) return;
                    if (processSelect) {
                        processSelect.innerHTML = '<option value=\"\">Selecciona un proceso</option>';
                        data.forEach((p) => {
                            const opt = document.createElement('option');
                            opt.value = String(p.id_proceso ?? p.id ?? '');
                            opt.textContent = String(p.nombre_proceso ?? p.nombre ?? '');
                            processSelect.appendChild(opt);
                        });
                    }
                } catch (err) {}
            };
            if (processSelect) {
                populateProcesses();
            }
            const loadCategories = async (procId) => {
                try {
                    const base = (window.AppConfig && window.AppConfig.baseUrl) ? window.AppConfig.baseUrl.replace(/\/+$/, '') : '';
                    const url = (base ? base : '') + '/api/processes/' + encodeURIComponent(procId) + '/categories';
                    const res = await fetch(url, { method: 'GET' });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (!Array.isArray(data)) return;
                    if (categorySelect) {
                        categorySelect.innerHTML = '<option value=\"\">Selecciona una categoría</option>';
                        data.forEach((c) => {
                            const opt = document.createElement('option');
                            opt.value = String(c.id_categoria ?? '');
                            opt.textContent = String(c.nombre_categoria ?? '');
                            categorySelect.appendChild(opt);
                        });
                        if (data.length === 0) {
                            const seedUrl = (base ? base : '') + '/api/processes/' + encodeURIComponent(procId) + '/categories/seed-default';
                            const seedRes = await fetch(seedUrl, { method: 'POST' });
                            if (seedRes.ok) {
                                const res2 = await fetch(url, { method: 'GET' });
                                if (res2.ok) {
                                    const data2 = await res2.json();
                                    categorySelect.innerHTML = '<option value=\"\">Selecciona una categoría</option>';
                                    (Array.isArray(data2) ? data2 : []).forEach((c2) => {
                                        const opt2 = document.createElement('option');
                                        opt2.value = String(c2.id_categoria ?? '');
                                        opt2.textContent = String(c2.nombre_categoria ?? '');
                                        categorySelect.appendChild(opt2);
                                    });
                                }
                            }
                        }
                    }
                } catch (err) {}
            };
            const updateFormEnabled = () => {
                const enabled = !!(processSelect && processSelect.value);
                if (categorySelect) categorySelect.disabled = !enabled;
                if (descriptionInput) descriptionInput.disabled = !enabled;
                if (filesInput) filesInput.disabled = !enabled;
                if (requestSubmit) requestSubmit.disabled = !enabled;
                if (fileDropzoneRoot) {
                    if (!enabled) fileDropzoneRoot.classList.add('disabled');
                    else fileDropzoneRoot.classList.remove('disabled');
                }
                if (requestFormBody) {
                    if (!enabled) requestFormBody.classList.add('disabled');
                    else requestFormBody.classList.remove('disabled');
                }
                if (processRequiredMsg) {
                    processRequiredMsg.style.display = enabled ? 'none' : 'block';
                }
            };
            if (processSelect) {
                updateFormEnabled();
                processSelect.addEventListener('change', () => {
                    const v = processSelect.value;
                    if (!v) {
                        if (categorySelect) {
                            categorySelect.innerHTML = '<option value=\"\">Selecciona una categoría</option>';
                        }
                        updateFormEnabled();
                        return;
                    }
                    updateFormEnabled();
                    loadCategories(v);
                });
            }
            const resetRequestForm = () => {
                if (processSelect) processSelect.value = '';
                if (categorySelect) categorySelect.innerHTML = '<option value="">Selecciona una categoría</option>';
                if (descriptionInput) descriptionInput.value = '';
                if (filesInput) {
                    filesInput.__selectedFiles = [];
                    try { filesInput.value = ''; } catch {}
                    if (typeof DataTransfer !== 'undefined') {
                        try {
                            const dt = new DataTransfer();
                            filesInput.files = dt.files;
                        } catch {}
                    }
                    const dz = requestModal.querySelector('.file-dropzone');
                    if (dz) {
                        const text = dz.querySelector('.file-dropzone-text');
                        const icon = dz.querySelector('.file-dropzone-icon');
                        const filesContainer = dz.querySelector('.file-dropzone-files');
                        if (text) text.textContent = 'Haz clic o arrastra archivos (máx. 10)';
                        if (icon) icon.style.display = 'flex';
                        if (filesContainer) filesContainer.innerHTML = '';
                        dz.classList.remove('has-files');
                    }
                }
                updateFormEnabled();
            };
            if (requestForm && categorySelect && descriptionInput) {
                requestForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const nowTs = Date.now();
                    if (requestSubmitLock || (nowTs - requestSubmitLastTs) < 1000) return;
                    requestSubmitLock = true;
                    requestSubmitLastTs = nowTs;
                    if (requestSubmit) requestSubmit.disabled = true;
                    if (!processSelect || !processSelect.value) {
                        requestSubmitLock = false;
                        if (requestSubmit) requestSubmit.disabled = false;
                        alert('Selecciona un proceso.');
                        return;
                    }
                    const cat = categorySelect.value;
                    const desc = descriptionInput.value.trim();
                    if (!cat) {
                        requestSubmitLock = false;
                        if (requestSubmit) requestSubmit.disabled = false;
                        alert('Selecciona una categoría.');
                        return;
                    }
                    if (!desc) {
                        requestSubmitLock = false;
                        if (requestSubmit) requestSubmit.disabled = false;
                        alert('Escribe una descripción.');
                        return;
                    }
                    const base = (window.AppConfig && window.AppConfig.baseUrl) ? window.AppConfig.baseUrl.replace(/\/+$/, '') : '';
                    const url = (base ? base : '') + '/api/tickets';
                    const fd = new FormData();
                    fd.append('category_id', cat);
                    fd.append('description', desc);
                    const filesForSend = (filesInput && filesInput.__selectedFiles && filesInput.__selectedFiles.length)
                        ? filesInput.__selectedFiles
                        : (filesInput && filesInput.files ? Array.from(filesInput.files) : []);
                    if (filesForSend && filesForSend.length) {
                        filesForSend.forEach((f) => fd.append('files[]', f));
                    }
                try {
                        const res = await fetch(url, { method: 'POST', body: fd });
                        if (!res.ok) {
                            if (window.Alertas && typeof window.Alertas.showError === 'function') {
                                window.Alertas.showError('No se pudo crear el ticket.');
                            } else {
                                alert('No se pudo crear el ticket.');
                            }
                            requestSubmitLock = false;
                            if (requestSubmit) requestSubmit.disabled = false;
                            return;
                        }
                        await res.json();
                        if (window.Alertas && typeof window.Alertas.showSuccess === 'function') {
                            window.Alertas.showSuccess('El ticket se generó correctamente.');
                        } else {
                            alert('El ticket se generó correctamente.');
                        }
                        resetRequestForm();
                        closeRequestModal();
                    } catch (_) {}
                    finally {
                        setTimeout(() => {
                            requestSubmitLock = false;
                            if (requestSubmit) requestSubmit.disabled = false;
                        }, 1000);
                    }
                });
            }
            const searchForm = searchModal.querySelector('.search-form');
            if (searchForm) {
                searchForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const base = (window.AppConfig && window.AppConfig.baseUrl) ? window.AppConfig.baseUrl.replace(/\/+$/, '') : '';
                    const url = (base ? base : '') + '/api/tickets/search';
                    const rp = (v) => (v === undefined || v === null) ? '' : String(v).trim();
                    const reportedBy = rp(searchForm.querySelector('#search-reported-by')?.value);
                    const problemId = rp(searchForm.querySelector('#search-problem-id')?.value);
                    const assignedTo = rp(searchForm.querySelector('#search-assigned-to')?.value);
                    const categoryId = rp(searchForm.querySelector('#search-category')?.value);
                    const departmentId = rp(searchForm.querySelector('#search-department')?.value);
                    const statusId = rp(searchForm.querySelector('#search-status')?.value);
                    const priorityId = rp(searchForm.querySelector('#search-priority')?.value);
                    const keywords = rp(searchForm.querySelector('#search-keywords')?.value);
                    const dateFrom = rp(searchForm.querySelector('#search-date-from')?.value);
                    const dateTo = rp(searchForm.querySelector('#search-date-to')?.value);
                    const orderBy = rp(searchForm.querySelector('input[name="order_by"]:checked')?.value || 'id');
                    const params = new URLSearchParams();
                    if (reportedBy) params.append('reported_by', reportedBy);
                    if (problemId) params.append('problem_id', problemId);
                    if (assignedTo) params.append('assigned_to', assignedTo);
                    if (categoryId) params.append('category_id', categoryId);
                    if (departmentId) params.append('department_id', departmentId);
                    if (statusId) params.append('status_id', statusId);
                    if (priorityId) params.append('priority_id', priorityId);
                    if (keywords) params.append('keywords', keywords);
                    if (dateFrom) params.append('date_from', dateFrom);
                    if (dateTo) params.append('date_to', dateTo);
                    if (orderBy) params.append('order_by', orderBy);
                    try {
                        const res = await fetch(url + '?' + params.toString(), { method: 'GET' });
                        const body = searchModal.querySelector('.search-results-body');
                        if (!res.ok) {
                            if (body) {
                                body.innerHTML = '<p class="search-results-empty">No se pudo buscar.</p>';
                            }
                            return;
                        }
                        const data = await res.json();
                        if (body) {
                            const fmtDate = (ds) => {
                                const d = new Date(ds);
                                if (isNaN(d.getTime())) return String(ds || '');
                                const dd = d.getDate();
                                const mm = d.getMonth() + 1;
                                const yyyy = d.getFullYear();
                                return `${dd}/${mm}/${yyyy}`;
                            };
                            if (!Array.isArray(data) || data.length === 0) {
                                body.innerHTML = '<p class="search-results-empty">Sin resultados.</p>';
                            } else {
                                const rowsHtml = data.map((row) => {
                                    const id = String(row.id ?? '');
                                    const title = String(row.descripcion ?? '').trim();
                                    const userName = String(row.usuario ?? '');
                                    const assignedTo = String(row.area ?? '');
                                    const dateSubmitted = fmtDate(String(row.fecha_creacion ?? ''));
                                    const status = String(row.estado ?? '');
                                    return `<tr>
                                        <td>${id}</td>
                                        <td>${title}</td>
                                        <td>${userName}</td>
                                        <td>${assignedTo}</td>
                                        <td>${dateSubmitted}</td>
                                        <td>${status}</td>
                                        <td><button type="button" class="result-view-btn" data-id="${id}">Ver</button></td>
                                    </tr>`;
                                }).join('');
                                body.innerHTML = `
                                    <table class="search-results-table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Título</th>
                                                <th>Usuario</th>
                                                <th>Asignado a</th>
                                                <th>Fecha</th>
                                                <th>Estado</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${rowsHtml}
                                        </tbody>
                                    </table>
                                `;
                            }
                        }
                        searchResults.classList.add('active');
                    } catch (err) {
                        const body = searchModal.querySelector('.search-results-body');
                        if (body) {
                            body.innerHTML = '<p class="search-results-empty">Ocurrió un error al buscar.</p>';
                        }
                        searchResults.classList.add('active');
                    }
                });
            }
            const searchResultsClose = searchModal.querySelector('.search-results-close');
            if (searchResultsClose) {
                searchResultsClose.addEventListener('click', () => {
                    searchResults.classList.remove('active');
                });
            }
            const ticketDetailsBackdrop = document.createElement('div');
            ticketDetailsBackdrop.className = 'ticket-details-backdrop';
            const ticketDetailsModal = document.createElement('div');
            ticketDetailsModal.className = 'ticket-details-modal';
            ticketDetailsModal.innerHTML = `
                <div class="ticket-details-card">
                    <div class="ticket-details-header">
                        <h3>Detalle de solicitud</h3>
                        <button type="button" class="ticket-details-close">&times;</button>
                    </div>
                    <div class="ticket-details-body"></div>
                </div>
            `;
            document.body.appendChild(ticketDetailsBackdrop);
            document.body.appendChild(ticketDetailsModal);
            const openTicketDetails = () => {
                ticketDetailsBackdrop.classList.add('active');
                ticketDetailsModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            };
            const closeTicketDetails = () => {
                ticketDetailsBackdrop.classList.remove('active');
                ticketDetailsModal.classList.remove('active');
                document.body.style.overflow = '';
            };
            ticketDetailsBackdrop.addEventListener('click', closeTicketDetails);
            const ticketDetailsClose = ticketDetailsModal.querySelector('.ticket-details-close');
            if (ticketDetailsClose) {
                ticketDetailsClose.addEventListener('click', (e) => {
                    e.preventDefault();
                    closeTicketDetails();
                });
            }
            const onViewDetails = async (id) => {
                const base = (window.AppConfig && window.AppConfig.baseUrl) ? window.AppConfig.baseUrl.replace(/\/+$/, '') : '';
                const url = (base ? base : '') + '/api/tickets/' + encodeURIComponent(id);
                try {
                    const res = await fetch(url, { method: 'GET' });
                    if (!res.ok) return;
                    const row = await res.json();
                    const body = ticketDetailsModal.querySelector('.ticket-details-body');
                    const fmtDate = (ds) => {
                        const d = new Date(ds);
                        if (isNaN(d.getTime())) return String(ds || '');
                        const dd = d.getDate();
                        const mm = d.getMonth() + 1;
                        const yyyy = d.getFullYear();
                        return `${dd}/${mm}/${yyyy}`;
                    };
                    if (body) {
                        body.innerHTML = `
                            <div class="ticket-detail-row"><div class="ticket-detail-label">ID</div><div class="ticket-detail-value">#${String(row.id ?? '')}</div></div>
                            <div class="ticket-detail-row"><div class="ticket-detail-label">Título</div><div class="ticket-detail-value">${String(row.descripcion ?? '')}</div></div>
                            <div class="ticket-detail-row"><div class="ticket-detail-label">Usuario</div><div class="ticket-detail-value">${String(row.usuario ?? '')}</div></div>
                            <div class="ticket-detail-row"><div class="ticket-detail-label">Correo</div><div class="ticket-detail-value">${String(row.correo ?? '')}</div></div>
                            <div class="ticket-detail-row"><div class="ticket-detail-label">Área</div><div class="ticket-detail-value">${String(row.area ?? '')}</div></div>
                            <div class="ticket-detail-row"><div class="ticket-detail-label">Departamento</div><div class="ticket-detail-value">${String(row.departamento ?? '')}</div></div>
                            <div class="ticket-detail-row"><div class="ticket-detail-label">Categoría</div><div class="ticket-detail-value">${String(row.categoria ?? '')}</div></div>
                            <div class="ticket-detail-row"><div class="ticket-detail-label">Prioridad</div><div class="ticket-detail-value">${String(row.prioridad ?? '')}</div></div>
                            <div class="ticket-detail-row"><div class="ticket-detail-label">Estado</div><div class="ticket-detail-value">${String(row.estado ?? '')}</div></div>
                            <div class="ticket-detail-row"><div class="ticket-detail-label">Fecha</div><div class="ticket-detail-value">${fmtDate(String(row.fecha_creacion ?? ''))}</div></div>
                            <div class="ticket-attachments">
                                <div class="ticket-attachments-title">Adjuntos</div>
                                <div class="ticket-attachments-list">
                                    ${(Array.isArray(row.attachments) ? row.attachments : []).map((a) => {
                                        const name = String(a.name ?? '');
                                        const type = String(a.type ?? '');
                                        const url = String(a.url ?? '');
                                        const icon = type.startsWith('image/') ? '🖼' : (type.includes('pdf') ? '📄' : '📎');
                                        return `<div class="ticket-attachment-item">
                                            <div class="ticket-attachment-icon">${icon}</div>
                                            <div class="ticket-attachment-name" title="${name}">${name}</div>
                                            <div class="ticket-attachment-actions"><a href="${url}" target="_blank" rel="noopener">Descargar</a></div>
                                        </div>`;
                                    }).join('')}
                                </div>
                            </div>
                        `;
                    }
                    openTicketDetails();
                } catch (_) {}
            };
            searchModal.addEventListener('click', (e) => {
                const t = e.target;
                if (t && t.classList && t.classList.contains('result-view-btn')) {
                    const id = t.getAttribute('data-id');
                    if (id) {
                        onViewDetails(id);
                    }
                }
            });
            const myRequestsTabs = myRequestsModal.querySelectorAll('.my-requests-tab');
            if (myRequestsTabs.length) {
                myRequestsTabs.forEach((tab) => {
                    tab.addEventListener('click', () => {
                        myRequestsTabs.forEach((t) => t.classList.remove('active'));
                        tab.classList.add('active');
                    });
                });
            }
            const myRequestsFilterToggle = myRequestsModal.querySelector('.my-requests-filter');
            const myRequestsFilterBar = myRequestsModal.querySelector('.my-requests-filter-bar');
            const myRequestsFilterOptions = myRequestsModal.querySelectorAll('.my-requests-filter-option');
            if (myRequestsFilterToggle && myRequestsFilterBar) {
                myRequestsFilterToggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    myRequestsFilterBar.classList.toggle('active');
                });
            }
            if (myRequestsFilterOptions.length) {
                myRequestsFilterOptions.forEach((opt) => {
                    opt.addEventListener('click', () => {
                        myRequestsFilterOptions.forEach((o) => o.classList.remove('active'));
                        opt.classList.add('active');
                    });
                });
            }
            const fileInput = requestModal.querySelector('#request-files');
            const fileDropzone = requestModal.querySelector('.file-dropzone');
            if (fileDropzone && fileInput) {
                const dropzoneText = fileDropzone.querySelector('.file-dropzone-text');
                const dropzoneIcon = fileDropzone.querySelector('.file-dropzone-icon');
                const filesContainer = fileDropzone.querySelector('.file-dropzone-files');
                let selectedFiles = fileInput.files ? Array.from(fileInput.files) : [];
                const syncInputFiles = () => {
                    if (typeof DataTransfer === 'undefined') return;
                    const dataTransfer = new DataTransfer();
                    selectedFiles.forEach((file) => dataTransfer.items.add(file));
                    fileInput.files = dataTransfer.files;
                };
                const openImagePreview = (url, name) => {
                    const overlay = document.createElement('div');
                    overlay.className = 'image-lightbox';
                    overlay.innerHTML = `
                        <div class="image-lightbox-inner">
                            <button type="button" class="image-lightbox-close">&times;</button>
                            <img src="${url}" alt="${name}">
                            <div class="image-lightbox-caption">${name}</div>
                        </div>
                    `;
                    const close = () => {
                        if (overlay.parentNode) {
                            overlay.parentNode.removeChild(overlay);
                        }
                        URL.revokeObjectURL(url);
                    };
                    overlay.addEventListener('click', (e) => {
                        if (e.target === overlay) {
                            close();
                        }
                    });
                    const closeBtn = overlay.querySelector('.image-lightbox-close');
                    if (closeBtn) {
                        closeBtn.addEventListener('click', (e) => {
                            e.preventDefault();
                            close();
                        });
                    }
                    document.body.appendChild(overlay);
                };
                const updateDropzoneUI = () => {
                    fileInput.__selectedFiles = selectedFiles;
                    if (!selectedFiles.length) {
                        if (dropzoneText) {
                            dropzoneText.textContent = 'Haz clic o arrastra archivos (máx. 10)';
                        }
                        if (dropzoneIcon) {
                            dropzoneIcon.style.display = 'flex';
                        }
                        if (filesContainer) {
                            filesContainer.innerHTML = '';
                        }
                        fileDropzone.classList.remove('has-files');
                        return;
                    }
                    fileDropzone.classList.add('has-files');
                    if (dropzoneIcon) {
                        dropzoneIcon.style.display = 'none';
                    }
                    if (dropzoneText) {
                        if (selectedFiles.length === 1) {
                            dropzoneText.textContent = '1 archivo seleccionado';
                        } else {
                            dropzoneText.textContent = selectedFiles.length + ' archivos seleccionados';
                        }
                    }
                    if (filesContainer) {
                        filesContainer.innerHTML = '';
                        selectedFiles.forEach((file, index) => {
                            const isImage = file.type && file.type.startsWith('image/');
                            const chip = document.createElement('div');
                            chip.className = 'file-chip';
                            if (isImage) {
                                const thumb = document.createElement('div');
                                thumb.className = 'file-image-thumb';
                                const img = document.createElement('img');
                                img.src = URL.createObjectURL(file);
                                img.alt = file.name;
                                thumb.appendChild(img);
                                thumb.addEventListener('click', (e) => {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    const previewUrl = URL.createObjectURL(file);
                                    openImagePreview(previewUrl, file.name);
                                });
                                chip.appendChild(thumb);
                            }
                            const label = document.createElement('span');
                            label.className = 'file-chip-label';
                            label.textContent = file.name;
                            chip.appendChild(label);
                            const removeBtn = document.createElement('button');
                            removeBtn.type = 'button';
                            removeBtn.className = 'file-chip-remove';
                            removeBtn.textContent = '×';
                            removeBtn.addEventListener('click', (e) => {
                                e.preventDefault();
                                e.stopPropagation();
                                selectedFiles = selectedFiles.filter((_, i) => i !== index);
                                syncInputFiles();
                                updateDropzoneUI();
                            });
                            chip.appendChild(removeBtn);
                            filesContainer.appendChild(chip);
                        });
                    }
                };
                const openFilePicker = () => {
                    if (fileInput.disabled) return;
                    fileInput.click();
                };
                fileDropzone.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (fileInput.disabled) return;
                    openFilePicker();
                });
                fileDropzone.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        if (fileInput.disabled) return;
                        openFilePicker();
                    }
                });
                ['dragenter', 'dragover'].forEach((eventName) => {
                    fileDropzone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        if (fileInput.disabled) return;
                        fileDropzone.classList.add('drag-over');
                    });
                });
                ['dragleave', 'drop'].forEach((eventName) => {
                    fileDropzone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        if (fileInput.disabled) return;
                        fileDropzone.classList.remove('drag-over');
                    });
                });
                fileDropzone.addEventListener('drop', (e) => {
                    if (fileInput.disabled) return;
                    const droppedFiles = e.dataTransfer && e.dataTransfer.files ? Array.from(e.dataTransfer.files) : [];
                    if (!droppedFiles.length) return;
                    if (selectedFiles.length + droppedFiles.length > 10) {
                        alert('Solo puedes adjuntar hasta 10 archivos.');
                        return;
                    }
                    selectedFiles = selectedFiles.concat(droppedFiles);
                    syncInputFiles();
                    updateDropzoneUI();
                });
                fileInput.addEventListener('change', () => {
                    if (fileInput.disabled) return;
                    const incoming = fileInput.files ? Array.from(fileInput.files) : [];
                    if (!incoming.length) return;
                    if (selectedFiles.length + incoming.length > 10) {
                        alert('Solo puedes adjuntar hasta 10 archivos.');
                        if (typeof DataTransfer !== 'undefined') {
                            fileInput.value = '';
                        }
                        return;
                    }
                    selectedFiles = selectedFiles.concat(incoming);
                    if (typeof DataTransfer !== 'undefined') {
                        fileInput.value = '';
                        syncInputFiles();
                    }
                    updateDropzoneUI();
                });
                updateDropzoneUI();
            }
            document.body.appendChild(fabContainer);
            document.body.appendChild(chatModal);
            document.body.appendChild(requestModalBackdrop);
            document.body.appendChild(requestModal);
            document.body.appendChild(searchModalBackdrop);
            document.body.appendChild(searchModal);
            document.body.appendChild(myRequestsModalBackdrop);
            document.body.appendChild(myRequestsModal);
        }

        // ============================================
        // 5. EVENT LISTENERS
        // ============================================
        const navItems = sidebar.querySelectorAll('.nav-item');
        navItems.forEach((item) => {
            item.addEventListener('click', (e) => {
                const href = item.getAttribute('href');
                if (!href || href === '#') {
                    e.preventDefault();
                }
                navItems.forEach((i) => i.classList.remove('active'));
                item.classList.add('active');
            });
        });
    };

    initLayout();
});

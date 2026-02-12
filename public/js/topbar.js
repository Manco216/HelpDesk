document.addEventListener('DOMContentLoaded', () => {
    const content = document.querySelector('.content');
    if (!content) return;

    let topBar = content.querySelector('.top-bar');
    if (!topBar) {
        topBar = document.createElement('header');
        topBar.className = 'top-bar';

        const welcome = document.createElement('div');
        welcome.className = 'welcome-message';
        const nm = (window.AppConfig && window.AppConfig.userName) ? String(window.AppConfig.userName).trim() : '';
        const em = (window.AppConfig && window.AppConfig.userEmail) ? String(window.AppConfig.userEmail).trim() : '';
        const name = nm || 'Usuario';
        const email = em || '';
        const dept = (window.AppConfig && window.AppConfig.userDeptName) ? String(window.AppConfig.userDeptName).trim() : '';
        const initials = (name.split(/\s+/).filter(Boolean).slice(0, 2).map(s => s[0]).join('') || 'U').toUpperCase();
        welcome.innerHTML = [
            '<h2 style="font-size: 20px; font-weight: 600; color: #1b1b1b; margin-bottom: 2px;">Bienvenido de nuevo</h2>',
            '<p style="font-size: 13px; color: #64748b; margin: 0;">' + (dept ? ('Departamento: ' + dept) : 'Departamento no disponible') + '</p>'
        ].join('');

        const actions = document.createElement('div');
        actions.className = 'top-bar-actions';
        actions.innerHTML = [
            '<div class="user-profile">',
            '  <div class="user-avatar">' + initials + '</div>',
            '  <div class="user-info">',
            '    <span class="user-name">' + name + '</span>',
            '    <span class="user-email">' + email + '</span>',
            '  </div>',
            '</div>'
        ].join('');

        topBar.appendChild(welcome);
        topBar.appendChild(actions);

        content.insertBefore(topBar, content.firstChild);
    }


});

document.addEventListener('DOMContentLoaded', () => {
    const content = document.querySelector('.content');
    if (!content) return;

    let topBar = content.querySelector('.top-bar');
    if (!topBar) {
        topBar = document.createElement('header');
        topBar.className = 'top-bar';

        const welcome = document.createElement('div');
        welcome.className = 'welcome-message';
        welcome.innerHTML = `
            <h2 style="font-size: 20px; font-weight: 600; color: #1b1b1b; margin-bottom: 2px;">Welcome Back</h2>
            <p style="font-size: 13px; color: #64748b; margin: 0;">Aquí verás tu información.</p>
        `;

        const actions = document.createElement('div');
        actions.className = 'top-bar-actions';
        actions.innerHTML = `
            <div class="user-profile">
                <div class="user-avatar">TM</div>
                <div class="user-info">
                    <span class="user-name">Totok Michael</span>
                    <span class="user-email">tmichael20@mail.com</span>
                </div>
            </div>
        `;

        topBar.appendChild(welcome);
        topBar.appendChild(actions);

        content.insertBefore(topBar, content.firstChild);
    }


});

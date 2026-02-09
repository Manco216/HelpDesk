document.addEventListener('DOMContentLoaded', () => {
    const content = document.querySelector('.content');
    if (!content) return;

    // 1. Aseguramos que el contenedor principal no tenga scroll interno bloqueado
    content.style.overflow = 'visible';
    content.style.height = 'auto';

    let topBar = content.querySelector('.top-bar');
    if (!topBar) {
        topBar = document.createElement('header');
        topBar.className = 'top-bar';

        topBar.innerHTML = `
            <div class="welcome-message">
                <h2 style="font-size: 20px; font-weight: 600; color: #1b1b1b; margin-bottom: 2px;">Welcome Back</h2>
                <p style="font-size: 13px; color: #64748b; margin: 0;">Aquí verás tu información.</p>
            </div>
            <div class="top-bar-actions">
                <div class="user-profile">
                    <div class="user-avatar">TM</div>
                    <div class="user-info">
                        <span class="user-name">Totok Michael</span>
                        <span class="user-email">tmichael20@mail.com</span>
                    </div>
                </div>
            </div>
        `;

        content.insertBefore(topBar, content.firstChild);
    }

    // 2. Quitamos cualquier restricción al main-body si existe
    let mainBody = content.querySelector('.main-body');
    if (mainBody) {
        mainBody.style.overflow = 'visible';
        mainBody.style.height = 'auto';
    }
});
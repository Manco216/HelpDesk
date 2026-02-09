(() => {
  const create = (kind, title, message, autoCloseMs = 2500) => {
    const overlay = document.createElement('div');
    overlay.className = 'alerta-overlay';
    const card = document.createElement('div');
    card.className = 'alerta-card';
    const body = document.createElement('div');
    body.className = 'alerta-body';
    const icon = document.createElement('div');
    icon.className = 'alerta-icon ' + (kind === 'error' ? 'error' : 'success');
    const ttl = document.createElement('div');
    ttl.className = 'alerta-title';
    ttl.textContent = title;
    const msg = document.createElement('div');
    msg.className = 'alerta-message';
    msg.textContent = message;
    const actions = document.createElement('div');
    actions.className = 'alerta-actions';
    const btn = document.createElement('button');
    btn.className = 'alerta-close';
    btn.type = 'button';
    btn.textContent = 'Cerrar';
    actions.appendChild(btn);
    body.appendChild(icon);
    body.appendChild(ttl);
    body.appendChild(msg);
    card.appendChild(body);
    card.appendChild(actions);
    overlay.appendChild(card);
    const close = () => {
      if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
    };
    btn.addEventListener('click', close);
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) close();
    });
    document.body.appendChild(overlay);
    if (autoCloseMs && autoCloseMs > 0) {
      setTimeout(close, autoCloseMs);
    }
  };
  const showError = (message) => {
    create('error', 'Proceso falló', message);
  };
  const showSuccess = (message) => {
    create('success', 'Proceso completado', message);
  };
  window.Alertas = { showError, showSuccess };
})();

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
    if (kind === 'success') {
      icon.innerHTML = '<svg class="alerta-svg" viewBox="0 0 100 100" aria-hidden="true"><circle class="alerta-ring" cx="50" cy="50" r="40" fill="none"></circle><polyline class="alerta-check" points="30,52 43,65 70,38" fill="none"></polyline></svg>';
    } else if (kind === 'error') {
      icon.innerHTML = '<svg class="alerta-svg" viewBox="0 0 100 100" aria-hidden="true"><circle class="alerta-ring" cx="50" cy="50" r="40" fill="none"></circle><line class="alerta-x1" x1="30" y1="30" x2="70" y2="70"></line><line class="alerta-x2" x1="70" y1="30" x2="30" y2="70"></line></svg>';
    }
    body.appendChild(icon);
    body.appendChild(ttl);
    body.appendChild(msg);
    card.appendChild(body);
    overlay.appendChild(card);
    const close = () => {
      if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
    };
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

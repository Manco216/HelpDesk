(() => {
  const firebaseConfig = {
    apiKey: "AIzaSyCuuBmox1OGPkpbMIht9vQcosV2lNgltPc",
    authDomain: "mesaservicio-b255e.firebaseapp.com",
    projectId: "mesaservicio-b255e",
    storageBucket: "mesaservicio-b255e.firebasestorage.app",
    messagingSenderId: "175198518115",
    appId: "1:175198518115:web:b6523bda98975318e23156",
    measurementId: "G-VN35Z523HB"
  };
  if (!window.firebase || !window.firebase.apps) return;
  try {
    window.firebase.initializeApp(firebaseConfig);
  } catch (_) {}
  const btn = document.getElementById('googleLoginBtn');
  const showError = (msg) => {
    if (window.Alertas) window.Alertas.showError(msg);
    else alert(msg);
  };
  const showSuccess = (msg) => {
    if (window.Alertas) window.Alertas.showSuccess(msg);
    else alert(msg);
  };
  const base = (window.AppConfig && window.AppConfig.baseUrl) ? window.AppConfig.baseUrl.replace(/\/+$/, '') : '';
  const loginUrl = (window.AppConfig && window.AppConfig.loginGoogle) ? window.AppConfig.loginGoogle : (base + '/login/google');
  if (btn) {
    btn.addEventListener('click', async () => {
      try {
        btn.disabled = true;
        btn.classList.add('loading');
        const textEl = btn.querySelector('.google-text');
        const spinnerEl = btn.querySelector('.google-spinner');
        if (spinnerEl) spinnerEl.style.display = 'inline-flex';
        if (textEl) textEl.textContent = 'Conectando...';
        const provider = new firebase.auth.GoogleAuthProvider();
        let result;
        try {
          result = await firebase.auth().signInWithPopup(provider);
        } catch (e) {
          if (e && (e.code === 'auth/popup-blocked' || e.code === 'auth/cancelled-popup-request')) {
            try {
              await firebase.auth().signInWithRedirect(provider);
              return;
            } catch (_) {
              btn.disabled = false;
              return;
            }
          } else {
            showError('Falló la autenticación con Google.');
            btn.disabled = false;
            return;
          }
        }
        const user = result.user;
        if (!user) {
          showError('No se pudo obtener el usuario de Google.');
          if (spinnerEl) spinnerEl.style.display = 'none';
          if (textEl) textEl.textContent = 'Iniciar con Google';
          btn.classList.remove('loading');
          btn.disabled = false;
          return;
        }
        const email = user.email || '';
        const name = user.displayName || 'Usuario';
        const uid = user.uid || '';
        const idToken = await user.getIdToken();
        const allowedDomainsStr = (window.AppConfig && window.AppConfig.allowedDomains) ? String(window.AppConfig.allowedDomains) : '';
        const allowedDomains = allowedDomainsStr.split(',').map(s => s.trim().toLowerCase()).filter(Boolean);
        const emailLower = (email || '').toLowerCase();
        const emailHasAt = emailLower.includes('@');
        const domainOk = allowedDomains.length === 0 ? true : allowedDomains.some(d => emailLower.endsWith('@' + d));
        if (!emailHasAt) {
          showError('Correo inválido. Verifica tu cuenta en Google.');
          if (spinnerEl) spinnerEl.style.display = 'none';
          if (textEl) textEl.textContent = 'Iniciar con Google';
          btn.classList.remove('loading');
          btn.disabled = false;
          return;
        }
        if (!domainOk) {
          showError('Tu correo no pertenece al dominio permitido (' + allowedDomainsStr + ').');
          if (spinnerEl) spinnerEl.style.display = 'none';
          if (textEl) textEl.textContent = 'Iniciar con Google';
          btn.classList.remove('loading');
          btn.disabled = false;
          return;
        }
        const fd = new FormData();
        fd.append('email', email);
        fd.append('name', name);
        fd.append('uid', uid);
        fd.append('idToken', idToken);
        const res = await fetch(loginUrl, { method: 'POST', body: fd });
        if (!res.ok) {
          let msg = 'No se pudo iniciar sesión con Google.';
          try {
            const data = await res.json();
            if (data && data.error === 'domain_not_allowed') {
              msg = 'Tu correo no pertenece al dominio permitido (' + allowedDomainsStr + ').';
            } else if (data && data.error === 'invalid_payload') {
              msg = 'Datos inválidos en la autenticación.';
            }
          } catch (_) {}
          showError(msg);
          if (spinnerEl) spinnerEl.style.display = 'none';
          if (textEl) textEl.textContent = 'Iniciar con Google';
          btn.classList.remove('loading');
          btn.disabled = false;
          return;
        }
        showSuccess('Bienvenido ' + name + '.');
        setTimeout(() => {
          const target = base + '/dashboard';
          window.location.href = target;
        }, 800);
      } catch (e) {
        showError('Falló la autenticación con Google.');
      } finally {
        try {
          const textEl = btn.querySelector('.google-text');
          const spinnerEl = btn.querySelector('.google-spinner');
          if (spinnerEl) spinnerEl.style.display = 'none';
          if (textEl) textEl.textContent = 'Iniciar con Google';
          btn.classList.remove('loading');
          btn.disabled = false;
        } catch (_) {}
      }
    });
  }
})();

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
  const preloadImg = (url) => new Promise((resolve, reject) => {
    if (!url) return resolve(false);
    const im = new Image();
    im.onload = () => resolve(true);
    im.onerror = () => reject(false);
    im.src = url;
  });
  const loadBackground = async () => {
    const shell = document.querySelector('.login-shell');
    if (!shell) return;
    const webp = 'https://socya.org.co/wp-content/uploads/ImagenesTI/background.webp';
    const png = 'https://socya.org.co/wp-content/uploads/ImagenesTI/background.png';
    try {
      await preloadImg(webp);
      shell.style.backgroundImage = "url('" + webp + "')";
    } catch (_) {
      try {
        await preloadImg(png);
        shell.style.backgroundImage = "url('" + png + "')";
      } catch (_e) {}
    }
  };
  const lazyIllustration = () => new Promise((resolve) => {
    const img = document.querySelector('.login-illustration');
    if (!img) return resolve(false);
    const realSrc = img.getAttribute('data-src') || img.getAttribute('src');
    const setLoaded = () => {
      try {
        const wrap = img.closest('.login-illustration-wrap');
        const w = img.naturalWidth || 480;
        const h = img.naturalHeight || 360;
        if (wrap && w > 0 && h > 0) wrap.style.aspectRatio = w + ' / ' + h;
        img.classList.add('loaded');
      } catch (_) {}
      resolve(true);
    };
    const setFallback = () => {
      try {
        const u = new URL(realSrc, window.location.origin);
        const fallback = new URL(window.location.origin);
        fallback.port = '';
        u.protocol = fallback.protocol;
        u.hostname = fallback.hostname;
        u.port = fallback.port;
        img.src = u.href;
      } catch (_) {
        try { img.src = String(realSrc || '').replace(':8080', ''); } catch (__){}
      }
    };
    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver((entries) => {
        entries.forEach((e) => {
          if (e.isIntersecting) {
            io.disconnect();
            if (img.src !== realSrc) img.src = realSrc;
            img.onerror = setFallback;
            if (img.decode) { img.decode().then(setLoaded).catch(setLoaded); }
            else { if (img.complete) setLoaded(); else img.onload = setLoaded; }
          }
        });
      }, { rootMargin: '120px' });
      io.observe(img);
    } else {
      if (img.src !== realSrc) img.src = realSrc;
      img.onerror = setFallback;
      if (img.decode) { img.decode().then(setLoaded).catch(setLoaded); }
      else { if (img.complete) setLoaded(); else img.onload = setLoaded; }
    }
  });
  (async () => {
    try { await loadBackground(); } catch (_) {}
    try { document.body.classList.add('bg-ready'); } catch (_) {}
    try { await lazyIllustration(); } catch (_) {}
    try { document.body.classList.add('login-ready'); } catch (_) {}
  })();
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

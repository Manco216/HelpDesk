window.ANS = (function () {
  function base() {
    var b = (window.AppConfig && window.AppConfig.baseUrl) ? window.AppConfig.baseUrl.replace(/\/+$/, "") : "";
    return b || "";
  }
  function getCsrfToken() {
    var m = document.querySelector('meta[name="csrf-token"]');
    if (m && m.content) return m.content;
    var match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : "";
  }
  function parseNombreAns(s) {
    var txt = String(s || "").trim();
    var m = txt.match(/^(\d+)\s+(\S.+)$/);
    if (!m) return { tiempo: "", unidad: "" };
    return { tiempo: m[1], unidad: m[2] };
  }
  function openCreateForm() {
    var ui = window.AdminUI;
    if (!ui) return;
    ui.ensureModal();
    var body = ui.getBody();
    if (!body) return;
    ui.setHeaderForForm();
    body.innerHTML = [
      '<div class="admin-form">',
      '  <div class="form-row"><label>Tiempo</label><input type="number" min="1" class="ans-tiempo input-min" placeholder="Cantidad"></div>',
      '  <div class="form-row"><label>Unidad</label><select class="ans-unidad select-min"><option value="">Seleccione…</option><option value="minutos">minutos</option><option value="horas">horas</option><option value="días">días</option></select></div>',
      '  <div class="form-actions">',
      '    <button type="button" class="btn-cancel btn-ghost">Cancelar</button>',
      '    <button type="button" class="btn-save btn-blue">Guardar</button>',
      '  </div>',
      '</div>'
    ].join("");
    var btnCancel = body.querySelector(".btn-cancel");
    var btnSave = body.querySelector(".btn-save");
    var tiempoEl = body.querySelector(".ans-tiempo");
    var unidadEl = body.querySelector(".ans-unidad");
    if (btnCancel) btnCancel.addEventListener("click", function () { ui.setHeaderForList(); ui.refreshList(); });
    if (btnSave) {
      btnSave.addEventListener("click", function () {
        var tiempo = parseInt((tiempoEl && tiempoEl.value) ? tiempoEl.value : "0", 10) || 0;
        var unidad = (unidadEl && unidadEl.value || "").trim();
        if (!tiempo || !unidad) { alert("Tiempo y Unidad son obligatorios"); return; }
        var token = getCsrfToken();
        fetch(base() + "/api/admin/ans", {
          method: "POST",
          credentials: "same-origin",
          headers: Object.assign({ "Content-Type": "application/json" }, token ? { "X-CSRF-TOKEN": token } : {}),
          body: JSON.stringify({ tiempo: tiempo, unidad_tiempo: unidad })
        }).then(function (res) {
          if (!res.ok) { alert("No se pudo guardar"); return; }
          ui.setHeaderForList(); ui.refreshList();
        }).catch(function () { alert("Error al guardar"); });
      });
    }
  }
  function openEditForm(id) {
    var ui = window.AdminUI;
    if (!ui) return;
    ui.ensureModal();
    var body = ui.getBody();
    if (!body) return;
    ui.setHeaderForForm();
    (function () {
      var titleRow = document.querySelector(".admin-categories-title-row");
      var h = titleRow ? titleRow.querySelector("h3") : null;
      var actions = document.querySelector(".admin-categories-actions-row");
      var tag = actions ? actions.querySelector(".admin-form-title") : null;
      if (h) h.textContent = "Editar ANS";
      if (tag) tag.textContent = "Editar ANS";
    })();
    body.innerHTML = [
      '<div class="admin-form">',
      '  <div class="form-row"><label>Tiempo</label><input type="number" min="1" class="ans-tiempo input-min" placeholder="Cantidad"></div>',
      '  <div class="form-row"><label>Unidad</label><select class="ans-unidad select-min"><option value="">Seleccione…</option><option value="minutos">minutos</option><option value="horas">horas</option><option value="días">días</option></select></div>',
      '  <div class="form-actions">',
      '    <button type="button" class="btn-cancel btn-ghost">Cancelar</button>',
      '    <button type="button" class="btn-save btn-blue">Guardar</button>',
      '  </div>',
      '</div>'
    ].join("");
    var btnCancel = body.querySelector(".btn-cancel");
    var btnSave = body.querySelector(".btn-save");
    var tiempoEl = body.querySelector(".ans-tiempo");
    var unidadEl = body.querySelector(".ans-unidad");
    if (btnCancel) btnCancel.addEventListener("click", function () { ui.setHeaderForList(); ui.refreshList(); });
    fetch(base() + "/api/admin/ans")
      .then(function (r) { return r.ok ? r.json() : []; })
      .then(function (rows) {
        var match = (Array.isArray(rows) ? rows : []).find(function (x) { return String(x.id) === String(id); });
        if (match) {
          var p = parseNombreAns(match.nombre_ans);
          if (tiempoEl) tiempoEl.value = String(p.tiempo || "");
          if (unidadEl) unidadEl.value = String(p.unidad || "");
        }
      })
      .catch(function () {});
    if (btnSave) {
      btnSave.addEventListener("click", function () {
        var tiempo = parseInt((tiempoEl && tiempoEl.value) ? tiempoEl.value : "0", 10) || 0;
        var unidad = (unidadEl && unidadEl.value || "").trim();
        if (!tiempo || !unidad) { alert("Tiempo y Unidad son obligatorios"); return; }
        var token = getCsrfToken();
        fetch(base() + "/api/admin/ans/" + encodeURIComponent(String(id)), {
          method: "PUT",
          credentials: "same-origin",
          headers: Object.assign({ "Content-Type": "application/json" }, token ? { "X-CSRF-TOKEN": token } : {}),
          body: JSON.stringify({ tiempo: tiempo, unidad_tiempo: unidad })
        }).then(function (res) {
          if (!res.ok) { alert("No se pudo actualizar"); return; }
          ui.setHeaderForList(); ui.refreshList();
        }).catch(function () { alert("Error al actualizar"); });
      });
    }
  }
  return { openCreateForm: openCreateForm, openEditForm: openEditForm };
})(); 

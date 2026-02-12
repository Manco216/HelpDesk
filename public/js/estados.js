window.Estado = (function () {
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
  function openCreateForm() {
    var ui = window.AdminUI;
    if (!ui) return;
    ui.ensureModal();
    var body = ui.getBody();
    if (!body) return;
    ui.setHeaderForForm();
    body.innerHTML = [
      '<div class="admin-form">',
      '  <div class="form-row"><label>Nombre</label><input type="text" class="estado-nombre input-min" placeholder="Nombre del estado"></div>',
      '  <div class="form-actions">',
      '    <button type="button" class="btn-cancel btn-ghost">Cancelar</button>',
      '    <button type="button" class="btn-save btn-blue">Guardar</button>',
      '  </div>',
      '</div>'
    ].join("");
    var btnCancel = body.querySelector(".btn-cancel");
    var btnSave = body.querySelector(".btn-save");
    var nombreEl = body.querySelector(".estado-nombre");
    if (btnCancel) btnCancel.addEventListener("click", function () { ui.setHeaderForList(); ui.refreshList(); });
    if (btnSave) {
      btnSave.addEventListener("click", function () {
        var nombre = (nombreEl && nombreEl.value || "").trim();
        if (!nombre) { alert("Nombre es obligatorio"); return; }
        var token = getCsrfToken();
        fetch(base() + "/api/admin/statuses", {
          method: "POST",
          credentials: "same-origin",
          headers: Object.assign({ "Content-Type": "application/json" }, token ? { "X-CSRF-TOKEN": token } : {}),
          body: JSON.stringify({ nombre: nombre })
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
      if (h) h.textContent = "Editar estado";
      if (tag) tag.textContent = "Editar estado";
    })();
    body.innerHTML = [
      '<div class="admin-form">',
      '  <div class="form-row"><label>Nombre</label><input type="text" class="estado-nombre input-min" placeholder="Nombre del estado"></div>',
      '  <div class="form-actions">',
      '    <button type="button" class="btn-cancel btn-ghost">Cancelar</button>',
      '    <button type="button" class="btn-save btn-blue">Guardar</button>',
      '  </div>',
      '</div>'
    ].join("");
    var btnCancel = body.querySelector(".btn-cancel");
    var btnSave = body.querySelector(".btn-save");
    var nombreEl = body.querySelector(".estado-nombre");
    if (btnCancel) btnCancel.addEventListener("click", function () { ui.setHeaderForList(); ui.refreshList(); });
    fetch(base() + "/api/admin/statuses")
      .then(function (r) { return r.ok ? r.json() : []; })
      .then(function (rows) {
        var match = (Array.isArray(rows) ? rows : []).find(function (x) { return String(x.id) === String(id); });
        if (match && nombreEl) nombreEl.value = String(match.nombre_estado || "");
      })
      .catch(function () {});
    if (btnSave) {
      btnSave.addEventListener("click", function () {
        var nombre = (nombreEl && nombreEl.value || "").trim();
        if (!nombre) { alert("Nombre es obligatorio"); return; }
        var token = getCsrfToken();
        fetch(base() + "/api/admin/statuses/" + encodeURIComponent(String(id)), {
          method: "PUT",
          credentials: "same-origin",
          headers: Object.assign({ "Content-Type": "application/json" }, token ? { "X-CSRF-TOKEN": token } : {}),
          body: JSON.stringify({ nombre: nombre })
        }).then(function (res) {
          if (!res.ok) { alert("No se pudo actualizar"); return; }
          ui.setHeaderForList(); ui.refreshList();
        }).catch(function () { alert("Error al actualizar"); });
      });
    }
  }
  return { openCreateForm: openCreateForm, openEditForm: openEditForm };
})(); 

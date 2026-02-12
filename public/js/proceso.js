window.Proceso = (function () {
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
      '  <div class="form-row"><label>Nombre</label><input type="text" class="proc-nombre input-min" placeholder="Nombre del proceso"></div>',
      '  <div class="form-row"><label>Descripción</label><input type="text" class="proc-desc input-min" placeholder="Descripción"></div>',
      '  <div class="form-row"><label>Estado</label><select class="proc-estado select-min"><option value="">Seleccione…</option><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>',
      '  <div class="form-actions">',
      '    <button type="button" class="btn-cancel btn-ghost">Cancelar</button>',
      '    <button type="button" class="btn-save btn-blue">Guardar</button>',
      '  </div>',
      '</div>'
    ].join("");
    var btnCancel = body.querySelector(".btn-cancel");
    var btnSave = body.querySelector(".btn-save");
    var nombreEl = body.querySelector(".proc-nombre");
    var descEl = body.querySelector(".proc-desc");
    var estadoEl = body.querySelector(".proc-estado");
    if (btnCancel) btnCancel.addEventListener("click", function () { ui.setHeaderForList(); ui.refreshList(); });
    if (btnSave) {
      btnSave.addEventListener("click", function () {
        var nombre = (nombreEl && nombreEl.value || "").trim();
        var desc = (descEl && descEl.value || "").trim();
        var estado = (estadoEl && estadoEl.value || "").trim();
        if (!nombre) { alert("Nombre es obligatorio"); return; }
        var token = getCsrfToken();
        fetch(base() + "/api/admin/processes", {
          method: "POST",
          credentials: "same-origin",
          headers: Object.assign({ "Content-Type": "application/json" }, token ? { "X-CSRF-TOKEN": token } : {}),
          body: JSON.stringify({ nombre: nombre, descripcion: desc, estado: estado })
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
      if (h) h.textContent = "Editar proceso";
      if (tag) tag.textContent = "Editar proceso";
    })();
    body.innerHTML = [
      '<div class="admin-form">',
      '  <div class="form-row"><label>Nombre</label><input type="text" class="proc-nombre input-min" placeholder="Nombre del proceso"></div>',
      '  <div class="form-row"><label>Descripción</label><input type="text" class="proc-desc input-min" placeholder="Descripción"></div>',
      '  <div class="form-row"><label>Estado</label><select class="proc-estado select-min"><option value="">Seleccione…</option><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>',
      '  <div class="form-actions">',
      '    <button type="button" class="btn-cancel btn-ghost">Cancelar</button>',
      '    <button type="button" class="btn-save btn-blue">Guardar</button>',
      '  </div>',
      '</div>'
    ].join("");
    var btnCancel = body.querySelector(".btn-cancel");
    var btnSave = body.querySelector(".btn-save");
    var nombreEl = body.querySelector(".proc-nombre");
    var descEl = body.querySelector(".proc-desc");
    var estadoEl = body.querySelector(".proc-estado");
    if (btnCancel) btnCancel.addEventListener("click", function () { ui.setHeaderForList(); ui.refreshList(); });
    fetch(base() + "/api/admin/processes/" + encodeURIComponent(String(id)))
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (data) {
        if (data) {
          if (nombreEl) nombreEl.value = String(data.nombre || "");
          if (descEl) descEl.value = String(data.descripcion || "");
          if (estadoEl) estadoEl.value = String(data.estado || "");
        }
      })
      .catch(function () {});
    if (btnSave) {
      btnSave.addEventListener("click", function () {
        var nombre = (nombreEl && nombreEl.value || "").trim();
        var desc = (descEl && descEl.value || "").trim();
        var estado = (estadoEl && estadoEl.value || "").trim();
        if (!nombre) { alert("Nombre es obligatorio"); return; }
        var token = getCsrfToken();
        fetch(base() + "/api/admin/processes/" + encodeURIComponent(String(id)), {
          method: "PUT",
          credentials: "same-origin",
          headers: Object.assign({ "Content-Type": "application/json" }, token ? { "X-CSRF-TOKEN": token } : {}),
          body: JSON.stringify({ nombre: nombre, descripcion: desc, estado: estado })
        }).then(function (res) {
          if (!res.ok) { alert("No se pudo actualizar"); return; }
          ui.setHeaderForList(); ui.refreshList();
        }).catch(function () { alert("Error al actualizar"); });
      });
    }
  }
  return { openCreateForm: openCreateForm, openEditForm: openEditForm };
})(); 

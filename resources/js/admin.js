document.addEventListener("DOMContentLoaded", function () {
    var backdrop = null;
    var modal = null;
    var abortCtrl = null;
    var rowsData = [];
    var queryText = "";
    var currentKey = "categorias";
    var endpointMap = { categorias: "categories", usuarios: "users", departamentos: "departments", tareas: "tasks", estados: "statuses", prioridades: "priorities", ans: "ans", procesos: "processes" };
    var titleMap = { categorias: "Categorías", usuarios: "Usuarios", departamentos: "Departamentos", tareas: "Tareas", estados: "Estados", prioridades: "Prioridades", ans: "ANS", procesos: "Procesos" };
    function ensureModal() {
        if (!backdrop) {
            backdrop = document.createElement("div");
            backdrop.className = "admin-categories-backdrop";
        }
        if (!modal) {
            modal = document.createElement("div");
            modal.className = "admin-categories-modal";
            modal.innerHTML = [
                '<div class="admin-categories-inner">',
                '  <div class="admin-categories-header">',
                '    <div class="admin-categories-title-row">',
                '      <h3>Categorías</h3>',
                '      <button type="button" class="admin-categories-close" title="Cerrar">&times;</button>',
                '    </div>',
                '    <div class="admin-categories-actions-row">',
                '      <input type="text" class="admin-categories-search" placeholder="Buscar…">',
                '      <button type="button" class="admin-category-add">Añadir categoría</button>',
                '    </div>',
                '  </div>',
                '  <div class="admin-categories-body">',
                '    <p class="admin-categories-empty">Cargando categorías…</p>',
                '  </div>',
                '</div>'
            ].join("");
            var closeBtn = modal.querySelector(".admin-categories-close");
            if (closeBtn) {
                closeBtn.addEventListener("click", closeModal);
            }
            backdrop.addEventListener("click", closeModal);
            var searchEl = modal.querySelector(".admin-categories-search");
            var addBtn = modal.querySelector(".admin-category-add");
            if (searchEl) {
                var t = null;
                searchEl.addEventListener("input", function () {
                    if (t) clearTimeout(t);
                    t = setTimeout(function () {
                        queryText = searchEl.value || "";
                        renderRows();
                    }, 200);
                });
            }
            if (addBtn) {
                addBtn.addEventListener("click", function () {
                    if (currentKey === "categorias") {
                        openCategoryForm();
                    } else if (currentKey === "usuarios") {
                        openUserForm();
                    } else if (currentKey === "procesos") {
                        openProcessForm();
                    }
                });
                addBtn.classList.add("btn-blue");
            }
        }
        if (!document.body.contains(backdrop)) document.body.appendChild(backdrop);
        if (!document.body.contains(modal)) document.body.appendChild(modal);
    }
    function setModalTitle() {
        var h = modal ? modal.querySelector(".admin-categories-title-row h3") : null;
        if (h) h.textContent = titleMap[currentKey] || "Administración";
    }
    function setHeaderForForm() {
        var titleRow = modal ? modal.querySelector(".admin-categories-title-row") : null;
        var h = titleRow ? titleRow.querySelector("h3") : null;
        var actions = modal ? modal.querySelector(".admin-categories-actions-row") : null;
        var searchEl = modal ? modal.querySelector(".admin-categories-search") : null;
        var addBtn = modal ? modal.querySelector(".admin-category-add") : null;
        var oldClose = titleRow ? titleRow.querySelector(".admin-categories-close") : null;
        var formTitle = currentKey === "usuarios" ? "Nuevo usuario" : (currentKey === "procesos" ? "Nuevo proceso" : "Nueva categoría");
        if (h) h.textContent = formTitle;
        if (searchEl) searchEl.style.display = "none";
        if (addBtn) addBtn.style.display = "none";
        if (actions) {
            var tag = actions.querySelector(".admin-form-title");
            if (!tag) {
                tag = document.createElement("span");
                tag.className = "admin-form-title";
                tag.textContent = formTitle;
                actions.appendChild(tag);
            } else {
                tag.style.display = "inline-block";
                tag.textContent = formTitle;
            }
        }
        if (titleRow && oldClose) {
            var backBtn = document.createElement("button");
            backBtn.type = "button";
            backBtn.className = "admin-categories-back btn-blue";
            backBtn.textContent = "←";
            backBtn.addEventListener("click", function () {
                setHeaderForList();
                fetchAdminList();
            });
            titleRow.replaceChild(backBtn, oldClose);
        }
    }
    function openUserForm() {
        ensureModal();
        var body = modal ? modal.querySelector(".admin-categories-body") : null;
        if (!body) return;
        body.innerHTML = [
            '<div class="admin-form">',
            '  <div class="form-row"><label>Nombre</label><input type="text" class="usr-nombre input-min" placeholder="Nombre del usuario"></div>',
            '  <div class="form-row"><label>Email</label><input type="email" class="usr-email input-min" placeholder="correo@dominio.com"></div>',
            '  <div class="form-row"><label>Estado</label><select class="usr-estado select-min"><option value="">Seleccione…</option><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>',
            '  <div class="form-row"><label>Rol</label><select class="usr-rol select-min"><option value="">Cargando…</option></select></div>',
            '  <div class="form-actions">',
            '    <button type="button" class="btn-cancel btn-ghost">Cancelar</button>',
            '    <button type="button" class="btn-save btn-blue">Guardar</button>',
            '  </div>',
            '</div>'
        ].join("");
        setHeaderForForm();
        var btnCancel = body.querySelector(".btn-cancel");
        var btnSave = body.querySelector(".btn-save");
        var nombreEl = body.querySelector(".usr-nombre");
        var emailEl = body.querySelector(".usr-email");
        var estadoEl = body.querySelector(".usr-estado");
        var rolEl = body.querySelector(".usr-rol");
        function fillSelect(el, items, labelKey, valueKey) {
            if (!el) return;
            var html = ['<option value="">Seleccione…</option>'].concat((items || []).map(function (it) {
                return '<option value="' + String(it[valueKey]) + '">' + String(it[labelKey]) + '</option>';
            })).join("");
            el.innerHTML = html;
        }
        try {
            if (window.Usuario && typeof window.Usuario.fetchRoles === "function") {
                window.Usuario.fetchRoles().then(function (rows) {
                    fillSelect(rolEl, Array.isArray(rows) ? rows : [], "nombre_rol", "id_rol");
                }).catch(function () {
                    fillSelect(rolEl, [], "nombre_rol", "id_rol");
                });
            }
        } catch (_e) {}
        if (btnCancel) btnCancel.addEventListener("click", function () { setHeaderForList(); fetchAdminList(); });
        if (btnSave) {
            btnSave.addEventListener("click", function () {
                var nombre = (nombreEl.value || "").trim();
                var email = (emailEl.value || "").trim();
                var estado = (estadoEl.value || "").trim();
                var rol_id = parseInt((rolEl && rolEl.value) ? rolEl.value : "0", 10) || 0;
                if (!nombre || !email) {
                    alert("Nombre y Email son obligatorios");
                    return;
                }
                var payload = { nombre: nombre, email: email, estado: estado, rol_id: rol_id };
                try {
                    if (window.Usuario && typeof window.Usuario.submitNewUser === "function") {
                        window.Usuario.submitNewUser(payload).then(function (ok) {
                            if (ok) {
                                if (window.Alertas && typeof window.Alertas.showSuccess === "function") {
                                    window.Alertas.showSuccess("El usuario se guardó con éxito.");
                                } else {
                                    alert("El usuario se guardó con éxito.");
                                }
                                fetchAdminList();
                            } else {
                                alert("No se pudo guardar");
                            }
                        }).catch(function () { alert("Error al guardar"); });
                    } else {
                        alert("Módulo de usuario no disponible");
                    }
                } catch (_e) {
                    alert("Error al guardar");
                }
            });
        }
    }
    function setHeaderForList() {
        var titleRow = modal ? modal.querySelector(".admin-categories-title-row") : null;
        var h = titleRow ? titleRow.querySelector("h3") : null;
        var actions = modal ? modal.querySelector(".admin-categories-actions-row") : null;
        var searchEl = modal ? modal.querySelector(".admin-categories-search") : null;
        var addBtn = modal ? modal.querySelector(".admin-category-add") : null;
        var backBtn = titleRow ? titleRow.querySelector(".admin-categories-back") : null;
        if (h) h.textContent = titleMap[currentKey] || "Administración";
        if (searchEl) searchEl.style.display = "";
        if (addBtn) addBtn.style.display = "";
        if (actions) {
            var tag = actions.querySelector(".admin-form-title");
            if (tag) tag.remove();
        }
        if (titleRow && backBtn) {
            var closeBtn = document.createElement("button");
            closeBtn.type = "button";
            closeBtn.className = "admin-categories-close";
            closeBtn.title = "Cerrar";
            closeBtn.innerHTML = "&times;";
            closeBtn.addEventListener("click", closeModal);
            titleRow.replaceChild(closeBtn, backBtn);
        }
    }
    function openCategoryForm() {
        ensureModal();
        var body = modal ? modal.querySelector(".admin-categories-body") : null;
        if (!body) return;
        var base = (window.AppConfig && window.AppConfig.baseUrl) ? window.AppConfig.baseUrl.replace(/\/+$/, "") : "";
        body.innerHTML = [
            '<div class="admin-form">',
            '  <div class="form-row"><label>Nombre</label><input type="text" class="cat-nombre input-min" placeholder="Nombre de la categoría"></div>',
            '  <div class="form-row"><label>Siglas</label><input type="text" class="cat-siglas input-min" placeholder="Ej: APP"></div>',
            '  <div class="form-row"><label>Departamento</label><select class="cat-dept select-min"><option value="">Cargando…</option></select></div>',
            '  <div class="form-row"><label>ANS</label><select class="cat-ans select-min"><option value="">Cargando…</option></select></div>',
            '  <div class="form-row"><label>Tarea</label><select class="cat-tarea select-min"><option value="">Cargando…</option></select></div>',
            '  <div class="form-row"><label>Representante</label><select class="cat-rep select-min"><option value="">Cargando…</option></select></div>',
            '  <div class="form-actions">',
            '    <button type="button" class="btn-cancel btn-ghost">Cancelar</button>',
            '    <button type="button" class="btn-save btn-blue">Guardar</button>',
            '  </div>',
            '</div>'
        ].join("");
        var selDept = body.querySelector(".cat-dept");
        var selAns = body.querySelector(".cat-ans");
        var selTarea = body.querySelector(".cat-tarea");
        var selRep = body.querySelector(".cat-rep");
        var btnCancel = body.querySelector(".btn-cancel");
        var btnSave = body.querySelector(".btn-save");
        setHeaderForForm();
        if (btnCancel) {
            btnCancel.addEventListener("click", function () { setHeaderForList(); fetchAdminList(); });
        }
        function fillSelect(el, items, labelKey, valueKey) {
            if (!el) return;
            var html = ['<option value="">Seleccione…</option>'].concat((items || []).map(function (it) {
                return '<option value="' + String(it[valueKey]) + '">' + String(it[labelKey]) + '</option>';
            })).join("");
            el.innerHTML = html;
        }
        fetch((base ? base : "") + "/api/admin/category-form-data")
            .then(function (r) { return r.ok ? r.json() : { ans: [], tasks: [], departments: [], users: [] }; })
            .then(function (data) {
                fillSelect(selAns, Array.isArray(data.ans) ? data.ans : [], "nombre_ans", "id");
                fillSelect(selTarea, Array.isArray(data.tasks) ? data.tasks : [], "nombre_tarea", "id");
                fillSelect(selDept, Array.isArray(data.departments) ? data.departments : [], "nombre_departamento", "id");
                fillSelect(selRep, Array.isArray(data.users) ? data.users : [], "nombre_usuario", "id");
            })
            .catch(function () {});
        if (btnSave) {
            btnSave.addEventListener("click", function () {
                var nombre = (body.querySelector(".cat-nombre").value || "").trim();
                var siglas = (body.querySelector(".cat-siglas").value || "").trim();
                var departamento_id = parseInt(selDept.value || "0", 10) || 0;
                var ans_id = parseInt(selAns.value || "0", 10) || 0;
                var tarea_id = parseInt(selTarea.value || "0", 10) || 0;
                var representante_id = parseInt(selRep.value || "0", 10) || 0;
                if (!nombre || !departamento_id) {
                    alert("Nombre y Departamento son obligatorios");
                    return;
                }
                var payload = {
                    nombre: nombre,
                    siglas: siglas,
                    departamento_id: departamento_id,
                    ans_id: ans_id,
                    tarea_id: tarea_id,
                    representante_id: representante_id
                };
                try {
                    if (window.Categoria && typeof window.Categoria.submitNewCategory === "function") {
                        window.Categoria.submitNewCategory(payload).then(function (ok) {
                            if (ok) {
                                if (window.Alertas && typeof window.Alertas.showSuccess === "function") {
                                    window.Alertas.showSuccess("La categoría se guardó con éxito.");
                                } else {
                                    alert("La categoría se guardó con éxito.");
                                }
                                fetchAdminList();
                            } else {
                                alert("No se pudo guardar");
                            }
                        }).catch(function () { alert("Error al guardar"); });
                    } else {
                        alert("Módulo de categoría no disponible");
                    }
                } catch (_e) {
                    alert("Error al guardar");
                }
            });
        }
    }
    function openModal() {
        ensureModal();
        backdrop.classList.add("active");
        modal.classList.add("active");
        document.body.style.overflow = "hidden";
    }
    function closeModal() {
        if (backdrop) backdrop.classList.remove("active");
        if (modal) modal.classList.remove("active");
        document.body.style.overflow = "";
    }
    function renderRows() {
        var body = modal ? modal.querySelector(".admin-categories-body") : null;
        if (!body) return;
        var q = (queryText || "").toLowerCase().trim();
        var filtered = (Array.isArray(rowsData) ? rowsData : []).filter(function (r) {
            if (!q) return true;
            var h = [
                String(r.nombre_categoria || r.nombre_usuario || r.nombre_departamento || r.nombre_tarea || r.nombre_estado || r.nombre_prioridad || r.nombre_proceso || ""),
                String(r.rep_nombre || r.email || r.rol_nombre || "")
            ].join(" ").toLowerCase();
            return h.indexOf(q) >= 0;
        }).map(function (r) {
            var id = String(r.id || "");
            var name = "";
            var second = "";
            var third = "";
            if (currentKey === "categorias") {
                name = String(r.nombre_categoria || "");
                second = String(r.rep_nombre || "-");
            } else if (currentKey === "usuarios") {
                name = String(r.nombre_usuario || "");
                second = String(r.email || "-");
                third = String(r.rol_nombre || "-");
            } else if (currentKey === "procesos") {
                name = String(r.nombre_proceso || "");
                second = "";
            } else if (currentKey === "departamentos") {
                name = String(r.nombre_departamento || "");
                second = "";
            } else if (currentKey === "tareas") {
                name = String(r.nombre_tarea || "");
                second = "";
            } else if (currentKey === "estados") {
                name = String(r.nombre_estado || "");
                second = "";
            } else if (currentKey === "prioridades") {
                name = String(r.nombre_prioridad || "");
                second = "";
            } else if (currentKey === "ans") {
                name = String(r.nombre_ans || "");
                second = "";
            }
            return [
                "<tr>",
                "  <td>" + name + "</td>",
                (currentKey === "categorias" || currentKey === "usuarios" ? "  <td>" + (second || "-") + "</td>" : ""),
                (currentKey === "usuarios" ? "  <td>" + (third || "-") + "</td>" : ""),
                '  <td><button type="button" class="btn-edit admin-cat-edit" data-id="' + id + '">Editar</button>',
                '      <label class="switch cat-active"><input type="checkbox" class="cat-active-toggle" data-id="' + id + '" checked><span class="slider"></span><span class="switch-label">Activo</span></label></td>',
                "</tr>"
            ].join("");
        }).join("");
        body.innerHTML = [
            '<table class="categories-table search-results-table">',
            '  <thead>',
            (currentKey === "categorias" ? '    <tr><th>Category</th><th>Rep</th><th>Modify</th></tr>' :
             currentKey === "usuarios" ? '    <tr><th>User</th><th>Email</th><th>Role</th><th>Modify</th></tr>' :
             currentKey === "procesos" ? '    <tr><th>Process</th><th>Modify</th></tr>' :
             currentKey === "departamentos" ? '    <tr><th>Department</th><th>Modify</th></tr>' :
             currentKey === "tareas" ? '    <tr><th>Task</th><th>Modify</th></tr>' :
             currentKey === "estados" ? '    <tr><th>Status</th><th>Modify</th></tr>' :
             currentKey === "ans" ? '    <tr><th>ANS</th><th>Modify</th></tr>' :
             '    <tr><th>Priority</th><th>Modify</th></tr>'),
            '  </thead>',
            '  <tbody>' + (filtered || "") + '</tbody>',
            '</table>'
        ].join("");
        if (!filtered) {
            var tbody = body.querySelector("tbody");
            var colSpan = (currentKey === "categorias" ? 3 : (currentKey === "usuarios" ? 4 : (currentKey === "procesos" ? 2 : 2)));
            if (tbody) tbody.innerHTML = '<tr><td colspan="' + colSpan + '" class="admin-categories-empty">Sin datos.</td></tr>';
        }
        body.addEventListener("click", function (e) {
            var editBtn = e.target && e.target.closest ? e.target.closest(".admin-cat-edit") : null;
            if (editBtn) {
                e.preventDefault();
                var id2 = editBtn.getAttribute("data-id");
                if (id2) {
                    var nid = parseInt(id2, 10) || 0;
                    if (currentKey === "categorias") {
                        openCategoryEditForm(nid);
                    } else if (currentKey === "usuarios") {
                        openUserEditForm(nid);
                    } else if (currentKey === "procesos") {
                        openProcessEditForm(nid);
                    }
                }
            }
        });
        body.addEventListener("change", function (e) {
            var toggle = e.target && e.target.closest ? e.target.closest(".cat-active-toggle") : null;
            if (toggle) {
                var checked = !!toggle.checked;
                var tr = toggle.closest("tr");
                if (tr) {
                    if (!checked) tr.classList.add("inactive"); else tr.classList.remove("inactive");
                }
                var lab = toggle.parentElement ? toggle.parentElement.querySelector(".switch-label") : null;
                if (lab) lab.textContent = checked ? "Activo" : "Inactivo";
            }
        });
    }
    function openCategoryEditForm(id) {
        if (!id) return;
        ensureModal();
        var body = modal ? modal.querySelector(".admin-categories-body") : null;
        if (!body) return;
        setHeaderForForm();
        var base = (window.AppConfig && window.AppConfig.baseUrl) ? window.AppConfig.baseUrl.replace(/\/+$/, "") : "";
        body.innerHTML = [
            '<div class="admin-form">',
            '  <div class="form-row"><label>Nombre</label><input type="text" class="cat-nombre input-min" placeholder="Nombre de la categoría"></div>',
            '  <div class="form-row"><label>Siglas</label><input type="text" class="cat-siglas input-min" placeholder="Ej: APP"></div>',
            '  <div class="form-row"><label>Departamento</label><select class="cat-dept select-min"><option value="">Cargando…</option></select></div>',
            '  <div class="form-row"><label>ANS</label><select class="cat-ans select-min"><option value="">Cargando…</option></select></div>',
            '  <div class="form-row"><label>Tarea</label><select class="cat-tarea select-min"><option value="">Cargando…</option></select></div>',
            '  <div class="form-row"><label>Representante</label><select class="cat-rep select-min"><option value="">Cargando…</option></select></div>',
            '  <div class="form-actions">',
            '    <button type="button" class="btn-cancel btn-ghost">Cancelar</button>',
            '    <button type="button" class="btn-save btn-blue">Guardar</button>',
            '  </div>',
            '</div>'
        ].join("");
        var titleRow = modal ? modal.querySelector(".admin-categories-title-row") : null;
        var h = titleRow ? titleRow.querySelector("h3") : null;
        if (h) h.textContent = "Editar categoría";
        var actions = modal ? modal.querySelector(".admin-categories-actions-row") : null;
        if (actions) {
            var tag = actions.querySelector(".admin-form-title");
            if (tag) tag.textContent = "Editar categoría";
        }
        var selDept = body.querySelector(".cat-dept");
        var selAns = body.querySelector(".cat-ans");
        var selTarea = body.querySelector(".cat-tarea");
        var selRep = body.querySelector(".cat-rep");
        var btnCancel = body.querySelector(".btn-cancel");
        var btnSave = body.querySelector(".btn-save");
        if (btnCancel) btnCancel.addEventListener("click", function () { setHeaderForList(); fetchAdminList(); });
        function fillSelect(el, items, labelKey, valueKey) {
            if (!el) return;
            var html = ['<option value="">Seleccione…</option>'].concat((items || []).map(function (it) {
                return '<option value="' + String(it[valueKey]) + '">' + String(it[labelKey]) + '</option>';
            })).join("");
            el.innerHTML = html;
        }
        var details = null;
        var formDataPromise = fetch((base ? base : "") + "/api/admin/category-form-data")
            .then(function (r) { return r.ok ? r.json() : { ans: [], tasks: [], departments: [], users: [] }; })
            .catch(function () { return { ans: [], tasks: [], departments: [], users: [] }; });
        var detailsPromise = (window.Categoria && typeof window.Categoria.fetchCategoryDetails === "function")
            ? window.Categoria.fetchCategoryDetails(id)
            : Promise.resolve(null);
        Promise.all([formDataPromise, detailsPromise]).then(function (vals) {
            var data = vals[0] || { ans: [], tasks: [], departments: [], users: [] };
            details = vals[1] || null;
            fillSelect(selAns, Array.isArray(data.ans) ? data.ans : [], "nombre_ans", "id");
            fillSelect(selTarea, Array.isArray(data.tasks) ? data.tasks : [], "nombre_tarea", "id");
            fillSelect(selDept, Array.isArray(data.departments) ? data.departments : [], "nombre_departamento", "id");
            fillSelect(selRep, Array.isArray(data.users) ? data.users : [], "nombre_usuario", "id");
            if (details) {
                var nombreEl = body.querySelector(".cat-nombre");
                var siglasEl = body.querySelector(".cat-siglas");
                if (nombreEl) nombreEl.value = String(details.nombre || "");
                if (siglasEl) siglasEl.value = String(details.siglas || "");
                if (selDept) selDept.value = String(details.departamento_id || "");
                if (selAns) selAns.value = String(details.ans_id || "");
                if (selTarea) selTarea.value = String(details.tarea_id || "");
                if (selRep) selRep.value = String(details.representante_id || "");
            }
        }).catch(function () {});
        if (btnSave) {
            btnSave.addEventListener("click", function () {
                var nombre = (body.querySelector(".cat-nombre").value || "").trim();
                var siglas = (body.querySelector(".cat-siglas").value || "").trim();
                var departamento_id = parseInt(selDept.value || "0", 10) || 0;
                var ans_id = parseInt(selAns.value || "0", 10) || 0;
                var tarea_id = parseInt(selTarea.value || "0", 10) || 0;
                var representante_id = parseInt(selRep.value || "0", 10) || 0;
                if (!nombre || !departamento_id) {
                    alert("Nombre y Departamento son obligatorios");
                    return;
                }
                var payload = {
                    nombre: nombre,
                    siglas: siglas,
                    departamento_id: departamento_id,
                    ans_id: ans_id,
                    tarea_id: tarea_id,
                    representante_id: representante_id
                };
                try {
                    if (window.Categoria && typeof window.Categoria.submitUpdateCategory === "function") {
                        window.Categoria.submitUpdateCategory(id, payload).then(function (ok) {
                            if (ok) {
                                if (window.Alertas && typeof window.Alertas.showSuccess === "function") {
                                    window.Alertas.showSuccess("La categoría se actualizó con éxito.");
                                } else {
                                    alert("La categoría se actualizó con éxito.");
                                }
                                fetchAdminList();
                            } else {
                                alert("No se pudo actualizar");
                            }
                        }).catch(function () { alert("Error al actualizar"); });
                    } else {
                        alert("Módulo de categoría no disponible");
                    }
                } catch (_e) {
                    alert("Error al actualizar");
                }
            });
        }
    }
    function openUserEditForm(id) {
        if (!id) return;
        ensureModal();
        var body = modal ? modal.querySelector(".admin-categories-body") : null;
        if (!body) return;
        setHeaderForForm();
        body.innerHTML = [
            '<div class="admin-form">',
            '  <div class="form-row"><label>Nombre</label><input type="text" class="usr-nombre input-min" placeholder="Nombre del usuario"></div>',
            '  <div class="form-row"><label>Email</label><input type="email" class="usr-email input-min" placeholder="correo@dominio.com"></div>',
            '  <div class="form-row"><label>Estado</label><select class="usr-estado select-min"><option value="">Seleccione…</option><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>',
            '  <div class="form-row"><label>Rol</label><select class="usr-rol select-min"><option value="">Cargando…</option></select></div>',
            '  <div class="form-actions">',
            '    <button type="button" class="btn-cancel btn-ghost">Cancelar</button>',
            '    <button type="button" class="btn-save btn-blue">Guardar</button>',
            '  </div>',
            '</div>'
        ].join("");
        var titleRow = modal ? modal.querySelector(".admin-categories-title-row") : null;
        var h = titleRow ? titleRow.querySelector("h3") : null;
        if (h) h.textContent = "Editar usuario";
        var actions = modal ? modal.querySelector(".admin-categories-actions-row") : null;
        if (actions) {
            var tag = actions.querySelector(".admin-form-title");
            if (tag) tag.textContent = "Editar usuario";
        }
        var btnCancel = body.querySelector(".btn-cancel");
        var btnSave = body.querySelector(".btn-save");
        var nombreEl = body.querySelector(".usr-nombre");
        var emailEl = body.querySelector(".usr-email");
        var estadoEl = body.querySelector(".usr-estado");
        var rolEl = body.querySelector(".usr-rol");
        if (btnCancel) btnCancel.addEventListener("click", function () { setHeaderForList(); fetchAdminList(); });
        var detailsPromise = (window.Usuario && typeof window.Usuario.fetchUserDetails === "function")
            ? window.Usuario.fetchUserDetails(id)
            : Promise.resolve(null);
        function fillSelect(el, items, labelKey, valueKey, selectedValue) {
            if (!el) return;
            var html = ['<option value="">Seleccione…</option>'].concat((items || []).map(function (it) {
                var val = String(it[valueKey]);
                var lbl = String(it[labelKey]);
                var sel = (String(selectedValue || "") === val) ? ' selected' : '';
                return '<option value="' + val + '"' + sel + '>' + lbl + '</option>';
            })).join("");
            el.innerHTML = html;
        }
        var detData = null;
        detailsPromise.then(function (det) {
            if (det) {
                detData = det;
                if (nombreEl) nombreEl.value = String(det.nombre || "");
                if (emailEl) emailEl.value = String(det.email || "");
                if (estadoEl) estadoEl.value = String(det.estado || "");
            }
        }).catch(function () {});
        try {
            if (window.Usuario && typeof window.Usuario.fetchRoles === "function") {
                window.Usuario.fetchRoles().then(function (rows) {
                    var selected = detData ? String(detData.rol_id || "") : "";
                    fillSelect(rolEl, Array.isArray(rows) ? rows : [], "nombre_rol", "id_rol", selected);
                }).catch(function () {
                    fillSelect(rolEl, [], "nombre_rol", "id_rol", "");
                });
            }
        } catch (_e) {}
        if (btnSave) {
            btnSave.addEventListener("click", function () {
                var nombre = (nombreEl.value || "").trim();
                var email = (emailEl.value || "").trim();
                var estado = (estadoEl.value || "").trim();
                var rol_id = parseInt((rolEl && rolEl.value) ? rolEl.value : "0", 10) || 0;
                if (!nombre || !email) {
                    alert("Nombre y Email son obligatorios");
                    return;
                }
                var payload = { nombre: nombre, email: email, estado: estado, rol_id: rol_id };
                try {
                    if (window.Usuario && typeof window.Usuario.submitUpdateUser === "function") {
                        window.Usuario.submitUpdateUser(id, payload).then(function (ok) {
                            if (ok) {
                                if (window.Alertas && typeof window.Alertas.showSuccess === "function") {
                                    window.Alertas.showSuccess("El usuario se actualizó con éxito.");
                                } else {
                                    alert("El usuario se actualizó con éxito.");
                                }
                                fetchAdminList();
                            } else {
                                alert("No se pudo actualizar");
                            }
                        }).catch(function () { alert("Error al actualizar"); });
                    } else {
                        alert("Módulo de usuario no disponible");
                    }
                } catch (_e) {
                    alert("Error al actualizar");
                }
            });
        }
    }
    function fetchAdminList() {
        ensureModal();
        setHeaderForList();
        var body = modal.querySelector(".admin-categories-body");
        try { if (abortCtrl) abortCtrl.abort(); } catch (_e) {}
        abortCtrl = new AbortController();
        if (body) body.innerHTML = '<p class="admin-categories-empty">Cargando…</p>';
        var base = (window.AppConfig && window.AppConfig.baseUrl) ? window.AppConfig.baseUrl.replace(/\/+$/, "") : "";
        var ep = endpointMap[currentKey] || "categories";
        var url = (base ? base : "") + "/api/admin/" + ep;
        fetch(url, { method: "GET", signal: abortCtrl.signal })
            .then(function (res) {
                abortCtrl = null;
                if (!res.ok) {
                    if (body) body.innerHTML = '<p class="admin-categories-empty">Error al cargar.</p>';
                    return Promise.resolve([]);
                }
                return res.json();
            })
            .then(function (data) {
                rowsData = Array.isArray(data) ? data : [];
                renderRows();
            })
            .catch(function () {
                abortCtrl = null;
                if (body) body.innerHTML = '<p class="admin-categories-empty">Error al cargar.</p>';
            });
    }
    var botones = Array.prototype.slice.call(document.querySelectorAll(".boton-panel"));
    if (botones && botones.length) {
        botones.forEach(function (b) {
            b.addEventListener("click", function (e) {
                var key = b.getAttribute("data-key") || "";
                var nombre = b.textContent ? b.textContent.trim() : "";
                if (key === "categorias" || key === "usuarios" || key === "departamentos" || key === "tareas" || key === "estados" || key === "prioridades" || key === "ans" || key === "procesos") {
                    e.preventDefault();
                    e.stopPropagation();
                    if (typeof e.stopImmediatePropagation === "function") e.stopImmediatePropagation();
                    currentKey = key;
                    setModalTitle();
                    openModal();
                    fetchAdminList();
                } else {
                    alert("Abrir: " + nombre);
                    try { console.log("Acción seleccionada:", nombre); } catch (_err) {}
                }
            }, true);
        });
    }
    function openProcessForm() {
        ensureModal();
        var body = modal ? modal.querySelector(".admin-categories-body") : null;
        if (!body) return;
        setHeaderForForm();
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
        if (btnCancel) btnCancel.addEventListener("click", function () { setHeaderForList(); fetchAdminList(); });
        if (btnSave) {
            btnSave.addEventListener("click", function () {
                var nombre = (nombreEl.value || "").trim();
                var desc = (descEl.value || "").trim();
                var estado = (estadoEl.value || "").trim();
                if (!nombre) { alert("Nombre es obligatorio"); return; }
                var base = (window.AppConfig && window.AppConfig.baseUrl) ? window.AppConfig.baseUrl.replace(/\/+$/, "") : "";
                var token = (document.querySelector('meta[name="csrf-token"]') || {}).getAttribute ? (document.querySelector('meta[name="csrf-token"]').getAttribute('content') || '') : '';
                fetch((base ? base : "") + "/api/admin/processes", {
                    method: "POST",
                    credentials: "same-origin",
                    headers: Object.assign({ "Content-Type": "application/json" }, token ? { "X-CSRF-TOKEN": token } : {}),
                    body: JSON.stringify({ nombre: nombre, descripcion: desc, estado: estado })
                }).then(function (res) {
                    if (!res.ok) { alert("No se pudo guardar"); return; }
                    setHeaderForList(); fetchAdminList();
                }).catch(function () { alert("Error al guardar"); });
            });
        }
    }
    function openProcessEditForm(id) {
        if (!id) return;
        ensureModal();
        var body = modal ? modal.querySelector(".admin-categories-body") : null;
        if (!body) return;
        setHeaderForForm();
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
        if (btnCancel) btnCancel.addEventListener("click", function () { setHeaderForList(); fetchAdminList(); });
        var base = (window.AppConfig && window.AppConfig.baseUrl) ? window.AppConfig.baseUrl.replace(/\/+$/, "") : "";
        fetch((base ? base : "") + "/api/admin/processes/" + String(id)).then(function (r) { return r.ok ? r.json() : null; }).then(function (data) {
            if (data) {
                if (nombreEl) nombreEl.value = String(data.nombre || "");
                if (descEl) descEl.value = String(data.descripcion || "");
                if (estadoEl) estadoEl.value = String(data.estado || "");
            }
        }).catch(function () {});
        if (btnSave) {
            btnSave.addEventListener("click", function () {
                var nombre = (nombreEl.value || "").trim();
                var desc = (descEl.value || "").trim();
                var estado = (estadoEl.value || "").trim();
                if (!nombre) { alert("Nombre es obligatorio"); return; }
                var token = (document.querySelector('meta[name="csrf-token"]') || {}).getAttribute ? (document.querySelector('meta[name="csrf-token"]').getAttribute('content') || '') : '';
                fetch((base ? base : "") + "/api/admin/processes/" + String(id), {
                    method: "PUT",
                    credentials: "same-origin",
                    headers: Object.assign({ "Content-Type": "application/json" }, token ? { "X-CSRF-TOKEN": token } : {}),
                    body: JSON.stringify({ nombre: nombre, descripcion: desc, estado: estado })
                }).then(function (res) {
                    if (!res.ok) { alert("No se pudo actualizar"); return; }
                    setHeaderForList(); fetchAdminList();
                }).catch(function () { alert("Error al actualizar"); });
            });
        }
    }
});

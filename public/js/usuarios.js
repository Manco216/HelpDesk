window.Usuario = (function () {
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
  function submitNewUser(payload) {
    try {
      var url = base() + "/api/admin/users";
      var token = getCsrfToken();
      return fetch(url, {
        method: "POST",
        credentials: "same-origin",
        headers: Object.assign(
          { "Content-Type": "application/json" },
          token ? { "X-CSRF-TOKEN": token } : {}
        ),
        body: JSON.stringify(payload)
      })
        .then(function (res) {
          if (!res.ok) return false;
          return res.json().then(function (j) { return !!(j && j.id); }).catch(function () { return true; });
        })
        .catch(function () { return false; });
    } catch (_e) {
      return Promise.resolve(false);
    }
  }
  function fetchUserDetails(id) {
    try {
      var url = base() + "/api/admin/users/" + encodeURIComponent(String(id));
      return fetch(url, { method: "GET", credentials: "same-origin" })
        .then(function (res) { return res.ok ? res.json() : null; })
        .catch(function () { return null; });
    } catch (_e) {
      return Promise.resolve(null);
    }
  }
  function fetchRoles() {
    try {
      var url = base() + "/api/catalog/roles";
      return fetch(url, { method: "GET", credentials: "same-origin" })
        .then(function (res) { return res.ok ? res.json() : []; })
        .catch(function () { return []; });
    } catch (_e) {
      return Promise.resolve([]);
    }
  }
  function submitUpdateUser(id, payload) {
    try {
      var url = base() + "/api/admin/users/" + encodeURIComponent(String(id));
      var token = getCsrfToken();
      return fetch(url, {
        method: "PUT",
        credentials: "same-origin",
        headers: Object.assign(
          { "Content-Type": "application/json" },
          token ? { "X-CSRF-TOKEN": token } : {}
        ),
        body: JSON.stringify(payload)
      })
        .then(function (res) {
          if (!res.ok) return false;
          return res.json().then(function (j) { return !!(j && j.updated); }).catch(function () { return true; });
        })
        .catch(function () { return false; });
    } catch (_e) {
      return Promise.resolve(false);
    }
  }
  return { submitNewUser: submitNewUser, fetchUserDetails: fetchUserDetails, submitUpdateUser: submitUpdateUser, fetchRoles: fetchRoles };
})();

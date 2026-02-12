window.Categoria = (function () {
  function base() {
    var b = (window.AppConfig && window.AppConfig.baseUrl) ? window.AppConfig.baseUrl.replace(/\/+$/, "") : "";
    return b || "";
  }
  function getCsrfToken() {
    var m = document.querySelector('meta[name="csrf-token"]');
    if (m && m.content) return m.content;
    var match = document.cookie.match(/(?:^|;\\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : "";
  }
  function submitNewCategory(payload) {
    try {
      var url = base() + "/api/admin/categories";
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
  function submitUpdateCategory(id, payload) {
    try {
      var url = base() + "/api/admin/categories/" + encodeURIComponent(String(id));
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
  function fetchCategoryDetails(id) {
    try {
      var url = base() + "/api/admin/categories/" + encodeURIComponent(String(id));
      return fetch(url, { method: "GET", credentials: "same-origin" })
        .then(function (res) { return res.ok ? res.json() : null; })
        .catch(function () { return null; });
    } catch (_e) {
      return Promise.resolve(null);
    }
  }
  return { submitNewCategory: submitNewCategory, submitUpdateCategory: submitUpdateCategory, fetchCategoryDetails: fetchCategoryDetails };
})(); 

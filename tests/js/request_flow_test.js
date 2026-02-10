// Simple unit/integration-like check of front-end gating using JSDOM
import { JSDOM } from 'jsdom';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

function assert(condition, message) {
  if (!condition) throw new Error(message || 'Assertion failed');
}

(async () => {
  const html = `<!doctype html><html><head></head><body><div class="page-content"></div></body></html>`;
  const dom = new JSDOM(html, { url: 'http://localhost/', runScripts: 'dangerously', resources: 'usable' });
  const { window } = dom;
  const { document } = window;

  window.AppConfig = { baseUrl: '' };
  window.Alertas = {
    showError(msg) { /* noop */ },
    showSuccess(msg) { /* noop */ },
  };
  window.DataTransfer = class { constructor() { this.files = []; } };
  Object.defineProperty(window, 'localStorage', { value: {
    _s: {},
    getItem(k) { return this._s[k] || null; },
    setItem(k, v) { this._s[k] = String(v); },
    removeItem(k) { delete this._s[k]; },
  }, configurable: true });
  const processesPayload = [{ id_proceso: 1, nombre_proceso: 'Proceso Test' }];
  const categoriesPayload = [{ id_categoria: 1, nombre_categoria: 'Incidencias' }];
  window.fetch = async (url, opts = {}) => {
    if (typeof url === 'string' && url.includes('/api/processes/1/categories') && (opts.method || 'GET') === 'GET') {
      return { ok: true, json: async () => categoriesPayload };
    }
    if (typeof url === 'string' && url.includes('/api/processes') && (opts.method || 'GET') === 'GET') {
      return { ok: true, json: async () => processesPayload };
    }
    if (typeof url === 'string' && url.includes('/api/tickets') && (opts.method || 'GET') === 'POST') {
      return { ok: true, json: async () => ({ id_tickets: 123 }) };
    }
    return { ok: true, json: async () => [] };
  };

  const __filename = fileURLToPath(import.meta.url);
  const __dirname = path.dirname(__filename);
  const jsPath = path.resolve(__dirname, '../../resources/js/main.js');
  const scriptCode = fs.readFileSync(jsPath, 'utf8');
  const scriptEl = document.createElement('script');
  scriptEl.textContent = scriptCode;
  document.body.appendChild(scriptEl);
  window.document.dispatchEvent(new window.Event('DOMContentLoaded', { bubbles: true }));

  const requestFab = Array.from(document.querySelectorAll('.fab-item'))
    .find(w => (w.querySelector('.fab-label')?.textContent || '').includes('Agregar una nueva solicitud'));
  assert(requestFab, 'FAB item for agregar solicitud no encontrado');
  const requestBtn = requestFab.querySelector('.fab-button-mini');
  assert(requestBtn, 'Botón mini de solicitud no encontrado');

  // Primera apertura: debe estar deshabilitado todo sin proceso
  requestBtn.click();
  const modal = document.querySelector('.request-modal');
  const processSelect = modal.querySelector('#request-process');
  const categorySelect = modal.querySelector('#request-category');
  const descriptionInput = modal.querySelector('#request-description');
  const submitBtn = modal.querySelector('.request-submit');
  const processMsg = modal.querySelector('.process-required-msg');
  assert(processSelect, 'Select de proceso no existe');
  console.log('Estado inicial submit disabled:', submitBtn.disabled);
  assert(submitBtn.disabled === true, 'Submit debe estar deshabilitado sin proceso');
  assert(categorySelect.disabled === true, 'Categoría debe estar deshabilitada sin proceso');
  assert(descriptionInput.disabled === true, 'Descripción debe estar deshabilitada sin proceso');
  assert(processMsg && processMsg.style.display !== 'none', 'Mensaje de proceso requerido debe estar visible');

  // Seleccionar proceso debe habilitar
  if (!Array.from(processSelect.options).some(o => o.value === '1')) {
    const o = document.createElement('option');
    o.value = '1';
    o.textContent = 'Proceso Test';
    processSelect.appendChild(o);
  }
  const opt = Array.from(processSelect.options).find(o => o.value === '1');
  if (opt) { opt.selected = true; }
  processSelect.value = '1';
  processSelect.dispatchEvent(new window.Event('change', { bubbles: true }));
  await new Promise(r => setTimeout(r, 5));
  console.log('Valor de proceso tras seleccionar:', processSelect.value);
  console.log('Estado tras seleccionar proceso submit disabled:', submitBtn.disabled);
  assert(submitBtn.disabled === false, 'Submit debe habilitarse al seleccionar proceso');
  assert(categorySelect.disabled === false, 'Categoría debe habilitarse al seleccionar proceso');
  assert(descriptionInput.disabled === false, 'Descripción debe habilitarse al seleccionar proceso');
  assert(processMsg.style.display === 'none', 'Mensaje debe ocultarse al seleccionar proceso');

  // Cerrar modal
  const cancelBtn = modal.querySelector('.request-cancel');
  cancelBtn.click();
  await new Promise(r => setTimeout(r, 5));

  // Segunda apertura: debe mantener los datos si no se envió,
  // y seguir habilitado si el proceso se quedó seleccionado
  requestBtn.click();
  await new Promise(r => setTimeout(r, 5));
  const submitBtn2 = document.querySelector('.request-modal .request-submit');
  const categorySelect2 = document.querySelector('.request-modal #request-category');
  const descriptionInput2 = document.querySelector('.request-modal #request-description');
  const processMsg2 = document.querySelector('.request-modal .process-required-msg');
  console.log('Segunda apertura submit disabled:', submitBtn2.disabled);
  assert(submitBtn2.disabled === false, 'Submit debe seguir habilitado si el proceso ya estaba seleccionado');
  assert(processMsg2 && processMsg2.style.display === 'none', 'Mensaje debe estar oculto si proceso sigue seleccionado');

  console.log('OK: gating y persistencia verificados en dos ciclos de creación');
})().catch(err => {
  console.error(err);
  process.exit(1);
});

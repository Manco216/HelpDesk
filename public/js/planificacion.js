const blue = "#00A0DF";
const weekdaysNames = ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"];
const monthNames = ["enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre"];
const storageKey = "planificacion.events";
let events = JSON.parse(localStorage.getItem(storageKey) || "{}");
const todosKey = "planificacion.todos";
let todos = JSON.parse(localStorage.getItem(todosKey) || "[]");
let current = new Date();
let selected = new Date(current.getFullYear(), current.getMonth(), current.getDate());
const COLORS = [
  "#00A0DF","#0284c7","#0ea5e9","#38bdf8","#60a5fa","#22d3ee",
  "#8b5cf6","#7c3aed","#6d28d9","#a78bfa","#c4b5fd","#d8b4fe",
  "#14b8a6","#2dd4bf","#10b981","#22c55e","#4ade80","#84cc16",
  "#f59e0b","#fbbf24","#fcd34d","#fde047","#fb923c","#fdba74"
];
const monthLabel = document.getElementById("monthLabel");
const weekdays = document.getElementById("weekdays");
const days = document.getElementById("days");
const todoList = document.getElementById("todoList");
const searchForm = document.getElementById("searchForm");
const searchInput = document.getElementById("searchInput");
const clearSearchBtn = document.getElementById("clearSearch");
const loadSamplesBtn = document.getElementById("loadSamples");
const resetAllBtn = document.getElementById("resetAll");
const dayModal = document.getElementById("dayModal");
const modalTitle = document.getElementById("modalTitle");
const modalEvents = document.getElementById("modalEvents");
const modalEmpty = document.getElementById("modalEmpty");
const closeModalBtn = document.getElementById("closeModal");
const modalBackdrop = document.getElementById("modalBackdrop");
const colorIndexKey = "planificacion.colorIndex";
let colorIndex = parseInt(localStorage.getItem(colorIndexKey) || "0", 10);
let currentTodosQuery = "";
let currentTodosPage = 1;
const TODOS_PAGE_SIZE = 8;
let todoPagination = null;
document.getElementById("prevMonth").addEventListener("click", () => navigateMonth(-1));
document.getElementById("nextMonth").addEventListener("click", () => navigateMonth(1));
function pad(n){return String(n).padStart(2,"0");}
function keyFromDate(d){return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;}
function save(){localStorage.setItem(storageKey, JSON.stringify(events));}
function saveTodos(){localStorage.setItem(todosKey, JSON.stringify(todos));}
if(todos.length === 0){
  todos = [
    {id:"t1", text:"Revisar correo", color: COLORS[0]},
    {id:"t2", text:"Preparar reporte semanal", color: COLORS[1]},
    {id:"t3", text:"Reunión con equipo", color: COLORS[2]},
    {id:"t4", text:"Actualizar documentación", color: COLORS[3]},
    {id:"t5", text:"Llamar a proveedor", color: COLORS[4]}
  ];
  saveTodos();
}
if (todos.some(t=>!t.color)) {
  let idx = 0;
  todos = todos.map(t=>{
    if(!t.color){
      const c = COLORS[idx % COLORS.length];
      idx++;
      return {...t, color: c};
    }
    return t;
  });
  saveTodos();
}
function getActiveColors(){
  const set = new Set();
  todos.forEach(t=> t.color && set.add(t.color));
  Object.values(events).forEach(list=>{
    list.forEach(ev=> { if(ev.color) set.add(ev.color); });
  });
  return set;
}
function nextCycleColor(){
  const c = COLORS[colorIndex % COLORS.length];
  colorIndex = (colorIndex + 1) % COLORS.length;
  localStorage.setItem(colorIndexKey, String(colorIndex));
  return c;
}
function pickColor(preferred){
  const used = getActiveColors();
  if(preferred && !used.has(preferred)) return preferred;
  for(const c of COLORS){
    if(!used.has(c)) return c;
  }
  return nextCycleColor();
}
function normalizeTodosColors(){
  const used = getActiveColors();
  const counts = {};
  todos.forEach(t=> {
    if(!t.color) return;
    counts[t.color] = (counts[t.color] || 0) + 1;
  });
  const unused = COLORS.filter(c=>!used.has(c));
  todos = todos.map(t=>{
    if(!t.color) {
      const c = unused.length ? unused.shift() : nextCycleColor();
      return {...t, color: c};
    }
    if(counts[t.color] > 1 && unused.length){
      counts[t.color]--;
      const c = unused.shift();
      return {...t, color: c};
    }
    return t;
  });
  saveTodos();
}
normalizeTodosColors();
function renderHeader(){
  monthLabel.textContent = `${capitalize(monthNames[current.getMonth()])} ${current.getFullYear()}`;
  weekdays.innerHTML = "";
  weekdaysNames.forEach(n=>{
    const div = document.createElement("div");
    div.textContent = n;
    weekdays.appendChild(div);
  });
}
function capitalize(s){return s.charAt(0).toUpperCase()+s.slice(1);}
function renderDays(){
  days.innerHTML = "";
  const year = current.getFullYear();
  const month = current.getMonth();
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const startWeekday = firstDay.getDay();
  const total = lastDay.getDate();
  for(let i=0;i<startWeekday;i++){
    const empty = document.createElement("div");
    empty.className = "day";
    empty.style.visibility = "hidden";
    days.appendChild(empty);
  }
  for(let d=1; d<=total; d++){
    const date = new Date(year, month, d);
    const cell = document.createElement("button");
    cell.type = "button";
    cell.className = "day";
    const key = keyFromDate(date);
    cell.dataset.date = key;
    const number = document.createElement("div");
    number.className = "day-number";
    number.textContent = d;
    cell.appendChild(number);
    const dots = document.createElement("div");
    dots.className = "dots";
    const list = events[key] || [];
    const maxRows = 2;
    const maxPerRow = 8;
    const maxDots = maxRows * maxPerRow;
    const maxVisibleDots = maxDots - 1;
    const totalEvents = list.length;
    const showMore = totalEvents > maxDots;
    const visibleCount = showMore ? Math.min(totalEvents, maxVisibleDots) : Math.min(totalEvents, maxDots);
    for(let i=0; i<visibleCount; i++){
      const ev = list[i];
      const dot = document.createElement("span");
      dot.className = "dot";
      dot.style.background = ev.color || COLORS[0];
      dots.appendChild(dot);
    }
    if(showMore){
      const remaining = totalEvents - maxVisibleDots;
      const more = document.createElement("span");
      more.className = "dot dot-more";
      more.textContent = remaining > 9 ? "9+" : `+${remaining}`;
      dots.appendChild(more);
    }
    cell.appendChild(dots);
    if(isToday(date)) cell.classList.add("today");
    if(isSameDay(date, selected)) cell.classList.add("selected");
    cell.addEventListener("click", ()=>{
      selected = date;
      renderDays();
      openDayModal();
    });
    cell.addEventListener("dragover", (e)=>{
      e.preventDefault();
      e.dataTransfer.dropEffect = "move";
      cell.classList.add("drop-target");
    });
    cell.addEventListener("dragleave", ()=>{
      cell.classList.remove("drop-target");
    });
    cell.addEventListener("drop", (e)=>{
      e.preventDefault();
      cell.classList.remove("drop-target");
      const id = e.dataTransfer.getData("text/plain");
      const task = todos.find(t => t.id === id);
      if(!task) return;
      const dayKey = cell.dataset.date;
      if(!events[dayKey]) events[dayKey] = [];
      const chosen = pickColor(task.color || COLORS[0]);
      events[dayKey].push({title: task.text, start: "09:00", end: "10:00", color: chosen});
      todos = todos.filter(t => t.id !== id);
      localStorage.setItem(todosKey, JSON.stringify(todos));
      save();
      renderTodos();
      renderDays();
      if(dayModal && dayModal.getAttribute("aria-hidden")==="false") openDayModal();
    });
    days.appendChild(cell);
  }
}
function isToday(d){
  const t = new Date();
  return isSameDay(d, t);
}
function isSameDay(a,b){
  return a.getFullYear()===b.getFullYear() && a.getMonth()===b.getMonth() && a.getDate()===b.getDate();
}
function navigateMonth(step){
  current = new Date(current.getFullYear(), current.getMonth()+step, 1);
  renderAll();
}
function ensureTodoPaginationContainer(){
  if(!todoList) return;
  if(todoPagination) return;
  const container = document.createElement("div");
  container.id = "todoPagination";
  container.className = "todo-pagination";
  if(searchForm && todoList.parentNode){
    todoList.parentNode.insertBefore(container, searchForm);
  }else if(todoList.parentNode){
    todoList.parentNode.insertBefore(container, todoList.nextSibling);
  }
  todoPagination = container;
}
function renderTodoPagination(totalItems, totalPages){
  ensureTodoPaginationContainer();
  if(!todoPagination) return;
  todoPagination.innerHTML = "";
  if(totalItems <= TODOS_PAGE_SIZE){
    todoPagination.style.display = "none";
    return;
  }
  todoPagination.style.display = "flex";
  const info = document.createElement("span");
  info.className = "todo-page-info";
  info.textContent = `Página ${currentTodosPage} de ${totalPages}`;
  const controls = document.createElement("div");
  controls.className = "todo-page-controls";
  const prev = document.createElement("button");
  prev.type = "button";
  prev.textContent = "Anterior";
  prev.disabled = currentTodosPage === 1;
  prev.addEventListener("click", ()=>{
    if(currentTodosPage > 1){
      currentTodosPage--;
      renderTodos();
    }
  });
  const next = document.createElement("button");
  next.type = "button";
  next.textContent = "Siguiente";
  next.disabled = currentTodosPage >= totalPages;
  next.addEventListener("click", ()=>{
    if(currentTodosPage < totalPages){
      currentTodosPage++;
      renderTodos();
    }
  });
  controls.appendChild(prev);
  controls.appendChild(next);
  todoPagination.appendChild(info);
  todoPagination.appendChild(controls);
}
function renderTodos(query){
  if(!todoList) return;
  if(typeof query === "string"){
    currentTodosQuery = query.trim().toLowerCase();
    currentTodosPage = 1;
  }
  todoList.innerHTML = "";
  const q = currentTodosQuery;
  const filtered = todos.filter(t=> t.text.toLowerCase().includes(q));
  const totalItems = filtered.length;
  const totalPages = totalItems === 0 ? 1 : Math.ceil(totalItems / TODOS_PAGE_SIZE);
  if(currentTodosPage > totalPages) currentTodosPage = totalPages;
  const startIndex = (currentTodosPage - 1) * TODOS_PAGE_SIZE;
  const pageItems = filtered.slice(startIndex, startIndex + TODOS_PAGE_SIZE);
  pageItems.forEach(t=>{
    const li = document.createElement("li");
    li.className = "todo-item";
      const sw = document.createElement("span");
      sw.className = "swatch";
      sw.style.background = t.color || COLORS[0];
      const txt = document.createElement("span");
      txt.textContent = t.text;
      li.appendChild(sw);
      li.appendChild(txt);
    li.dataset.id = t.id;
    li.draggable = true;
    li.addEventListener("dragstart",(e)=>{
      e.dataTransfer.setData("text/plain", t.id);
      e.dataTransfer.effectAllowed = "move";
    });
    todoList.appendChild(li);
  });
  renderTodoPagination(totalItems, totalPages);
}
if (searchForm) {
  searchForm.addEventListener("submit", e=>{
    e.preventDefault();
    renderTodos(searchInput.value);
  });
}
if (searchInput) {
  searchInput.addEventListener("input", ()=>{
    renderTodos(searchInput.value);
  });
}
if (clearSearchBtn) {
  clearSearchBtn.addEventListener("click", ()=>{
    if(!searchInput) return;
    searchInput.value = "";
    renderTodos("");
  });
}
function resetAndLoadSamples(){
  events = {};
  save();
  colorIndex = 0;
  localStorage.setItem(colorIndexKey, "0");
  todos = [];
  for(let i=1;i<=24;i++){
    const color = pickColor();
    todos.push({id:`s${i}`, text:`Tarea de prueba ${i}`, color});
  }
  saveTodos();
  renderTodos("");
  renderDays();
}
if(loadSamplesBtn){
  loadSamplesBtn.addEventListener("click", resetAndLoadSamples);
}
if(resetAllBtn){
  resetAllBtn.addEventListener("click", ()=>{
    events = {};
    save();
    todos = [];
    saveTodos();
    colorIndex = 0;
    localStorage.setItem(colorIndexKey, "0");
    renderTodos("");
    renderDays();
  });
}
function openDayModal(){
  if(!dayModal || !modalTitle || !modalEvents || !modalEmpty) return;
  dayModal.setAttribute("aria-hidden","false");
  modalTitle.textContent = selected.toLocaleDateString("es-ES",{weekday:"long", day:"numeric", month:"long", year:"numeric"});
  const key = keyFromDate(selected);
  const list = events[key] || [];
  modalEvents.innerHTML = "";
  if(list.length === 0){
    modalEmpty.style.display = "block";
    return;
  }
  modalEmpty.style.display = "none";
  list.slice().sort((a,b)=>a.start.localeCompare(b.start)).forEach(ev=>{
    const li = document.createElement("li");
    li.className = "event";
    const bar = document.createElement("div");
    bar.className = "bar";
    bar.style.background = ev.color || COLORS[0];
    const content = document.createElement("div");
    content.className = "content";
    const title = document.createElement("div");
    title.className = "title";
    title.textContent = ev.title;
    const time = document.createElement("div");
    time.className = "time";
    time.textContent = `${ev.start}–${ev.end}`;
    content.appendChild(title);
    content.appendChild(time);
    li.appendChild(bar);
    li.appendChild(content);
    modalEvents.appendChild(li);
  });
}
function closeDayModal(){
  if(!dayModal) return;
  dayModal.setAttribute("aria-hidden","true");
}
if(closeModalBtn) closeModalBtn.addEventListener("click", closeDayModal);
if(modalBackdrop) modalBackdrop.addEventListener("click", closeDayModal);
function renderAll(){
  renderHeader();
  renderDays();
  renderTodos();
}
renderAll();

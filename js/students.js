const API = '/api/students';
let currentPage = 1, lastPage = 1;
let sortKey = 'last_name', sortDir = 'asc';
let currentRows = [];
let useMock = false;

// ---- Auth ----
function getToken() {
  return localStorage.getItem('students_token');
}

function requireAuth() {
  if (!getToken()) {
    window.location.href = '/login';
    return false;
  }
  return true;
}

function authHeaders(extra = {}) {
  return { ...extra, 'Authorization': `Bearer ${getToken()}` };
}

async function authFetch(url, options = {}) {
  const res = await fetch(url, {
    ...options,
    headers: authHeaders(options.headers || {}),
  });
  if (res.status === 401) {
    localStorage.removeItem('students_token');
    window.location.href = '/login';
    throw new Error('Unauthorized');
  }
  return res;
}

function logout() {
  authFetch('/api/logout', { method: 'POST' }).catch(() => {});
  localStorage.removeItem('students_token');
  window.location.href = '/login';
}

// ---- Mock fallback so the page still shows something if the API isn't reachable ----
const MOCK = Array.from({ length: 23 }).map((_, i) => ({
  id: 'mock-' + i,
  first_name: ['Ana', 'Miguel', 'Reyna', 'Josef', 'Liwayway', 'Carlo', 'Dianne', 'Emman'][i % 8],
  last_name: ['Santos', 'Reyes', 'Dela Cruz', 'Bautista', 'Villanueva', 'Cortez', 'Aquino', 'Ramos'][i % 8],
  email: `student${i + 1}@campus.edu`,
  age: 18 + (i % 6),
  course: ['BS Computer Science', 'BS Nursing', 'BS Accountancy', 'BA Psychology'][i % 4],
  year_level: (i % 4) + 1,
  status: i % 3 === 0 ? 'inactive' : 'active',
}));

function mockQuery({ search, course, status, page, per_page }) {
  let rows = MOCK.slice();
  if (search) { const s = search.toLowerCase(); rows = rows.filter(r => (`${r.first_name} ${r.last_name} ${r.email}`).toLowerCase().includes(s)); }
  if (course) { const c = course.toLowerCase(); rows = rows.filter(r => r.course.toLowerCase().includes(c)); }
  if (status) rows = rows.filter(r => r.status === status);
  rows.sort((a, b) => {
    let av = a[sortKey], bv = b[sortKey];
    if (typeof av === 'string') { av = av.toLowerCase(); bv = bv.toLowerCase(); }
    return (av < bv ? -1 : av > bv ? 1 : 0) * (sortDir === 'asc' ? 1 : -1);
  });
  const total = rows.length;
  const start = (page - 1) * per_page;
  const pageRows = rows.slice(start, start + per_page);
  return { data: pageRows, meta: { current_page: page, last_page: Math.max(1, Math.ceil(total / per_page)), total } };
}

function toast(message, tone = 'moss') {
  const colors = { moss: 'var(--moss)', brick: 'var(--brick)', brass: 'var(--brass)' };
  const el = document.createElement('div');
  el.className = 'toast font-mono text-xs px-4 py-2.5 rounded-md text-white shadow-lg';
  el.style.background = colors[tone] || colors.moss;
  el.textContent = message;
  document.getElementById('toast-stack').appendChild(el);
  setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .25s'; setTimeout(() => el.remove(), 250); }, 2600);
}

function skeletonRows(n = 6) {
  return Array.from({ length: n }).map(() => `
    <tr>
      <td class="pl-5 pr-2 py-3"><div class="skeleton h-3 w-6 rounded"></div></td>
      <td class="px-3 py-3"><div class="skeleton h-3 w-28 rounded"></div></td>
      <td class="px-3 py-3"><div class="skeleton h-3 w-36 rounded"></div></td>
      <td class="px-3 py-3"><div class="skeleton h-3 w-8 rounded"></div></td>
      <td class="px-3 py-3"><div class="skeleton h-3 w-24 rounded"></div></td>
      <td class="px-3 py-3"><div class="skeleton h-3 w-6 rounded"></div></td>
      <td class="px-3 py-3"><div class="skeleton h-3 w-14 rounded"></div></td>
      <td class="px-5 py-3"><div class="skeleton h-3 w-16 rounded ml-auto"></div></td>
    </tr>`).join('');
}

async function loadStats() {
  try {
    const res = await authFetch(`${API}/statistics`);
    if (!res.ok) throw 0;
    const data = await res.json();
    document.getElementById('stat-total').textContent = data.total_students;
    document.getElementById('stat-active').textContent = data.active_students;
    document.getElementById('stat-inactive').textContent = data.inactive_students;
  } catch {
    const total = MOCK.length, active = MOCK.filter(m => m.status === 'active').length;
    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-active').textContent = active;
    document.getElementById('stat-inactive').textContent = total - active;
  }
}

function setSort(key) {
  if (sortKey === key) sortDir = sortDir === 'asc' ? 'desc' : 'asc';
  else { sortKey = key; sortDir = 'asc'; }
  document.querySelectorAll('.sort-arrow').forEach(a => a.classList.remove('active', 'desc'));
  const arrow = document.getElementById('arrow-' + key);
  if (arrow) { arrow.classList.add('active'); if (sortDir === 'desc') arrow.classList.add('desc'); }
  loadStudents(1);
}

async function loadStudents(page = 1) {
  currentPage = page;
  document.getElementById('student-rows').innerHTML = skeletonRows();

  const search = document.getElementById('search').value.trim();
  const course = document.getElementById('filter-course').value.trim();
  const status = document.getElementById('filter-status').value;
  const per_page = 10;

  let json;
  try {
    if (useMock) throw 0;
    const params = new URLSearchParams({ page, per_page, sort: sortKey, direction: sortDir });
    if (search) params.set('search', search);
    if (course) params.set('course', course);
    if (status) params.set('status', status);
    const res = await authFetch(`${API}?${params.toString()}`);
    if (!res.ok) throw 0;
    json = await res.json();
  } catch {
    useMock = true;
    json = mockQuery({ search, course, status, page, per_page });
  }

  currentRows = json.data;
  const rows = document.getElementById('student-rows');

  if (json.data.length === 0) {
    rows.innerHTML = `
      <tr><td colspan="8" class="text-center py-14">
        <p class="font-display text-lg opacity-70">No entries found</p>
        <p class="text-xs opacity-40 mt-1 font-mono">Try clearing filters, or add the first record.</p>
      </td></tr>`;
  } else {
    rows.innerHTML = json.data.map((s, i) => {
      const idx = String((json.meta.current_page - 1) * per_page + i + 1).padStart(3, '0');
      const active = s.status === 'active';
      const isDeleted = s.status === 'deleted' || !!s.deleted_at;

      const actions = isDeleted
        ? `<button onclick="restoreStudent('${s.id}', '${s.first_name} ${s.last_name}')" class="focus-ring text-xs font-medium hover:underline" style="color:var(--moss-dark);">Restore</button>
           <button onclick="forceDeleteStudent('${s.id}', '${s.first_name} ${s.last_name}')" class="focus-ring text-xs font-medium hover:underline" style="color:var(--brick);">Delete</button>`
        : `<button onclick='editStudent(${JSON.stringify(s)})' class="focus-ring text-xs font-medium hover:underline" style="color:var(--brass);">Edit</button>
           <button onclick="deleteStudent('${s.id}', '${s.first_name} ${s.last_name}')" class="focus-ring text-xs font-medium hover:underline" style="color:var(--brick);">Delete</button>`;

      return `
        <tr class="rise-in hover:bg-[var(--paper)] transition cursor-pointer" style="animation-delay:${i * 25}ms" onclick="openDetail('${s.id}')">
          <td class="pl-5 pr-2 py-3 font-mono text-xs opacity-40 border-r" style="border-color:var(--rule);">${idx}</td>
          <td class="px-3 py-3 font-medium">${s.first_name} ${s.last_name}</td>
          <td class="px-3 py-3 opacity-70 font-mono text-xs">${s.email}</td>
          <td class="px-3 py-3 tabular">${s.age}</td>
          <td class="px-3 py-3">${s.course}</td>
          <td class="px-3 py-3 tabular">${s.year_level}</td>
          <td class="px-3 py-3">
            <span class="px-2 py-0.5 rounded-full text-[11px] font-medium" style="background:${active ? '#E7EFE4' : '#EFEAE0'}; color:${active ? 'var(--moss-dark)' : '#6b6355'};">
              ${s.status}
            </span>
          </td>
          <td class="px-5 py-3 text-right space-x-3" onclick="event.stopPropagation()">
            ${actions}
          </td>
        </tr>`;
    }).join('');
  }

  lastPage = json.meta.last_page;
  document.getElementById('pagination-info').textContent =
    `Page ${json.meta.current_page} of ${json.meta.last_page} · ${json.meta.total} total`;
  document.getElementById('prev-page').disabled = json.meta.current_page <= 1;
  document.getElementById('next-page').disabled = json.meta.current_page >= json.meta.last_page;

  loadStats();
}

function changePage(delta) {
  const next = currentPage + delta;
  if (next >= 1 && next <= lastPage) loadStudents(next);
}

function openModal() {
  document.getElementById('modal-title').textContent = 'New Entry';
  document.getElementById('student-form').reset();
  document.getElementById('student-id').value = '';
  document.getElementById('form-error').classList.add('hidden');
  document.getElementById('modal').classList.remove('hidden');
  document.getElementById('modal').classList.add('flex');
  setTimeout(() => document.getElementById('first_name').focus(), 30);
}
function closeModal() {
  document.getElementById('modal').classList.add('hidden');
  document.getElementById('modal').classList.remove('flex');
}

function editStudent(s) {
  document.getElementById('modal-title').textContent = 'Edit Entry';
  document.getElementById('student-id').value = s.id;
  document.getElementById('first_name').value = s.first_name;
  document.getElementById('last_name').value = s.last_name;
  document.getElementById('email').value = s.email;
  document.getElementById('age').value = s.age;
  document.getElementById('year_level').value = s.year_level;
  document.getElementById('course').value = s.course;
  document.getElementById('status').value = s.status;
  document.getElementById('form-error').classList.add('hidden');
  document.getElementById('modal').classList.remove('hidden');
  document.getElementById('modal').classList.add('flex');
}

async function deleteStudent(id, name) {
  if (!confirm(`Remove ${name} from the ledger? This can't be undone.`)) return;
  try {
    if (useMock) throw 0;
    const res = await authFetch(`${API}/${id}`, { method: 'DELETE' });
    if (!res.ok) throw 0;
  } catch {
    const i = MOCK.findIndex(m => m.id === id);
    if (i > -1) MOCK.splice(i, 1);
  }
  toast(`Removed ${name}`, 'brick');
  loadStudents(currentPage);
}

async function restoreStudent(id, name) {
  try {
    if (useMock) throw 0;
    const res = await authFetch(`${API}/${id}/restore`, { method: 'POST' });
    if (!res.ok) throw 0;
  } catch {
    toast('Could not restore', 'brick');
    return;
  }
  toast(`Restored ${name}`, 'moss');
  loadStudents(currentPage);
}

async function forceDeleteStudent(id, name) {
  if (!confirm(`Permanently delete ${name}? This cannot be undone and the record cannot be restored.`)) return;
  try {
    if (useMock) throw 0;
    const res = await authFetch(`${API}/${id}/force`, { method: 'DELETE' });
    if (!res.ok) throw 0;
  } catch {
    toast('Could not permanently delete', 'brick');
    return;
  }
  toast(`Permanently deleted ${name}`, 'brick');
  loadStudents(currentPage);
}

function openDetail(id) {
  const s = currentRows.find(r => String(r.id) === String(id));
  if (!s) return;
  const active = s.status === 'active';
  const isDeleted = s.status === 'deleted' || !!s.deleted_at;

  const detailActions = isDeleted
    ? `<button onclick="closeDetail(); restoreStudent('${s.id}', '${s.first_name} ${s.last_name}')" class="focus-ring flex-1 px-4 py-2 rounded-md text-white text-sm font-medium" style="background:var(--moss-dark);">Restore</button>
       <button onclick="closeDetail(); forceDeleteStudent('${s.id}', '${s.first_name} ${s.last_name}')" class="focus-ring px-4 py-2 rounded-md border text-sm" style="border-color:var(--brick); color:var(--brick);">Delete</button>`
    : `<button onclick='closeDetail(); editStudent(${JSON.stringify(s)})' class="focus-ring flex-1 px-4 py-2 rounded-md text-white text-sm font-medium" style="background:var(--ink);">Edit entry</button>
       <button onclick="closeDetail(); deleteStudent('${s.id}', '${s.first_name} ${s.last_name}')" class="focus-ring px-4 py-2 rounded-md border text-sm" style="border-color:var(--brick); color:var(--brick);">Delete</button>`;

  document.getElementById('detail-content').innerHTML = `
    <p class="text-[11px] uppercase tracking-[0.2em] font-mono opacity-40 mb-1">Record</p>
    <h2 class="font-display text-2xl mb-4">${s.first_name} ${s.last_name}</h2>
    <div class="space-y-3 text-sm">
      <div class="flex justify-between border-b pb-2" style="border-color:var(--rule);"><span class="opacity-50">Email</span><span class="font-mono text-xs">${s.email}</span></div>
      <div class="flex justify-between border-b pb-2" style="border-color:var(--rule);"><span class="opacity-50">Age</span><span class="tabular">${s.age}</span></div>
      <div class="flex justify-between border-b pb-2" style="border-color:var(--rule);"><span class="opacity-50">Course</span><span>${s.course}</span></div>
      <div class="flex justify-between border-b pb-2" style="border-color:var(--rule);"><span class="opacity-50">Year level</span><span class="tabular">${s.year_level}</span></div>
      <div class="flex justify-between pb-2"><span class="opacity-50">Status</span>
        <span class="px-2 py-0.5 rounded-full text-[11px] font-medium" style="background:${active ? '#E7EFE4' : '#EFEAE0'}; color:${active ? 'var(--moss-dark)' : '#6b6355'};">${s.status}</span>
      </div>
    </div>
    <div class="flex gap-3 mt-6">
      ${detailActions}
    </div>
  `;
  document.getElementById('detail-backdrop').classList.remove('hidden');
  document.getElementById('detail-panel').classList.remove('closed');
}
function closeDetail() {
  document.getElementById('detail-panel').classList.add('closed');
  setTimeout(() => document.getElementById('detail-backdrop').classList.add('hidden'), 280);
}

function exportCSV() {
  if (!currentRows.length) { toast('Nothing to export', 'brass'); return; }
  const headers = ['first_name', 'last_name', 'email', 'age', 'course', 'year_level', 'status'];
  const csv = [headers.join(',')].concat(
    currentRows.map(r => headers.map(h => `"${String(r[h]).replace(/"/g, '""')}"`).join(','))
  ).join('\n');
  const blob = new Blob([csv], { type: 'text/csv' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'students.csv';
  a.click();
  toast('CSV exported', 'moss');
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') { closeModal(); closeDetail(); }
});

document.addEventListener('DOMContentLoaded', () => {
  if (!requireAuth()) return;

  document.getElementById('student-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('student-id').value;
    const payload = {
      first_name: document.getElementById('first_name').value,
      last_name: document.getElementById('last_name').value,
      email: document.getElementById('email').value,
      age: parseInt(document.getElementById('age').value),
      year_level: parseInt(document.getElementById('year_level').value),
      course: document.getElementById('course').value,
      status: document.getElementById('status').value,
    };

    try {
      if (useMock) throw 0;
      const url = id ? `${API}/${id}` : API;
      const method = id ? 'PUT' : 'POST';
      const res = await authFetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      });
      if (!res.ok) {
        const err = await res.json();
        const errEl = document.getElementById('form-error');
        errEl.textContent = err.message || 'Something went wrong.';
        errEl.classList.remove('hidden');
        return;
      }
    } catch {
      if (id) {
        const i = MOCK.findIndex(m => m.id === id);
        if (i > -1) MOCK[i] = { ...MOCK[i], ...payload };
      } else {
        MOCK.unshift({ id: 'mock-' + Date.now(), ...payload });
      }
    }

    closeModal();
    toast(id ? 'Entry updated' : 'Entry added', 'moss');
    loadStudents(currentPage);
  });

  document.getElementById('arrow-last_name').classList.add('active');
  loadStudents();
});

// Expose functions used by inline onclick handlers in the Blade markup
window.loadStudents = loadStudents;
window.changePage = changePage;
window.setSort = setSort;
window.openModal = openModal;
window.closeModal = closeModal;
window.editStudent = editStudent;
window.deleteStudent = deleteStudent;
window.restoreStudent = restoreStudent;
window.forceDeleteStudent = forceDeleteStudent;
window.openDetail = openDetail;
window.closeDetail = closeDetail;
window.exportCSV = exportCSV;
window.logout = logout;
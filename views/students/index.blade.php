<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Records — Ledger</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,500;0,6..72,600;1,6..72,500&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
@vite(['resources/css/students.css', 'resources/js/students.js'])
</head>
<body class="min-h-screen">

  <header class="text-[var(--paper)] border-b-2" style="background:var(--ink); border-color:var(--brass);">
    <div class="max-w-6xl mx-auto px-6 py-6 flex items-center justify-between flex-wrap gap-4">
      <div>
        <p class="text-[11px] uppercase tracking-[0.25em] font-medium" style="color:var(--brass);">Office of the Registrar</p>
        <h1 class="font-display text-3xl mt-1">Student Ledger</h1>
      </div>
      <div class="flex gap-6 text-sm font-mono">
        <div class="text-right">
          <p class="text-2xl tabular" id="stat-total">—</p>
          <p class="opacity-50 text-[10px] uppercase tracking-widest font-sans">Entries</p>
        </div>
        <div class="text-right">
          <p class="text-2xl tabular" style="color:#8BAE7F;" id="stat-active">—</p>
          <p class="opacity-50 text-[10px] uppercase tracking-widest font-sans">Active</p>
        </div>
        <div class="text-right">
          <p class="text-2xl tabular opacity-50" id="stat-inactive">—</p>
          <p class="opacity-50 text-[10px] uppercase tracking-widest font-sans">Inactive</p>
        </div>
        <button onclick="logout()" class="focus-ring text-xs uppercase tracking-widest opacity-60 hover:opacity-100 self-start ml-2" style="color:var(--paper);">
          Sign out
        </button>
      </div>
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-6 py-8">

    <div class="flex flex-wrap gap-3 items-center justify-between mb-6">
      <div class="flex flex-wrap gap-3 flex-1">
        <input id="search" type="text" placeholder="Search name or email…"
          class="focus-ring px-4 py-2 rounded-md border bg-white text-sm w-56" style="border-color:var(--rule);">
        <input id="filter-course" type="text" placeholder="Course"
          class="focus-ring px-4 py-2 rounded-md border bg-white text-sm w-36" style="border-color:var(--rule);">
        <select id="filter-status" class="focus-ring px-4 py-2 rounded-md border bg-white text-sm" style="border-color:var(--rule);">
          <option value="">Any status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="deleted">Deleted</option>
        </select>
        <button onclick="loadStudents(1)" class="focus-ring px-4 py-2 rounded-md text-white text-sm font-medium transition" style="background:var(--ink);" onmouseover="this.style.background='#2b3547'" onmouseout="this.style.background='var(--ink)'">
          Filter
        </button>
        <button onclick="exportCSV()" class="focus-ring px-4 py-2 rounded-md border text-sm font-medium transition hover:bg-white" style="border-color:var(--rule); color:var(--ink);">
          Export CSV
        </button>
      </div>
      <button onclick="openModal()" class="focus-ring px-5 py-2 rounded-md text-white text-sm font-medium transition shadow-sm" style="background:var(--moss);" onmouseover="this.style.background='var(--moss-dark)'" onmouseout="this.style.background='var(--moss)'">
        + New Entry
      </button>
    </div>

    <div class="bg-white rounded-lg border overflow-hidden shadow-sm" style="border-color:var(--rule);">
      <table class="w-full text-sm">
        <thead class="text-[11px] uppercase tracking-wide" style="background:#EFEAE0; color:#6b6355;">
          <tr>
            <th class="text-left pl-5 pr-2 py-3 font-medium font-mono w-14">No.</th>
            <th class="th-sort text-left px-3 py-3 font-medium" onclick="setSort('last_name')">
              Name <span class="sort-arrow" id="arrow-last_name">▾</span>
            </th>
            <th class="text-left px-3 py-3 font-medium">Email</th>
            <th class="th-sort text-left px-3 py-3 font-medium" onclick="setSort('age')">
              Age <span class="sort-arrow" id="arrow-age">▾</span>
            </th>
            <th class="text-left px-3 py-3 font-medium">Course</th>
            <th class="th-sort text-left px-3 py-3 font-medium" onclick="setSort('year_level')">
              Year <span class="sort-arrow" id="arrow-year_level">▾</span>
            </th>
            <th class="text-left px-3 py-3 font-medium">Status</th>
            <th class="text-right px-5 py-3 font-medium">Actions</th>
          </tr>
        </thead>
        <tbody id="student-rows" class="divide-y" style="border-color:var(--rule);"></tbody>
      </table>
    </div>

    <div class="flex items-center justify-between mt-4 text-sm opacity-60">
      <span id="pagination-info" class="font-mono text-xs"></span>
      <div class="flex gap-2">
        <button id="prev-page" onclick="changePage(-1)" class="focus-ring px-3 py-1.5 rounded-md border hover:bg-white transition disabled:opacity-30" style="border-color:var(--rule);" disabled>Prev</button>
        <button id="next-page" onclick="changePage(1)" class="focus-ring px-3 py-1.5 rounded-md border hover:bg-white transition disabled:opacity-30" style="border-color:var(--rule);" disabled>Next</button>
      </div>
    </div>
  </main>

  <!-- Add/Edit Modal -->
  <div id="modal" class="hidden fixed inset-0 items-center justify-center px-4 z-50 backdrop" style="background:rgba(27,36,48,.5);">
    <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl rise-in">
      <h2 class="font-display text-xl mb-4" id="modal-title">New Entry</h2>
      <form id="student-form" class="space-y-3">
        <input type="hidden" id="student-id">
        <div class="grid grid-cols-2 gap-3">
          <input required id="first_name" placeholder="First name" class="focus-ring px-3 py-2 rounded-md border text-sm" style="border-color:var(--rule);">
          <input required id="last_name" placeholder="Last name" class="focus-ring px-3 py-2 rounded-md border text-sm" style="border-color:var(--rule);">
        </div>
        <input required type="email" id="email" placeholder="Email" class="focus-ring w-full px-3 py-2 rounded-md border text-sm" style="border-color:var(--rule);">
        <div class="grid grid-cols-3 gap-3">
          <input required type="number" id="age" placeholder="Age" min="15" class="focus-ring px-3 py-2 rounded-md border text-sm" style="border-color:var(--rule);">
          <input required type="number" id="year_level" placeholder="Year" min="1" max="4" class="focus-ring px-3 py-2 rounded-md border text-sm" style="border-color:var(--rule);">
          <select id="status" class="focus-ring px-3 py-2 rounded-md border text-sm" style="border-color:var(--rule);">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <input required id="course" placeholder="Course" class="focus-ring w-full px-3 py-2 rounded-md border text-sm" style="border-color:var(--rule);">

        <p id="form-error" class="text-xs hidden" style="color:var(--brick);"></p>

        <div class="flex gap-3 pt-2">
          <button type="submit" class="focus-ring flex-1 px-4 py-2 rounded-md text-white text-sm font-medium transition" style="background:var(--ink);">Save entry</button>
          <button type="button" onclick="closeModal()" class="focus-ring px-4 py-2 rounded-md border text-sm hover:bg-[var(--paper)] transition" style="border-color:var(--rule);">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Detail slide-over -->
  <div id="detail-backdrop" class="hidden fixed inset-0 z-40 backdrop" style="background:rgba(27,36,48,.35);" onclick="closeDetail()"></div>
  <aside id="detail-panel" class="slide-panel closed fixed top-0 right-0 h-full w-full max-w-sm bg-white z-50 shadow-2xl px-6 py-6 overflow-y-auto">
    <button onclick="closeDetail()" class="focus-ring text-xs uppercase tracking-widest opacity-50 hover:opacity-100 mb-6">← Close</button>
    <div id="detail-content"></div>
  </aside>

  <!-- Toasts -->
  <div id="toast-stack" class="fixed bottom-5 right-5 z-[60] space-y-2"></div>

</body>
</html>
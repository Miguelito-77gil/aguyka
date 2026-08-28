<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — Student Ledger</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,500;0,6..72,600;1,6..72,500&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
@vite(['resources/css/students.css'])
</head>
<body class="min-h-screen flex items-center justify-center">

  <div class="bg-white rounded-lg border p-8 w-full max-w-sm shadow-sm" style="border-color:var(--rule);">
    <p class="text-[11px] uppercase tracking-[0.25em] font-medium mb-1" style="color:var(--brass);">Office of the Registrar</p>
    <h1 class="font-display text-2xl mb-6">Sign In</h1>

    <form id="login-form" class="space-y-3">
      <input required type="email" id="email" placeholder="Email" class="focus-ring w-full px-3 py-2 rounded-md border text-sm" style="border-color:var(--rule);">
      <input required type="password" id="password" placeholder="Password" class="focus-ring w-full px-3 py-2 rounded-md border text-sm" style="border-color:var(--rule);">

      <p id="login-error" class="text-xs hidden" style="color:var(--brick);"></p>

      <button type="submit" class="focus-ring w-full px-4 py-2 rounded-md text-white text-sm font-medium transition" style="background:var(--ink);">
        Sign In
      </button>
    </form>
  </div>

  <script>
    document.getElementById('login-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const email = document.getElementById('email').value;
      const password = document.getElementById('password').value;
      const errEl = document.getElementById('login-error');
      errEl.classList.add('hidden');

      try {
        const res = await fetch('/api/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ email, password }),
        });
        const data = await res.json();

        if (!res.ok) {
          errEl.textContent = data.message || 'Invalid credentials.';
          errEl.classList.remove('hidden');
          return;
        }

        localStorage.setItem('students_token', data.token);
        window.location.href = '/students-ui';
      } catch {
        errEl.textContent = 'Could not reach the server.';
        errEl.classList.remove('hidden');
      }
    });
  </script>

</body>
</html>
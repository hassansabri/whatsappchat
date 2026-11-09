(() => {
  const base = (document.body.dataset.base || '').replace(/\/$/, '');
  const modalEl = document.getElementById('addFriendModal');
  const input = document.getElementById('friendSearchInput');
  const results = document.getElementById('friendSearchResults');
  const status = document.getElementById('friendSearchStatus');
  const flash = document.getElementById('friendsFlash');
  if (!modalEl || !input || !results) return;

  let timer = null;
  let lastQuery = null;

  const escapeHtml = (value) => String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

  const showFlash = (type, message) => {
    if (!flash) return;
    flash.className = `alert alert-${type}`;
    flash.textContent = message;
  };

  let showUsername = false;

  const renderUsers = (users) => {
    if (!users.length) {
      results.innerHTML = '<div class="list-group-item text-secondary border-0 px-0">No matching users found.</div>';
      return;
    }

    results.innerHTML = users.map((u) => `
      <div class="list-group-item px-0 py-3 d-flex align-items-center gap-3 border-0 border-bottom" data-user-id="${u.id}">
        ${u.avatar_url
          ? `<div class="avatar has-image ${u.online ? 'online' : ''}"><img src="${escapeHtml(u.avatar_url)}" alt=""></div>`
          : `<div class="avatar ${u.online ? 'online' : ''}" style="background:${escapeHtml(u.avatar_color || '#0d6efd')}">${escapeHtml(u.initials)}</div>`
        }
        <div class="flex-grow-1 overflow-hidden">
          <div class="fw-semibold text-truncate">${escapeHtml(u.display_name)}</div>
          <div class="small text-secondary text-truncate">${
            showUsername && u.username
              ? `@${escapeHtml(u.username)}`
              : (u.online ? 'Online' : 'Offline')
          }</div>
        </div>
        <button type="button" class="btn btn-sm btn-primary js-add-friend" data-user-id="${u.id}">
          <i class="bi bi-person-plus"></i> Add
        </button>
      </div>
    `).join('');
  };

  const searchUsers = async (query) => {
    const q = String(query || '').trim();
    lastQuery = q;
    status.textContent = q ? 'Searching…' : 'Loading people you can add…';
    try {
      const res = await fetch(`${base}/friends/search?q=${encodeURIComponent(q)}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      });
      const data = await res.json();
      if (lastQuery !== q) return;
      if (!data.ok) {
        status.textContent = 'Could not load users.';
        results.innerHTML = '';
        return;
      }
      showUsername = !!data.show_username;
      status.textContent = data.users.length
        ? `${data.users.length} user${data.users.length === 1 ? '' : 's'} found`
        : (q ? 'No matches' : 'No more users to add');
      renderUsers(data.users || []);
    } catch (err) {
      status.textContent = 'Search failed. Try again.';
      results.innerHTML = '';
    }
  };

  const scheduleSearch = () => {
    clearTimeout(timer);
    timer = setTimeout(() => searchUsers(input.value), 250);
  };

  modalEl.addEventListener('shown.bs.modal', () => {
    input.value = '';
    results.innerHTML = '';
    input.focus();
    searchUsers('');
  });

  input.addEventListener('input', scheduleSearch);
  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      clearTimeout(timer);
      searchUsers(input.value);
    }
  });

  results.addEventListener('click', async (e) => {
    const btn = e.target.closest('.js-add-friend');
    if (!btn) return;
    const userId = btn.getAttribute('data-user-id');
    if (!userId) return;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    try {
      const res = await fetch(`${base}/friends/add/${userId}`, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      const data = await res.json();
      if (!data.ok) {
        showFlash('danger', data.error || 'Could not send request.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-person-plus"></i> Add';
        return;
      }

      const row = btn.closest('[data-user-id]');
      if (row) row.remove();
      status.textContent = 'Friend request sent.';
      showFlash('success', 'Friend request sent.');
      window.setTimeout(() => {
        window.location.href = `${base}/friends?success=sent`;
      }, 400);
    } catch (err) {
      showFlash('danger', 'Could not send request.');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-person-plus"></i> Add';
    }
  });
})();

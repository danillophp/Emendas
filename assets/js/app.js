document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initCalendar();
  initDataTable();
  initNotificationPolling();
});

function initSidebar() {
  const appShell = document.getElementById('appShell');
  const sidebar = document.getElementById('sidebarNav');
  const sidebarToggle = document.getElementById('sidebarToggle');

  if (!appShell || !sidebar || !sidebarToggle) return;

  sidebarToggle.addEventListener('click', () => appShell.classList.toggle('sidebar-open'));
  document.addEventListener('click', (event) => {
    if (window.innerWidth > 991) return;
    if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
      appShell.classList.remove('sidebar-open');
    }
  });
}

function initCalendar() {
  const calendarEl = document.getElementById('calendar');
  if (!calendarEl || !window.FullCalendar) return;

  const events = JSON.parse(calendarEl.dataset.events || '[]');
  new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    locale: 'pt-br',
    events,
    height: 'auto',
    dayMaxEvents: true,
    buttonText: { today: 'Hoje' },
  }).render();
}

function initDataTable() {
  if (!window.jQuery || !window.jQuery.fn.DataTable) return;

  window.jQuery('.data-table').DataTable({
    pageLength: 10,
    order: [],
    responsive: true,
    language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json' },
  });
}

function initNotificationPolling() {
  const appShell = document.getElementById('appShell');
  if (!appShell) return;

  const pollingUrl = appShell.dataset.pollingUrl;
  const readUrl = appShell.dataset.notificationReadUrl;
  if (!pollingUrl || !readUrl) return;

  const csrfToken = appShell.dataset.csrfToken || '';
  const fallbackListUrl = appShell.dataset.notificationBaseUrl || '#';
  const toastArea = document.getElementById('toastArea');

  let latestId = Number(appShell.dataset.notificationLatestId || 0);
  let nativeAllowed = false;

  if ('Notification' in window) {
    if (Notification.permission === 'granted') {
      nativeAllowed = true;
    } else if (Notification.permission === 'default') {
      // Opcional: não força prompt contínuo, tenta uma vez por sessão.
      Notification.requestPermission().then((permission) => {
        nativeAllowed = permission === 'granted';
      }).catch(() => {
        nativeAllowed = false;
      });
    }
  }

  const poll = async () => {
    try {
      const response = await fetch(`${pollingUrl}?after_id=${latestId}`, {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });

      if (!response.ok) return;

      const payload = await response.json();
      if (!payload.ok) return;

      latestId = Number(payload.latest_id || latestId);
      updateUnreadBadge(payload.unread_count || 0);

      if (!Array.isArray(payload.items) || payload.items.length === 0) return;

      payload.items.forEach((item) => {
        showToast(item, item.link || fallbackListUrl, toastArea, async () => {
          await markNotificationRead(readUrl, csrfToken, Number(item.id || 0));
        });

        if (nativeAllowed) {
          new Notification(item.titulo || 'Notificação', {
            body: item.mensagem || '',
          });
        }
      });
    } catch (error) {
      console.warn('Polling de notificações falhou:', error);
    }
  };

  poll();
  setInterval(poll, 15000);
}

async function markNotificationRead(readUrl, csrfToken, id) {
  if (!id) return;

  const body = new URLSearchParams();
  body.set('_csrf', csrfToken);
  body.set('id', String(id));

  try {
    const response = await fetch(readUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: body.toString(),
    });

    if (!response.ok) return;
    const payload = await response.json();
    if (payload.ok) {
      updateUnreadBadge(payload.unread_count || 0);
    }
  } catch (error) {
    console.warn('Falha ao marcar notificação como lida:', error);
  }
}

function updateUnreadBadge(count) {
  const btn = document.querySelector('button[data-bs-toggle="dropdown"]');
  if (!btn) return;

  let badge = document.getElementById('headerUnreadBadge');

  if (count <= 0) {
    if (badge) badge.remove();
    return;
  }

  if (!badge) {
    badge = document.createElement('span');
    badge.id = 'headerUnreadBadge';
    badge.className = 'badge rounded-pill bg-warning text-dark position-absolute top-0 start-100 translate-middle';
    btn.appendChild(badge);
  }

  badge.textContent = String(count);
}

function showToast(item, destinationUrl, toastArea, onOpen) {
  if (!toastArea || !window.bootstrap) return;

  const title = escapeHtml(item.titulo || 'Notificação');
  const message = escapeHtml(item.mensagem || 'Nova atualização disponível.');
  const timestamp = new Date(item.created_at || Date.now()).toLocaleTimeString('pt-BR', {
    hour: '2-digit',
    minute: '2-digit',
  });

  const wrapper = document.createElement('div');
  wrapper.innerHTML = `
    <div class="toast border-0 shadow-sm" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="6000">
      <div class="toast-header">
        <strong class="me-auto">${title}</strong>
        <small>${timestamp}</small>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body">
        <a class="text-decoration-none" href="${destinationUrl}">${message}</a>
      </div>
    </div>`;

  const toastElement = wrapper.firstElementChild;
  toastArea.appendChild(toastElement);

  const anchor = toastElement.querySelector('a');
  if (anchor && typeof onOpen === 'function') {
    anchor.addEventListener('click', () => onOpen());
  }

  const toast = new window.bootstrap.Toast(toastElement);
  toast.show();
  toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

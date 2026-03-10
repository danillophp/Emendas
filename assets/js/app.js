document.addEventListener('DOMContentLoaded', () => {
  const appShell = document.getElementById('appShell');
  const sidebar = document.getElementById('sidebarNav');
  const sidebarToggle = document.getElementById('sidebarToggle');

  if (appShell && sidebar && sidebarToggle) {
    sidebarToggle.addEventListener('click', () => appShell.classList.toggle('sidebar-open'));
    document.addEventListener('click', (event) => {
      if (window.innerWidth > 991) return;
      if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
        appShell.classList.remove('sidebar-open');
      }
    });
  }

  const calendarEl = document.getElementById('calendar');
  if (calendarEl && window.FullCalendar) {
    const events = JSON.parse(calendarEl.dataset.events || '[]');
    new FullCalendar.Calendar(calendarEl, { initialView: 'dayGridMonth', locale: 'pt-br', events, height: 'auto', dayMaxEvents: true, buttonText: { today: 'Hoje' } }).render();
  }

  if (window.jQuery && window.jQuery.fn.DataTable) {
    window.jQuery('.data-table').DataTable({ pageLength: 10, order: [], responsive: true, language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json' } });
  }

  initNotificationPolling();
});

function initNotificationPolling() {
  const appShell = document.getElementById('appShell');
  if (!appShell || !appShell.dataset.pollingUrl) return;

  let latestId = Number(appShell.dataset.notificationLatestId || 0);
  const pollingUrl = appShell.dataset.pollingUrl;
  const destinationUrl = appShell.dataset.notificationBaseUrl || '#';
  const toastArea = document.getElementById('toastArea');

  const poll = async () => {
    try {
      const response = await fetch(`${pollingUrl}?after_id=${latestId}`, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!response.ok) return;
      const payload = await response.json();
      if (!payload.ok) return;

      latestId = Number(payload.latest_id || latestId);
      updateUnreadBadge(payload.unread_count || 0);

      if (Array.isArray(payload.items) && payload.items.length > 0) {
        payload.items.forEach((item) => showToast(item, destinationUrl, toastArea));
      }
    } catch (error) {
      console.warn('Polling notifications failed', error);
    }
  };

  poll();
  setInterval(poll, 15000);
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

function showToast(item, destinationUrl, toastArea) {
  if (!toastArea || !window.bootstrap) return;

  const title = escapeHtml(item.titulo || 'Notificação');
  const message = escapeHtml(item.mensagem || 'Nova atualização disponível.');
  const timestamp = new Date(item.created_at || Date.now()).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

  const wrapper = document.createElement('div');
  wrapper.innerHTML = `
    <div class="toast border-0 shadow-sm" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
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
  const toast = new window.bootstrap.Toast(toastElement);
  toast.show();

  if ('Notification' in window && Notification.permission === 'granted') {
    new Notification(item.titulo || 'Notificação', { body: item.mensagem || '' });
  }

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

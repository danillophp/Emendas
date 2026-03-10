document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initCalendar();
  initDataTable();
  initRealtimePanelSync();
});

function initSidebar() {
  const appShell = document.getElementById('appShell');
  const sidebar = document.getElementById('sidebarNav');
  const sidebarToggle = document.getElementById('sidebarToggle');
  if (!appShell || !sidebar || !sidebarToggle) return;
  sidebarToggle.addEventListener('click', () => appShell.classList.toggle('sidebar-open'));
  document.addEventListener('click', (event) => {
    if (window.innerWidth > 991) return;
    if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) appShell.classList.remove('sidebar-open');
  });
}

function initCalendar() {
  const calendarEl = document.getElementById('calendar');
  if (!calendarEl || !window.FullCalendar) return;
  const events = JSON.parse(calendarEl.dataset.events || '[]');
  new FullCalendar.Calendar(calendarEl, { initialView: 'dayGridMonth', locale: 'pt-br', events, height: 'auto', dayMaxEvents: true, buttonText: { today: 'Hoje' } }).render();
}

function initDataTable() {
  if (!window.jQuery || !window.jQuery.fn.DataTable) return;
  window.jQuery('.data-table').DataTable({ pageLength: 10, order: [], responsive: true, language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json' } });
}

function initRealtimePanelSync() {
  const appShell = document.getElementById('appShell');
  if (!appShell) return;

  const syncUrl = appShell.dataset.syncUrl;
  const readUrl = appShell.dataset.notificationReadUrl;
  const csrfToken = appShell.dataset.csrfToken || '';
  const fallbackListUrl = appShell.dataset.notificationBaseUrl || '#';
  const masterDemandsUrl = appShell.dataset.masterDemandsUrl || fallbackListUrl;
  const employeeDemandsUrl = appShell.dataset.employeeDemandsUrl || fallbackListUrl;
  const attachmentDownloadUrl = appShell.dataset.attachmentDownloadUrl || '';
  const toastArea = document.getElementById('toastArea');

  if (!syncUrl || !readUrl) return;

  let lastNotificationId = Number(appShell.dataset.notificationLatestId || 0);

  const poll = async () => {
    try {
      const response = await fetch(syncUrl, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!response.ok) return;
      const payload = await response.json();
      if (!payload.ok) return;

      updateUnreadBadge(payload.unread_count || 0);
      renderHeaderNotifications(payload.notifications || [], fallbackListUrl);

      const newItems = (payload.notifications || []).filter((n) => Number(n.id || 0) > lastNotificationId);
      if (newItems.length) {
        lastNotificationId = Math.max(lastNotificationId, ...newItems.map((n) => Number(n.id || 0)));
        newItems.reverse().forEach((item) => {
          const destination = (item.referencia_tabela === 'demandas')
            ? (payload.role === 'master' ? masterDemandsUrl : employeeDemandsUrl)
            : fallbackListUrl;
          showToast(item, item.link || destination, toastArea, () => markNotificationRead(readUrl, csrfToken, Number(item.id || 0)));
        });
      }

      if (payload.role === 'master') {
        renderMasterDemands(payload.demands || [], masterDemandsUrl, attachmentDownloadUrl);
        renderMasterStats(payload.stats || {});
        renderMasterUpcoming(payload.upcoming || []);
        renderMasterDashboardNotifications(payload.notifications || []);
      } else {
        renderEmployeeSummary(payload.summary || {});
      }
    } catch (error) {
      console.warn('Falha no sync parcial:', error);
    }
  };

  poll();
  setInterval(poll, 15000);
}

function statusLabel(status) {
  const map = { pendente: 'Pendente', cadastrada: 'Cadastrada', 'concluído': 'Concluído' };
  return map[status] || status || '-';
}

function statusBadgeClass(status) {
  const map = { pendente: 'badge-pendente', cadastrada: 'badge-cadastrada', 'concluído': 'badge-concluido' };
  return map[status] || 'bg-secondary';
}

function renderHeaderNotifications(items, fallbackUrl) {
  const box = document.getElementById('headerNotificationList');
  if (!box) return;
  if (!items.length) {
    box.innerHTML = '<div class="px-3 py-3 text-muted small">Sem notificações recentes.</div>';
    return;
  }
  box.innerHTML = items.map((n) => `
    <a class="dropdown-item py-2" href="${n.link || fallbackUrl}">
      <div class="small fw-semibold">${escapeHtml(n.titulo || 'Notificação')}</div>
      <div class="small text-muted">${escapeHtml(n.mensagem || '')}</div>
    </a>`).join('');
}

function renderMasterDemands(demands, masterDemandsUrl, attachmentDownloadUrl) {
  const tbody = document.getElementById('masterDemandsBody');
  if (!tbody) return;
  tbody.innerHTML = demands.map((d) => `
    <tr>
      <td><strong>${escapeHtml(d.emenda || '')}</strong><br><small class="text-muted">${escapeHtml(d.funcionario_nome || '-')}</small></td>
      <td>${escapeHtml(d.funcionario_nome || '-')}</td>
      <td>
        <small class="text-muted d-block">Prazo funcionário</small>${formatDate(d.data_prazo_resposta)}
        <small class="text-muted d-block mt-1">Prazo administrativo</small>${formatDate(d.data_cadastro_emenda, true)}
      </td>
      <td><span class="badge badge-status ${statusBadgeClass(d.status)}">${escapeHtml(statusLabel(d.status))}</span></td>
      <td>${d.anexo_emenda ? `<a class="btn btn-sm btn-outline-dark" href="${attachmentDownloadUrl}?inline=1&demand_id=${Number(d.id||0)}" target="_blank">Visualizar</a>` : '<span class="text-muted">-</span>'}</td>
      <td><a class="btn btn-sm btn-outline-primary" href="${masterDemandsUrl}">Abrir painel</a></td>
    </tr>
  `).join('');
}

function renderMasterStats(stats) {
  const map = [
    ['masterStatTotal', stats.total || 0],
    ['masterStatPendente', stats.pendente || 0],
    ['masterStatCadastrada', stats.cadastrada || 0],
    ['masterStatConcluido', stats['concluído'] || 0],
  ];
  map.forEach(([id, val]) => { const el = document.getElementById(id); if (el) el.textContent = String(val); });
}

function renderEmployeeSummary(summary) {
  const map = [
    ['employeeSummaryTotal', summary.total || 0],
    ['employeeSummaryPendente', summary.pendente || 0],
    ['employeeSummaryCadastrada', summary.cadastrada || 0],
    ['employeeSummaryConcluido', summary['concluído'] || 0],
  ];
  map.forEach(([id, val]) => { const el = document.getElementById(id); if (el) el.textContent = String(val); });
}

function renderMasterUpcoming(items) {
  const ul = document.getElementById('masterUpcomingList');
  if (!ul) return;
  ul.innerHTML = items.length ? items.map((i) => `<li class="list-group-item px-0 d-flex justify-content-between align-items-start"><div><strong>${escapeHtml(i.emenda || '')}</strong><br><small class="text-muted">${escapeHtml(i.funcionario_nome || '')}</small></div><small class="badge bg-dark">${formatDate(i.data_prazo_resposta, true)}</small></li>`).join('') : '<li class="list-group-item px-0 text-muted">Nenhuma demanda com prazo crítico.</li>';
}

function renderMasterDashboardNotifications(items) {
  const ul = document.getElementById('masterDashboardNotifications');
  if (!ul) return;
  ul.innerHTML = items.length ? items.map((n) => `<li class="list-group-item px-0"><strong>${escapeHtml(n.titulo || '')}</strong><br><small class="text-muted">${escapeHtml(n.mensagem || '')}</small></li>`).join('') : '<li class="list-group-item px-0 text-muted">Sem notificações recentes.</li>';
}

async function markNotificationRead(readUrl, csrfToken, id) {
  if (!id) return;
  const body = new URLSearchParams(); body.set('_csrf', csrfToken); body.set('id', String(id));
  try {
    const response = await fetch(readUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' }, body: body.toString() });
    if (!response.ok) return;
    const payload = await response.json();
    if (payload.ok) updateUnreadBadge(payload.unread_count || 0);
  } catch (_) {}
}

function updateUnreadBadge(count) {
  const btn = document.querySelector('button[data-bs-toggle="dropdown"]'); if (!btn) return;
  let badge = document.getElementById('headerUnreadBadge');
  if (count <= 0) { if (badge) badge.remove(); return; }
  if (!badge) { badge = document.createElement('span'); badge.id = 'headerUnreadBadge'; badge.className = 'badge rounded-pill bg-warning text-dark position-absolute top-0 start-100 translate-middle'; btn.appendChild(badge); }
  badge.textContent = String(count);
}

function showToast(item, destinationUrl, toastArea, onOpen) {
  if (!toastArea || !window.bootstrap) return;
  const title = escapeHtml(item.titulo || 'Notificação');
  const message = escapeHtml(item.mensagem || 'Nova atualização disponível.');
  const timestamp = new Date(item.created_at || Date.now()).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
  const wrapper = document.createElement('div');
  wrapper.innerHTML = `<div class="toast toast-gray border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="6500"><div class="toast-header toast-gray-header"><strong class="me-auto">${title}</strong><small>${timestamp}</small><button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button></div><div class="toast-body"><a class="text-decoration-none text-light" href="${destinationUrl}">${message}</a></div></div>`;
  const toastElement = wrapper.firstElementChild; toastArea.appendChild(toastElement);
  const anchor = toastElement.querySelector('a'); if (anchor && typeof onOpen === 'function') anchor.addEventListener('click', () => onOpen());
  new window.bootstrap.Toast(toastElement).show();
  toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
}

function formatDate(value, compact = false) {
  if (!value) return '-';
  const date = new Date(String(value).replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return escapeHtml(value);
  return date.toLocaleString('pt-BR', compact ? { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' } : { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function escapeHtml(value) {
  return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

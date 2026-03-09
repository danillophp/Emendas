document.addEventListener('DOMContentLoaded', () => {
  const appShell = document.getElementById('appShell');
  const sidebar = document.getElementById('sidebarNav');
  const sidebarToggle = document.getElementById('sidebarToggle');

  if (appShell && sidebar && sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
      appShell.classList.toggle('sidebar-open');
    });

    document.addEventListener('click', (event) => {
      if (window.innerWidth > 991) return;
      const clickedInsideSidebar = sidebar.contains(event.target);
      const clickedToggle = sidebarToggle.contains(event.target);
      if (!clickedInsideSidebar && !clickedToggle) {
        appShell.classList.remove('sidebar-open');
      }
    });
  }

  const calendarEl = document.getElementById('calendar');
  if (calendarEl && window.FullCalendar) {
    const events = JSON.parse(calendarEl.dataset.events || '[]');
    const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      locale: 'pt-br',
      events,
      height: 'auto',
      dayMaxEvents: true,
      buttonText: { today: 'Hoje' },
    });
    calendar.render();
  }

  if (window.jQuery && window.jQuery.fn.DataTable) {
    $('.data-table').DataTable({
      pageLength: 10,
      order: [],
      responsive: true,
      language: {
        url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json',
      },
    });
  }
});

document.addEventListener('DOMContentLoaded', () => {
  const calendarEl = document.getElementById('calendar');
  if (calendarEl) {
    const events = JSON.parse(calendarEl.dataset.events || '[]');
    const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth', locale: 'pt-br', events, height: 'auto'
    });
    calendar.render();
  }

  if (window.jQuery) {
    $('.data-table').DataTable({
      pageLength: 10,
      language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json' }
    });
  }
});

document.addEventListener('DOMContentLoaded', () => {
  const calendarEl = document.getElementById('calendar');
  if (calendarEl && window.FullCalendar) {
    const events = JSON.parse(calendarEl.dataset.events || '[]');
    const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      locale: 'pt-br',
      events,
      height: 'auto',
      dayMaxEvents: true,
      buttonText: {
        today: 'Hoje',
      },
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

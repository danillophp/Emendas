document.addEventListener('DOMContentLoaded', () => {
    const calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        const events = JSON.parse(calendarEl.dataset.events || '[]');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'pt-br',
            events,
            height: 'auto'
        });
        calendar.render();
    }
});

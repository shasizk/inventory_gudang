export default function realTimeClock(timeZone) {
    let interval;
    const timeFormatter = new Intl.DateTimeFormat('id-ID', {
        timeZone,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hourCycle: 'h23',
    });
    const dateFormatter = new Intl.DateTimeFormat('id-ID', {
        timeZone,
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

    return {
        now: new Date(),
        time: '',
        date: '',

        init() {
            this.update();
            interval = window.setInterval(() => this.update(), 1000);
        },

        update() {
            this.now = new Date();
            this.time = timeFormatter.format(this.now);
            this.date = dateFormatter.format(this.now);
        },

        destroy() {
            window.clearInterval(interval);
        },
    };
}

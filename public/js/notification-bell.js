(() => {
    const bell = document.querySelector('[data-notifications-url]');
    if (!bell) return;

    const badge = bell.querySelector('[data-notifications-count]');
    const storageKey = `expiry-alerts:${bell.dataset.notificationsUser}`;
    let knownAlerts = [];
    let pendingSound = false;
    let audio;
    let refreshing = false;

    try {
        knownAlerts = JSON.parse(sessionStorage.getItem(storageKey) ?? '[]');
    } catch {
        // The bell still works when the browser blocks session storage.
    }

    function chime() {
        if (!pendingSound || audio?.state !== 'running') return;

        pendingSound = false;
        const start = audio.currentTime;
        for (const [index, frequency] of [660, 880].entries()) {
            const note = audio.createOscillator();
            const volume = audio.createGain();
            const time = start + index * 0.12;
            note.frequency.value = frequency;
            volume.gain.setValueAtTime(0.12, time);
            volume.gain.exponentialRampToValueAtTime(0.001, time + 0.16);
            note.connect(volume).connect(audio.destination);
            note.start(time);
            note.stop(time + 0.16);
        }
    }

    async function unlockAudio() {
        if (!window.AudioContext) return;

        try {
            audio ??= new AudioContext();
            await audio.resume();
            chime();
        } catch {
            // Browser playback restrictions do not affect the badge.
        }
    }

    async function refresh() {
        if (refreshing || document.hidden) return;
        refreshing = true;

        try {
            const response = await fetch(bell.dataset.notificationsUrl, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
            });
            if (!response.ok) return;

            const { alerts } = await response.json();
            badge.textContent = alerts.length;
            badge.hidden = alerts.length === 0;
            bell.setAttribute('aria-label', `Notifications (${alerts.length})`);

            const previous = new Set(knownAlerts);
            pendingSound = alerts.length > 0 && (pendingSound || alerts.some(alert => !previous.has(alert)));
            knownAlerts = alerts;
            chime();
            sessionStorage.setItem(storageKey, JSON.stringify(alerts));
        } catch {
            // Keep the last successful count until the next refresh.
        } finally {
            refreshing = false;
        }
    }

    document.addEventListener('pointerdown', unlockAudio, { once: true });
    document.addEventListener('keydown', unlockAudio, { once: true });
    document.addEventListener('visibilitychange', refresh);
    refresh();
    setInterval(refresh, 30_000);
})();

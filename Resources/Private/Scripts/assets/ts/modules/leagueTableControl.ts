function init(): void {
    const leagueTables = document.querySelectorAll<HTMLElement>('.t3sports-leaguetable:not([data-initialized])');

    // Einmalig die Spinner-Animation zum Dokument hinzufügen, falls sie noch nicht existiert
    if (!document.getElementById('t3sports-spinner-css')) {
        const style = document.createElement('style');
        style.id = 't3sports-spinner-css';
        style.innerHTML = `
            @keyframes t3sports-spin { to { transform: rotate(360deg); } }
            .t3-spinner-overlay {
                position: absolute; top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(255, 255, 255, 0.6);
                display: flex; align-items: center; justify-content: center;
                z-index: 100; pointer-events: none;
            }
            .t3-spinner-circle {
                width: 30px; height: 30px;
                border: 3px solid rgba(0,0,0,0.1);
                border-left-color: #0054a6;
                border-radius: 50%;
                animation: t3sports-spin 0.8s linear infinite;
            }
        `;
        document.head.appendChild(style);
    }

    leagueTables.forEach(leagueTable => {
        leagueTable.setAttribute('data-initialized', 'true');
        // Sicherstellen, dass der Container positioniert ist für das Overlay
        if (getComputedStyle(leagueTable).position === 'static') {
            leagueTable.style.position = 'relative';
        }

        leagueTable.addEventListener('click', async (e: MouseEvent) => {
            const link = (e.target as HTMLElement).closest<HTMLAnchorElement>('a');
            if (!link || !link.getAttribute('href')) return;
            
            e.preventDefault();
            const url = link.getAttribute('href')!;

            // Lade-Indikator (Overlay) erstellen und hinzufügen
            const overlay = document.createElement('div');
            overlay.className = 't3-spinner-overlay';
            overlay.innerHTML = '<div class="t3-spinner-circle"></div>';
            leagueTable.appendChild(overlay);

            try {
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newTable = doc.querySelector('.t3sports-leaguetable');
                
                if (newTable) {
                    leagueTable.replaceWith(newTable);
                    init(); // Rekursiver Aufruf für neue Inhalte
                }
            } catch (error) {
                console.error('AJAX Error:', error);
                alert('Fehler beim Laden der Daten.');
                overlay.remove(); // Spinner bei Fehler entfernen
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', init);
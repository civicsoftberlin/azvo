//import './stimulus_bootstrap.js';


import { TabulatorFull as Tabulator } from 'tabulator-tables';
import * as echarts from 'echarts';
import "htmx.org";
import "htmx-ext-sse";
/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/admin.css';


document.addEventListener('DOMContentLoaded', () => {

    let charEl = document.getElementById('chart');

    if (charEl) {
        const myChart = echarts.init(charEl);
        console.info("Element gefunden")
        myChart.setOption({
            tooltip: {},
            xAxis: {
                data: ['shirt', 'cardigan', 'chiffon', 'pants', 'heels', 'socks']
            },
            yAxis: {},
            series: [
                {
                    name: 'sales',
                    type: 'bar',
                    data: [5, 20, 36, 10, 10, 20]
                }
            ]
        });
        window.addEventListener('resize', () => myChart.resize());
    }

    const table = document.getElementById('teacherTable');
    if (table) {
        new Tabulator(table, {
            layout: "fitColumns",
            pagination: "local",
            paginationSize: 25,
            placeholder: "Keine Lehrer gefunden",
            columns: [
                { title: "ID", field: "id" },
                { title: "Kürzel", field: "shortName", formatter: "html" },
                // Für die Spalte mit den Buttons/Links:
                //{ title: "Aktionen", field: "actions", width: 150 }
            ]
            // Tabulator erkennt die Spalten automatisch aus dem HTML-thead!
        });
    }

    function updateStickyTop() {
        const contentHeader = document.querySelector('.content-header');
        if (contentHeader) {
            const headerHeight = contentHeader.offsetHeight;
            document.documentElement.style.setProperty('--sticky-top', headerHeight + 'px');
        }
    }

    const progressBars = document.querySelectorAll('.progress-infinite');

    progressBars.forEach((bar) => {
        // Bootstrap-Transition deaktivieren für flüssige 100%-Animation
        bar.style.transition = 'none';

        let progress = 0;
        let filling = true;
        let isPaused = false; // Steuert die Pause bei 100%

        setInterval(() => {
            // Wenn pausiert, diesen Durchlauf überspringen
            if (isPaused) return;

            if (filling) {
                // Phase 1: Von links füllen
                progress += 1;
                bar.style.marginLeft = '0%';
                bar.style.width = progress + '%';

                if (progress >= 100) {
                    filling = false;
                    isPaused = true; // Pause aktivieren

                    // Nach 1 Sekunde (1000ms) die Pause wieder aufheben
                    setTimeout(() => {
                        isPaused = false;
                    }, 1000);
                }
            } else {
                // Phase 2: Von links leeren
                progress -= 1;
                bar.style.width = progress + '%';
                bar.style.marginLeft = (100 - progress) + '%';

                if (progress <= 0) {
                    filling = true;
                }
            }
        }, 15);
    });

    updateStickyTop();
    window.addEventListener('resize', updateStickyTop);
});

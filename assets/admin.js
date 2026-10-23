//import './stimulus_bootstrap.js';


import { TabulatorFull as Tabulator } from 'tabulator-tables';
/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/admin.css';


document.addEventListener('DOMContentLoaded', () => {
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
});

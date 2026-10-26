### Übersicht und Zusammenfassung

![Dashboard]({{ asset('/docs/overview.png') }}){.img-fluid .img-thumbnail}
*Abbildung 1: Zusammenfassung aller Daten für den aktuellen Monat*

[Übersicht anzeigen]({{ crud_url('') }}){.btn .btn-primary target="_blank"}

#### Aktionen
1. Details zur Lehrkraft aufrufen
2. Stundenkonto anzeigen für Lehrkraft und aktuellen Monat
3. Stundenkontoauszug exportieren für Lehrkraft und aktuellen Monat

### Zeitkonten prüfen
- [Alle Stundenkonten anzeigen]({{ crud_url('overtime_entry') }})
- [Oktober 2026 filtern]({{ crud_url('overtime_entry', {
  'filters[incidentDateMonth]': 10,
  'filters[incidentDateYear]': 2026
  }) }}){target="_blank"}


[Alle Stundenkonten anzeigen]({{ crud_url('untis_import/new') }}){.btn .btn-primary target="_blank"}

![GPU-Datei exportieren]({{ asset('/img/img_9.png') }}){.img-fluid}

Wieso das?

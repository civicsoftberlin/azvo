## Manueller Import

### Import vorbereiten
Bevor Sie neue Daten importieren, achten Sie beim Export in Untis darauf, dass Sie
- **Komma** als Trennzeichen verwenden
- UTF8 abwählen

![GPU-Datei exportieren]({{ asset('/img/img_9.png') }}){.img-fluid .img-thumbnail}
*Abbildung 1: Die wichtigen Einstellungen im Untis-Export-Dialog*

### Import starten
Sind bereits zuvor Daten importiert worden, die auch in der neuen **GPU014.txt** enthalten sind, so werden diese
ignoriert, um Doppelbuchungen zu vermeiden.

![Import starten]({{ asset('/docs/gpu_import_start.png') }}){.img-fluid .img-thumbnail}
*Abbildung 2: Importvorgang starten*

Klicken Sie oben rechts auf **Importieren**, um den Import abzuschließen. Im Anschluss wechselt die Ansicht
automatisch zum [Stundenkonto]({{ crud_url('overtime_entry') }}){target="_blank"}.

[Jetzt GPU importieren]({{ crud_url('untis_import/new') }}){.btn .btn-primary target="_blank"}

### Fehlende Lehrkräfte automatisch erzeugen
Ist diese Option aktiv, werden fehlende Lehrkräfte vor dem eigentlichen Import der Daten automatisch erzeugt.
Stundendeputate/Schuljahre müssen für neue Lehrkräfte manuell erzeugt werden.

![GPU-Datei importieren]({{ asset('/docs/gpu_import.png') }}){.img-fluid .img-thumbnail}
*Abbildung 3: Fehlende Lehrkräfte erzeugen*

## Automatischer Import

Aktivieren Sie den automatischen Import, damit Sie keine GPU-Datei mehr hochladen müssen.
Der Upload findet immer um 00:00 Uhr und um 12:00 Uhr statt. Die Quelle können Sie individuell
einstellen. Als Quelle ist derzeit Ihre Infoscreen-App zu empfehlen.

In Zukunft können Sie auch alle Daten von WebUntis automatisch synchronisieren lassen.

![GPU-Datei importieren]({{ asset('/docs/gpu_auto_import.png') }}){.img-fluid .img-thumbnail}
*Abbildung 4: Daten automatisch importieren*

### Report

Hier können Sie nachlesen, wann der letzte Auto-Import stattgefunden hat, und, ob dieser erfolgreich war.

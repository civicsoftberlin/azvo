## Änderungen importieren
### Import vorbereiten
Bevor Sie neue Daten importieren, achten Sie beim Export in Untis darauf, dass Sie
- **Komma** als Trennzeichen verwenden
- UTF8 abwählen

![GPU-Datei exportieren]({{ asset('/img/img_9.png') }}){.img-fluid .img-thumbnail}

### Import starten
Sind bereits zuvor Daten importiert worden, die auch in der neuen **GPU014.txt** enthalten sind, so werden diese
ignoriert, um Doppelbuchungen zu vermeiden.

[Jetzt GPU importieren]({{ crud_url('untis_import/new') }}){.btn .btn-primary target="_blank"}

### Fehlende Lehrkräfte automatisch erzeugen
Ist diese Option aktiv, werden fehlende Lehrkräfte vor dem eigentlichen Import der Daten automatisch erzeugt.
Stundendeputate/Schuljahre müssen für neue Lehrkräfte manuell erzeugt werden.

![GPU-Datei exportieren]({{ asset('/docs/gpu_import.png') }}){.img-fluid .img-thumbnail}

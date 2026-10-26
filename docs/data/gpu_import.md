### Datenstrukturen

#### GPU014

##### Feld 01
- **Name**:   Vertretungsnummer
- **Format**: Ganze Zahl > 0

##### Feld 02
- **Name**: Datum
- **Format**: YYYYMMDD

##### Feld 03
- **Name**: Stunde
- **Format**: Ganze Zahl >= 0 oder ``null``

##### Feld 04
- **Name**: Absenznummer
- **Format**: Ganze Zahl >= 0

##### Feld 05
- **Name**: Unterrichtsnummer
- **Format**: Ganze Zahl >= 0

ID der im System hinterlegten Unterrichtseinheit.

##### Feld 06
- **Name**: Absente Lehrkraft
- **Format**: Zeichenkette

Kürzel der absenten Lehrkraft

##### Feld 07
- **Name**: Vertretende Lehrkraft
- **Format**: Zeichenkette

Kürzel der vertretenden Lehrkraft

##### Feld 08
- **Name**: Fach
- **Format**: Zeichenkette oder ``(leer)``

Kürzel des betroffenen Fachs

##### Feld 09
- **Name**: Statistikkennzeichen des Fachs
- **Format**: Zeichenkette oder ``(leer)``

Statistikkennzeichen des betroffenen Fachs

##### Feld 10
- **Name**: Vertretungsfach
- **Format**: Zeichenkette oder ``(leer)``

Kürzel des betroffenen Vertretungsfachs

##### Feld 11
- **Name**: Statistikkennzeichen des Vertretungsfachs
- **Format**: Zeichenkette oder ``(leer)``

Statistikkennzeichen des betroffenen Vertretungsfachs

##### Feld 12
- **Name**: Raum
- **Format**: Zeichenkette oder ``(leer)``

##### Feld 13
- **Name**: Vertretungsraum
- **Format**: Zeichenkette oder ``(leer)``

##### Feld 14
- **Name**: Statistik-Kennzeichen
- **Format**: Zeichenkette oder ``(leer)``

##### Feld 15
- **Name**: Klasse(n) (mit ``~`` getrennt)
- **Format**: Zeichenkette oder ``(leer)``

##### Feld 16
- **Name**: Absenzgrund
- **Format**: Zeichenkette oder ``(leer)``

##### Feld 17
- **Name**: Text zur Vertretung
- **Format**: Zeichenkette oder ``(leer)``

#####  Feld 18
- **Name**: Art (Bitfeld)
- **Format**: Dezimalzahl >= 0

Das Feld kann ein oder mehrere gesetzte Bits enthalten, die im Dezimalformat vorliegen.

**Beispiel**:

2.097.154 = 2.097.152 + 2
Das ergibt: Bit 1 und Bit 21, also *Betreuung* und *Kennzeichen neu*



<table class="table table-striped mb-3">
    <thead>
        <tr>
            <th>Bit</th>
            <th class="text-end">Hex-Wert</th>
            <th class="text-end">Dezimal</th>
            <th>Art</th>
            <th>Hinweise</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Bit 0</td>
            <td class="text-end"><code>0x1</code></td>
            <td class="text-end">1</td>
            <td>Entfall</td>
            <td></td>
        </tr>
        <tr>
            <td>Bit 1</td>
            <td class="text-end"><code>0x2</code></td>
            <td class="text-end">2</td>
            <td>Betreuung</td>
            <td></td>
        </tr>
        <tr>
            <td>Bit 2</td>
            <td class="text-end"><code>0x4</code></td>
            <td class="text-end">4</td>
            <td>Sondereinsatz</td>
            <td></td>
        </tr>
        <tr>
            <td>Bit 3</td>
            <td class="text-end"><code>0x8</code></td>
            <td class="text-end">8</td>
            <td>Wegverlegung</td>
            <td></td>
        </tr>
        <tr>
            <td>Bit 4</td>
            <td class="text-end"><code>0x10</code></td>
            <td class="text-end">16</td>
            <td>Freisetzung</td>
            <td></td>
        </tr>
        <tr>
            <td>Bit 5</td>
            <td class="text-end"><code>0x20</code></td>
            <td class="text-end">32</td>
            <td>Plus als Vertreter</td>
            <td></td>
        </tr>
        <tr>
            <td>Bit 6</td>
            <td class="text-end"><code>0x40</code></td>
            <td class="text-end">64</td>
            <td>Teilvertretung</td>
            <td></td>
        </tr>
        <tr>
            <td>Bit 7</td>
            <td class="text-end"><code>0x80</code></td>
            <td class="text-end">128</td>
            <td>Hinverlegung</td>
            <td></td>
        </tr>
        <tr>
            <td>Bit 16</td>
            <td class="text-end"><code>0x10000</code></td>
            <td class="text-end">65.536</td>
            <td>Raumvertretung</td>
            <td></td>
        </tr>
        <tr>
            <td>Bit 17</td>
            <td class="text-end"><code>0x20000</code></td>
            <td class="text-end">131.072</td>
            <td>Pausenaufsichtsvertretung</td>
            <td></td>
        </tr>
        <tr>
            <td>Bit 18</td>
            <td class="text-end"><code>0x40000</code></td>
            <td class="text-end">262.144</td>
            <td>Stunde ist unterrichtsfrei</td>
            <td></td>
        </tr>
        <tr>
            <td>Bit 20</td>
            <td class="text-end"><code>0x100000</code></td>
            <td class="text-end">1.048.576</td>
            <td>Kennzeichen nicht drucken</td>
            <td></td>
        </tr>
        <tr>
            <td>Bit 21</td>
            <td class="text-end"><code>0x200000</code></td>
            <td class="text-end">2.097.152</td>
            <td>Kennzeichen neu</td>
            <td></td>
        </tr>
    </tbody>
</table>

##### Feld 19
- **Name**: Vertretungsklasse(n) (mit ``~`` getrennt)
- **Format**: Zeichenkette oder ``(leer)``

##### Feld 20
- **Name**: Vertretungsart
- **Format**: Zeichen, ``~`` oder ``(leer)``

<table class="table table-striped mb-3">
    <thead>
        <tr>
            <th>Zeichen</th>
            <th>Art</th>
            <th>Hinweise</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>(leer)</code></td>
            <td>Regulärer Unterricht</td>
            <td>Keine Änderung, normale Stunde</td>
        </tr>
        <tr>
            <td><code>T</code></td>
            <td>Verlegt</td>
            <td></td>
        </tr>
        <tr>
            <td><code>F</code></td>
            <td>Verlegt von</td>
            <td></td>
        </tr>
        <tr>
            <td><code>W</code></td>
            <td>Tausch</td>
            <td></td>
        </tr>
        <tr>
            <td><code>S</code></td>
            <td>Betreuung</td>
            <td></td>
        </tr>
        <tr>
            <td><code>A</code></td>
            <td>Sondereinsatz</td>
            <td></td>
        </tr>
        <tr>
            <td><code>C</code></td>
            <td>Entfall</td>
            <td></td>
        </tr>
        <tr>
            <td><code>L</code></td>
            <td>Freisetzung</td>
            <td></td>
        </tr>
        <tr>
            <td><code>P</code></td>
            <td>Teil-Vertretung</td>
            <td></td>
        </tr>
        <tr>
            <td><code>R</code></td>
            <td>Raumvertretung</td>
            <td></td>
        </tr>
        <tr>
            <td><code>B</code></td>
            <td>Pausenaufsichtsvertretung</td>
            <td></td>
        </tr>
        <tr>
            <td><code>~</code></td>
            <td>Lehrertausch</td>
            <td></td>
        </tr>
        <tr>
            <td><code>E</code></td>
            <td>Klausur</td>
            <td></td>
        </tr>
    </tbody>
</table>


##### Feld 21
- **Name**: Zeit der letzten Änderung
- **Format**: YYYYMMDDHHMM

##### Feld 22
- **Name**: Vertretungen/Entfälle zählen
- **Format**: ``+``, ``-``, ``+~-`` oder ``(leer)``

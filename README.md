# Hafen Dashboard — Hamburg

Live-Dashboard für den Hamburger Hafen: Wasserstände (BSH/PEGELONLINE),
Gezeitenvorausberechnung, Wetter, Verkehrslage und Brückensperrmeldungen.

## Deployment (PHP-Webserver)

Benötigt: Apache/PHP-Hosting mit `curl`-Extension (Standard bei jedem Shared Hosting).

1. Dateien ins Zielverzeichnis hochladen (z. B. `/hafen-dashboard/`):
   - `index.html`
   - `api.php`
2. `config.example.php` als `config.php` kopieren und den TomTom API Key eintragen.
   `config.php` **nicht** ins Git-Repo committen (steht in `.gitignore`).
3. Fertig — keine Datenbank, kein Build-Schritt.

## Architektur

- **`index.html`** — komplette Anwendung (statisch, kein Framework)
- **`api.php`** — same-origin Proxy für APIs ohne CORS-Header:
  - `?target=bsh` → BSH Wasserstandsvorhersage (gdi.bsh.de)
  - `?target=mobilithek` → Brückensperrmeldungen (mobilithek.info:8443)
  - `?target=config` → liefert den TomTom-Key aus `config.php` ans Frontend
- **Direkt vom Browser abgerufen** (CORS-fähig): PEGELONLINE, Open-Meteo, TomTom

## Sicherheit

- Content-Security-Policy im HTML-Head
- SRI-Hashes für CDN-Ressourcen (Leaflet, Chart.js)
- Alle externen API-Daten werden vor dem Rendern escaped (`esc()`)
- `api.php`: Ziel-Whitelist, nur same-origin, keine offenen CORS-Header
- API-Keys liegen nur auf dem Server (`config.php`), nie im Repo

## Hinweis Mobilithek

Das Brücken-Abo (HPA Brückensperrmeldungen) verlangt aktuell ein
Client-Zertifikat von Mobilithek. Solange das nicht hinterlegt ist,
zeigt das Dashboard eingebettete Fallback-Daten.

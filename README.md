
# Aufgaben-Management-System API Dokumentation

Dies ist ein einfaches Aufgaben-Management-System, das mit Laravel entwickelt wurde. Das System ermöglicht es Benutzern, Aufgaben zu erstellen, zu bearbeiten, zu löschen und anzuzeigen. Alle API-Endpunkte sind durch Authentifizierung gesichert und können nur von authentifizierten Benutzern verwendet werden.

## Voraussetzungen: Docker

Um das Projekt lokal auszuführen, benötigst du lediglich Docker, das zum Starten der Umgebung und Ausführen der Anwendung verwendet wird.

## Installation

1. Klone das Repository auf deinen lokalen Rechner:
    - `git clone https://github.com/??.git`

2. Gehe in das Projektverzeichnis:
    - `cd aufgaben-management-api`

3. Starte Docker:
    - `./scripts/docker-start`

4. Installiere alle Abhängigkeiten mit Composer:
    - `./scripts/composer install`

5. Migrations:
   - `./scripts/artisan migrate`


### Stop Docker

Um Docker und die Container zu stoppen, führe folgenden Befehl aus:
```
./scripts/docker-stop
```

## API Dokumentation

Die vollständige API-Dokumentation ist unter folgendem Link verfügbar:

- [API-Dokumentation (Scramble UI)](http://localhost/docs/api)  
  Dies ist die interaktive UI, die alle verfügbaren Endpunkte, HTTP-Methoden, Anforderungs- und Antwortbeispiele enthält.

- [OpenAPI Dokument (im JSON-Format)](http://localhost/docs/api.json)  
  Hier ist das OpenAPI-Dokument im JSON-Format, das die vollständige API-Spezifikation beschreibt. Dieses Dokument kann von verschiedenen Tools zur API-Integration und -Generierung verwendet werden, z.B. Swagger, Postman oder für automatisierte Tests.


## API Testen mit Postman

Die Postman-Collection für diese API befindet sich im Projektverzeichnis. Um mit der API zu interagieren, importiere einfach die Datei:

- **Postman-Collection importieren**:
```
./postman/api-collection.json
```

### Schritte:

1. **Postman-Collection importieren**:
   - Öffne Postman und klicke auf "Import".
   - Wähle die Datei `api-collection.json` aus dem Projektverzeichnis und importiere sie.

2. **Login und Token speichern**:
   - Melde dich unter `/api/v1/login` an, um einen **Token** zu erhalten.
   - Speichere den Token in der **Authorization**-Sektion der Collection:
      - Wähle „Bearer Token“ und füge den erhaltenen Token ein.
      - Der Token wird automatisch für alle weiteren Anfragen verwendet, die Authentifizierung benötigen.
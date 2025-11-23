
# Aufgaben-Management-System API

Dies ist ein einfaches Aufgaben-Management-System, das mit Laravel entwickelt wurde. Das System ermöglicht es Benutzern, Aufgaben zu erstellen, zu bearbeiten, zu löschen und anzuzeigen. Alle API-Endpunkte sind durch Authentifizierung gesichert und können nur von authentifizierten Benutzern verwendet werden.

## Voraussetzungen

Um das Projekt lokal auszuführen, stelle sicher, dass du folgende Software installiert hast:

- Docker
- Postman oder ein anderes API-Testwerkzeug (optional)

## Installation

1. Klone das Repository auf deinen lokalen Rechner:
    - `git clone https://github.com/??.git`

2. Gehe in das Projektverzeichnis:
    - `cd aufgaben-management-api`

3. Start Docker:
    - `./scripts/docker-start`

4. Installiere alle Abhängigkeiten mit Composer:
    - `./scripts/composer install`


### Stop Docker

Stop docker containers:
```
./scripts/docker-stop
```


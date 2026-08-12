# Installazione

La guida completa e aggiornata si trova nel [README](README.md#installazione-rapida).

In sintesi:

1. copia `.env.example` in `.env` e configura database e sito;
2. crea il database e importa `sql/schema.sql`;
3. genera una password con `password_hash()` e inserisci il primo amministratore;
4. crea la cartella `uploads` con permessi di scrittura per il server web;
5. apri `/login.php` e accedi con l'account appena creato.

Non esistono credenziali amministrative predefinite. Non pubblicare mai `.env`, dump SQL o contenuti della cartella `uploads`.

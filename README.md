# Blog Vito

Blog tecnico self-hosted realizzato con PHP nativo e MariaDB/MySQL in vibe coding.
Include una homepage pubblica, categorie, commenti, un pannello di amministrazione e un editor visuale per i post.

## Funzionalità

- pubblicazione e modifica di articoli con stato bozza/pubblicato;
- editor WYSIWYG TinyMCE con immagini e contenuti multimediali;
- categorie e pagine filtrate;
- commenti pubblici con moderazione dal pannello admin;
- upload di immagini JPEG, PNG, GIF e WebP fino a 5 MB;
- tema responsive e modalità scura;
- query PDO preparate, token CSRF e password archiviate con hash;
- supporto opzionale all'incorporamento della homepage in Nextcloud.

## Tecnologie

- PHP 8.1 o successivo;
- MariaDB 10.4+ oppure MySQL 5.7+;
- Bootstrap 5 e Bootstrap Icons;
- TinyMCE 6;
- estensioni PHP `pdo_mysql`, `fileinfo` e `gd`.

## Installazione rapida

### 1. Clona il progetto

```bash
git clone https://github.com/dosmaker/blog-vito.git
cd blog-vito
```

### 2. Configura l'ambiente

```bash
cp .env.example .env
```

Apri `.env` e inserisci i parametri del database e l'URL del sito. Il file contiene dati locali ed è escluso da Git.

### 3. Crea il database

```sql
CREATE DATABASE blog_vito
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

Importa quindi lo schema:

```bash
mysql -u root -p blog_vito < sql/schema.sql
```

### 4. Crea il primo amministratore

Genera l'hash di una password personale:

```bash
php -r "echo password_hash('SOSTITUISCI_CON_UNA_PASSWORD_FORTE', PASSWORD_DEFAULT), PHP_EOL;"
```

Copia l'hash restituito ed esegui questa query, sostituendo il valore di esempio:

```sql
INSERT INTO admin (username, password)
VALUES ('admin', 'INCOLLA_QUI_HASH_GENERATO');
```

Il progetto non crea credenziali predefinite: in questo modo non è possibile dimenticare in produzione una password nota pubblicamente.

### 5. Prepara gli upload

```bash
mkdir -p uploads
chmod 755 uploads
```

Il processo del server web deve poter scrivere nella cartella `uploads`.

### 6. Avvia in locale

Per una prova veloce puoi usare il server integrato di PHP:

```bash
php -S 127.0.0.1:8080
```

Apri `http://127.0.0.1:8080` e accedi al pannello da `/login.php`.

## Configurazione

Le opzioni disponibili in `.env` sono:

| Variabile | Descrizione | Esempio |
| --- | --- | --- |
| `DB_HOST` | Host del database | `127.0.0.1` |
| `DB_NAME` | Nome del database | `blog_vito` |
| `DB_USER` | Utente del database | `blog_user` |
| `DB_PASS` | Password del database | una password locale |
| `SITE_NAME` | Titolo mostrato nel sito | `Il mio blog` |
| `SITE_URL` | URL pubblico, senza slash finale | `https://blog.example.com` |
| `TINYMCE_API_KEY` | Chiave Tiny Cloud; `no-api-key` solo per test | `...` |
| `MAX_FILE_SIZE` | Dimensione massima upload in byte | `5242880` |
| `FRAME_ANCESTORS` | Origini autorizzate a incorporare la homepage | `'self'` |

Per consentire l'incorporamento in un'istanza Nextcloud specifica:

```env
FRAME_ANCESTORS='self' https://cloud.example.com
```

Le pagine di login e amministrazione restano limitate alla stessa origine.

## Dati di esempio

Dopo aver importato lo schema puoi aggiungere contenuti dimostrativi:

```bash
mysql -u root -p blog_vito < sql/post_esempio.sql
```

Non eseguire `sql/reset_database.sql` su un database con dati da conservare: elimina e ricrea le tabelle dell'applicazione.

## Struttura del progetto

```text
.
├── admin.php                 # pannello di amministrazione
├── index.php                 # sito pubblico e commenti
├── login.php                 # autenticazione amministratore
├── nuovo_post.php            # editor degli articoli
├── upload.php                # upload e ottimizzazione immagini
├── config.php                # configurazione derivata da .env
├── includes/                 # loader .env e protezione CSRF
├── css/                      # fogli di stile
├── sql/                      # schema e dati dimostrativi
└── uploads/                  # file caricati, esclusi da Git
```

## Controlli prima del deploy

- usa HTTPS e configura i cookie di sessione come sicuri sul server;
- usa un utente SQL dedicato con i soli privilegi necessari;
- imposta `FRAME_ANCESTORS` su origini esplicite invece di `*`;
- limita a livello di web server l'esecuzione di script dentro `uploads`;
- conserva `.env`, dump e backup fuori dal repository;
- configura backup periodici del database e della cartella `uploads`.

Per controllare rapidamente la sintassi PHP:

```bash
find . -name '*.php' -not -path './uploads/*' -print0 | xargs -0 -n1 php -l
```

## Contribuire

Issue e pull request sono benvenute. Prima di proporre una modifica:

1. crea un branch dedicato;
2. verifica la sintassi di tutti i file PHP;
3. non includere credenziali, dump del database o immagini caricate;
4. descrivi nella pull request cosa cambia e come provarlo.

## Licenza

Distribuito con licenza MIT. Consulta [LICENSE](LICENSE).

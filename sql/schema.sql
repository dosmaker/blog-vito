-- Database per il Blog di Vito
-- Eseguire questo script per creare le tabelle necessarie

-- Tabella amministratori
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella categorie
CREATE TABLE IF NOT EXISTS categorie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    descrizione TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella post
CREATE TABLE IF NOT EXISTS post (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titolo VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    contenuto LONGTEXT NOT NULL,
    data_creazione DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_modifica DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    categoria_id INT,
    pubblicato TINYINT(1) DEFAULT 1,
    FOREIGN KEY (categoria_id) REFERENCES categorie(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella commenti
CREATE TABLE IF NOT EXISTS commenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    contenuto TEXT NOT NULL,
    creato_il DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX fk_commenti_post (post_id),
    CONSTRAINT fk_commenti_post
        FOREIGN KEY (post_id) REFERENCES post(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per sicurezza non viene creato un account amministratore predefinito.
-- Consulta README.md per generare una password e creare il primo account.

-- Categorie di esempio
INSERT IGNORE INTO categorie (nome, slug, descrizione) VALUES 
('Programmazione', 'programmazione', 'Articoli su linguaggi e framework'),
('Tutorial', 'tutorial', 'Guide passo-passo'),
('News', 'news', 'Novità dal mondo tech'),
('Recensioni', 'recensioni', 'Recensioni di tool e software');

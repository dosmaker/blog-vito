-- ============================================
-- RESET COMPLETO DATABASE BLOG
-- ============================================
-- Questo script svuota completamente il database e lo ricrea

-- 1. ELIMINA TUTTE LE TABELLE (se esistono)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS commenti;
DROP TABLE IF EXISTS post;
DROP TABLE IF EXISTS categorie;
DROP TABLE IF EXISTS admin;
SET FOREIGN_KEY_CHECKS = 1;

-- 2. RICREA LE TABELLE

-- Tabella amministratori
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella categorie
CREATE TABLE categorie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    descrizione TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella post
CREATE TABLE post (
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
CREATE TABLE commenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    contenuto TEXT NOT NULL,
    creato_il DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX fk_commenti_post (post_id),
    CONSTRAINT fk_commenti_post
        FOREIGN KEY (post_id) REFERENCES post(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. INSERISCI DATI DI BASE

-- L'account amministratore va creato manualmente con una password personale.
-- Consulta README.md.

-- Categorie
INSERT INTO categorie (nome, slug, descrizione) VALUES 
('Programmazione', 'programmazione', 'Articoli su linguaggi e framework'),
('Tutorial', 'tutorial', 'Guide passo-passo'),
('News', 'news', 'Novità dal mondo tech'),
('Recensioni', 'recensioni', 'Recensioni di tool e software');

-- ============================================
-- COMPLETATO! Database pronto all'uso.
-- ============================================

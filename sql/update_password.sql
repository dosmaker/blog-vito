-- Genera un hash personale con password_hash() e incollalo qui.
-- Non eseguire questa query senza avere sostituito il segnaposto.
UPDATE admin SET password = 'INCOLLA_QUI_HASH_GENERATO' WHERE username = 'admin';

-- Verifica
SELECT id, username, password FROM admin WHERE username = 'admin';

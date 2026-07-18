-- Correction finale des accents casses apres import/export SQL.
-- A importer dans phpMyAdmin en ayant bien selectionne la base :
-- viteetgourmand33_vite_et_gourmand
--
-- Ce script ne supprime aucune table et ne modifie pas la structure.
-- Il parcourt toutes les colonnes texte de la base active et remplace
-- les caracteres mal encodes les plus frequents.

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS corriger_accents_vite_gourmand;

DELIMITER //

CREATE PROCEDURE corriger_accents_vite_gourmand()
BEGIN
    DECLARE fini INT DEFAULT 0;
    DECLARE nom_table VARCHAR(255);
    DECLARE nom_colonne VARCHAR(255);

    DECLARE curseur_colonnes CURSOR FOR
        SELECT TABLE_NAME, COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND DATA_TYPE IN ('char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext');

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET fini = 1;

    OPEN curseur_colonnes;

    boucle_colonnes: LOOP
        FETCH curseur_colonnes INTO nom_table, nom_colonne;

        IF fini = 1 THEN
            LEAVE boucle_colonnes;
        END IF;

        SET @table_sql = CONCAT('`', REPLACE(nom_table, '`', '``'), '`');
        SET @colonne_sql = CONCAT('`', REPLACE(nom_colonne, '`', '``'), '`');

        SET @expression = @colonne_sql;

        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã©'), ', ', QUOTE('é'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã¨'), ', ', QUOTE('è'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ãª'), ', ', QUOTE('ê'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã«'), ', ', QUOTE('ë'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã‰'), ', ', QUOTE('É'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ãˆ'), ', ', QUOTE('È'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('ÃŠ'), ', ', QUOTE('Ê'), ')');

        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã '), ', ', QUOTE('à'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã¡'), ', ', QUOTE('á'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã¢'), ', ', QUOTE('â'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã¤'), ', ', QUOTE('ä'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã€'), ', ', QUOTE('À'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã‚'), ', ', QUOTE('Â'), ')');

        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã®'), ', ', QUOTE('î'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã¯'), ', ', QUOTE('ï'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('ÃŽ'), ', ', QUOTE('Î'), ')');

        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã´'), ', ', QUOTE('ô'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã¶'), ', ', QUOTE('ö'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã”'), ', ', QUOTE('Ô'), ')');

        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã¹'), ', ', QUOTE('ù'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã»'), ', ', QUOTE('û'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã¼'), ', ', QUOTE('ü'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã™'), ', ', QUOTE('Ù'), ')');

        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã§'), ', ', QUOTE('ç'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã‡'), ', ', QUOTE('Ç'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Ã±'), ', ', QUOTE('ñ'), ')');

        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Å“'), ', ', QUOTE('œ'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Å’'), ', ', QUOTE('Œ'), ')');

        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Â°'), ', ', QUOTE('°'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Â€'), ', ', QUOTE('€'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('â‚¬'), ', ', QUOTE('€'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('Â '), ', ', QUOTE(' '), ')');

        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('â€™'), ', ', QUOTE(''''), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('â€˜'), ', ', QUOTE(''''), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('â€œ'), ', ', QUOTE('"'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('â€'), ', ', QUOTE('"'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('â€“'), ', ', QUOTE('-'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('â€”'), ', ', QUOTE('-'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('â€¦'), ', ', QUOTE('...'), ')');
        SET @expression = CONCAT('REPLACE(', @expression, ', ', QUOTE('â†’'), ', ', QUOTE('->'), ')');

        SET @sql = CONCAT(
            'UPDATE ',
            @table_sql,
            ' SET ',
            @colonne_sql,
            ' = ',
            @expression,
            ' WHERE ',
            @colonne_sql,
            ' IS NOT NULL'
        );

        PREPARE requete FROM @sql;
        EXECUTE requete;
        DEALLOCATE PREPARE requete;
    END LOOP;

    CLOSE curseur_colonnes;
END//

DELIMITER ;

CALL corriger_accents_vite_gourmand();

DROP PROCEDURE IF EXISTS corriger_accents_vite_gourmand;

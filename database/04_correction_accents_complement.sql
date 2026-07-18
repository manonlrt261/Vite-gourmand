-- Correctif complementaire des accents apres import Alwaysdata.
-- A importer dans phpMyAdmin apres avoir selectionne la base :
-- viteetgourmand33_vite_et_gourmand
--
-- Le script parcourt toutes les colonnes texte de la base selectionnee.
-- Il ne modifie pas la structure des tables.

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS corriger_accents_complement;

DELIMITER //

CREATE PROCEDURE corriger_accents_complement()
BEGIN
    DECLARE fini INT DEFAULT 0;
    DECLARE nom_table VARCHAR(255);
    DECLARE nom_colonne VARCHAR(255);

    DECLARE curseur CURSOR FOR
        SELECT TABLE_NAME, COLUMN_NAME
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND DATA_TYPE IN ('char', 'varchar', 'text', 'tinytext', 'mediumtext', 'longtext');

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET fini = 1;

    OPEN curseur;

    boucle_colonnes: LOOP
        FETCH curseur INTO nom_table, nom_colonne;

        IF fini = 1 THEN
            LEAVE boucle_colonnes;
        END IF;

        SET @table_sql = CONCAT('`', REPLACE(nom_table, '`', '``'), '`');
        SET @colonne_sql = CONCAT('`', REPLACE(nom_colonne, '`', '``'), '`');
        SET @valeur_corrigee = @colonne_sql;

        -- Accents doublement mal encodes.
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('ÃƒÂ©'), ', ', QUOTE('é'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('ÃƒÂ¨'), ', ', QUOTE('è'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('ÃƒÂª'), ', ', QUOTE('ê'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('ÃƒÂ«'), ', ', QUOTE('ë'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('ÃƒÂ '), ', ', QUOTE('à'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('ÃƒÂ¢'), ', ', QUOTE('â'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('ÃƒÂ¹'), ', ', QUOTE('ù'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('ÃƒÂ»'), ', ', QUOTE('û'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('ÃƒÂ®'), ', ', QUOTE('î'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('ÃƒÂ¯'), ', ', QUOTE('ï'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('ÃƒÂ´'), ', ', QUOTE('ô'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('ÃƒÂ¶'), ', ', QUOTE('ö'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('ÃƒÂ§'), ', ', QUOTE('ç'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Ãƒâ€°'), ', ', QUOTE('É'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Ãƒâ‚¬'), ', ', QUOTE('À'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Ãƒâ€¡'), ', ', QUOTE('Ç'), ')');

        -- Accents simplement mal encodes.
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Ã©'), ', ', QUOTE('é'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Ã¨'), ', ', QUOTE('è'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Ãª'), ', ', QUOTE('ê'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Ã«'), ', ', QUOTE('ë'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Ã '), ', ', QUOTE('à'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Ã¢'), ', ', QUOTE('â'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Ã¹'), ', ', QUOTE('ù'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Ã»'), ', ', QUOTE('û'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Ã®'), ', ', QUOTE('î'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Ã¯'), ', ', QUOTE('ï'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Ã´'), ', ', QUOTE('ô'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Ã¶'), ', ', QUOTE('ö'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Ã§'), ', ', QUOTE('ç'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Ã‰'), ', ', QUOTE('É'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Ã€'), ', ', QUOTE('À'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Ã‡'), ', ', QUOTE('Ç'), ')');

        -- Symboles et ponctuation mal encodes.
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Â°'), ', ', QUOTE('°'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Â€'), ', ', QUOTE('€'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Â«'), ', ', QUOTE('«'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Â»'), ', ', QUOTE('»'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Â '), ', ', QUOTE(' '), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('â€™'), ', CHAR(39))');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('â€˜'), ', CHAR(39))');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('â€œ'), ', CHAR(34))');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('â€�'), ', CHAR(34))');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('â€“'), ', ', QUOTE('-'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('â€”'), ', ', QUOTE('-'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('â€¦'), ', ', QUOTE('...'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Å“'), ', ', QUOTE('oe'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Å’'), ', ', QUOTE('OE'), ')');
        SET @valeur_corrigee = CONCAT('REPLACE(', @valeur_corrigee, ', ', QUOTE('Â'), ', ', QUOTE(''), ')');

        SET @sql = CONCAT(
            'UPDATE ', @table_sql,
            ' SET ', @colonne_sql, ' = ', @valeur_corrigee,
            ' WHERE ', @colonne_sql, ' LIKE ', QUOTE('%Ã%'),
            ' OR ', @colonne_sql, ' LIKE ', QUOTE('%Â%'),
            ' OR ', @colonne_sql, ' LIKE ', QUOTE('%â%'),
            ' OR ', @colonne_sql, ' LIKE ', QUOTE('%Å%')
        );

        PREPARE requete FROM @sql;
        EXECUTE requete;
        DEALLOCATE PREPARE requete;
    END LOOP;

    CLOSE curseur;
END//

DELIMITER ;

CALL corriger_accents_complement();

DROP PROCEDURE IF EXISTS corriger_accents_complement;

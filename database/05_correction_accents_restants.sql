SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Ce script corrige les accents encore casses dans les colonnes texte.
-- Il parcourt automatiquement les colonnes de type texte de la base selectionnee.
-- Il ne supprime aucune table et ne modifie pas la structure de la base.

DROP PROCEDURE IF EXISTS corriger_accents_restants;

DELIMITER $$

CREATE PROCEDURE corriger_accents_restants()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE table_name_var VARCHAR(255);
    DECLARE column_name_var VARCHAR(255);
    DECLARE update_sql LONGTEXT;
    DECLARE value_sql LONGTEXT;

    DECLARE cur CURSOR FOR
        SELECT TABLE_NAME, COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND DATA_TYPE IN ('char', 'varchar', 'text', 'tinytext', 'mediumtext', 'longtext');

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO table_name_var, column_name_var;

        IF done = 1 THEN
            LEAVE read_loop;
        END IF;

        SET value_sql = CONCAT('`', column_name_var, '`');

        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Ã©'), ', ', QUOTE('é'), ')');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Ã¨'), ', ', QUOTE('è'), ')');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Ãª'), ', ', QUOTE('ê'), ')');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Ã«'), ', ', QUOTE('ë'), ')');

        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Ã '), ', ', QUOTE('à'), ')');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Ã¢'), ', ', QUOTE('â'), ')');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Ã¤'), ', ', QUOTE('ä'), ')');

        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Ã¹'), ', ', QUOTE('ù'), ')');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Ã»'), ', ', QUOTE('û'), ')');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Ã¼'), ', ', QUOTE('ü'), ')');

        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Ã´'), ', ', QUOTE('ô'), ')');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Ã¶'), ', ', QUOTE('ö'), ')');

        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Ã®'), ', ', QUOTE('î'), ')');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Ã¯'), ', ', QUOTE('ï'), ')');

        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Ã§'), ', ', QUOTE('ç'), ')');

        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Ã‰'), ', ', QUOTE('É'), ')');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Ãˆ'), ', ', QUOTE('È'), ')');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('ÃŠ'), ', ', QUOTE('Ê'), ')');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Ã€'), ', ', QUOTE('À'), ')');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Ã‡'), ', ', QUOTE('Ç'), ')');

        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Â°'), ', ', QUOTE('°'), ')');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Â€'), ', ', QUOTE('€'), ')');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Â«'), ', ', QUOTE('«'), ')');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Â»'), ', ', QUOTE('»'), ')');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('Â'), ', ', QUOTE(''), ')');

        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('â€™'), ', CHAR(39))');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('â€˜'), ', CHAR(39))');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('â€œ'), ', CHAR(34))');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('â€�'), ', CHAR(34))');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('â€“'), ', ', QUOTE('-'), ')');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('â€”'), ', ', QUOTE('-'), ')');
        SET value_sql = CONCAT('REPLACE(', value_sql, ', ', QUOTE('â€¦'), ', ', QUOTE('...'), ')');

        SET update_sql = CONCAT(
            'UPDATE `', table_name_var, '` SET `', column_name_var, '` = ', value_sql,
            ' WHERE `', column_name_var, '` IS NOT NULL'
        );

        PREPARE stmt FROM update_sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END LOOP;

    CLOSE cur;
END$$

DELIMITER ;

CALL corriger_accents_restants();

DROP PROCEDURE IF EXISTS corriger_accents_restants;

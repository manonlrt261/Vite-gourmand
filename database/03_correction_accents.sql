SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Correction des caracteres casses apres import/export avec un mauvais encodage.
-- A importer dans phpMyAdmin uniquement apres avoir selectionne la bonne base de donnees.
-- Ce script ne modifie pas la structure : il corrige seulement les colonnes texte.

DELIMITER //

DROP PROCEDURE IF EXISTS corriger_accents_mojibake//

CREATE PROCEDURE corriger_accents_mojibake()
BEGIN
    DECLARE finished INT DEFAULT 0;
    DECLARE current_table VARCHAR(255);
    DECLARE current_column VARCHAR(255);

    DECLARE text_columns CURSOR FOR
        SELECT TABLE_NAME, COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND DATA_TYPE IN ('char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext');

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET finished = 1;

    OPEN text_columns;

    correction_loop: LOOP
        FETCH text_columns INTO current_table, current_column;

        IF finished = 1 THEN
            LEAVE correction_loop;
        END IF;

        SET @sql = CONCAT(
            'UPDATE `', current_table, '` SET `', current_column, '` = CASE ',
            'WHEN `', current_column, '` IS NULL THEN NULL ELSE ',
            'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(',
            'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(',
            'REPLACE(REPLACE(REPLACE(REPLACE(`', current_column, '`, ',
            '''Ã©'', ''é''), ',
            '''Ã¨'', ''è''), ',
            '''Ãª'', ''ê''), ',
            '''Ã«'', ''ë''), ',
            '''Ã '', ''à''), ',
            '''Ã¢'', ''â''), ',
            '''Ã¤'', ''ä''), ',
            '''Ã´'', ''ô''), ',
            '''Ã¶'', ''ö''), ',
            '''Ã®'', ''î''), ',
            '''Ã¯'', ''ï''), ',
            '''Ã»'', ''û''), ',
            '''Ã¼'', ''ü''), ',
            '''Ã¹'', ''ù''), ',
            '''Ã§'', ''ç''), ',
            '''Ã‰'', ''É''), ',
            '''Ã€'', ''À''), ',
            '''Ã‡'', ''Ç''), ',
            '''â€™'', ''’''), ',
            '''â€“'', ''-''), ',
            '''â€œ'', ''"''), ',
            '''â€'', ''"''), ',
            '''Â°'', ''°''), ',
            '''Â'', '''') ',
            'END ',
            'WHERE `', current_column, '` LIKE ''%Ã%'' ',
            'OR `', current_column, '` LIKE ''%Â%'' ',
            'OR `', current_column, '` LIKE ''%â%'' ',
            'OR `', current_column, '` LIKE ''%�%'';'
        );

        PREPARE correction_statement FROM @sql;
        EXECUTE correction_statement;
        DEALLOCATE PREPARE correction_statement;
    END LOOP;

    CLOSE text_columns;
END//

DELIMITER ;

CALL corriger_accents_mojibake();
DROP PROCEDURE corriger_accents_mojibake;

-- Aggiunta importazione impianti
INSERT INTO `zz_imports` (`name`, `class`) VALUES ('Impianti', 'Modules\\Impianti\\Import\\CSV');

-- Aggiunta importazione attività
INSERT INTO `zz_imports` (`name`, `class`) VALUES ('Attività', 'Modules\\Interventi\\Import\\CSV');

ALTER TABLE `my_impianti_categorie` ADD `parent` INT NULL DEFAULT NULL; 
ALTER TABLE `my_impianti` ADD `id_sottocategoria` INT NULL DEFAULT NULL AFTER `id_categoria`; 
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `my_impianti_categorie` WHERE 1=1 AND parent IS NULL HAVING 2=2' WHERE `zz_modules`.`name` = 'Categorie impianti'; 

-- Aggiornamento vista Impianti
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT `id` FROM `zz_modules` WHERE `name` = 'Impianti'), 'Sottocategoria', 'sub.nome', '7', '1', '0', '0', '0', '', '', '1', '0', '0');
UPDATE `zz_modules` SET `options` = "SELECT
    |select| 
FROM
    `my_impianti`
    LEFT JOIN `an_anagrafiche` AS clienti ON `clienti`.`idanagrafica` = `my_impianti`.`idanagrafica`
    LEFT JOIN `an_anagrafiche` AS tecnici ON `tecnici`.`idanagrafica` = `my_impianti`.`idtecnico` 
    LEFT JOIN `my_impianti_categorie` ON `my_impianti_categorie`.`id` = `my_impianti`.`id_categoria`
    LEFT JOIN `my_impianti_categorie` as sub ON sub.`id` = `my_impianti`.`id_sottocategoria`
    LEFT JOIN (SELECT an_sedi.id, CONCAT(an_sedi.nomesede, '<br />',IF(an_sedi.telefono!='',CONCAT(an_sedi.telefono,'<br />'),''),IF(an_sedi.cellulare!='',CONCAT(an_sedi.cellulare,'<br />'),''),an_sedi.citta,IF(an_sedi.indirizzo!='',CONCAT(' - ',an_sedi.indirizzo),'')) AS info FROM an_sedi
) AS sede ON sede.id = my_impianti.idsede
WHERE
    1=1
HAVING
    2=2
ORDER BY
    `matricola`" WHERE `name` = 'Impianti';

-- Serial in Contratti
ALTER TABLE `mg_prodotti` ADD `id_riga_contratto` INT NULL AFTER `id_riga_intervento`;
ALTER TABLE `mg_prodotti` ADD FOREIGN KEY (`id_riga_contratto`) REFERENCES `co_righe_contratti`(`id`) ON DELETE CASCADE;

-- Aggiunta stampa preventivo (solo totale imponibile)
INSERT INTO `zz_prints` (`id_module`, `is_record`, `name`, `title`, `filename`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `default`, `enabled`, `available_options`) VALUES ((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), '1', 'Preventivo(solo totale imponibile)', 'Preventivo (solo totale imponibile)', 'Preventivo num. {numero} del {data} rev {revisione}', 'preventivi', 'idpreventivo', '{\"pricing\": false, \"last-page-footer\": true, \"images\": true, \"no-iva\":true, \"show-only-total\":true }', 'fa fa-print', '', '', '0', '0', '1', '1', '{\"pricing\":\"Visualizzare i prezzi\", \"hide-total\": \"Nascondere i totali delle righe\", \"show-only-total\": \"Visualizzare solo i totali del documento\", \"hide-header\": \"Nascondere intestazione\", \"hide-footer\": \"Nascondere footer\", \"last-page-footer\": \"Visualizzare footer solo su ultima pagina\", \"hide-item-number\": \"Nascondere i codici degli articoli\"}');

-- Aggiunta indice per ricerca su files più rapida
ALTER TABLE `zz_files` ADD INDEX(`id_record`);

ALTER TABLE `co_scadenziario` ADD `tipo_pagamento` INT NOT NULL;
ALTER TABLE `co_scadenziario` ADD `id_banca_azienda` INT NULL;
ALTER TABLE `co_scadenziario` ADD `id_banca_controparte` INT NULL;   
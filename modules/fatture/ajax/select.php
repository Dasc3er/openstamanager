<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    /*
     * Opzioni utilizzate:
     * - id_anagrafica
     */
    case 'riferimenti-fe':
        $direzione = 'uscita';
        $id_anagrafica = $superselect['id_anagrafica'];
        if (empty($id_anagrafica)) {
            return [];
        }

        // Campi di ricerca
        $search_fields = [];
        if (!empty($search)) {
            $search_fields[] = "IF(numero_esterno != '', numero_esterno, numero) LIKE ".prepare('%'.$search.'%');
            $search_fields[] = "DATE_FORMAT(data, '%d/%m/%Y') LIKE ".prepare('%'.$search.'%');
        }

        $where = implode(' OR ', $search_fields);
        $where = $where ? '('.$where.')' : '1=1';

        $query_ordini = "SELECT 
                `or_ordini`.`id`,
                CONCAT('Ordine num. ', IF(`numero_esterno` != '', `numero_esterno`, `numero`), ' del ', DATE_FORMAT(`data`, '%d/%m/%Y'), ' [', `or_statiordine_lang`.`name`  , ']') AS text,
                'Ordini' AS optgroup,
                'ordine' AS tipo,
                'uscita' AS dir
            FROM 
                `or_ordini`
                INNER JOIN `or_righe_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id`
                INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine` = `or_statiordine`.`id`
                LEFT JOIN `or_statiordine_lang` ON (`or_statiordine`.`id` = `or_statiordine_lang`.`id_record` AND `or_statiordine_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).')
                INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
            WHERE 
                `idanagrafica` = '.prepare($id_anagrafica)."
                AND `name` != 'Fatturato'
                AND `dir` = ".prepare($direzione).'
                AND |where|
            GROUP BY 
                `or_ordini`.`id`
            HAVING 
                SUM(`or_righe_ordini`.`qta` - `or_righe_ordini`.`qta_evasa`) > 0
            ORDER BY 
                `data` DESC, `numero` DESC';

        $query_ddt = "SELECT 
                `dt_ddt`.`id`,
                CONCAT('DDT num. ', IF(`numero_esterno` != '', `numero_esterno`, `numero`), ' del ', DATE_FORMAT(`data`, '%d/%m/%Y'), ' [', `dt_statiddt_lang`.`name`, ']') AS text,
                'DDT' AS optgroup,
                'ddt' AS tipo,
                'uscita' AS dir
            FROM `dt_ddt`
                INNER JOIN `dt_righe_ddt` ON `dt_righe_ddt`.`idddt` = `dt_ddt`.`id`
                INNER JOIN `dt_statiddt` ON `dt_ddt`.`idstato` = `dt_statiddt`.`id`
                INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`
                LEFT JOIN `dt_statiddt_lang` ON (`dt_statiddt_lang`.`id_record` = `dt_statiddt`.`id` AND `dt_statiddt_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).')
            WHERE 
                `idanagrafica` = '.prepare($id_anagrafica)." AND
                `dt_statiddt_lang`.`name` != 'Fatturato' AND
                `dt_tipiddt`.`dir`=".prepare($direzione).'AND 
                |where|
            GROUP BY 
                `dt_ddt`.`id`
            HAVING 
                SUM(`dt_righe_ddt`.`qta` - `dt_righe_ddt`.`qta_evasa`) > 0
            ORDER BY 
                `data` DESC, `numero` DESC';

        // Sostituzione per la ricerca
        $query_ordini = replace($query_ordini, [
            '|where|' => $where,
        ]);

        $query_ddt = replace($query_ddt, [
            '|where|' => $where,
        ]);

        $ordini = $database->fetchArray($query_ordini);
        $ddt = $database->fetchArray($query_ddt);
        $results = array_merge($ordini, $ddt);

        break;

    case 'riferimenti-vendita-fe':
        $direzione = 'entrata';
        $id_articolo = $superselect['id_articolo'];
        if (empty($id_articolo)) {
            return [];
        }

        // Campi di ricerca
        $search_fields = [];
        if (!empty($search)) {
            $search_fields[] = "IF(numero_esterno != '', numero_esterno, numero) LIKE ".prepare('%'.$search.'%');
            $search_fields[] = "DATE_FORMAT(data, '%d/%m/%Y') LIKE ".prepare('%'.$search.'%');
        }

        $where = implode(' OR ', $search_fields);
        $where = $where ? '('.$where.')' : '1=1';

        $query_ordini = "SELECT 
                `or_ordini`.`id`,
                CONCAT('Ordine num. ', IF(`numero_esterno` != '', `numero_esterno`, `numero`), ' del ', DATE_FORMAT(data, '%d/%m/%Y'), ' [', `or_statiordine_lang`.`name`, ']') AS text,
                'Ordini' AS optgroup,
                'ordine' AS tipo,
                'entrata' AS dir
            FROM `or_ordini`
                INNER JOIN `or_righe_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id`
                INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine` = `or_statiordine`.`id`
                LEFT JOIN `or_statiordine_lang` ON (`or_statiordine_lang`.`id_record` = `or_statiordine`.`id` AND `or_statiordine_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).')
                INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipiordine` = `or_tipiordine`.`id`
            WHERE 
                `idarticolo` = '.prepare($id_articolo)."
                AND `name` != 'Fatturato'
                AND `dir` = ".prepare($direzione).'
                AND |where|
            GROUP BY 
                `or_ordini`.`id`
            ORDER BY 
                `data` DESC, `numero` DESC';

        $query_ddt = "SELECT 
                `dt_ddt`.`id`,
                CONCAT('DDT num. ', IF(`numero_esterno` != '', `numero_esterno`, `numero`), ' del ', DATE_FORMAT(`data`, '%d/%m/%Y'), ' [', `dt_statiddt_lang`.`name`, ']') AS text,
                'DDT' AS optgroup,
                'ddt' AS tipo,
                'entrata' AS dir
            FROM 
                `dt_ddt`
                INNER JOIN `dt_righe_ddt` ON `dt_righe_ddt`.`idddt` = `dt_ddt`.`id`
                INNER JOIN `dt_statiddt` ON `dt_ddt`.`idstato` = `dt_statiddt`.`id`
                LEFT JOIN `dt_statiddt_lang` ON (`dt_statiddt_lang`.`id_record` = `dt_statiddt`.`id` AND `dt_statiddt_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).')
                INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`
            WHERE 
                `idarticolo` = '.prepare($id_articolo)." AND
                `dt_stati_lang`.`name` != 'Fatturato' AND
                `dt_tipiddt`.`dir`=".prepare($direzione).'AND 
                |where|
            GROUP BY 
                `dt_ddt`.`id`
            HAVING 
                SUM(`dt_righe_ddt`.`qta` - `dt_righe_ddt`.`qta_evasa`) > 0
            ORDER BY 
                `data` DESC, `numero` DESC';

        // Sostituzione per la ricerca
        $query_ordini = replace($query_ordini, [
            '|where|' => $where,
        ]);

        $query_ddt = replace($query_ddt, [
            '|where|' => $where,
        ]);

        $ordini = $database->fetchArray($query_ordini);
        $ddt = $database->fetchArray($query_ddt);
        $results = array_merge($ordini, $ddt);

        break;
}

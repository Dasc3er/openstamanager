<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../core.php';

use Modules\Contratti\Contratto;

if (!empty($id_record)) {
    $contratto = Contratto::find($id_record);

    $record = $dbo->fetchOne('SELECT 
        `co_contratti`.*,
        `an_anagrafiche`.`tipo` AS tipo_anagrafica,
        `co_staticontratti`.`is_fatturabile` AS is_fatturabile,
        `co_staticontratti`.`is_pianificabile` AS is_pianificabile,
        `co_staticontratti`.`is_bloccato` AS is_bloccato,
        `co_staticontratti_lang`.`title` AS stato,
        GROUP_CONCAT(`my_impianti_contratti`.`idimpianto`) AS idimpianti,
        `co_contratti`.`id_categoria` as id_categoria,
        `co_contratti`.`id_sottocategoria` as id_sottocategoria
    FROM 
        `co_contratti`
        INNER JOIN `an_anagrafiche` ON `co_contratti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
        INNER JOIN `co_staticontratti` ON `co_contratti`.`idstato` = `co_staticontratti`.`id`
        LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        LEFT JOIN `my_impianti_contratti` ON `my_impianti_contratti`.`idcontratto` = `co_contratti`.`id`
    WHERE 
        `co_contratti`.`id`='.prepare($id_record));

    $elementi = $dbo->fetchArray('
    SELECT 
        `co_documenti`.`id`, 
        `co_documenti`.`data`, 
        `co_documenti`.`numero`, 
        `co_documenti`.`numero_esterno`, 
        `co_tipidocumento_lang`.`title` AS tipo_documento, 
        IF(`co_tipidocumento`.`dir` = \'entrata\', \'Fatture di vendita\', \'Fatture di acquisto\') AS modulo,
        GROUP_CONCAT(CONCAT(`co_righe_documenti`.`original_id`, " - ", `co_righe_documenti`.`qta`) SEPARATOR ", ") AS righe,
        `co_statidocumento_lang`.`title` AS stato_documento
    FROM `co_documenti` 
    INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id` 
    INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` 
    LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') 
    LEFT JOIN co_statidocumento ON co_documenti.idstatodocumento=co_statidocumento.id LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE `co_righe_documenti`.`idcontratto` = '.prepare($id_record).'
    GROUP BY id

    UNION

    SELECT 
        `in_interventi`.`id`, 
        `in_interventi`.`data_richiesta`, 
        `in_interventi`.`codice`, 
        NULL, 
        \'Attività\', 
        \'Interventi\',
        GROUP_CONCAT(CONCAT(`original_id`, " - ", `qta`) SEPARATOR ", ") AS righe,
        `in_statiintervento_lang`.`title` AS stato_documento
    FROM `in_interventi` 
    JOIN `in_righe_interventi` ON `in_righe_interventi`.`idintervento` = `in_interventi`.`id` 
    LEFT JOIN in_statiintervento ON in_interventi.idstatointervento=in_statiintervento.id LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento`.`id` = `in_statiintervento_lang`.`id_record` AND `in_statiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE (`in_righe_interventi`.`original_document_id` = '.prepare($id_record).' AND `in_righe_interventi`.`original_document_type` = \'Modules\\\\Contratti\\\\Contratto\') OR `in_interventi`.`id_contratto` = '.prepare($id_record).'
    GROUP BY id
    
    ORDER BY `modulo`');

    $is_anagrafica_deleted = !$contratto->anagrafica;

    // Estrazione numero contratto originale
    $numero_contratto_originale = '';
    $id_contratto_prev = $contratto->idcontratto_prev;
    while ($id_contratto_prev != 0) {
        $contratto_prev = Contratto::find($id_contratto_prev);
        $id_contratto_prev = $contratto_prev->idcontratto_prev;
        $numero_contratto_originale = 'Orig. '.$contratto_prev->numero;
    }
}

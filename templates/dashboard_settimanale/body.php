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

use Carbon\Carbon;

?>
<style type="text/css">
    .divTable
    {
        display:  table;
        width:auto;
        background-color:#eee;
        border:1px solid  #666666;
        border-spacing:5px;/*cellspacing:poor IE support for  this*/
       /* border-collapse:separate;*/
    }

    .divRow
    {
       display:table-row;
       width:auto;
    }

    .divCell
    {
        float:left;/*fix for  buggy browsers*/
        display:table-column;
        width:147px;
        background-color:#ccc;
    }
</style>
<?php

$calendar = $_SESSION['dashboard'];

$date_start = $calendar['date_week_start'];
$date_end = date('Y-m-d', strtotime('+1 day', strtotime($calendar['date_week_end'])));

$title = date('d/m/Y', strtotime($date_start)).' - '.date('d/m/Y', strtotime($date_end));

$min_date = new Carbon($date_start);
$max_date = new Carbon($date_end);

$stati = (array) $calendar['idstatiintervento'];
$tipi = (array) $calendar['idtipiintervento'];
$tecnici = (array) $calendar['idtecnici'];

$query = "SELECT
        DATE(`orario_inizio`) AS data,
        `in_interventi`.`richiesta` AS richiesta,
        DATE_FORMAT(`orario_inizio`, '%H:%i') AS ora_inizio,
        DATE_FORMAT(`orario_fine`, '%H:%i') AS ora_fine,
        `an_anagrafiche`.`ragione_sociale` AS anagrafica,
        GROUP_CONCAT(`tecnico`.`ragione_sociale` SEPARATOR ', ') AS tecnico,
        `in_statiintervento`.`colore` AS color
    FROM 
        `in_interventi_tecnici`
        INNER JOIN `in_interventi` ON `in_interventi_tecnici`.`idintervento`=`in_interventi`.`id`
        INNER JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica`=`an_anagrafiche`.`idanagrafica`
        LEFT JOIN `an_anagrafiche` AS tecnico ON `in_interventi_tecnici`.`idtecnico`=`tecnico`.`idanagrafica`
        LEFT JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`id`
    WHERE 
        ".$where.'
        `idtecnico` IN('.implode(',', $tecnici).') AND
        `in_interventi`.`idstatointervento` IN('.implode(',', $stati).') AND
        `in_interventi_tecnici`.`idtipointervento` IN('.implode(',', $tipi).') '.Modules::getAdditionalsQuery('Interventi').'
    GROUP BY 
        `in_interventi`.`id`, `data`
    ORDER BY 
        `ora_inizio` ASC';
$sessioni = $dbo->fetchArray($query);

$sessioni = collect($sessioni)->groupBy('data');

// Intestazione tabella
echo '
<h3 class="text-bold">'.tr('Calendario _PERIOD_', [
    '_PERIOD_' => $title,
], ['upper' => true]).'</h3>';

// Elenco per la gestione
$list = [];

// Filler per i giorni non inclusi della settimana iniziale
$current_day = $min_date;

// Elenco del periodo indicato
while ($current_day->lessThan($max_date)) {
    $list[] = [
        'date' => $current_day->copy(),
        'contents' => $sessioni[$current_day->toDateString()] ?: [],
    ];

    $current_day->addDay();
}

// Stampa della tabella
echo '
<div class="divTable">';

$count = count($list);
for ($i = 0; $i < $count; $i = $i + 7) {
    echo '
    <div class="divRow">';

    for ($c = 0; $c < 7; ++$c) {
        $element = $list[$i + $c];

        echo '
        <div class="divCell" align="center">'.ucfirst($element['date']->isoFormat('MMMM YYYY')).'</div>';
    }

    echo '
    </div>';

    echo '
    <div class="divRow">';

    for ($c = 0; $c < 7; ++$c) {
        $element = $list[$i + $c];

        $clienti = '';
        foreach ($element['contents'] as $sessione) {
            $clienti .= '<table><tr><td style="background-color:'.$sessione['color'].';font-size:8pt;">'.$sessione['ora_inizio'].' - '.$sessione['ora_fine'].'</small><br><b>'.$sessione['anagrafica'].'</b><br>
            <i>'.$sessione['richiesta'].'</i><small>'.$sessione['tecnico'].'</td></tr></table>';
        }

        $background = '#ffffff';

        if (empty($clienti)) {
            $background = 'lightgray';
        }

        echo '
        <div class="divCell" style="background:'.$background.'">'.(!empty($clienti) ? $clienti : '&nbsp;').'</div>';
    }

    echo '
    </div>';
}

echo '
</div>';

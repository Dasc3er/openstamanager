<?php

include_once __DIR__.'/../../core.php';
use Models\Module;

$id_record = get('idautomezzo');

// Form di inserimento responsabili automezzo
echo '
<form action="'.$rootdir.'/editor.php?id_module='.Module::where('name', 'Automezzi')->first()->id.'&id_record='.$id_record.'" method="post">
    <input type="hidden" name="op" value="addtech">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_record" value="'.$id_record.'">

    <div class="row">';

// Tecnico
echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Tecnico').'", "name": "idtecnico", "required": 1, "values": "query=SELECT `an_anagrafiche`.`idanagrafica` AS id, `ragione_sociale` AS descrizione FROM `an_anagrafiche` INNER JOIN (`an_tipianagrafiche_anagrafiche` INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche`.`id` LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')) ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica` WHERE (`title`=\'Tecnico\') AND `deleted_at` IS NULL ORDER BY `ragione_sociale`", "value": "'.$idtecnico.'" ]}
        </div>';

// Data di partenza
echo '
        <div class="col-md-3">
            {[ "type": "date", "label": "'.tr('Data dal').'", "name": "data_inizio", "required": 1, "value": "-now-" ]}
        </div>';

// Data di fine
echo '
        <div class="col-md-3">
            {[ "type": "date", "label": "'.tr('Data al').'", "name": "data_fine", "min-date": "-now-" ]}
        </div>';

echo '
	</div>

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
    </div>
</form>';

echo '
<script type="text/javascript">
    $(document).ready(function(){init();});

    $(function () {
        $("#data_inizio").on("dp.change", function (e) {
            $("#data_fine").data("DateTimePicker").minDate(e.date);

            if($("#data_fine").data("DateTimePicker").date() < e.date){
                $("#data_fine").data("DateTimePicker").date(e.date);
            }
        })
    });
</script>';

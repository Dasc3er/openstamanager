<?php

include_once __DIR__.'/../../core.php';
use Models\Module;

$idautomezzo = get('idautomezzo');
$idarticolo = get('idarticolo');
$op = 'addrow';
$qta = 1;

if (!empty($idarticolo) && !empty($idautomezzo)) {
    $qta = $dbo->fetchOne('SELECT SUM(mg_movimenti.qta) AS qta FROM mg_movimenti WHERE mg_movimenti.idarticolo='.prepare($idarticolo).' AND mg_movimenti.idsede='.prepare($idautomezzo))['qta'];
    $op = 'editrow';
}

/*
    Form di inserimento riga documento
*/
echo '
<form id="link_form" action="'.$rootdir.'/editor.php?id_module='.(new Module())->getByField('name', 'Automezzi', Models\Locale::getPredefined()->id).'&id_record='.$idautomezzo.'" method="post">
    <input type="hidden" name="op" value="'.$op.'">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_record" value="'.$idautomezzo.'">';

// Seleziona articolo
echo '
    <div class="col-md-8">
        {[ "type": "select", "label": "'.tr('Articolo').'", "name": "idarticolo", "required": 1, "value": "'.$idarticolo.'", "ajax-source": "articoli", "select-options": '.json_encode(['idsede_partenza' => 0]).' ]}
    </div>';

// Quantità
echo '
    <div class="col-md-4">
        {[ "type": "number", "label": "'.tr('Q.tà su questo automezzo').'", "name": "qta", "value": "'.$qta.'", "decimals": "qta" ]}
    </div>';

echo '
    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
	</div>
</form>';

echo '
<script>
    $(document).ready(function(){init();});
</script>';

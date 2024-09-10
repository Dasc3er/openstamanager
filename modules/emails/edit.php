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
use Models\Module;

if (!$record['predefined']) {
    $attr = '';
} else {
    $attr = 'readonly';
    echo '<div class="alert alert-warning">'.tr('Alcune impostazioni non possono essere modificate per questo template.').'</div>';
}

?>
<form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">

	<!-- DATI -->
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Dati'); ?></h3>
		</div>

		<div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    {[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "name", "value": "$title$", "required": 1, "extra": "<?php echo $attr; ?>" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "span", "label": "<?php echo tr('Modulo del template'); ?>", "name": "module", "values": "query=SELECT `zz_modules`.`id`, `title` AS descrizione FROM `zz_modules` LEFT JOIN `zz_modules_lang` ON (`zz_modules`.`id` = `zz_modules_lang`.`id_record` AND `zz_modules_lang`.`id_lang` = <?php echo prepare(Models\Locale::getDefault()->id); ?>) WHERE `enabled` = 1", "value": "<?php echo Module::find($record['id_module'])->getTranslation('title'); ?>" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    {[ "type": "select", "label": "<?php echo tr('Indirizzo email'); ?>", "name": "smtp", "value": "$id_account$", "ajax-source": "smtp" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "<?php echo tr('Richiedi notifica di lettura'); ?>", "name": "read_notify", "value": "$read_notify$", "placeholder": "<?php echo tr('Richiedi la notifica di lettura al destinatario.'); ?>" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Oggetto'); ?>", "name": "subject", "value": "$subject$" ]}
                </div>
                <div class="col-md-3">
                    {[ "type": "select", "label": "<?php echo tr('Proponi destinatari'); ?>", "name": "indirizzi_proposti", "value": "$indirizzi_proposti$", "values":"list=\"0\":\"<?php echo tr('Nessuno'); ?>\", \"1\":\"<?php echo tr('Clienti'); ?>\", \"2\":\"<?php echo tr('Fornitori'); ?>\", \"3\":\"<?php echo tr('Tutti'); ?>\" " ]}
                </div>
                <div class="col-md-3">
                    {[ "type": "select", "label": "<?php echo tr('Tipologia destinatari'); ?>", "name": "type", "value": "$type$", "values":"list=\"a\":\"<?php echo tr('A'); ?>\", \"cc\":\"<?php echo tr('CC'); ?>\", \"bcc\":\"<?php echo tr('CCN'); ?>\" ", "required":1 ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('CC'); ?>", "name": "cc", "value": "$cc$", "help": "<?php echo 'Copia carbone.'; ?>" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('CCN'); ?>", "name": "bcc", "value": "$bcc$", "help": "<?php echo 'Copia carbone nascosta.'; ?>" ]}
                </div>

                <div class="col-md-4">
					<?php $records[0]['icon'] = (empty($records[0]['icon'])) ? 'fa fa-envelope' : $records[0]['icon']; ?>
                    {[ "type": "text", "label": "<?php echo tr('Icona'); ?>", "name": "icon", "value": "<?php echo $records[0]['icon']; ?>" ,"help":"<?php echo tr('Es. \'fa fa-envelope\''); ?>" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "select", "label": "<?php echo tr('Rispondi a'); ?>", "name": "tipo_reply_to", "values": "list=\"0\":\"Mittente (predefinito)\", \"email_user\":\"Email dell'utente che esegue l'invio\", \"email_fissa\":\"Destinatario fisso\"", "value": "$tipo_reply_to$", "help": "<?php echo 'Indirizzo email a cui rispondere'; ?>" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "email", "label": "<?php echo tr('Destinatario fisso'); ?>", "name": "reply_to", "value": "$reply_to$", "help": "<?php echo 'Rispondi a questo indirizzo e-mail.'; ?>" ]}
                </div>
            </div>

<?php

// Stampe
$selected_prints = $dbo->fetchArray('SELECT id_print FROM em_print_template WHERE id_template = '.prepare($id_record));
$selected_prints = array_column($selected_prints, 'id_print');

$selected_mansioni = $dbo->fetchArray('SELECT idmansione FROM em_mansioni_template WHERE id_template = '.prepare($id_record));
$selected_mansioni = array_column($selected_mansioni, 'idmansione');

echo '

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "select", "multiple": "1", "label": "'.tr('Stampe').'", "name": "prints[]", "value": "'.implode(',', $selected_prints).'", "values": "query=SELECT `zz_prints`.`id`, `zz_prints_lang`.`title` AS text FROM `zz_prints` LEFT JOIN `zz_prints_lang` ON (`zz_prints`.`id` = `zz_prints_lang`.`id_record` AND `zz_prints_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `id_module` = '.prepare($record['id_module']).' AND `enabled`=1 AND `is_record`=1" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "select", "multiple": "1", "label": "'.tr('Mansioni').'", "name": "idmansioni[]", "value": "'.implode(',', $selected_mansioni).'", "ajax-source": "mansioni" ]}
                </div>
            </div>';

?>

            <div class="row">
                <div class="col-md-12">
                    <?php echo input([
                        'type' => 'ckeditor',
                        'use_full_ckeditor' => 1,
                        'label' => tr('Contenuto'),
                        'name' => 'body',
                        'value' => $record['body'],
                    ]);
?>
                </div>
            </div>

            <div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note interne'); ?>", "name": "note_aggiuntive", "value": "$note_aggiuntive$", "class": "unblockable" ]}
				</div>
			</div>

<?php

// Variabili utilizzabili
$module = Module::find($record['id_module']);
$variables = $module->getPlaceholders($id_record);

echo '
<!-- Istruzioni per il contenuto -->
<div class="card card-info">
    <div class="card-header">
        <h3 class="card-title">'.tr('Variabili').'</h3>
    </div>

    <div class="card-body">';

if (!empty($variables)) {
    echo '
        <p>'.tr("Puoi utilizzare le seguenti variabili nell'oggetto e nel corpo della mail").':</p>
        <ul>';

    foreach ($variables as $variable => $value) {
        echo '
            <li><code>'.$variable.'</code></li>';
    }

    echo '
        </ul>';
} else {
    echo '
        <p><i class="fa fa-warning"></i> '.tr('Non sono state definite variabili da utilizzare nel template').'.</p>';
}

echo '
    </div>
</div>

<hr>';

?>

        </div>
    </div>

</form>
<?php
if (!empty($newsletters[0])) {
    echo '
    <div class="alert alert-danger">
        '.tr('Questo template non può essere rimosso dal sistema perchè collegato alle seguenti newsletter:').'
        <ul>';

    foreach ($newsletters as $newsletter) {
        echo '
            <li>'.Modules::link('Newsletter', $newsletter->id, $newsletter->getTranslation('title'), null, '').'</li>';
    }

    echo '
        </ul>
    </div>';
} elseif (!$record['predefined']) {
    ?>
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

<?php
}
?>

<script>
    $(document).ready(function () {
        if ($("#tipo_reply_to").val() != 'email_fissa') {
            $("#reply_to").val("");
            $("#reply_to").attr("readonly", true)
        }
    });

    $("#tipo_reply_to").on("change", function () {
        if ($(this).val() == 'email_fissa') {
            $("#reply_to").attr("readonly", false)
        } else {
            $("#reply_to").val("");
            $("#reply_to").attr("readonly", true)
        }
    });
</script>
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
use Models\OperationLog;
use Modules\Anagrafiche\Anagrafica;
use Modules\Emails\Mail;
use Modules\Emails\Template;
use Modules\Fatture\Fattura;
use Modules\Fatture\Tipo;
use Modules\Interventi\Intervento;
use Modules\Interventi\Stato;
use Util\Zip;

// Segmenti
$id_fatture = Module::where('name', 'Fatture di vendita')->first()->id;
if (!isset($_SESSION['module_'.$id_fatture]['id_segment'])) {
    $segments = Modules::getSegments($id_fatture);
    $_SESSION['module_'.$id_fatture]['id_segment'] = $segments[0]['id'] ?? null;
}
$id_segment = $_SESSION['module_'.$id_fatture]['id_segment'];
$idtipodocumento = $dbo->selectOne('co_tipidocumento', ['id'], [
    'predefined' => 1,
    'dir' => 'entrata',
])['id'];

switch (post('op')) {
    case 'export_bulk':
        $dir = base_dir().'/files/export_interventi/';
        directory($dir.'tmp/');

        // Rimozione dei contenuti precedenti
        $files = glob($dir.'/*.zip');
        foreach ($files as $file) {
            delete($file);
        }

        // Selezione degli interventi da stampare
        $interventi = $dbo->fetchArray('SELECT in_interventi.id, in_interventi.codice, data_richiesta, ragione_sociale FROM in_interventi INNER JOIN an_anagrafiche ON in_interventi.idanagrafica=an_anagrafiche.idanagrafica WHERE in_interventi.id IN('.implode(',', $id_records).')');

        if (!empty($interventi)) {
            foreach ($interventi as $r) {
                $print = Prints::getModulePredefinedPrint($id_module);

                Prints::render($print['id'], $r['id'], $dir.'tmp/', false, false);
            }

            $dir = slashes($dir);
            $file = slashes($dir.'interventi_'.time().'.zip');

            // Creazione zip
            if (extension_loaded('zip')) {
                Zip::create($dir.'tmp/', $file);

                // Invio al browser dello zip
                download($file);

                // Rimozione dei contenuti
                delete($dir.'tmp/');
            }
        }

        break;

    case 'create_invoice':
        $id_documento_cliente = [];
        $fatturati = [];
        $n_interventi = 0;

        $data = date('Y-m-d');
        $dir = 'entrata';
        $tipo_documento = Tipo::where('id', post('idtipodocumento'))->first();

        $id_conto = setting('Conto predefinito fatture di vendita');

        $accodare = post('accodare');
        $id_segment = post('id_segment');
        $raggruppamento = post('raggruppamento');

        $where = '';
        $query = 'SELECT `in_interventi`.*, IFNULL((SELECT MIN(`orario_inizio`) FROM `in_interventi_tecnici` WHERE `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id`), `in_interventi`.`data_richiesta`) AS data, `in_statiintervento_lang`.`title` AS stato, `in_interventi`.`codice` AS codice_intervento FROM `in_interventi` INNER JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`id` LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento_lang`.`id_record`=`in_statiintervento`.`id` AND `in_statiintervento_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') WHERE `in_statiintervento`.`is_fatturabile`=1 AND `in_interventi`.`id` NOT IN (SELECT `idintervento` FROM `co_righe_documenti` WHERE `idintervento` IS NOT NULL) AND `in_interventi`.`id` IN ('.implode(',', $id_records).')';

        // Se non è attiva la relativa impostazione considero solo interventi non collegati a contratti, ordini o preventivi (default)
        if (!setting('Permetti fatturazione delle attività collegate a contratti')) {
            $where = ' AND in_interventi.id_contratto IS NULL';

            $num_interventi_collegati_a_contratti = $dbo->fetchArray($query.' AND in_interventi.id_contratto IS NOT NULL');

            if (count($num_interventi_collegati_a_contratti) > 0) {
                flash()->warning(tr('_NUM_ interventi collegati a contratti non sono stati fatturati. Puoi procedere alla fatturazione abilitando la relativa impostazione: _SETTING_', [
                    '_NUM_' => count($num_interventi_collegati_a_contratti),
                    '_SETTING_' => Modules::link('Impostazioni', null, tr('Permetti fatturazione delle attività collegate a contratti'), true, null, true, null, '&search=Permetti fatturazione delle attività collegate a contratti'),
                ]));
            }
        }

        if (!setting('Permetti fatturazione delle attività collegate a ordini')) {
            $where .= ' AND in_interventi.id_ordine IS NULL';

            $num_interventi_collegati_a_ordini = $dbo->fetchArray($query.' AND in_interventi.id_ordine IS NOT NULL');

            if (count($num_interventi_collegati_a_ordini) > 0) {
                flash()->warning(tr('_NUM_ interventi collegati a ordini non sono stati fatturati. Puoi procedere alla fatturazione abilitando la relativa impostazione: _SETTING_', [
                    '_NUM_' => count($num_interventi_collegati_a_ordini),
                    '_SETTING_' => Modules::link('Impostazioni', null, tr('Permetti fatturazione delle attività collegate a ordini'), true, null, true, null, '&search=Permetti fatturazione delle attività collegate a ordini'),
                ]));
            }
        }
        if (!setting('Permetti fatturazione delle attività collegate a preventivi')) {
            $where .= ' AND in_interventi.id_preventivo IS NULL';

            $num_interventi_collegati_a_preventivi = $dbo->fetchArray($query.' AND in_interventi.id_preventivo IS NOT NULL');

            if (count($num_interventi_collegati_a_preventivi) > 0) {
                flash()->warning(tr('_NUM_ interventi collegati a preventivi non sono stati fatturati. Puoi procedere alla fatturazione abilitando la relativa impostazione: _SETTING_', [
                    '_NUM_' => count($num_interventi_collegati_a_preventivi),
                    '_SETTING_' => Modules::link('Impostazioni', null, tr('Permetti fatturazione delle attività collegate a preventivi'), true, null, true, null, '&search=Permetti fatturazione delle attività collegate a preventivi'),
                ]));
            }
        }

        $interventi = $dbo->fetchArray($query.' '.$where);

        // Lettura righe selezionate
        foreach ($interventi as $intervento) {
            if (!empty($intervento['idclientefinale'])) {
                $id_anagrafica = $intervento['idclientefinale'];
            } else {
                $id_anagrafica = $intervento['idanagrafica'];
            }

            $idsede_destinazione = $raggruppamento == 'sede' ? $intervento['idsede_destinazione'] : 0;
            $id_documento = $id_documento_cliente[$id_anagrafica][$idsede_destinazione];

            $anagrafica = Anagrafica::find($id_anagrafica);
            $id_iva = $anagrafica->idiva_vendite ?: setting('Iva predefinita');

            // Se non c'è già una fattura appena creata per questo cliente, creo una fattura nuova
            if (empty($id_documento)) {
                if (!empty($accodare)) {
                    $where = $raggruppamento == 'sede' ? ' AND `idsede_destinazione` = '.prepare($intervento['idsede_destinazione']) : '';
                    $documento = $dbo->fetchOne('SELECT `co_documenti`.`id` FROM `co_documenti` INNER JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id` LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` INNER JOIN `zz_segments` ON `zz_segments`.`id` = `co_documenti`.`id_segment` WHERE `co_statidocumento_lang`.`title` = "Bozza"  AND `co_documenti`.`idanagrafica` = '.prepare($id_anagrafica).' AND `co_tipidocumento`.`id`='.prepare($tipo_documento['id']).' AND `co_documenti`.`id_segment` = '.prepare($id_segment).$where);

                    $id_documento = $documento['id'];
                    $id_documento_cliente[$id_anagrafica] = $id_documento;
                }

                if (empty($id_documento)) {
                    $fattura = Fattura::build($anagrafica, $tipo_documento, $data, $id_segment);
                    $fattura->idsede_destinazione = $idsede_destinazione;
                    $fattura->save();

                    $id_documento = $fattura->id;
                    $id_documento_cliente[$id_anagrafica][$idsede_destinazione] = $id_documento;
                }
            }

            $descrizione = str_replace("'", ' ', strip_tags((string) $module->replacePlaceholders($intervento['id'], setting('Descrizione personalizzata in fatturazione')))) ?: tr('Attività numero _NUM_ del _DATE_', [
                '_NUM_' => $intervento['codice_intervento'],
                '_DATE_' => Translator::dateToLocale($intervento['data']),
            ]);

            aggiungi_intervento_in_fattura($intervento['id'], $id_documento, $descrizione, $id_iva, $id_conto);
            $fatturati[] = $intervento['id'];
            ++$n_interventi;
        }

        if ($n_interventi > 0) {
            flash()->info(tr('_NUM_ interventi fatturati.', [
                '_NUM_' => $n_interventi,
            ]));
        }

        if (!empty(array_diff($id_records, $fatturati))) {
            flash()->warning(tr('_NUM_ interventi non sono stati fatturati.', [
                '_NUM_' => sizeof(array_diff($id_records, $fatturati)),
            ]));
        }

        break;

    case 'change_status':
        $id_stato = post('id_stato');

        $n_interventi = 0;
        $stato = Stato::find($id_stato);

        // Lettura righe selezionate
        foreach ($id_records as $id) {
            $intervento = Intervento::find($id);

            $intervento->stato()->associate($stato);
            $intervento->save();

            ++$n_interventi;
        }

        if ($n_interventi > 0) {
            flash()->info(tr('Stato cambiato a _NUM_ attività!', [
                '_NUM_' => $n_interventi,
            ]));
        } else {
            flash()->warning(tr('Nessuna attività modificata!'));
        }

        break;

    case 'copy_bulk':
        $id_stato = post('idstatointervento');
        $data_richiesta = post('data_richiesta');
        $copia_sessioni = post('sessioni');
        $copia_righe = post('righe');
        $copia_impianti = post('impianti');
        $copia_allegati = post('allegati');

        foreach ($id_records as $idintervento) {
            $intervento = Intervento::find($idintervento);

            $new = $intervento->replicate();
            $new->idstatointervento = $id_stato;

            // Calcolo del nuovo codice sulla base della data di richiesta
            $new->codice = Intervento::getNextCodice($data_richiesta, $new->id_segment);
            $new->firma_file = '';
            $new->firma_data = '';
            $new->save();

            $id_record = $new->id;

            // Copio le righe
            if (!empty($copia_righe)) {
                $righe = $intervento->getRighe();
                foreach ($righe as $riga) {
                    $new_riga = $riga->replicate();
                    $new_riga->setDocument($new);

                    $new_riga->qta_evasa = 0;
                    $new_riga->save();
                }
            }

            // Copia delle sessioni
            $numero_sessione = 0;
            if (!empty($copia_sessioni)) {
                $sessioni = $intervento->sessioni;
                foreach ($sessioni as $sessione) {
                    // Se è la prima sessione che copio importo la data con quella della richiesta
                    if ($numero_sessione == 0) {
                        $orario_inizio = date('Y-m-d', strtotime($data_richiesta)).' '.date('H:i:s', strtotime($sessione->orario_inizio));
                    } else {
                        $diff = strtotime($sessione->orario_inizio) - strtotime((string) $inizio_old);
                        $orario_inizio = date('Y-m-d H:i:s', strtotime($new_sessione->orario_inizio) + $diff);
                    }

                    $diff_fine = strtotime($sessione->orario_fine) - strtotime($sessione->orario_inizio);
                    $orario_fine = date('Y-m-d H:i:s', strtotime($orario_inizio) + $diff_fine);

                    $new_sessione = $sessione->replicate();
                    $new_sessione->idintervento = $new->id;

                    $new_sessione->orario_inizio = $orario_inizio;
                    $new_sessione->orario_fine = $orario_fine;
                    $new_sessione->save();

                    ++$numero_sessione;
                    $inizio_old = $sessione->orario_inizio;
                }
            }

            // Copia degli impianti
            if (!empty($copia_impianti)) {
                $impianti = $dbo->select('my_impianti_interventi', '*', [], ['idintervento' => $intervento->id]);
                foreach ($impianti as $impianto) {
                    $dbo->insert('my_impianti_interventi', [
                        'idintervento' => $id_record,
                        'idimpianto' => $impianto['idimpianto'],
                    ]);
                }

                $componenti = $dbo->select('my_componenti_interventi', '*', [], ['id_intervento' => $intervento->id]);
                foreach ($componenti as $componente) {
                    $dbo->insert('my_componenti_interventi', [
                        'id_intervento' => $id_record,
                        'id_componente' => $componente['id_componente'],
                    ]);
                }
            }

            // copia allegati
            if (!empty($copia_allegati)) {
                $allegati = $intervento->uploads();
                foreach ($allegati as $allegato) {
                    $allegato->copia([
                        'id_module' => $new->getModule()->id,
                        'id_record' => $new->id,
                    ]);
                }
            }
        }

        flash()->info(tr('Attività duplicate correttamente!'));

        break;

    case 'delete_bulk':
        $count = 0;
        $count_tot = sizeof($id_records);

        foreach ($id_records as $id) {
            $intervento = Intervento::find($id);

            $elementi = $dbo->fetchArray('
            SELECT
                `co_documenti`.`id`
            FROM
                `co_documenti`
                INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id`
            WHERE
                `co_righe_documenti`.`idintervento` = '.prepare($id).' OR
                (`co_righe_documenti`.`original_document_id` = '.prepare($id).' AND `co_righe_documenti`.`original_document_type` = \'Modules\\\\Interventi\\\\Intervento\')
            GROUP BY id

            ORDER BY `data`');

            if (empty($elementi)) {
                try {
                    // Eliminazione associazioni tra interventi e contratti
                    $dbo->query('UPDATE co_promemoria SET idintervento = NULL WHERE idintervento='.prepare($id_record));

                    $intervento->delete();

                    // Elimino il collegamento al componente
                    $dbo->query('DELETE FROM my_componenti WHERE id_intervento='.prepare($id_record));

                    // Eliminazione associazione tecnici collegati all'intervento
                    $dbo->query('DELETE FROM in_interventi_tecnici WHERE idintervento='.prepare($id_record));

                    // Eliminazione associazione interventi e my_impianti
                    $dbo->query('DELETE FROM my_impianti_interventi WHERE idintervento='.prepare($id_record));
                } catch (InvalidArgumentException) {
                }
            } else {
                ++$count;
            }
        }
        $count_eliminati = $count_tot - $count;
        flash()->info(tr('_NUM_ Interventi eliminati!', [
            '_NUM_' => $count_eliminati,
        ]));

        if ($count > 0) {
            flash()->warning(tr('_NUM_ Interventi non eliminati perchè sono collegati ad almeno un documento!', [
                '_NUM_' => $count,
            ]));
        }

        break;

    case 'print_summary':
        $_SESSION['superselect']['interventi'] = $id_records;
        $id_print = Prints::getPrints()['Riepilogo interventi'];

        redirect(base_path().'/pdfgen.php?id_print='.$id_print.'&tipo='.post('tipo'));
        exit;

    case 'send_mail':
        $template = Template::find(post('id_template'));

        $list = [];
        foreach ($id_records as $id) {
            $intervento = Intervento::find($id);
            $id_anagrafica = $intervento->idanagrafica;

            // Selezione destinatari e invio mail
            if (!empty($template)) {
                $creata_mail = false;
                $emails = [];

                // Aggiungo email anagrafica
                if (!empty($intervento->anagrafica->email)) {
                    $emails[] = $intervento->anagrafica->email;
                    $mail = Mail::build(auth()->getUser(), $template, $id);
                    $mail->addReceiver($intervento->anagrafica->email);
                    $creata_mail = true;
                }

                // Aggiungo email referenti in base alla mansione impostata nel template
                $mansioni = $dbo->select('em_mansioni_template', 'idmansione', [], ['id_template' => $template->id]);
                foreach ($mansioni as $mansione) {
                    $referenti = $dbo->table('an_referenti')->where('idmansione', $mansione['idmansione'])->where('idanagrafica', $id_anagrafica)->where('email', '!=', '')->get();
                    if (!$referenti->isEmpty() && $creata_mail == false) {
                        $mail = Mail::build(auth()->getUser(), $template, $id);
                        $creata_mail = true;
                    }

                    foreach ($referenti as $referente) {
                        if (!in_array($referente->email, $emails)) {
                            $emails[] = $referente->email;
                            $mail->addReceiver($referente->email);
                        }
                    }
                }
                if ($creata_mail == true) {
                    $mail->save();
                    OperationLog::setInfo('id_email', $mail->id);
                    OperationLog::setInfo('id_module', $id_module);
                    OperationLog::setInfo('id_record', $mail->id_record);
                    OperationLog::build('send-email');

                    array_push($list, $intervento->codice);
                }
            }
        }

        if ($list) {
            flash()->info(tr('Mail inviata per le attività _LIST_ !', [
                '_LIST_' => implode(',', $list),
            ]));
        }

        break;
}

$operations['change_status'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Cambia stato'),
    'data' => [
        'title' => tr('Vuoi davvero cambiare lo stato per questi interventi?'),
        'msg' => tr('Seleziona lo stato in cui spostare tutti gli interventi non completati').'.<br>
            <br>{[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT `in_statiintervento`.`id`, `title` as descrizione, `colore` AS _bgcolor_ FROM `in_statiintervento` LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento`.`id` = `in_statiintervento_lang`.`id_record` AND `in_statiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `deleted_at` IS NULL ORDER BY `title`" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

$operations['copy_bulk'] = [
    'text' => '<span><i class="fa fa-clone"></i> '.tr('Duplica'),
    'data' => [
        'title' => tr('Vuoi davvero fare una copia degli interventi selezionati?'),
        'msg' => '<br>{[ "type": "timestamp", "label": "'.tr('Data/ora richiesta').'", "name": "data_richiesta", "required": 0, "value": "-now-", "required":1 ]}
            <br>{[ "type": "select", "label": "'.tr('Stato').'", "name": "idstatointervento", "required": 1, "values": "query=SELECT `in_statiintervento`.`id`, `title` as descrizione, `colore` AS _bgcolor_ FROM `in_statiintervento` LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento`.`id` = `in_statiintervento_lang`.`id_record` AND `in_statiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `deleted_at` IS NULL ORDER BY `title`", "value": "" ]}
            <br>{[ "type":"checkbox", "label":"'.tr('Duplica righe').'", "name":"righe", "value":"" ]}
            <br>{[ "type":"checkbox", "label":"'.tr('Duplica sessioni').'", "name":"sessioni", "value":"" ]}
            <br>{[ "type":"checkbox", "label":"'.tr('Duplica impianti').'", "name":"impianti", "value":"" ]}
            <br>{[ "type":"checkbox", "label":"'.tr('Duplica allegati').'", "name":"allegati", "value":"" ]}
            <style>.swal2-modal{ width:600px !important; }</style>',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

$operations['delete_bulk'] = [
    'text' => '<span><i class="fa fa-trash"></i> '.tr('Elimina').'</span>',
];

$operations['export_bulk'] = [
    'text' => '<span><i class="fa fa-file-archive-o"></i> '.tr('Esporta stampe'),
    'data' => [
        'title' => tr('Vuoi davvero esportare queste stampe in un archivio ZIP?'),
        'msg' => '',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => true,
    ],
];

$operations['create_invoice'] = [
    'text' => '<span><i class="fa fa-file-code-o"></i> '.tr('Fattura _TYPE_', ['_TYPE_' => strtolower((string) $module->getTranslation('title'))]),
    'data' => [
        'title' => tr('Fatturare le attività selezionate?'),
        'msg' => '<small>Verranno fatturate solo le attività completate, <br>non collegate a contratti o preventivi.</small><br>
            <br>{[ "type": "checkbox", "label": "<small>'.tr('Aggiungere alle fatture di vendita non ancora emesse?').'</small>", "placeholder": "'.tr('Aggiungere alle fatture di vendita nello stato bozza?').'", "name": "accodare" ]}<br>
            {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "ajax-source": "segmenti", "select-options": '.json_encode(['id_module' => $id_fatture, 'is_sezionale' => 1]).', "value": "'.$id_segment.'", "select-options-escape": true ]}<br>
            {[ "type": "select", "label": "'.tr('Tipo documento').'", "name": "idtipodocumento", "required": 1, "values": "query=SELECT `co_tipidocumento`.`id`, CONCAT(`codice_tipo_documento_fe`, \' - \', `title`) AS descrizione FROM `co_tipidocumento` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `enabled` = 1 AND `dir` =\'entrata\' ORDER BY `codice_tipo_documento_fe`", "value": "'.$idtipodocumento.'" ]}<br>
            {[ "type": "select", "label": "'.tr('Raggruppa per').'", "name": "raggruppamento", "required": 1, "values": "list=\"cliente\":\"Cliente\",\"sede\":\"Sede\"", "value": "'.setting('Raggruppamento fatturazione massiva attività').'" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

$operations['firma-intervento'] = [
    'text' => '<span><i class="fa fa-pencil"></i> '.tr('Firma interventi').'</span>',
    'data' => [
        'title' => tr('Firma'),
        'type' => 'modal',
        'origine' => 'interventi',
        'url' => $module->fileurl('modals/firma.php'),
    ],
];

$operations['send_mail'] = [
    'text' => '<span><i class="fa fa-envelope"></i> '.tr('Invia mail').'</span>',
    'data' => [
        'title' => tr('Inviare mail?'),
        'msg' => tr('Per ciascuna attività selezionata, verrà inviata una mail').'<br><br>
            {[ "type": "select", "label": "'.tr('Template').'", "name": "id_template", "required": "1", "values": "query=SELECT `em_templates`.`id`, `em_templates_lang`.`title` AS descrizione FROM `em_templates` LEFT JOIN `em_templates_lang` ON (`em_templates`.`id` = `em_templates_lang`.`id_record` AND `em_templates_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `id_module`='.prepare($id_module).' AND `deleted_at` IS NULL;" ]}',
        'button' => tr('Invia'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

$operations['print_summary'] = [
    'text' => '<span><i class="fa fa-print"></i> '.tr('Stampa riepilogo'),
    'data' => [
        'title' => tr('Stampare il riepilogo delle attività selezionate?'),
        'msg' => '<br>{[ "type": "select", "label": "'.tr('Stampa riepilogo').'", "name": "tipo", "required": "1", "values": "list=\"cliente\": \"Clienti\", \"interno\": \"Interno\"", "value": "cliente" ]}',
        'button' => tr('Stampa'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => true,
    ],
];



return $operations;

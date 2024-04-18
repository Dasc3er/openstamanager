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

namespace Modules\Interventi\Import;

use Importer\CSVImporter;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Tipo as TipoAnagrafica;
use Modules\Impianti\Impianto;
use Modules\Interventi\Components\Sessione;
use Modules\Interventi\Intervento;
use Modules\Interventi\Stato;
use Modules\TipiIntervento\Tipo as TipoIntervento;

/**
 * Struttura per la gestione delle operazioni di importazione (da CSV) degli Interventi.
 *
 * @since 2.4.52
 */
class CSV extends CSVImporter
{
    public function getAvailableFields()
    {
        return [
            [
                'field' => 'codice',
                'label' => 'Codice',
                'primary_key' => true,
            ],
            [
                'field' => 'telefono',
                'label' => 'Telefono',
            ],
            [
                'field' => 'data',
                'label' => 'Data',
            ],
            [
                'field' => 'data_richiesta',
                'label' => 'Data richiesta',
            ],
            [
                'field' => 'ora_inizio',
                'label' => 'Ora inizio',
            ],
            [
                'field' => 'ora_fine',
                'label' => 'Ora fine',
            ],
            [
                'field' => 'tecnico',
                'label' => 'Tecnico',
            ],
            [
                'field' => 'tipo',
                'label' => 'Tipo',
            ],
            [
                'field' => 'note',
                'label' => 'Note',
            ],
            [
                'field' => 'impianto',
                'label' => 'Impianto',
            ],
            [
                'field' => 'richiesta',
                'label' => 'Richiesta',
            ],
            [
                'field' => 'descrizione',
                'label' => 'Descrizione',
            ],
            [
                'field' => 'stato',
                'label' => 'Stato',
            ],
        ];
    }

    public function import($record)
    {
        $database = database();
        $primary_key = $this->getPrimaryKey();

        if (!empty($record['telefono'])) {
            $anagrafica = Anagrafica::where('telefono', $record['telefono'])->first();
        }

        if (!empty($record['impianto'])) {
            $impianto = Impianto::where('matricola', $record['impianto'])->first();
        }

        if (!empty($anagrafica)) {
            $intervento = null;

            // Ricerca sulla base della chiave primaria se presente
            if (!empty($primary_key)) {
                $intervento = Intervento::where($primary_key, $record[$primary_key])->first();
            }

            // Verifico tipo e stato per creare l'intervento
            if (empty($record['tipo'])) {
                $tipo = TipoIntervento::where('codice', 'GEN')->first();
            } else {
                $tipo = TipoIntervento::where('codice', $record['tipo'])->first();
            }
            unset($record['tipo']);

            if (empty($record['stato'])) {
                $stato = Stato::find((new Stato())->getByField('title', 'Completato', \Models\Locale::getPredefined()->id));
            } else {
                $stato = Stato::find((new Stato())->getByField('title', $record['stato']));
            }
            unset($record['stato']);

            // Crea l'intervento
            if (empty($intervento)) {
                $intervento = Intervento::build($anagrafica, $tipo, $stato, $record['data_richiesta']);
            }
            unset($record['codice']);
            unset($record['data']);
            unset($record['ora_inizio']);
            unset($record['telefono']);

            if (!empty($impianto)) {
                $collegamento = $database->table('my_impianti_interventi')->where('idimpianto', $impianto['id'])->where('idintervento', $intervento['id'])->first();

                if (empty($collegamento)) {
                    // Collega l'impianto all'intervento
                    $database->query('INSERT INTO my_impianti_interventi(idimpianto, idintervento) VALUES('.prepare($impianto['id']).', '.prepare($intervento['id']).')');
                }
                unset($record['impianto']);
            }

            // Inserisce la data richiesta e la richiesta
            $intervento->data_richiesta = $record['data_richiesta'];
            $intervento->richiesta = $record['richiesta'];

            // Inserisce la descrizione se presente
            if (!empty($record['descrizione'])) {
                $intervento->descrizione = $record['descrizione'];
            }

            // Inserisce le note se presenti
            if (!empty($record['note'])) {
                $intervento->informazioniaggiuntive = $record['note'];
            }

            $intervento->save();

            $inizio = date('Y-m-d H:i', strtotime($record['data'].' '.$record['ora_inizio']));
            $fine = date('Y-m-d H:i', strtotime($record['data'].' '.$record['ora_fine']));

            // Verifica il tecnico e inserisce la sessione
            $anagrafica_t = Anagrafica::where('ragione_sociale', $record['tecnico'])->first();
            $tipo = $database->fetchOne('SELECT `idtipoanagrafica` FROM `an_tipianagrafiche_anagrafiche` WHERE `idanagrafica` = '.prepare($anagrafica_t['idanagrafica']));
            $tecnico = (new TipoAnagrafica())->getByField('title', 'Tecnico', \Models\Locale::getPredefined()->id);

            if ($tipo = $tecnico) {
                $anagrafica_t['tipo'] = TipoAnagrafica::find($tecnico);
            }

            if (!empty($record['data']) && !empty($record['ora_inizio']) && !empty($record['tecnico'])) {
                $sessione = Sessione::build($intervento, $anagrafica_t, $inizio, $fine);
                $sessione->save();
            }
        }
    }

    public static function getExample()
    {
        return [
            ['Codice', 'Telefono', 'Data', 'Data richiesta', 'Ora inizio', 'Ora fine', 'Tecnico', 'Tipo', 'Note', 'Impianto', 'Richiesta', 'Descrizione', 'Stato'],
            ['00001/2024', '+39 0429 60 25 12', '07/11/2024', '03/11/2024', '8:30', '9:30', 'Stefano Bianchi', '', '', '12345-85A22', 'Manutenzione ordinaria', 'eseguito intervento di manutenzione', 'Da programmare'],
            ['0002/2024', '+39 0429 60 25 12', '08/11/2024', '04/11/2024', '11:20', '', 'Stefano Bianchi', '', '', '12345-85B23', 'Manutenzione ordinaria', 'eseguito intervento di manutenzione', ''],
        ];
    }
}

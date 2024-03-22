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

namespace Plugins\ImportFE;

use Models\Module;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Nazione;
use Modules\Anagrafiche\Tipo as TipoAnagrafica;
use Modules\Banche\Banca;
use Modules\Fatture\Fattura;
use Modules\Fatture\Stato;
use Modules\Fatture\Tipo as TipoFattura;
use Util\XML;

/**
 * Classe per la gestione della fatturazione elettronica in XML.
 *
 * @since 2.4.9
 */
class FatturaElettronica
{
    protected static $directory;

    /** @var array Percorso del file XML */
    protected $file;

    /** @var array XML della fattura */
    protected $xml;

    /** @var Fattura Fattura collegata */
    protected $fattura;

    public function __construct($name)
    {
        $this->file = static::getImportDirectory().'/'.$name;

        if (string_ends_with($name, '.p7m')) {
            $file = XML::decodeP7M($this->file);

            if (!empty($file)) {
                delete($this->file);

                $this->file = $file;
            }
        }

        $this->xml = XML::readFile($this->file);

        // Individuazione fattura pre-esistente
        $dati_generali = $this->getBody()['DatiGenerali']['DatiGeneraliDocumento'];
        $data = $dati_generali['Data'];
        $numero = $dati_generali['Numero'];
        $progressivo_invio = $this->getHeader()['DatiTrasmissione']['ProgressivoInvio'];

        $fattura = Fattura::where([
            'progressivo_invio' => $progressivo_invio,
            'numero_esterno' => $numero,
            'data' => $data,
        ])->first();

        if (!empty($fattura) && $fattura->tipo->dir == 'uscita') {
            throw new \UnexpectedValueException();
        }
    }

    public static function getImportDirectory()
    {
        if (!isset(self::$directory)) {
            $module = Module::find((new Module())->getByField('name', 'Fatture di acquisto'));

            $plugins = $module->plugins;
            if (!empty($plugins)) {
                $plugin = $plugins->first(function ($value, $key) {
                    return $value->getTranslation('name') == 'Fatturazione Elettronica';
                });

                self::$directory = base_dir().'/'.$plugin->upload_directory;
            }
        }

        return self::$directory;
    }

    public static function store($filename, $content)
    {
        $directory = static::getImportDirectory();
        $file = $directory.'/'.$filename;

        directory($directory);
        file_put_contents($file, $content);

        return $filename;
    }

    public static function isValid($name)
    {
        try {
            new static($name);

            return true;
        } catch (\UnexpectedValueException $e) {
            $file = static::getImportDirectory().'/'.$name;
            delete($file);

            return false;
        }
    }

    public static function manage($name)
    {
        try {
            $manager = new FatturaOrdinaria($name);

            $tipo = $manager->getBody()['DatiGenerali']['DatiGeneraliDocumento']['TipoDocumento'];
            if ($tipo == 'TD06') {
                $manager = new Parcella($name);
            }
        } catch (\UnexpectedValueException $e) {
            $manager = new FatturaSemplificata($name);
        }

        return $manager;
    }

    public function getHeader()
    {
        return $this->xml['FatturaElettronicaHeader'];
    }

    public function getBody()
    {
        return $this->xml['FatturaElettronicaBody'];
    }

    public function delete()
    {
        delete($this->file);
    }

    public function getAllegati()
    {
        $result = $this->getBody()['Allegati'];

        $result = $this->forceArray($result);

        return array_clean($result);
    }

    public function saveAllegati()
    {
        $allegati = $this->getAllegati();

        $id_module = (new Module())->getByField('name', 'Fatture di acquisto');

        $info = [
            'category' => tr('Fattura Elettronica'),
            'id_module' => $id_module,
            'id_record' => $this->fattura->id,
        ];

        foreach ($allegati as $allegato) {
            $content = base64_decode($allegato['Attachment']);

            $extension = '.pdf';
            if (!empty($allegato['FormatoAttachment'])) {
                $extension = '.'.strtolower($allegato['FormatoAttachment']);
            }

            $original = $allegato['NomeAttachment'].$extension;
            try {
                \Uploads::upload($content, array_merge($info, [
                    'name' => $allegato['NomeAttachment'],
                    'original_name' => $original,
                ]));
            } catch (\UnexpectedValueException $e) {
            }
        }

        // Registrazione XML come allegato
        \Uploads::upload($this->file, array_merge($info, [
            'name' => tr('Fattura Elettronica'),
            'original_name' => basename($this->file),
        ]));
    }

    public function findAnagrafica($type = 'Fornitore')
    {
        $info = $this->getAnagrafe();

        $anagrafica = Anagrafica::where('tipo', $type);

        if (!empty($info['partita_iva']) && !empty($info['codice_fiscale'])) {
            $anagrafica->where('piva', $info['partita_iva'])
                ->orWhere('codice_fiscale', $info['codice_fiscale'])
                ->orWhere('piva', 'like', '__'.$info['partita_iva'])
                ->orwhere('codice_fiscale', 'like', '__'.$info['codice_fiscale']);
        } elseif (!empty($info['codice_fiscale'])) {
            $anagrafica->where('codice_fiscale', $info['codice_fiscale'])
                ->orWhere('codice_fiscale', 'like', '__'.$info['codice_fiscale']);
        } elseif (!empty($info['partita_iva'])) {
            $anagrafica->where('piva', $info['partita_iva'])
                ->orWhere('piva', 'like', '__'.$info['partita_iva']);
        }

        $anagrafica = $anagrafica->get();

        // Se non trovo l'anagrafica tra i fornitori, provo a ricercarla anche tra i clienti
        if (empty($anagrafica)) {
            $type = 'Cliente';

            $anagrafica = Anagrafica::where('tipo', $type);

            if (!empty($info['partita_iva']) && !empty($info['codice_fiscale'])) {
                $anagrafica->where('piva', $info['partita_iva'])
                    ->orWhere('codice_fiscale', $info['codice_fiscale'])
                    ->orWhere('piva', 'like', '__'.$info['partita_iva'])
                    ->orwhere('codice_fiscale', 'like', '__'.$info['codice_fiscale']);
            } elseif (!empty($info['codice_fiscale'])) {
                $anagrafica->where('codice_fiscale', $info['codice_fiscale'])
                    ->orWhere('codice_fiscale', 'like', '__'.$info['codice_fiscale']);
            } elseif (!empty($info['partita_iva'])) {
                $anagrafica->where('piva', $info['partita_iva'])
                    ->orWhere('piva', 'like', '__'.$info['partita_iva']);
            }
        }

        return $anagrafica->first();
    }

    /**
     * Restituisce l'anagrafica collegata alla fattura, eventualmente generandola con i dati forniti.
     *
     * @param string $type
     *
     * @return Anagrafica
     */
    public function saveAnagrafica($type = 'Fornitore')
    {
        $anagrafica = $this->findAnagrafica($type);

        if (!empty($anagrafica)) {
            return $anagrafica;
        }

        $info = $this->getAnagrafe();

        $anagrafica = Anagrafica::build($info['ragione_sociale'], $info['nome'], $info['cognome'], [
            (new TipoAnagrafica())->getByField('name', $type),
        ]);

        if (!empty($info['partita_iva'])) {
            $anagrafica->partita_iva = $info['partita_iva'];
        }

        if (!empty($info['codice_fiscale'])) {
            $anagrafica->codice_fiscale = $info['codice_fiscale'];
        }

        // Informazioni sull'anagrafica
        if (!empty($info['rea'])) {
            if (!empty($info['rea']['codice'])) {
                $anagrafica->codicerea = $info['rea']['codice'];
            }

            if (!empty($info['rea']['capitale_sociale'])) {
                $anagrafica->capitale_sociale = $info['rea']['capitale_sociale'];
            }
        }

        $anagrafica->save();

        // Informazioni sulla sede
        $sede = $anagrafica->sedeLegale;

        $sede->indirizzo = $info['sede']['indirizzo'];
        $sede->cap = $info['sede']['cap'];
        $sede->citta = $info['sede']['citta'];
        if (!empty($info['sede']['provincia'])) {
            $sede->provincia = $info['sede']['provincia'];
        }
        $sede->nazione()->associate(Nazione::where('iso2', $info['sede']['nazione'])->first());

        $contatti = $info['contatti'];
        if (!empty($contatti)) {
            if (!empty($contatti['telefono'])) {
                $sede->telefono = $contatti['telefono'];
            }

            if (!empty($contatti['fax'])) {
                $sede->fax = $contatti['fax'];
            }

            if (!empty($contatti['email'])) {
                $sede->email = $contatti['email'];
            }
        }

        $sede->save();

        return $anagrafica;
    }

    /**
     * Registra la fattura elettronica come fattura del gestionale.
     *
     * @param int    $id_pagamento
     * @param int    $id_sezionale
     * @param int    $id_tipo
     * @param string $data_registrazione
     * @param int    $ref_fattura
     *
     * @return Fattura
     */
    public function saveFattura($id_pagamento, $id_sezionale, $id_tipo, $data_registrazione, $ref_fattura, $is_ritenuta_pagata = false)
    {
        $dati_generali = $this->getBody()['DatiGenerali']['DatiGeneraliDocumento'];
        $data = self::parseDate($dati_generali['Data']);

        $fattura = $this->prepareFattura($id_tipo, $data, $data_registrazione, $id_sezionale, $ref_fattura);
        $this->fattura = $fattura;

        $numero_esterno = $dati_generali['Numero'];
        $progressivo_invio = $this->getHeader()['DatiTrasmissione']['ProgressivoInvio'];

        $fattura->progressivo_invio = $progressivo_invio;
        $fattura->numero_esterno = $numero_esterno;
        $fattura->idpagamento = $id_pagamento;
        $fattura->is_ritenuta_pagata = $is_ritenuta_pagata;

        // Salvataggio banca fornitore se specificata nel file XML
        $info_pagamento = $this->getBody()['DatiPagamento']['DettaglioPagamento'];
        if ($info_pagamento['IBAN']) {
            $banca_fornitore = Banca::where('iban', $info_pagamento['IBAN'])->first();
            if (empty($banca_fornitore)) {
                $anagrafica = $fattura->anagrafica;
                $nome = $info_pagamento['IstitutoFinanziario'] ?: 'Banca di '.$anagrafica->ragione_sociale;
                try {
                    $banca_fornitore = Banca::build($anagrafica, $nome, $info_pagamento['IBAN'], $info_pagamento['BIC'] ?: '');
                } catch (\UnexpectedValueException $e) {
                    flash()->error(tr('Errore durante la creazione della banca: verificare la correttezza dei dati').'.');
                }
            }
        }

        // Banca addebito del cliente o banca collegata al pagamento
        if (!empty($fattura->anagrafica->idbanca_acquisti)) {
            $banca = $fattura->anagrafica->idbanca_acquisti;
        } else {
            $banca = Banca::where('id_pianodeiconti3', $fattura->pagamento->idconto_acquisti)->where('id_anagrafica', setting('Azienda predefinita'))->first()->id;
        }

        $fattura->id_banca_azienda = $banca;

        // Riferimento per nota di credito e debito
        $fattura->ref_documento = $ref_fattura ?: null;

        // Per il destinatario, la data di registrazione della fattura assume grande rilievo ai fini IVA, poiché determina la decorrenza dei termini per poter esercitare il diritto alla detrazione.
        // La data di ricezione della fattura è contenuta all’interno della “ricevuta di consegna” visibile al trasmittente della stessa.
        $fattura->data_registrazione = $data_registrazione;
        $fattura->data_competenza = $fattura->data;

        $stato_documento = (new Stato())->getByField('name', 'Emessa');
        $fattura->stato()->associate($stato_documento);

        $causali = $dati_generali['Causale'];
        if (!empty($causali)) {
            $note = '';
            foreach ($causali as $causale) {
                $note .= $causale;
            }

            $fattura->note = $note;
        }

        // Sconto finale da ScontoMaggiorazione: non importato
        $fattura->save();

        // Fix generazione idsede
        $fattura->refresh();

        return $fattura;
    }

    public function getFattura()
    {
        return $this->fattura;
    }

    public function save($info = [])
    {
        $this->saveFattura($info['id_pagamento'], $info['id_segment'], $info['id_tipo'], $info['data_registrazione'], $info['ref_fattura'], $info['is_ritenuta_pagata']);

        $this->saveRighe($info['articoli'], $info['iva'], $info['conto'], $info['movimentazione'], $info['crea_articoli'], $info['tipo_riga_riferimento'], $info['id_riga_riferimento'], $info['tipo_riga_riferimento_vendita'], $info['id_riga_riferimento_vendita'], $info['update_info'], $info['serial']);

        $this->saveAllegati();

        $this->getFattura()->save(['forza_emissione']);

        return $this->getFattura()->id;
    }

    public static function parseDate($data)
    {
        return date('Y-m-d', strtotime($data));
    }

    protected function prepareFattura($id_tipo, $data, $data_registrazione, $id_sezionale, $ref_fattura)
    {
        $anagrafica = $this->saveAnagrafica();

        $tipo = TipoFattura::where('id', $id_tipo)->first();

        $fattura = Fattura::build($anagrafica, $tipo, $data, $id_sezionale, null, $data_registrazione);
        $this->fattura = $fattura;

        // Riferimento per nota di credito e debito
        $fattura->ref_documento = $ref_fattura ?: null;

        return $fattura;
    }

    protected function forceArray($result)
    {
        $result = isset($result[0]) ? $result : [$result];

        return $result;
    }
}

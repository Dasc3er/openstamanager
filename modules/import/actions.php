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

use Models\Module;
use Modules\FileAdapters\FileAdapter;
use Modules\Importazione\Import;

include_once __DIR__.'/../../core.php';

$modulo_import = Module::find($id_module);

switch (filter('op')) {
    case 'add':
        $id_import = filter('id_import');
        $import = Import::find($id_import);
        $id_adapter = FileAdapter::getLocalConnector()->id;

        $id_record = $import->id;

        Uploads::upload($_FILES['file'], [
            'id_module' => $id_module,
            'id_record' => $id_record,
            'id_adapter' => $id_adapter,
        ]);

        break;

    case 'example':
        $id_import = filter('id_import');

        $import = Import::find($id_import);
        $import_manager = $import->class;

        if (!empty($import_manager)) {
            // Generazione percorso
            $file = $modulo_import->upload_directory.'/example-'.strtolower($import->getTranslation('name')).'.csv';
            $filepath = base_dir().'/'.$file;

            // Generazione del file
            $import_manager::createExample($filepath);

            echo base_path().'/'.$file;
        }

        break;

    case 'import':
        // Individuazione del modulo
        $import = Import::find($id_record);
        $import_manager = $import->class;

        // Dati indicati
        $include_first_row = post('include_first_row');
        $fields = (array) post('fields');
        $page = post('page');

        $limit = 500;

        // Inizializzazione del lettore CSV
        $filepath = base_dir().'/files/'.$record->directory.'/'.$record->filename;
        $csv = new $import_manager($filepath);
        foreach ($fields as $key => $value) {
            $csv->setColumnAssociation($key, (int) $value - 1);
        }

        // Generazione offset sulla base della pagina
        $offset = isset($page) ? $page * $limit : 0;

        // Ignora la prima riga se composta da header
        if ($offset == 0 && empty($include_first_row)) {
            ++$offset;
        }

        // Gestione automatica dei valori convertiti
        $primary_key = post('primary_key');
        $csv->setPrimaryKey($primary_key - 1);

        // Operazioni di inizializzazione per l'importazione
        if (!isset($page) || empty($page)) {
            $csv->init();
        }

        $count = $csv->importRows($offset, $limit);
        $more = $count == $limit;

        // Operazioni di finalizzazione per l'importazione
        if (!$more) {
            $csv->complete();
        }

        echo json_encode([
            'more' => $more,
            'count' => $count,
        ]);

        break;
}

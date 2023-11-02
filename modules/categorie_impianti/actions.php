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

use Modules\Checklists\Check;

$modulo_impianti = Modules::get('Impianti');

switch (filter('op')) {
    case 'update':
        $nome = filter('nome');
        $nota = filter('nota');
        $colore = filter('colore');

        if (isset($nome) && isset($nota) && isset($colore)) {
            $dbo->query('UPDATE `my_impianti_categorie` SET `nome`='.prepare($nome).', `nota`='.prepare($nota).', `colore`='.prepare($colore).' WHERE `id`='.prepare($id_record));
            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'add':
        $nome = post('nome');
        $nota = post('nota');
        $colore = post('colore');

        // Verifico che il nome non sia duplicato
        $count = $dbo->fetchNum('SELECT `id` FROM `my_impianti_categorie` WHERE `nome`='.prepare($nome));

        if ($count != 0) {
            flash()->error(tr('Categoria _NAME_ già esistente!', [
                '_NAME_' => $nome,
            ]));
        } else {
            if (isset($nome)) {
                $dbo->query('INSERT INTO `my_impianti_categorie` (`nome`, `colore`, `nota`) VALUES ('.prepare($nome).', '.prepare($colore).', '.prepare($nota).')');

                $id_record = $dbo->lastInsertedID();

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $id_record, 'text' => $nome]);
                }

                flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                    '_TYPE_' => 'categoria',
                ]));
            }
        }
        break;

    case 'delete':
        $id = filter('id');
        if (empty($id)) {
            $id = $id_record;
        }

        if ($dbo->fetchNum('SELECT * FROM `my_impianti` WHERE `id_categoria`='.prepare($id)) == 0) {
            $dbo->query('DELETE FROM `my_impianti_categorie` WHERE `id`='.prepare($id));

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'categoria',
            ]));
        } else {
            flash()->error(tr('Esistono ancora alcuni articoli sotto questa categoria!'));
        }

        break;

    case 'sync_checklist':
        $checks_categoria = $dbo->fetchArray('SELECT * FROM zz_checks WHERE id_module = '.prepare($id_module).' AND id_record = '.prepare($id_record));

        $impianti = $dbo->select('my_impianti', '*', [], ['id_categoria' => $id_record]);
        foreach ($impianti as $impianto) {
            Check::deleteLinked([
                'id_module' => $modulo_impianti['id'],
                'id_record' => $impianto['id'],
            ]);
            foreach ($checks_categoria as $check_categoria) {
                $id_parent_new = null;
                if ($check_categoria['id_parent']) {
                    $parent = $dbo->selectOne('zz_checks', '*', ['id' => $check_categoria['id_parent']]);
                    $id_parent_new = $dbo->selectOne('zz_checks', '*', ['content' => $parent['content'], 'id_module' => $modulo_impianti['id'], 'id_record' => $impianto['id']])['id'];
                }
                $check = Check::build($user, $structure, $impianto['id'], $check_categoria['content'], $id_parent_new, $check_categoria['is_titolo'], $check_categoria['order']);
                $check->id_module = $modulo_impianti['id'];
                $check->id_plugin = null;
                $check->note = $check_categoria['note'];
                $check->save();
            }
        }
        flash()->info(tr('Impianti sincronizzati correttamente!'));

        break;
}

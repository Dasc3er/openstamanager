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

use Modules\CategorieDocumentali\Categoria;

switch (post('op')) {
    case 'update':
        $descrizione = post('descrizione');

        // Verifico che il nome non sia duplicato
        $count = $dbo->fetchNum('SELECT `name` FROM `do_categorie` LEFT JOIN `do_categorie_lang` ON (`do_categorie_lang`.`id_record` = `do_categorie`.`id` AND `do_categorie_lang`.`id_lang` = '.prepare(setting('Lingua')).') WHERE `name`='.prepare($descrizione).' AND `deleted_at` IS NULL AND `do_categorie`.`id` !='.prepare($id_record));
        if ($count != 0) {
            flash()->error(tr('Categoria _NAME_ già esistente!', [
                '_NAME_' => $descrizione,
            ]));
        } else {
            $dbo->query('UPDATE `do_categorie_lang` SET `name` = '.prepare($descrizione).' WHERE `id_record` = '.prepare($id_record).' AND `id_lang` = '.prepare(setting('Lingua')));
            $categoria->syncPermessi(post('permessi') ?: []);

            flash()->info(tr('Informazioni salvate correttamente!'));
        }

        break;

    case 'add':
        $descrizione = post('descrizione');

        // Verifico che il nome non sia duplicato
        $count = $dbo->fetchNum('SELECT `name` FROM `do_categorie`  LEFT JOIN `do_categorie_lang` ON (`do_categorie_lang`.`id_record` = `do_categorie`.`id` AND `do_categorie_lang`.`id_lang` = '.prepare(setting('Lingua')).') WHERE `name`='.prepare($descrizione).' AND `deleted_at` IS NULL');
        if ($count != 0) {
            flash()->error(tr('Categoria _NAME_ già esistente!', [
                '_NAME_' => $descrizione,
            ]));
        } else {
            $categoria = Categoria::build();
            $id_record = $categoria->id;
            $dbo->query('INSERT INTO `do_categorie_lang` (`id_record`, `id_lang`, `name`) VALUES ('.prepare($id_record).', '.prepare(setting('Lingua')).', '.prepare($descrizione).')');

            if (isAjaxRequest()) {
                echo json_encode(['id' => $id_record, 'text' => $descrizione]);
            }

            flash()->info(tr('Nuova categoria documenti aggiunta!'));
        }

        break;

    case 'delete':
        $dbo->query('UPDATE `do_categorie` SET `deleted_at` = NOW() WHERE `id` = '.prepare($id_record));

        flash()->info(tr('Categoria documenti eliminata!'));

        break;
}

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

use Models\Clause;
use Models\Module;
use Models\View;

switch (filter('op')) {
    case 'update':
        $options2 = htmlspecialchars_decode(post('options2'), ENT_QUOTES);

        if (check_query($options2)) {
            $dbo->query('UPDATE `zz_modules` SET `options2`='.prepare($options2).' WHERE `id`='.prepare($id_record));
            $dbo->query('UPDATE `zz_modules_lang` SET `title`='.prepare(post('title')).' WHERE (`id_record`='.prepare($id_record).' AND `id_lang`='.prepare(Models\Locale::getDefault()->id).')');
            $rs = true;
        } else {
            $rs = false;
        }

        if ($rs) {
            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'fields':
        $rs = true;

        // Fix per la protezone contro XSS, che interpreta la sequenza "<testo" come un tag HTML
        $queries = (array) $_POST['query'];
        foreach ($queries as $c => $query) {
            if (check_query($query)) {
                $array = [
                    'query' => $query,
                    'visible' => post('visible')[$c],
                    'search' => post('search')[$c],
                    'slow' => post('slow')[$c],
                    'format' => post('format')[$c],
                    'html_format' => post('html_format')[$c],
                    'summable' => post('sum')[$c],
                    'search_inside' => post('search_inside')[$c],
                    'order_by' => post('order_by')[$c],
                    'id_module' => $id_record,
                ];

                $name = post('name')[$c];

                if (!empty(post('id')[$c]) && !empty($query)) {
                    $id = post('id')[$c];

                    $dbo->update('zz_views', $array, ['id' => $id]);
                } elseif (!empty($query)) {
                    $array['order'] = orderValue('zz_views', 'id_module', $id_record);
                    $dbo->insert('zz_views', $array);
                    $id = $dbo->lastInsertedID();
                }

                // Aggiornamento traduzione nome campo
                $vista = View::find($id);
                $vista->setTranslation('title', $name);

                // Aggiornamento dei permessi relativi
                $dbo->sync('zz_group_view', ['id_vista' => $id], ['id_gruppo' => (array) post('gruppi')[$c]]);
            } else {
                $rs = false;
            }
        }

        if ($rs) {
            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'filters':
        $rs = true;

        // Fix per la protezone contro XSS, che interpreta la sequenza "<testo" come un tag HTML
        $queries = (array) $_POST['query'];
        foreach ($queries as $c => $query) {
            $query = $_POST['query'][$c];

            if (check_query($query)) {
                $array = [
                    'idgruppo' => post('gruppo')[$c],
                    'idmodule' => $id_record,
                    'clause' => $query,
                    'position' => !empty(post('position')[$c]) ? 'HVN' : 'WHR',
                ];

                if (!empty(post('id')[$c]) && !empty($query)) {
                    $id = post('id')[$c];
                    $clause = Clause::find($id);
                    $clause->idgruppo = post('gruppo')[$c];
                    $clause->idmodulo = $id_record;
                    $clause->clause = $query;
                    $clause->position = !empty(post('position')[$c]) ? 'HVN' : 'WHR';
                    $clause->setTranslation('title', post('name')[$c]);
                    $clause->save();
                } elseif (!empty($query)) {
                    $clause = Clause::build();
                    $id_record = $dbo->lastInsertedID();
                    $clause->idgruppo = post('gruppo')[$c];
                    $clause->idmodulo = $id_record;
                    $clause->clause = $query;
                    $clause->position = !empty(post('position')[$c]) ? 'HVN' : 'WHR';
                    $clause->setTranslation('title', post('name')[$c]);
                    $clause->save();
                }
            } else {
                $rs = false;
            }
        }

        if ($rs) {
            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'change':
        $id = filter('id');

        $clause = Clause::find($id);
        $clause->enabled = !empty($clause->enabled) ? 0 : 1;
        $clause->save();

        flash()->info(tr('Salvataggio completato!'));

        break;

    case 'test':
        $module_query = Util\Query::getQuery(Module::find(get('id_record')));

        try {
            $dbo->fetchArray($module_query.' LIMIT 1');
            echo 'ok';
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;

    case 'delete':
        $id = filter('id');

        $dbo->query('DELETE FROM `zz_views` WHERE `id`='.prepare($id));
        $dbo->query('DELETE FROM `zz_group_view` WHERE `id_vista`='.prepare($id));

        flash()->info(tr('Eliminazione completata!'));

        break;

    case 'delete_filter':
        $id = filter('id');

        $dbo->query('DELETE FROM `zz_group_module` WHERE `id`='.prepare($id));

        flash()->info(tr('Eliminazione completata!'));

        break;

    case 'update_position':
        $order = explode(',', post('order', true));

        foreach ($order as $i => $id_riga) {
            $dbo->query('UPDATE `zz_views` SET `order` = '.prepare($i).' WHERE `id`='.prepare($id_riga));
        }

        break;

    case 'update_visible':
        $visible = filter('visible');
        $id_riga = filter('id_vista');

        $dbo->query('UPDATE `zz_views` SET `visible` = '.prepare($visible).' WHERE `id` = '.prepare($id_riga));

        break;
}

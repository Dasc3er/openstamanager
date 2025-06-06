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

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'tipiintervento':
        $query = 'SELECT `in_tipiintervento`.`id`, CASE WHEN ISNULL(`tempo_standard`) OR `tempo_standard` <= 0 THEN CONCAT(`codice`, \' - \', `title`, IF(`in_tipiintervento`.`deleted_at` IS NULL, "", " ('.tr('eliminato').')")) WHEN `tempo_standard` > 0 THEN  CONCAT(`codice`, \' - \', `title`, \' (\', REPLACE(FORMAT(`tempo_standard`, 2), \'.\', \',\'), \' ore)\', IF(`in_tipiintervento`.`deleted_at` IS NULL, "", " ('.tr('eliminato').')")) END AS descrizione, `tempo_standard` FROM `in_tipiintervento` LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento`.`id` = `in_tipiintervento_lang`.`id_record` AND `in_tipiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') |where| ORDER BY `title`';

        foreach ($elements as $element) {
            $filter[] = '`in_tipiintervento`.`id`='.prepare($element);
        }

        if (empty($filter)) {
            $where[] = '`in_tipiintervento`.`deleted_at` IS NULL';
        }

        if (!empty($search)) {
            $search_fields[] = '`title` LIKE '.prepare('%'.$search.'%');
        }

        break;
}

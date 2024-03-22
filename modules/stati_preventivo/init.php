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
use Modules\Preventivi\Stato;

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM `co_statipreventivi` LEFT JOIN `co_statipreventivi_lang` ON (`co_statipreventivi`.`id`=`co_statipreventivi_lang`.`id_record` AND `co_statipreventivi_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') WHERE `co_statipreventivi`.`id`='.prepare($id_record));

    $stato = Stato::find($id_record);
}

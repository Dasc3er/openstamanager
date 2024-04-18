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

/*
 * Controlla se il database presenta alcune sezioni personalizzate.
 *
 * @return array
 */

if (!function_exists('customStructure')) {
    function customStructure()
    {
        $results = [];

        $dirs = [
            'modules',
            'templates',
            'plugins',
        ];

        // Controlli di personalizzazione fisica
        foreach ($dirs as $dir) {
            $files = glob(base_dir().'/'.$dir.'/*/custom/*.{php,html}', GLOB_BRACE);
            $recursive_files = glob(base_dir().'/'.$dir.'/*/custom/**/*.{php,html}', GLOB_BRACE);

            $files = array_merge($files, $recursive_files);

            foreach ($files as $file) {
                $file = str_replace(base_dir().'/', '', $file);
                $result = explode('/custom/', $file)[0];

                if (!in_array($result, $results)) {
                    $results[] = $result;
                }
            }
        }

        // Gestione cartella include
        $files = glob(base_dir().'/include/custom/*.{php,html}', GLOB_BRACE);
        $recursive_files = glob(base_dir().'/include/custom/**/*.{php,html}', GLOB_BRACE);

        $files = array_merge($files, $recursive_files);

        foreach ($files as $file) {
            $file = str_replace(base_dir().'/', '', $file);
            $result = explode('/custom/', $file)[0];

            if (!in_array($result, $results)) {
                $results[] = $result;
            }
        }

        return $results;
    }
}

/*
 * Controlla se il database presenta alcune sezioni personalizzate.
 *
 * @return array
 */
if (!function_exists('customTables')) {
    function customTables()
    {
        $tables = include base_dir().'/update/tables.php';

        $names = [];
        foreach ($tables as $table) {
            $names[] = prepare($table);
        }

        $database = database();

        $results = $database->fetchArray('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '.prepare($database->getDatabaseName()).' AND TABLE_NAME NOT IN ('.implode(',', $names).") AND TABLE_NAME != 'updates'");

        return array_column($results, 'TABLE_NAME');
    }
}

/*
 * Controlla se il database presenta alcune sezioni personalizzate.
 *
 * @return array
 */

if (!function_exists('customDatabase')) {
    function customDatabase()
    {
        $database = database();
        $modules = $database->fetchArray("SELECT `title`, CONCAT('modules/', `directory`) AS directory FROM `zz_modules` LEFT JOIN `zz_modules_lang` ON (`zz_modules`.`id` = `zz_modules_lang`.`id_record` AND `zz_modules_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).") WHERE `options2` != ''");
        $plugins = $database->fetchArray("SELECT `title`, CONCAT('plugins/', `directory`) AS directory FROM `zz_plugins` LEFT JOIN `zz_plugins_lang` ON (`zz_plugins`.`id` = `zz_plugins_lang`.`id_record` AND `zz_plugins_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).") WHERE `options2` != ''");

        $results = array_merge($modules, $plugins);

        return $results;
    }
}

if (!function_exists('customComponents')) {
    function customComponents()
    {
        $database_check = customDatabase();
        $structure_check = customStructure();

        $list = [];
        foreach ($database_check as $element) {
            $pos = array_search($element['directory'], $structure_check);

            $list[] = [
                'path' => $element['directory'],
                'database' => true,
                'directory' => $pos !== false,
            ];

            if ($pos !== false) {
                unset($structure_check[$pos]);
            }
        }

        foreach ($structure_check as $element) {
            $list[] = [
                'path' => $element,
                'database' => false,
                'directory' => true,
            ];
        }

        return $list;
    }
}

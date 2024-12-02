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

namespace Models;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;

class Locale extends Model
{
    use SimpleModelTrait;

    protected $table = 'zz_langs';

    protected static $lang;
    protected static $predefined;

    public static function getDefault()
    {
        return self::$lang;
    }

    public static function setDefault($value)
    {
        self::$lang = database()->table('zz_langs')->where('id', '=', $value)->first();
    }

    public static function getPredefined()
    {
        return self::$predefined;
    }

    public static function setPredefined()
    {
        self::$predefined = database()->table('zz_langs')->where('predefined', '=', 1)->first();
    }
}

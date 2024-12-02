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

namespace Modules\Anagrafiche;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;

class Referente extends Model
{
    use SimpleModelTrait;

    protected $table = 'an_referenti';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Crea un nuovo referente.
     *
     * @param string $nome
     *
     * @return self
     */
    public static function build($idanagrafica = null, $nome = null, $idmansione = null, $idsede = null)
    {
        $model = new static();

        $model->idanagrafica = $idanagrafica;

        $model->nome = $nome;

        $model->idmansione = $idmansione;
        $model->idsede = $idsede;

        $model->save();

        return $model;
    }

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'idsede');
    }
}

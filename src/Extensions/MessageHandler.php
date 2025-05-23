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

namespace Extensions;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

/**
 * Gestore dei messaggi di avvertenza in caso di malfunzionamento del gestionale.
 *
 * @since 2.4.6
 */
class MessageHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        // Controlla se la richiesta è AJAX
        if (\Whoops\Util\Misc::isAjaxRequest()) {
            return;
        }

        // Costruisci il messaggio di errore
        $message = tr('Si è verificato un errore').' <i>[uid: '.$record['extra']['uid'].']</i>.';

        // Aggiungi informazioni utente se autenticato
        if (auth()->check()) {
            $message .= '
            '.tr('Se il problema persiste siete pregati di chiedere assistenza tramite il forum apposito (_LINK_FORUM_)', [
                '_LINK_FORUM_' => '<a href="https://forum.openstamanager.com/">https://forum.openstamanager.com/</a>',
            ]).'.</a>';

            if (auth()->isAdmin()) {
                $message .= '
            <br><small>'.$record['message'].'</small>';
            }
        }

        // Messaggio nella sessione
        try {
            flash()->error($message);
        } catch (\Exception) {
            // Gestisci l'eccezione se necessario
        }

        // Messaggio visivo immediato
        echo '
    <div class="alert alert-danger push">
        <i class="fa fa-times"></i> '.$message.'
    </div>';
    }
}

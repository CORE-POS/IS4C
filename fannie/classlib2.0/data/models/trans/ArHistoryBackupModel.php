<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of CORE-POS.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/**
  @class ArHistoryBackupModel
*/
class ArHistoryBackupModel extends ArHistoryModel
{

    protected $name = "ar_history_backup";

    public function doc()
    {
        return '
Depends on:
* dlog (view)
* ar_history (table)

Depended on by:
* Table AR_EOM_Summary

Use:
Stores an extra copy of ar_history

Maintenance:
cron/nightly.ar.php, after updating ar_history,
 truncates and then appends all of ar_history
        ';
    }
}


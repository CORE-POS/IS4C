<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class VoidHistoryTask extends FannieTask
{

    public $name = 'Void History Task';

    public $description = 'Creates a record of fully voided transactions.
    Replaces nightly.voidhistory.php';

    public $default_schedule = array(
        'min' => 45,
        'hour' => 1,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $sql = FannieDB::get($this->config->get('TRANS_DB'));

        $query = "INSERT INTO voidTransHistory
            SELECT datetime,description,
            ".$sql->concat(
                $sql->convert('emp_no','char'),"'-'",
                $sql->convert('register_no','char'),"'-'",
                $sql->convert('trans_no','char'),'')
            .",
            0
            FROM transarchive WHERE trans_subtype='CM'
            AND ".$sql->datediff('datetime',$sql->now())." = -1
            AND description LIKE 'VOIDING TRANSACTION %-%-%'
            AND register_no <> 99 AND emp_no <> 9999 AND trans_status <> 'X'";
        $success = $sql->query($query);

        if ($success) {
            $this->cronMsg("Voids logged");
        } else {
            $this->cronMsg("Error logging voids");
        }
    }
}


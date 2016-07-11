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
  @class SuspendedTodayModel
*/
class SuspendedTodayModel extends SuspendedModel
{

    protected $name = "suspendedtoday";
    protected $preferred_db = 'trans';

    public function create()
    {
        if ($this->connection->tableExists($this->name)) {
            return true;
        }

        $createQ = 'CREATE VIEW ' . $this->name . ' AS
            SELECT *
            FROM suspended
            WHERE ' . $this->connection->datediff('datetime', $this->connection->now()) . ' = 0';
        $createR = $this->connection->query($createQ);

        return ($createR) ? true : false;
    }

    public function save()
    {
        return false;
    }

    public function delete()
    {
        return false;
    }

    public function doc()
    {
        return '
Depends on:
* suspended (table)

Use:
This view omits all entries in suspended
that aren\'t from the current day. Resuming
a transaction from a previous day wouldn\'t
necessarily cause problems, but "stale"
suspended transactions that never get resumed
could eventually make the list of available
transactions unwieldy.
        ';
    }
}


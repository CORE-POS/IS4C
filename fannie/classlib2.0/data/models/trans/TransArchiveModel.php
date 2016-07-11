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
  @class TransArchiveModel
*/
class TransArchiveModel extends DTransactionsModel
{

    protected $name = "transarchive";
    protected $preferred_db = 'trans';

    public function __construct($con)
    {
        $this->columns['store_row_id']['increment'] = false;
        $this->columns['store_row_id']['primary_key'] = false;
        $this->columns['store_row_id']['index'] = false;
        $this->columns['pos_row_id']['index'] = false;

        parent::__construct($con); 
    }

    /**
      Use DTransactionsModel to normalize same-schema tables
    */
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=false)
    {
        return 0;
    }

    public function doc()
    {
        return '
Depends on:
* dtransactions (table)

Use:
This is a look-up table. Under WFC\'s day
end polling, transarchive contains the last
90 days\' transaction entries. For queries
in that time frame, using this table can
simplify or speed up queries.

Maintenance:
cron/nightly.dtrans.php appends all of dtransactions
 and deletes records older than 90 days.
        ';
    }
}


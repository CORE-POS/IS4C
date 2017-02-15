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
  @class SuspendedModel
*/
class SuspendedModel extends DTransactionsModel
{

    protected $name = "suspended";
    protected $preferred_db = 'trans';

    public function __construct($con)
    {
        unset($this->columns['store_row_id']);
        unset($this->columns['pos_row_id']);
        
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
This table exists so that transactions that
are suspended at one register can be resume
at another.

When a transaction is suspended, that register\'s
localtemptrans table is copied here. When a transaction
is resumed, appropriate rows are sent from here
to that register\'s localtemptrans table.
        ';
    }
}


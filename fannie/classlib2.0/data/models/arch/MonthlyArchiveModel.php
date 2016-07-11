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

class MonthlyArchiveModel extends DTransactionsModel
{

    protected $name = "__needs_initialization";
    protected $preferred_db = 'arch';

    public function __construct($con)
    {
        $this->columns['store_row_id']['increment'] = false;
        $this->columns['store_row_id']['primary_key'] = false;
        $this->columns['store_row_id']['index'] = false;

        parent::__construct($con); 
    }

    /**
      Initialize year and month to establish table name
      @param $year [int] year
      @param $month [int] month
    */
    public function setDate($year, $month)
    {
        $this->name = 'transArchive' . $year . str_pad($month, 2, '0', STR_PAD_LEFT);
    }

    /**
      Use DTransactionsModel to normalize same-schema tables
    */
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=false)
    {
        return 0;
    }

    /**
      Override BasicModel::create to ensure
      date settings are present
    */
    public function create()
    {
        if ($this->name == '__needs_initialization') {
            return false;
        } else {
            return parent::create();
        }
    }
}


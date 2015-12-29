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
  @class BigArchiveModel
*/
class BigArchiveModel extends DTransactionsModel
{

    protected $name = "bigArchive";
    protected $preferred_db = 'arch';

    public function __construct($con)
    {
        $this->columns['store_row_id']['increment'] = false;
        $this->columns['store_row_id']['primary_key'] = false;
        $this->columns['store_row_id']['index'] = false;

        parent::__construct($con); 
    }

    /**
      Use DTransactionsModel to normalize same-schema tables
    */
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=false)
    {
        return 0;
    }

    /**
      Override BasicModel::create to add an 
      initial partition when the table is first
      created
    */
    public function create()
    {
        $exists = $this->connection->tableExists($this->name);
        $created = parent::create();
        if ($created && !$exists) {
            $this->initPartitions(date('Y'), date('n'));
        }

        return $created;
    }

    /**
      Initialize monthly paritioning for the table
      @param $year [int] year
      @param $month [int] month
      @return [boolean] success
    */
    public function initPartitions($year, $month)
    {
        $timestamp = mktime(0, 0, 0, $month, 1, $year);
        $partition_name = 'p' . date('Ym', $timestamp);
        $next_month = date('Y-m-d', mktime(
            0, 0, 0,
            date('n', $timestamp)+1,
            1,
            date('Y', $timestamp)
        ));

        $partitionQ = "
            ALTER TABLE " . $this->connection->identifierEscape($this->name) . "
            PARTITION BY RANGE(TO_DAYS(" . $this->connection->identifierEscape('datetime') . "))
            (PARTITION {$partition_name}
                VALUES LESS THAN (TO_DAYS('{$next_month}'))
            )";
        $added = $this->connection->query($partitionQ);

        return ($added) ? true : false;
    }

    /**
      Add a partition to the table
      @param $year [int] year
      @param $month [int] month
      @return [boolean] success
    */
    public function addPartition($year, $month)
    {
        $timestamp = mktime(0, 0, 0, $month, 1, $year);
        $partition_name = 'p' . date('Ym', $timestamp);
        $next_month = date('Y-m-d', mktime(
            0, 0, 0,
            date('n', $timestamp)+1,
            1,
            date('Y', $timestamp)
        ));

        $partitionQ = "
            ALTER TABLE " . $this->connection->identifierEscape($this->name) . "
            ADD PARTITION
            (PARTITION {$partition_name}
                VALUES LESS THAN (TO_DAYS('{$next_month}'))
            )";
        $added = $this->connection->query($partitionQ);

        return ($added) ? true : false;
    }
}


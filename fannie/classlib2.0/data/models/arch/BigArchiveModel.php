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
        $next_month = date('Y-m-d', mktime(
            0, 0, 0,
            date('n', $timestamp)+1,
            1,
            date('Y', $timestamp)
        ));

        $dbms = $this->connection->dbmsName();

        if (strstr($dbms, 'mysql')) {
            $partition_name = 'p' . date('Ym', $timestamp);
            $partitionQ = "
                ALTER TABLE " . $this->connection->identifierEscape($this->name) . "
                PARTITION BY RANGE(TO_DAYS(" . $this->connection->identifierEscape('datetime') . "))
                (PARTITION {$partition_name}
                    VALUES LESS THAN (TO_DAYS('{$next_month}'))
                )";
            $added = $this->connection->query($partitionQ);

            return ($added) ? true : false;
        } elseif ($dbms === 'postgres9') {
            $partition_name = 'bigArchive' . date('Ym', $timestamp);
            $start = date('Y-m-01', $timestamp);
            $end = date('Y-m-t', $timestamp);
            $partitionQ = "
                CREATE TABLE {$partition_name}
                    (CHECK (datetime >= '{$start}' AND datetime <= '{$end}'))
                    INHERITS (bigArchive)";
            $added = $this->connection->query($partitionQ);
            $this->connection->query("CREATE INDEX datetime_idx ON {$partition_name} (datetime)");
            $this->connection->query($this->generateTrigger($year, $month, $year, $month));
            $this->connection->query("
                CREATE TRIGGER bigarchive_insert
                    BEFORE INSERT ON bigArchive
                    FOR EACH ROW EXECUTE PROCEDURE bigarchive_input_mapper();
            ");

            $added = $this->connection->query($partitionQ);
        }

        return false;
    }

    /**
      Generate postgres trigger function to insert records into appropriate
      partitions between the given dates
    */
    private function generateTrigger($startYear, $startMonth, $endYear, $endMonth)
    {
        $trigger = "
            CREATE OR REPLACE FUNCTION bigarchive_input_mapper()
            RETURNS TRIGGER AS \$\$
            BEGIN ";
        while ($startYear < $endYear || ($startYear == $endYear && $startMonth <= $endMonth)) {
            $timestamp = mktime(0,0,0, $startMonth, 1, $startYear);
            $start = date('Y-m-01', $timestamp);
            $end = date('Y-m-t', $timestamp);
            $partition_name = 'bigArchive' . date('Ym', $timestamp);
            $trigger .= " IF (NEW.datetime >= '{$start}' AND NEW.datetime <= '{$end}') THEN
                            INSERT INTO {$partition_name} VALUES (NEW.*);\n";
            $startMonth++;
            if ($startMonth > 12) {
                $startYear++;
                $startMonth = 1;
            }
        }
        $trigger .= "
                ELSE
                    RAISE EXCEPTION 'Date out of range';
                END IF;
                RETURN NULL;
            END;
            \$\$
            LANGUAGE plpgsql;";

        return $trigger;
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
        $next_month = date('Y-m-d', mktime(
            0, 0, 0,
            date('n', $timestamp)+1,
            1,
            date('Y', $timestamp)
        ));

        $dbms = $this->connection->dbmsName();

        if (strstr($dbms, 'mysql')) {
            $partition_name = 'p' . date('Ym', $timestamp);
            $partitionQ = "
                ALTER TABLE " . $this->connection->identifierEscape($this->name) . "
                ADD PARTITION
                (PARTITION {$partition_name}
                    VALUES LESS THAN (TO_DAYS('{$next_month}'))
                )";
            $added = $this->connection->query($partitionQ);

            return ($added) ? true : false;
        } elseif ($dbms === 'postgres9') {
            $partition_name = 'bigArchive' . date('Ym', $timestamp);
            $start = date('Y-m-01', $timestamp);
            $end = date('Y-m-t', $timestamp);
            // create new partition table
            $partitionQ = "
                CREATE TABLE {$partition_name}
                    (CHECK (datetime >= '{$start}' AND datetime <= '{$end}'))
                    INHERITS (bigArchive)";
            $added = $this->connection->query($partitionQ);
            // ensure indexing
            $this->connection->query("CREATE INDEX datetime_idx ON {$partition_name} (datetime)");

            // lookup minimum date to see where partitioning starts
            $minP = $this->connection->prepare("SELECT MIN(datetime) FROM bigArchive");
            $min = $this->connection->getValue($minP);
            $startTS = strtotime($min);
            $startYear = date('Y', $startTS);
            $startMonth = date('n', $startTS);
            // regenerate the partition function for the full date range
            $this->connection->query($this->generateTrigger($startYear, $startMonth, $year, $month));

            return ($added) ? true : false;
        }

        return false;
    }
}


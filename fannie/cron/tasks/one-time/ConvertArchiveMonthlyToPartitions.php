<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class ConvertArchiveMonthlyToPartitions extends FannieTask
{
    public $name = 'One-time: Convert Archive Format';

    public $description = 'Copies values from monthly transaction archive
tables to one, unified table (bigArchive). Also adds partitions as required.
Note it does not truncate the unified table first.';


    public $schedulable = false;

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('ARCHIVE_DB'));

        /**
          Find monthly tables
        */
        $tablesR = $dbc->query('SHOW TABLES');
        $monthly_tables = array();
        while ($w = $dbc->fetchRow($tablesR)) {
            if (preg_match('/transArchive[0-9]{6}/', $w[0])) {
                $monthly_tables[] = $w[0];
            }
        }
        if (count($monthly_tables) == 0) {
            echo "No monthly tables found!\n";
            echo "No data has been copied\n";
            return false;
        } else {
            sort($monthly_tables);
        }

        /**
          Check for basic errors before attempting
          to copy any data
        */
        foreach ($monthly_tables as $table) {
            $valid = preg_match('/transArchive([0-9]{4})([0-9]{2})/', $table, $matches);
            if (!$valid) {
                echo "Cannot detect month and year for $table\n";
                echo "No data has been copied\n";
                return false;
            }
        }

        /**
          Get partition info from
          CREATE TABLE statement
        */
        $bigArchive = $dbc->query('SHOW CREATE TABLE bigArchive'); 
        if ($bigArchive === false) {
            echo "Table bigArchive does not exist\n";
            echo "Change archive method to \"partitions\" to create it\n";
            echo "No data has been copied\n";
            return false;
        }
        $bigArchive = $dbc->fetchRow($bigArchive);
        $bigArchive = $bigArchive[0];

        /**
          Create all necessary partitions
        */
        foreach ($monthly_tables as $table) {
            $valid = preg_match('/transArchive([0-9]{4})([0-9]{2})/', $table, $matches);
            $year = $matches[1];
            $month = $matches[2];

            $partition_name = 'p' . $year . $month;
            if (strstr($bigArchive, 'PARTITION ' . $partition_name . ' VALUES')) {
                echo "Partition $partition_name already exists; skipping partition creation\n";
            } else {
                $timestamp = mktime(0, 0, 0, $month, 1, $year);
                $boundary = date('Y-m-d', mktime(0,0,0,date('n',$timestamp)+1,1, date('Y',$timestamp)));
                $newQ = sprintf("ALTER TABLE bigArchive ADD PARTITION 
                    (PARTITION %s 
                    VALUES LESS THAN (TO_DAYS('%s'))
                    )",$partition_name,$boundary);
                $newR = $dbc->query($newQ);
                if ($newR === false) {
                    echo "Failed to create partition $partition_name\n";
                    echo "Details: " . $dbc->error() . "\n";
                    echo "Data transfer will not proceed until all partitions exist\n";
                    echo "No data has been copied\n";
                    return false;
                }
            }
        }

        /**
          All the partitioning code is MySQL specific
          anyway so using "LIMIT" doesn't really matter
        */
        $checkP = $dbc->prepare('
            SELECT upc
            FROM bigArchive
            WHERE datetime BETWEEN ? AND ?
            LIMIT 1');

        /**
          Finally, copy data
        */
        foreach ($monthly_tables as $table) {
            $valid = preg_match('/transArchive([0-9]{4})([0-9]{2})/', $table, $matches);
            $year = $matches[1];
            $month = $matches[2];
            $timestamp = mktime(0, 0, 0, $month, 1, $year);

            $start_date = date('Y-m-d 00:00:00', $timestamp);
            $end_date = date('Y-m-t 23:59:59', $timestamp);
            $checkR = $dbc->execute($checkP, array($start_date, $end_date));
            if ($checkR === false) {
                echo "Something went wrong checking for existing data\n";
                echo "Skipping table $table to avoid compounding problems\n";
                echo "To manually copy data if applicable, run:\n";
                echo "\tINSERT INTO bigArchive SELECT * FROM $table\n";
                continue;
            } elseif ($dbc->numRows($checkR) != 0) {
                echo "Transaction data already exists for $start_date to $end_date\n";
                echo "Skipping table $table to avoid duplicating records\n";
                echo "To manually copy data if applicable, run:\n";
                echo "\tINSERT INTO bigArchive SELECT * FROM $table\n";
                continue;
            }

            echo "Migrating data from $table to bigArchive...\n";
            $success = $dbc->query('INSERT INTO bigArchive SELECT * FROM ' . $table);
            if ($success) {
                echo "\tData migrated successfully\n";
            } else {
                echo "\tAn error occurred\n";
                echo "\tDetails: " . $dbc->error() . "\n";
            }
        }

        echo "Process complete\n";
    }
}


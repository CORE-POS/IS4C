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

namespace COREPOS\Fannie\API\data {

class DataLoad
{
    /**
      Load sample data from fannie/install/sample_data
      @param $sql [SQLManager] database connection
      @param $table [string] table name
      @param $search_dir [path, optional] look in different file
        location for sample data files
      @return [boolean] success

      Sample data should be one of the following
      1. A file named $table.csv with each line containing
         a database record
      2. A file named $table.sql with each line containing
         a VALUES clauses (including parenthesis). The resulting
         query will be:
         INSERT INTO $table VALUES $line_from_file
    */
    public static function loadSampleData($sql, $table, $search_dir='') 
    {
        $loaded = 0;
        $success = true;
        if (empty($search_dir)) {
            $search_dir = dirname(__FILE__) . '/../../install/sample_data/';
        }

        /**
          Detect number of columns in table to ensure
          dataset is complete. If the last column is autoincrement,
          that value may be omitted.
        */
        $table_def = $sql->detailedDefinition($table);
        $valid_range = array(count($table_def), count($table_def));
        $columns = array_keys($table_def);
        $last_column = array_pop($columns);
        if ($table_def[$last_column]['increment'] === true) {
            $valid_range[0]--;
        }

        if (file_exists($search_dir . "/$table.sql")) {
            echo " from $table.sql<br>\n";
            $success = self::loadFromSql($table, $search_dir . '/' . $table . '.sql', $sql);
        } elseif (file_exists($search_dir . "/$table.csv")) {
            $filename = realpath($search_dir . "/$table.csv");

            $fptr = fopen($filename, 'r');
            $first_line = fgetcsv($fptr);
            fclose($fptr);
            if (count($first_line) < $valid_range[0] || count($first_line) > $valid_range[1]) {
                printf('Sample data for table %s has %d columns; should have between %d and %d columns', 
                        $table, count($first_line),
                        $valid_range[0], $valid_range[1]);

                return false;
            }

            echo " from $table.csv ";

            if (self::loadFromCsv($table, $filename, $sql) !== false) {
                echo " succeeded!<br>\n";
                $loaded = 'All';
                $success = true;
            } else {
                echo " line-by-line<br>\n";
                $success = self::loadLinesFromCsv($table, $filename, $sql);
            }
        } else {
            echo "<br><span style='color:red;'>Table data not found in either {$table}.sql or {$table}.csv</span><br>\n";
            $success = false;
        }

        echo ($success? ' success!' : "<br>\n'$table' load " . ($loaded? 'partial success;' : 'failed;'))
            . " $loaded " . ($loaded == 1? 'record was' : 'records were') . " loaded.<br>\n";

        return $success;
    }

    private static function loadFromSql($table, $file, $sql)
    {
        echo " from $table.sql<br>\n";
        $fptr = fopen($file, 'r');
        $ret = true;
        while ($line = fgets($fptr)) {
            $prep = $sql->prepare("INSERT INTO $table VALUES $line");
            $try = $sql->execute($prep);
            if ($try === false) {
                $error = $sql->error();
                $ret = false;
                echo "<br><small style='color:red;'>"
                    . (strlen($error)? $error : 'Unknown error')
                    . " executing:<br><code>{$prep[0]}</code></small><br>\n";
            }
        }
        fclose($fptr);

        return $ret;
    }

    private static function loadFromCsv($table, $file, $sql)
    {
        $fannie_host = \FannieConfig::factory()->get('SERVER');
        $LOCAL = 'LOCAL';
        if ($fannie_host == '127.0.0.1' || $fannie_host == 'localhost') {
            $LOCAL = '';
        }
        $prep = $sql->prepare("LOAD DATA $LOCAL INFILE
            '{$file}'
            INTO TABLE $table
            FIELDS TERMINATED BY ','
            ESCAPED BY '\\\\'
            OPTIONALLY ENCLOSED BY '\"'
            LINES TERMINATED BY '\\r\\n'");
        $try = $sql->execute($prep);
        if ($try === false) {
            $error = $sql->error();
            echo "<br><span style='color:red;'>"
                . (strlen($error)? $error : 'Unknown error')
                . " executing:<br><code>{$prep[0]}</code><br></span><br>\n";
            return false;
        } else {
            return true;
        }
    }

    private static function loadLinesFromCsv($table, $file, $sql)
    {
        $fptr = fopen($file, 'r');
        $stmt = false;
        $ret = true;
        while (!feof($fptr)) {
            $line = fgetcsv($fptr);
            if (!is_array($line)) continue;
            if ($stmt === false) {
                $query = 'INSERT INTO '.$table.' VALUES (';
                foreach ($line as $field) {
                    $query .= '?,';
                }
                $query = substr($query,0,strlen($query)-1).')';
                $stmt = $sql->prepare($query);
            }
            $try = $sql->execute($stmt, $line);
            if ($try === false) {
                $error = $sql->error();
                $ret = false;
                echo "<br><span style='color:red;'>"
                    . (strlen($error)? $error : 'Unknown error')
                    . " executing:<br><code>{$query}</code><br>("
                    . "'" . join("', '", $line) . "')"
                    . ' [' . count($line) . ' operands]'
                    . "</span><br>\n";
            }
        }
        fclose($fptr);

        return $ret;
    }

}

}


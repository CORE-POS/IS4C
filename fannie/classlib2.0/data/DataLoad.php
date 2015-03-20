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
        $fannie_host = \FannieConfig::factory()->get('SERVER');
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
        if ($table_def[$last_column]['increment'] == true) {
            $valid_range[0]--;
        }

        if (file_exists($search_dir . "/$table.sql")) {
            echo " from $table.sql<br>\n";
            $fp = fopen($search_dir . "/$table.sql","r");
            while ($line = fgets($fp)) {
                $prep = $sql->prepare_statement("INSERT INTO $table VALUES $line");
                $try = $sql->exec_statement($prep);
                if ($try === false) {
                    $error = $sql->error();
                    $success = false;
                    echo "<br><small style='color:red;'>"
                        . (strlen($error)? $error : 'Unknown error')
                        . " executing:<br><code>{$prep[0]}</code></small><br>\n";
                } else {
                    if (++$loaded % 100 === 0) {
                        echo "<br>\n";
                        flush();
                    }
                    echo ".";
                }
            }
            fclose($fp);
        } elseif (file_exists($search_dir . "/$table.csv")) {
            $LOCAL = 'LOCAL';
            if ($fannie_host == '127.0.0.1' || $fannie_host == 'localhost') {
                $LOCAL = '';
            }
            $filename = realpath($search_dir . "/$table.csv");

            $fp = fopen($filename, 'r');
            $first_line = fgetcsv($fp);
            fclose($fp);
            if (count($first_line) < $valid_range[0] || count($first_line) > $valid_range[1]) {
                printf('Sample data for table %s has %d columns; should have between %d and %d columns', 
                        $table, count($first_line),
                        $valid_range[0], $valid_range[1]);

                return false;
            }

            echo " from $table.csv ";

            $prep = $sql->prepare_statement("LOAD DATA $LOCAL INFILE
                '{$filename}'
                INTO TABLE $table
                FIELDS TERMINATED BY ','
                ESCAPED BY '\\\\'
                OPTIONALLY ENCLOSED BY '\"'
                LINES TERMINATED BY '\\r\\n'");
            $try = $sql->exec_statement($prep);
            if ($try === false) {
                $error = $sql->error();
                echo "<br><span style='color:red;'>"
                    . (strlen($error)? $error : 'Unknown error')
                    . " executing:<br><code>{$query}</code><br></span><br>\n";
            }
            /** alternate implementation
                for non-mysql and/or LOAD DATA LOCAL
                not allowed */
            if ($try !== false) {
                echo " succeeded!<br>\n";
                $loaded = 'All';
            } else {
                echo " line-by-line<br>\n";
                $fp = fopen($filename, 'r');
                $stmt = false;
                while (!feof($fp)) {
                    $line = fgetcsv($fp);
                    if (!is_array($line)) continue;
                    if ($stmt === false){
                        $query = 'INSERT INTO '.$table.' VALUES (';
                        foreach ($line as $field) {
                            $query .= '?,';
                        }
                        $query = substr($query,0,strlen($query)-1).')';
                        $stmt = $sql->prepare_statement($query);
                    }
                    $try = $sql->exec_statement($stmt, $line);
                    if ($try === false) {
                        $error = $sql->error();
                        $success = false;
                        echo "<br><span style='color:red;'>"
                            . (strlen($error)? $error : 'Unknown error')
                            . " executing:<br><code>{$query}</code><br>("
                            . "'" . join("', '", $line) . "')"
                            . ' [' . count($line) . ' operands]'
                            . "</span><br>\n";
                    } else {
                        if (++$loaded % 100 === 0) {
                            echo "<br>\n";
                            flush();
                        }
                        echo ".";
                    }
                }
                fclose($fp);
            }
        } else {
            echo "<br><span style='color:red;'>Table data not found in either {$table}.sql or {$table}.csv</span><br>\n";
            $success = false;
        }

        echo ($success? ' success!' : "<br>\n'$table' load " . ($loaded? 'partial success;' : 'failed;'))
            . " $loaded " . ($loaded == 1? 'record was' : 'records were') . " loaded.<br>\n";

        return $success;
    }

}

}

namespace {
    class DataLoad extends \COREPOS\Fannie\API\data\DataLoad {}
}


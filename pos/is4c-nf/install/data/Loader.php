<?php

namespace COREPOS\pos\install\data;
use COREPOS\pos\lib\MiscLib;

class Loader
{
    static private function loadFromSql($sql, $table)
    {
        $loaded = 0;
        echo "from data/$table.sql<br>\n";
        $fptr = fopen(__DIR__ . "/$table.sql","r");
        $success = true;
        while($line = fgets($fptr)) {
            $query = "INSERT INTO $table VALUES $line";
            $try = $sql->query("INSERT INTO $table VALUES $line");
            if ($try === false) {
                $error = $sql->error();
                $success = false;
                echo "<br><small style='color:red;'>"
                    . (strlen($error)? $error : _('Unknown error'))
                    . _(" executing") . ":<br><code>$query</code></small><br>\n";
            } else {
                if(++$loaded % 50 === 0) {
                    echo "<br>\n";
                    flush();
                }
                echo ".";
            }
        }
        fclose($fptr);
        echo ($success? _(' success!') : "<br>\n'$table' " . _('load') . " " . ($loaded? _('partial success;') : _('failed;')))
            . " $loaded " . ($loaded == 1? _('record was') : _('records were')) . _(" loaded.") . "<br>\n";

        return $success;
    }

    static private function loadFromCsv($sql, $table, $path)
    {
        $LOCAL = 'LOCAL';
        if (\CoreLocal::get('localhost') == '127.0.0.1' || \CoreLocal::get('localhost') == 'localhost') {
            $LOCAL = '';
        }
        $query = "LOAD DATA $LOCAL INFILE
                '$path'
                INTO TABLE $table
                FIELDS TERMINATED BY ','
                ESCAPED BY '\\\\'
                OPTIONALLY ENCLOSED BY '\"'
                LINES TERMINATED BY '\\r\\n'";
        $prep = $sql->prepare($query);
        $try = $sql->execute($prep);
        if ($try === false) {
            $error = $sql->error();
            echo "<br><span style='color:red;'>"
                . (strlen($error)? $error : _('Unknown error'))
                . _(" executing") . ":<br><code>$query</code><br></span><br>\n";
        }

        return $try;
    }

    static private function loadCsvLines($sql, $table, $path)
    {
        $loaded = 0;
        echo _("line-by-line") . "<br>\n";
        $fptr = fopen($path, 'r');
        $stmt = false;
        $success = false;
        while(!feof($fptr)) {
            $line = fgetcsv($fptr);
            if (!is_array($line)) continue;
            if ($stmt === false) {
                $query = 'INSERT INTO '.$table.' VALUES (';
                foreach($line as $field) {
                    $query .= '?,';
                }
                $query = substr($query,0,strlen($query)-1).')';
                $stmt = $sql->prepare($query);
                if ($stmt === false) {
                    $error = $sql->error();
                    $success = false;
                    echo "<br><span style='color:red;'>"
                        . (strlen($error)? $error : _('Unknown error'))
                        . _(" preparing") . ":<br><code>$query</code></span><br>\n";
                    break;
                }
            }
            $try = $sql->execute($stmt, $line);
            if ($try === false) {
                $error = $sql->error();
                $success = false;
                echo "<br><span style='color:red;'>"
                    . (strlen($error)? $error : _('Unknown error'))
                    . _(" executing") . ":<br><code>$query</code><br>("
                    . "'" . join("', '", $line) . "')"
                    . ' [' . count($line) . ' operands]'
                    . "</span><br>\n";
            } else {
                if(++$loaded % 100 === 0) {
                    echo "<br>\n";
                    flush();
                }
                echo ".";
            }
        }
        fclose($fptr);
        echo ($success? _(' success!') : "<br>\n'$table' " . _('load') . " " . ($loaded? _('partial success;') : _('failed;')))
            . " $loaded " . ($loaded == 1? _('record was') : _('records were')) . _(" loaded") . ".<br>\n";

        return $success;
    }

    /**
      Load sample data into the table
      @param $sql [SQLManager object] connected to database
      @param $table [string] table name
      @param $quiet [boolean, default false] suppress output
      @return [boolean] success
    */
    static public function loadSampleData($sql, $table, $quiet=false)
    {
        $success = true; 
        ob_start();
        echo _("Loading") . " `$table` ";
        if (file_exists(__DIR__ . "/$table.sql")) {
            $success = self::loadFromSql($sql, $table);
        } elseif (file_exists(__DIR__ . "/$table.csv")) {
            echo _("from") . " data/$table.csv ";
            $path = realpath(__DIR__ . "/$table.csv");
            /**
              Handle symlinks on windows by checking if the first line
              of the file contains the name of another CSV file.
            */
            if (MiscLib::win32()) {
                $fptr = fopen($path, 'r');
                $first_line = trim(fgets($fptr));
                if (substr($first_line, -4) == '.csv') {
                    $path = realpath(substr($first_line, 3));
                    if (!file_exists($path)) {
                        if (!$quiet) {
                            echo _('File not found: ') . $path . '<br />';
                            echo ob_end_clean();
                        }
                        return false;
                    }
                }
                fclose($fptr);
                $path = str_replace('\\', '/', $path);
            }
            $try = self::loadFromCsv($sql, $table, $path);
            /** alternate implementation
            for non-mysql and/or LOAD DATA LOCAL
            not allowed */
            if ($try !== false) {
                echo _("succeeded!") . "<br>\n";
                $success = true;
            } else {
                $success = self::loadCsvLines($sql, $table, $path);
            }
        } else {
            echo "<br><span style='color:red;'>" . _('Table data not found in either') . " {$table}.sql or {$table}.csv</span><br>\n";
            $success = false;
        }

        $verbose = ob_get_clean();
        if (!$quiet) {
            echo $verbose;
        }

        return $success;
    }
}


<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

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

if (!class_exists('AutoLoader')) {
    include_once(dirname(__FILE__).'/../lib/AutoLoader.php');
}

class InstallUtilities extends LibraryClass 
{

    const INI_SETTING   = 1;
    const PARAM_SETTING  = 2;
    const EITHER_SETTING = 3;

    static public function whoami()
    {
        if (function_exists('posix_getpwuid')){
            $chk = posix_getpwuid(posix_getuid());
            return $chk['name'];
        } else {
            return get_current_user();
        }
    }

    static public function checkWritable($filename, $optional=False, $template=False)
    {
        $basename = basename($filename);
        $failure = ($optional) ? 'blue' : 'red';
        $status = ($optional) ? 'Optional' : 'Warning';

        if (!file_exists($filename) && !$optional && is_writable($filename)) {
            $fp = fopen($filename,'w');
            if ($template !== False) {
                switch($template) {
                    case 'PHP':
                        fwrite($fp,"<?php\n");
                        fwrite($fp,"?>\n");
                        break;
                }
            }
            fclose($fp);
        }

        if (!file_exists($filename)) {
            echo "<span style=\"color:$failure;\"><b>$status</b>: $basename does not exist</span><br />";
            if (!$optional) {
                echo "<b>Advice</b>: <div style=\"font-face:mono;background:#ccc;padding:8px;\">
                    touch \"".realpath(dirname($filename))."/".basename($filename)."\"<br />
                    chown ".self::whoami()." \"".realpath(dirname($filename))."/".basename($filename)."\"</div>";
            }
        } elseif (is_writable($filename)) {
            echo "<span style=\"color:green;\">$basename is writeable</span><br />";
        } else {
            echo "<span style=\"color:red;\"><b>Warning</b>: $basename is not writeable</span><br />";
            echo "<b>Advice</b>: <div style=\"font-face:mono;background:#ccc;padding:8px;\">
                chown ".self::whoami()." \"".realpath(dirname($filename))."/".basename($filename)."\"<br />
                chmod 600 \"".realpath(dirname($filename))."/".basename($filename)."\"</div>";
        }
    }

    /**
      Save entry to ini.php or ini-local.php
      @param $key string key
      @param $value string value
      @param $prefer_local use ini-local if it exists
      @return boolean success

      Values are written to a file and must be valid
      PHP code. For example, a PHP string should include
      single or double quote delimiters in $value.
    */
    static public function confsave($key,$value,$prefer_local=False)
    {
        // lane #0 is the server config editor
        // no ini.php file to write values to
        if (CoreLocal::get('laneno') == 0) {
            return null;
        }


        /*
        Attempt to update settings in both ini.php and ini-local.php.
        If found in both, return False if (and only if) ini-local.php
        couldn't be set to the correct value. If not found in either,
        add to whichever is writeable; if both writeable, respect the
        $prefer_local setting. If neither is writeable, return False.
        */

        $path_global = '../ini.php';
        $path_local = '../ini-local.php';

        $writeable_global = is_writable($path_global);
        $writeable_local = is_writable($path_local);

        if (!$writeable_global && !$writeable_local)
            return false;

        $found_global = $found_local = false;
        $written_global = $written_local = false;
        $added_global = $added_local = false;

        $orig_setting = '|\$CORE_LOCAL->set\([\'"]'.$key.'[\'"],\s*(.+)\);[\r\n]|';
        $new_setting = "\$CORE_LOCAL->set('{$key}',{$value}, True);\n";

        $orig_global = file_get_contents($path_global);
        if (strstr($orig_global,'<?php') === false) {
            $orig_global = "<?php\n".$orig_global;
        }
        $orig_local = $writeable_local ? file_get_contents($path_local) : '';
        if ($writeable_local && strstr($orig_local,'<?php') === false) {
            $orig_local = "<?php\n".$orig_local;
        }

        $new_global = preg_replace($orig_setting, $new_setting, $orig_global,
					-1, $found_global);
        $new_local = preg_replace($orig_setting, $new_setting, $orig_local,
                        -1, $found_local);
        if ($found_global) {
            preg_match($orig_setting, $orig_global, $matches);
            if ($matches[1] === $value.', True') {// found with exact same value
                $written_global = True;	// no need to bother rewriting it
            } elseif ($writeable_global) {
                $written_global = file_put_contents($path_global, $new_global);
            }
        }

        if ($found_local) {
            preg_match($orig_setting, $orig_local, $matches);
            if ($matches[1] === $value.', True') {// found with exact same value
                $written_local = True;	// no need to bother rewriting it
            } elseif ($writeable_local) {
                $written_local = file_put_contents($path_local, $new_local);
            }
        }

        if ($found_local && !$written_local) {
            return false;	// ini-local.php is overriding ini.php with bad data!
        }
        if ($written_global || $written_local) {
            return true;	// successfully written somewhere relevant
        }

        if (!$found_global && !$found_local) {
            $append_path = ($prefer_local? $path_local : $path_global);
            $append_path = ($writeable_local? $append_path : $path_global);
            $append_path = ($writeable_global? $append_path : $path_local);
        } elseif (!$found_local && $writeable_local) {
            $append_path = $path_local;
        }
        if ($append_path === $path_global) {
            $new_global = preg_replace("|(\?>)?\s*$|", $new_setting.'?>', $orig_global,
                            1, $found_global);
            $added_global = file_put_contents($append_path, $new_global);
        } else {
            $new_local = preg_replace("|(\?>)?\s*$|", $new_setting.'?>', $orig_local,
                            1, $found_local);
            $added_local = file_put_contents($append_path, $new_local);
        }
        if ($added_global || $added_local){
            return true;	// successfully appended somewhere relevant
        }

        return false;	// didn't manage to write anywhere!
    }

    static public function confRemove($key, $local=false)
    {
        $path_global = dirname(__FILE__) . '/../ini.php';
        $path_local = dirname(__FILE__) .  '/../ini-local.php';

        $orig_setting = '|\$CORE_LOCAL->set\([\'"]'.$key.'[\'"],\s*(.+)\);[\r\n]|';
        $current_conf = ($local) ? file_get_contents($path_local) : file_get_contents($path_global);
        if (preg_match($orig_setting, $current_conf) == 1) {
            $removed = preg_replace($orig_setting, '', $current_conf);
            file_put_contents($local ? $path_local : $path_global, $removed);
        }
    }

    static public function confExists($key, $local=False)
    {
        $path_global = '../ini.php';
        $path_local = '../ini-local.php';

        if (!file_exists($local ? $path_local : $path_global)) {
            return false;
        }

        $orig_setting = '|\$CORE_LOCAL->set\([\'"]'.$key.'[\'"],\s*(.+)\);[\r\n]|';
        $current_conf = ($local) ? file_get_contents($path_local) : file_get_contents($path_global);

        if (preg_match($orig_setting, $current_conf) == 1) {
            return True;
        } else {
            return False;
        }
    }

    /**
      Save value to the parameters table.
    */
    static public function paramSave($key, $value) 
    {
        $sql = self::dbTestConnect(
                CoreLocal::get('localhost'),
                CoreLocal::get('DBMS'),
                CoreLocal::get('pDatabase'),
                CoreLocal::get('localUser'),
                CoreLocal::get('localPass'));

        $save_as_array = 0;
        if (is_array($value) && count($value) > 0 && isset($value[0])) {
            // normal array (probably)
            $tmp = '';
            foreach($value as $v) {
                $tmp .= $v.',';
            }
            $value = substr($tmp, 0, strlen($tmp)-1);
            $save_as_array = 1;
        } else if (is_array($value) && count($value) > 0){
            // array with meaningful keys
            $tmp = '';
            foreach($value as $k => $v) {
                $tmp .= $k.'=>'.$v.','; 
            }
            $value = substr($tmp, 0, strlen($tmp)-1);
            $save_as_array = 1;
        } else if (is_array($value)) {
            // empty array
            $value = '';
            $save_as_array = 1;
        }

        $saved = false;
        if ($sql !== false) {
            $prep = $sql->prepare_statement('SELECT param_value FROM parameters
                                        WHERE param_key=? AND lane_id=?');
            $exists = $sql->exec_statement($prep, array($key, CoreLocal::get('laneno')));
            if ($sql->num_rows($exists)) {
                $prep = $sql->prepare_statement('UPDATE parameters SET param_value=?,
                                        is_array=? WHERE param_key=? AND lane_id=?');
                $saved = $sql->exec_statement($prep, array($value, $save_as_array, $key, CoreLocal::get('laneno')));
            } else {
                $prep = $sql->prepare_statement('INSERT INTO parameters (store_id, lane_id, param_key,
                                        param_value, is_array) VALUES (0, ?, ?, ?, ?)');
                $saved = $sql->exec_statement($prep, array(CoreLocal::get('laneno'), $key, $value, $save_as_array));
            }
        }

        // maintain ini.php value too
        if (self::confExists($key)) {
            // tweak value for safe output to ini.php
            if ($save_as_array == 1 && $value !== '') {
                $saveStr = 'array(';
                foreach(explode(',', $value) as $entry) {
                    if (strstr($entry, '=>')) {
                        list($k, $v) = explode('=>', $entry, 2);
                        $saveStr .= "'".$k."'=>'".$v."',";
                    } else {
                        $saveStr .= "'".$entry."',";
                    }
                }
                $value = substr($saveStr, 0, strlen($saveStr)-1).')';
            } else if ($save_as_array == 1 && $value === '') {
                $value = 'array()';
            } else if (strtoupper($value) === 'TRUE'){
                $value = 'True';
            } else if (strtoupper($value) === 'FALSE'){
                $value = 'False';
            } else if (!is_numeric($value) || (strlen($value)>1 && substr($value,0,1) == '0')) {
                $value = "'".$value."'";
            }

            self::confsave($key, $value);
        }

        return ($saved) ? true : false;
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
        $loaded = 0;
        ob_start();
        echo "Loading `$table` ";
        if (file_exists(dirname(__FILE__) . "/data/$table.sql")) {
            echo "from data/$table.sql<br>\n";
            $fp = fopen(dirname(__FILE__) . "/data/$table.sql","r");
            while($line = fgets($fp)) {
                $query = "INSERT INTO $table VALUES $line";
                $try = $sql->query("INSERT INTO $table VALUES $line");
                if ($try === false) {
                    $error = $sql->error();
                    $success = false;
                    echo "<br><small style='color:red;'>"
                        . (strlen($error)? $error : 'Unknown error')
                        . " executing:<br><code>$query</code></small><br>\n";
                } else {
                    if(++$loaded % 50 === 0) {
                        echo "<br>\n";
                        flush();
                    }
                    echo ".";
                }
            }
            fclose($fp);
            echo ($success? ' success!' : "<br>\n'$table' load " . ($loaded? 'partial success;' : 'failed;'))
                . " $loaded " . ($loaded == 1? 'record was' : 'records were') . " loaded.<br>\n";
        } else if (file_exists(dirname(__FILE__) . "/data/$table.csv")) {
            echo "from data/$table.csv ";
            $LOCAL = 'LOCAL';
            if (CoreLocal::get('localhost') == '127.0.0.1' || CoreLocal::get('localhost') == 'localhost') {
                $LOCAL = '';
            }
            $path = realpath(dirname(__FILE__) . "/data/$table.csv");
            $query = "LOAD DATA $LOCAL INFILE
                    '$path'
                    INTO TABLE $table
                    FIELDS TERMINATED BY ','
                    ESCAPED BY '\\\\'
                    OPTIONALLY ENCLOSED BY '\"'
                    LINES TERMINATED BY '\\r\\n'";
            $prep = $sql->prepare_statement($query);
            $try = $sql->exec_statement($prep);
            if ($try === false) {
                $error = $sql->error();
                echo "<br><span style='color:red;'>"
                    . (strlen($error)? $error : 'Unknown error')
                    . " executing:<br><code>$query</code><br></span><br>\n";
            }
            /** alternate implementation
            for non-mysql and/or LOAD DATA LOCAL
            not allowed */
            if ($try !== false) {
                echo "succeeded!<br>\n";
            } else {
                echo "line-by-line<br>\n";
                $fp = fopen($path, 'r');
                $stmt = false;
                while(!feof($fp)) {
                    $line = fgetcsv($fp);
                    if (!is_array($line)) continue;
                    if ($stmt === false) {
                        $query = 'INSERT INTO '.$table.' VALUES (';
                        foreach($line as $field) {
                            $query .= '?,';
                        }
                        $query = substr($query,0,strlen($query)-1).')';
                        $stmt = $sql->prepare_statement($query);
                        if ($stmt === false) {
                            $error = $sql->error();
                            $success = false;
                            echo "<br><span style='color:red;'>"
                                . (strlen($error)? $error : 'Unknown error')
                                . " preparing:<br><code>$query</code></span><br>\n";
                            break;
                        }
                    }
                    $try = $sql->exec_statement($stmt, $line);
                    if ($try === false) {
                        $error = $sql->error();
                        $success = false;
                        echo "<br><span style='color:red;'>"
                            . (strlen($error)? $error : 'Unknown error')
                            . " executing:<br><code>$query</code><br>("
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
                fclose($fp);
                echo ($success? ' success!' : "<br>\n'$table' load " . ($loaded? 'partial success;' : 'failed;'))
                    . " $loaded " . ($loaded == 1? 'record was' : 'records were') . " loaded.<br>\n";
            }
        } else {
            echo "<br><span style='color:red;'>Table data not found in either {$table}.sql or {$table}.csv</span><br>\n";
        }

        $verbose = ob_get_clean();
        if (!$quiet) {
            echo $verbose;
        }

        return $success;
    }

    static public function dbTestConnect($host,$type,$db,$user,$pw)
    {
        $sql = false;
        try {
            if ($type == 'mysql') {
                ini_set('mysql.connect_timeout',1);
            } elseif ($type == 'mssql') {
                ini_set('mssql.connect_timeout',1);
            }
            ob_start();
            $sql =  @ new SQLManager($host,$type,$db,$user,$pw);
            ob_end_clean();
        } catch(Exception $ex) {}

        if ($sql === false || $sql->connections[$db] === false) {
            return false;
        } else {
            return $sql;
        }
    }

    /* query to create another table with the same
        columns
       @retrun string query or boolean false
    */
    static public function duplicateStructure($dbms,$table1,$table2)
    {
        if (strstr($dbms,"MYSQL")) {
            return "CREATE TABLE `$table2` LIKE `$table1`";
        } elseif ($dbms == "MSSQL") {
            return "SELECT * INTO [$table2] FROM [$table1] WHERE 1=0";
        } elseif ($dbms == 'PDOLITE') {
            $path = realpath(dirname(__FILE__).'/sql');
            if (file_exists($path.'/op/'.$table1.'.php')) {
                include($path.'/op/'.$table1.'.php');
                return str_replace($table1, $table2, $CREATE['op.'.$table1]);
            } elseif (file_exists($path.'/trans/'.$table1.'.php')){
                include($path.'/trans/'.$table1.'.php');
                return str_replace($table1, $table2, $CREATE['trans.'.$table1]);
            } else {
                return false;
            }
        }

        return false;
    }

    static public function createIfNeeded($con, $dbms, $db_name, $table_name, $stddb, &$errors=array())
    {
        if ($con->table_exists($table_name,$db_name)) return $errors;
        $dbms = strtoupper($dbms);

        $fn = dirname(__FILE__)."/sql/$stddb/$table_name.php";
        if (!file_exists($fn)) {
            $errors[] = array(
                'struct'=>$table_name,
                'error' => 1,
                'query'=>'n/a',
                'details'=>'Missing file: '.$fn,
                'important'=>True
            );
            return $errors;
        }

        include($fn);
        if (!isset($CREATE["$stddb.$table_name"])) {
            $errors[] = array(
                'struct'=>$table_name,
                'error' => 1,
                'query'=>'n/a',
                'details'=>'No valid $CREATE in: '.$fn,
                'important'=>True
            );
            return $errors;
        }

        return self::dbStructureModify($con, $table_name, $CREATE["$stddb.$table_name"], $errors);
    }

    public static function dbStructureModify($sql, $struct_name, $queries, &$errors=array())
    {
        if (!is_array($queries)) {
            $queries = array($queries);
        }

        $error = array(
            'struct' => $struct_name,
            'error' => 0,
            'query' => '',
            'details' => '',
        );
        foreach ($queries as $query) {
            ob_start();
            $try = @$sql->query($query);
            ob_end_clean();
            if ($try === false){
                $error['query'] .= $query . '; ';
                if (stristr($query, "DROP ") && stristr($query,"VIEW ")) {
                    /* suppress unimportant errors
                    $errors[] = array(
                    'struct' => $struct_name,
                    'query' => $query,
                    'important' => False
                    );
                    */
                } else {
                    $error['error'] = 1;
                    $error['details'] = $sql->error() . '; ';
                    $error['important'] = true;
                }
            }
        }
        $errors[] = $error;

        return $errors;
    }

    public static function normalizeDbName($name)
    {
        if ($name == 'op') {
            return CoreLocal::get('pDatabase');
        } else if ($name == 'trans') {
            return CoreLocal::get('tDatabase');
        } else {
            return false;
        }
    }

    /**
      Render configuration variable as an <input> tag
      Process any form submissions
      Write configuration variable to config.php

      @param $name [string] name of the variable
      @param $default_value [mixed, default empty string] default value for the setting
      @param $quoted [boolean, default true] write value to config.php with single quotes
      @param $attributes [array, default empty] array of <input> tag attribute names and values

      @return [string] html input field
    */
    static public function installTextField($name, $default_value='', $storage=self::EITHER_SETTING, $quoted=true, $attributes=array())
    {
        $current_value = CoreLocal::get($name);
        if ($current_value === '') {
            $current_value = $default_value;
        }
        if (isset($_REQUEST[$name])) {
            $current_value = $_REQUEST[$name];
            /**
              If default is array, value is probably supposed to be an array
              Split quoted values on whitespace, commas, and semicolons
              Split non-quoted values on non-numeric characters
            */
            if (is_array($default_value)) {
                if ($quoted) {
                    $current_value = preg_split('/[\s,;]+/', $current_value); 
                } else {
                    $current_value = preg_split('/\D+/', $current_value); 
                }
            }
        }

        // sanitize values:
        if (!$quoted) {
            // unquoted must be a number or boolean
            // arrays of unquoted values only allow numbers
            if (is_array($current_value)) {
                for ($i=0; $i<count($current_value); $i++) {
                    if (!is_numeric($current_value[$i])) {
                        $current_value[$i] = (int)$current_value[$i];
                    }
                }
            } elseif (!is_numeric($current_value) && strtolower($current_value) !== 'true' && strtolower($current_value) !== false) {
                $current_value = (int)$current_value;
            }
        } else if ($quoted && !is_array($current_value)) {
            // quoted must not contain single quotes
            $current_value = str_replace("'", '', $current_value);
            // must not start with backslash
            while (strlen($current_value) > 0 && substr($current_value, 0, 1) == "\\") {
                $current_value = substr($current_value, 1);
            }
            // must not end with backslash
            while (strlen($current_value) > 0 && substr($current_value, -1) == "\\") {
                $current_value = substr($current_value, 0, strlen($current_value)-1);
            }
        }

        CoreLocal::set($name, $current_value);
        if ($storage == self::INI_SETTING) {
            if (is_array($current_value)) {
                $out_value = 'array(' . implode(',', $current_value) . ')';
                self::confsave($name, $out_value);
            } elseif (!is_numeric($current_value) && strtolower($current_value) !== 'true' && strtolower($current_value !== 'false')) {
                self::confsave($name, "'" . $current_value . "'");
            } else {
                self::confsave($name, $current_value);
            }
        } else {
            self::paramSave($name, $current_value);
        }

        if (is_array($current_value)) {
            $current_value = implode(', ', $current_value);
        }
        
        if ($storage == self::INI_SETTING) {
            $attributes['title'] = 'Stored in ini.php';
        } elseif (self::confExists($name)) {
            $attributes['title'] = 'Stored in ini and DB';
        } else {
            $attributes['title'] = 'Stored in opdata.parameters';
        }

        $ret = sprintf('<input name="%s" value="%s"',
            $name, $current_value);
        if (!isset($attributes['type'])) {
            $attributes['type'] = 'text';
        }
        foreach ($attributes as $name => $value) {
            if ($name == 'name' || $name == 'value') {
                continue;
            }
            $ret .= ' ' . $name . '="' . $value . '"';
        }
        $ret .= " />\n";

        return $ret;
    }

    /**
      Render configuration variable as an <select> tag
      Process any form submissions
      Write configuration variable to config.php
      
      @param $name [string] name of the variable
      @param $options [array] list of options
        This can be a keyed array in which case the keys
        are what is written to config.php and the values
        are what is shown in the user interface, or it
        can simply be an array of valid values.
      @param $default_value [mixed, default empty string] default value for the setting
      @param $quoted [boolean, default true] write value to config.php with single quotes

      @return [string] html select field
    */
    static public function installSelectField($name, $options, $default_value='', $storage=self::EITHER_SETTING, $quoted=true, $attributes=array())
    {
        $current_value = CoreLocal::get($name);
        if ($current_value === '') {
            $current_value = $default_value;
        }
        if (isset($_REQUEST[$name])) {
            $current_value = $_REQUEST[$name];
        }

        $is_array = false;
        if (isset($attributes['multiple'])) {
            $is_array = true;
            if (!isset($attributes['size'])) {
                $attributes['size'] = 5;
            }
            // with multi select, no value means no POST
            if (count($_POST) > 0 && !isset($_REQUEST[$name])) {
                $current_value = array();
            }
        }

        // sanitize values:
        if (!$is_array && !$quoted) {
            // unquoted must be a number or boolean
            if (!is_numeric($current_value) && strtolower($current_value) !== 'true' && strtolower($current_value) !== 'false') {
                $current_value = (int)$current_value;
            }
        } else if (!$is_array && $quoted) {
            // quoted must not contain single quotes
            $current_value = str_replace("'", '', $current_value);
            // must not start with backslash
            while (strlen($current_value) > 0 && substr($current_value, 0, 1) == "\\") {
                $current_value = substr($current_value, 1);
            }
            // must not end with backslash
            while (strlen($current_value) > 0 && substr($current_value, -1) == "\\") {
                $current_value = substr($current_value, 0, strlen($current_value)-1);
            }
        } else if ($is_array && !is_array($current_value)) {
            $current_value = $default_value;
        }
        
        CoreLocal::set($name, $current_value);
        if ($storage == self::INI_SETTING) {
            if (!is_numeric($current_value) && strtolower($current_value) !== 'true' && strtolower($current_value !== 'false')) {
                self::confsave($name, "'" . $current_value . "'");
            } else {
                self::confsave($name, $current_value);
            }
        } else {
            self::paramSave($name, $current_value);
        }

        if ($storage == self::INI_SETTING) {
            $attributes['title'] = 'Stored in ini.php';
        } elseif (self::confExists($name)) {
            $attributes['title'] = 'Stored in ini and DB';
        } else {
            $attributes['title'] = 'Stored in opdata.parameters';
        }

        $ret = '<select name="' . $name . ($is_array ? '[]' : '') . '" ';
        foreach ($attributes as $name => $value) {
            if ($name == 'name' || $name == 'value') {
                continue;
            }
            $ret .= ' ' . $name . '="' . $value . '"';
        }
        $ret .= ">\n";
        // array has non-numeric keys
        // if the array has meaningful keys, use the key value
        // combination to build <option>s with labels
        $has_keys = ($options === array_values($options)) ? false : true;
        foreach ($options as $key => $value) {
            $selected = '';
            if ($is_array && $has_keys && in_array($key, $current_value)) {
                $selected = 'selected';
            } elseif ($is_array && !$has_keys && in_array($value, $current_value)) {
                $selected = 'selected';
            } elseif ($has_keys && $current_value == $key) {
                $selected = 'selected';
            } elseif (!$has_keys && $current_value == $value) {
                $selected = 'selected';
            }
            $optval = $has_keys ? $key : $value;

            $ret .= sprintf('<option value="%s" %s>%s</option>',
                $optval, $selected, $value);
            $ret .= "\n";
        }
        $ret .= '</select>' . "\n";

        return $ret;
    }

    static public function installCheckboxField($name, $label, $default_value=0, $storage=self::EITHER_SETTING, $choices=array(0, 1))
    {
        $current_value = CoreLocal::get($name);
        if ($current_value === '') {
            $current_value = $default_value;
        }
        if (isset($_REQUEST[$name])) {
            $current_value = $_REQUEST[$name];
        }

        // sanitize
        if (!is_array($choices) || count($choices) != 2) {
            $choices = array(0, 1);
        }
        if (!in_array($current_value, $choices)) {
            $current_value = $default_value;
        }

        if (count($_POST) > 0 && !isset($_REQUEST[$name])) {
            $current_value = $choices[0];
        }

        CoreLocal::set($name, $current_value);
        if ($storage == self::INI_SETTING) {
            if (!is_numeric($current_value) && strtolower($current_value) !== 'true' && strtolower($current_value !== 'false')) {
                self::confsave($name, "'" . $current_value . "'");
            } else {
                self::confsave($name, $current_value);
            }
        } else {
            self::paramSave($name, $current_value);
        }

        if ($storage == self::INI_SETTING) {
            $attributes['title'] = 'Stored in ini.php';
        } elseif (self::confExists($name)) {
            $attributes['title'] = 'Stored in ini and DB';
        } else {
            $attributes['title'] = 'Stored in opdata.parameters';
        }

        $ret = '<fieldset class="toggle">' . "\n";
        $ret .= sprintf('<input type="checkbox" name="%s" id="%s" value="%s" %s />',
                    $name, $name, $choices[1],
                    ($current_value == $choices[1] ? 'checked' : '')
        );
        $ret .= "\n";
        $ret .= sprintf('<label for="%s" onclick="">%s: </label>', $name, $label);
        $ret .= "\n";
        $ret .= '<span class="toggle-button" style="border: solid 3px black;"></span></fieldset>' . "\n";

        return $ret;
    }
    
    public static function validateConfiguration()
    {
        global $CORE_LOCAL;
        /**
          Opposite of normal. Load the parameters table values
          first, then include ini.php second. If the resulting
          $CORE_LOCAL does not match the paramters table, that
          means ini.php overwrote a setting with a different
          value.
        */
        CoreState::loadParams();
        include(dirname(__FILE__) . '/../ini.php');

        $dbc = Database::pDataConnect();

        /**
          Again backwards. Check lane-specific parameters first
        */
        $parameters = new ParametersModel($dbc);
        $parameters->store_id(0);
        $parameters->lane_id($CORE_LOCAL->get('laneno'));
        $checked = array();
        $wrong = array();
        foreach ($parameters->find() as $param) {
            $p_value = $param->materializeValue();
            $checked[$param->param_key()] = true;
            $i_value = $CORE_LOCAL->get($param->param_key());
            if (is_numeric($i_value) && is_numeric($p_value) && $i_value == $p_value) {
                // allow loose comparison on numbers
                // i.e., permit integer 1 equal string '1'
                continue;
            }
            if ($p_value !== $i_value) {
                printf('<span style="color:red;">Setting mismatch for</span>
                    <a href="" onclick="$(this).next().toggle(); return false;">%s</a>
                    <span style="display:none;"> parameters says %s, ini.php says %s</span></p>',
                    $param->param_key(), print_r($p_value, true), print_r($i_value, true)
                );
                $wrong[$param->param_key()] = $p_value;
            }
        }

        /**
          Now check global parameters
        */
        $parameters->reset();
        $parameters->store_id(0);
        $parameters->lane_id(0);
        foreach ($parameters->find() as $param) {
            if (isset($checked[$param->param_key()])) {
                // setting has a lane-specific parameters
                // value. no need to check this one.
                continue;
            }
            $p_value = $param->materializeValue();
            $i_value = $CORE_LOCAL->get($param->param_key());
            if (is_numeric($i_value) && is_numeric($p_value) && $i_value == $p_value) {
                // allow loose comparison on numbers
                // i.e., permit integer 1 equal string '1'
                continue;
            }
            if ($p_value !== $i_value) {
                printf('<p>Setting mismatch for 
                    <a href="" onclick=$(this).next.toggle();return false;">%s</a>
                    <span style="display:none;"> parameters says %s, ini.php says %s</span></p>',
                    $param->param_key(), print_r($p_value, true), print_r($i_value, true)
                );
                $wrong[$param->param_key()] = $p_value;
            }
        }

        /**
          Finally, re-save any conflicting values.
          This should rewrite them in ini.php if that
          file is writable.
        */
        foreach ($wrong as $key => $value) {
            self::paramSave($key, $value);
        }
    }

    /**
      Create opdata tables and views
      @param $db [SQLManager] database connection
      @param $name [string] database name
      @return [array] of error messages
    */
    public static function createOpDBs($db, $name)
    {
        $errors = array();

        if (CoreLocal::get('laneno') == 0) {
            $errors[] = array(
                'struct' => 'No structures created for lane #0',
                'query' => 'None',
                'details' => 'Zero is reserved for server',
            );

            return $errors;
        }

        $models = array(
            'AutoCouponsModel',
            'CouponCodesModel',
            'CustdataModel',
            'CustPreferencesModel',
            'CustReceiptMessageModel',
            'CustomReceiptModel',
            'DateRestrictModel',
            'DepartmentsModel',
            'DisableCouponModel',
            'DrawerOwnerModel',
            'EmployeesModel',
            'GlobalValuesModel',
            'HouseCouponsModel',
            'HouseCouponItemsModel',
            'HouseVirtualCouponsModel',
            'MasterSuperDeptsModel',
            'MemberCardsModel',
            'MemtypeModel',
            'ParametersModel',
            'ProductsModel',
            'ShrinkReasonsModel',
            'SpecialDeptMapModel',
            'SubDeptsModel',
            'TendersModel',
            'UnpaidArTodayModel',
            // depends on custdata
            'MemberCardsViewModel',
        );
        foreach ($models as $class) {
            $obj = new $class($db);
            $errors[] = $obj->createIfNeeded($name);
        }
        
        $sample_data = array(
            'couponcodes',
            'globalvalues',
            'parameters',
            'tenders',
        );

        foreach ($sample_data as $table) {
            $chk = $db->query('SELECT * FROM ' . $table, $name);
            if (!$db->fetch_row($chk)){
                $loaded = self::loadSampleData($db, $table, true);
                if (!$loaded) {
                    $errors[] = array(
                        'struct' => $table,
                        'query' => 'None',
                        'details' => 'Failed loading sample data',
                    );
                }
            } else {
                $db->end_query($chk);
            }
        }

        $chk = $db->query('SELECT drawer_no FROM drawerowner', $name);
        if ($db->num_rows($chk) == 0){
            $db->query('INSERT INTO drawerowner (drawer_no) VALUES (1)', $name);
            $db->query('INSERT INTO drawerowner (drawer_no) VALUES (2)', $name);
        }

        CoreState::loadParams();
        
        return $errors;
    }

    /**
      Create translog tables and views
      @param $db [SQLManager] database connection
      @param $name [string] database name
      @return [array] of error messages
    */
    public static function createTransDBs($db, $name)
    {
        $errors = array();
        $type = $db->dbms_name();

        if (CoreLocal::get('laneno') == 0) {
            $errors[] = array(
                'struct' => 'No structures created for lane #0',
                'query' => 'None',
                'details' => 'Zero is reserved for server',
            );

            return $errors;
        }

        /* lttsummary, lttsubtotals, and subtotals
         * always get rebuilt to account for tax rate
         * changes */
        if (!function_exists('buildLTTViews')) {
            include(dirname(__FILE__) . '/buildLTTViews.php');
        }

        $models = array(
            'DTransactionsModel',
            'LocalTransModel',
            'LocalTransArchiveModel',
            'LocalTransTodayModel',
            'LocalTempTransModel',
            'SuspendedModel',
            'TaxRatesModel',
            'CouponAppliedModel',
            'EfsnetRequestModel',
            'EfsnetRequestModModel',
            'EfsnetResponseModel',
            'EfsnetTokensModel',
            'PaycardTransactionsModel',
            'CapturedSignatureModel',
            // placeholder,
            '__LTT__',
            // Views
            'CcReceiptViewModel',
            'MemDiscountAddModel',
            'MemDiscountRemoveModel',
            'StaffDiscountAddModel',
            'StaffDiscountRemoveModel',
            'ScreenDisplayModel',
            'TaxViewModel',
        );
        foreach ($models as $class) {
            if ($class == '__LTT__') {
                $errors = buildLTTViews($db,$type,$errors);
                continue;
            }
            $obj = new $class($db);
            $errors[] = $obj->createIfNeeded($name);
        }
        
        /**
          Not using models for receipt views. Hopefully many of these
          can go away as deprecated.
        */
        $lttR = "CREATE view ltt_receipt as 
            select
            l.description as description,
            case 
                when voided = 5 
                    then 'Discount'
                when trans_status = 'M'
                    then 'Mbr special'
                when trans_status = 'S'
                    then 'Staff special'
                when unitPrice = 0.01
                    then ''
                when scale <> 0 and quantity <> 0 
                    then ".$db->concat('quantity', "' @ '", 'unitPrice','')."
                when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
                    then ".$db->concat('volume', "' / '", 'unitPrice','')."
                when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
                    then ".$db->concat('quantity', "' @ '", 'volume', "' /'", 'unitPrice','')."
                when abs(itemQtty) > 1 and discounttype = 3
                    then ".$db->concat('ItemQtty', "' / '", 'unitPrice','')."
                when abs(itemQtty) > 1
                    then ".$db->concat('quantity', "' @ '", 'unitPrice','')."
                when matched > 0
                    then '1 w/ vol adj'
                else ''
            end
            as comment,
            total,
            case 
                when trans_status = 'V' 
                    then 'VD'
                when trans_status = 'R'
                    then 'RF'
                when tax = 1 and foodstamp <> 0
                    then 'TF'
                when tax = 1 and foodstamp = 0
                    then 'T' 
                when tax = 0 and foodstamp <> 0
                    then 'F'
                WHEN (tax > 1 and foodstamp <> 0)
                    THEN ".$db->concat('SUBSTR(t.description,1,1)',"'F'",'')."
                WHEN (tax > 1 and foodstamp = 0)
                    THEN SUBSTR(t.description,1,1)
                when tax = 0 and foodstamp = 0
                    then '' 
            end
            as Status,
            trans_type,
            unitPrice,
            voided,
            CASE 
                WHEN upc = 'DISCOUNT' THEN (
                SELECT MAX(trans_id) FROM localtemptrans WHERE voided=3
                )-1
                WHEN trans_type = 'T' THEN trans_id+99999    
                ELSE trans_id
            END AS trans_id
            from localtemptrans as l
            left join taxrates as t
            on l.tax = t.id
            where voided <> 5 and UPC <> 'TAX'
            AND trans_type <> 'L'";
        if($type == 'mssql'){
            $lttR = "CREATE view ltt_receipt as 
                select
                l.description,
                case 
                    when voided = 5 
                        then 'Discount'
                    when trans_status = 'M'
                        then 'Mbr special'
                    when trans_status = 'S'
                        then 'Staff special'
                    when unitPrice = 0.01
                        then ''
                    when scale <> 0 and quantity <> 0 
                        then quantity+ ' @ '+ unitPrice
                    when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
                        then volume+ ' /'+ unitPrice
                    when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
                        then Quantity+ ' @ '+Volume+ ' /'+ unitPrice
                    when abs(itemQtty) > 1 and discounttype = 3
                        then ItemQtty+ ' /'+ UnitPrice
                    when abs(itemQtty) > 1
                        then quantity+' @ '+unitPrice
                    when matched > 0
                        then '1 w/ vol adj'
                    else ''
                end
                as comment,
                total,
                case 
                    when trans_status = 'V' 
                        then 'VD'
                    when trans_status = 'R'
                        then 'RF'
                    when tax = 1 and foodstamp <> 0
                        then 'TF'
                    when tax = 1 and foodstamp = 0
                        then 'T' 
                    WHEN (tax > 1 and foodstamp <> 0)
                        THEN LEFT(t.description,1)+'F'
                    WHEN (tax > 1 and foodstamp = 0)
                        THEN LEFT(t.description,1)
                    when tax = 0 and foodstamp <> 0
                        then 'F'
                    when tax = 0 and foodstamp = 0
                        then '' 
                end
                as Status,
                trans_type,
                unitPrice,
                trans_id
                CASE 
                    WHEN upc = 'DISCOUNT' THEN (
                    SELECT MAX(trans_id) FROM localtemptrans WHERE voided=3
                    )-1
                    WHEN trans_type = 'T' THEN trans_id+99999    
                    ELSE trans_id
                END AS trans_id
                from localtemptrans as l
                left join taxrates as t
                on l.tax = t.id
                where voided <> 5 and UPC <> 'TAX'
                AND trans_type <> 'L'
                order by trans_id";
        }
        self::dbStructureModify($db,'ltt_receipt','DROP VIEW ltt_receipt',$errors);
        if(!$db->table_exists('ltt_receipt',$name)){
            self::dbStructureModify($db,'ltt_receipt',$lttR,$errors);
        }

        $rV = "CREATE view receipt as
            select
            case 
                when trans_type = 'T'
                    then     ".$db->concat( "SUBSTR(".$db->concat('UPPER(TRIM(description))','space(44)','').", 1, 44)" 
                        , "right(".$db->concat( 'space(8)', 'FORMAT(-1 * total, 2)','').", 8)" 
                        , "right(".$db->concat( 'space(4)', 'status','').", 4)",'')."
                when voided = 3 
                    then     ".$db->concat("SUBSTR(".$db->concat('description', 'space(30)','').", 1, 30)"
                        , 'space(9)'
                        , "'TOTAL'"
                        , 'right('.$db->concat( 'space(8)', 'FORMAT(unitPrice, 2)','').', 8)','')."
                when voided = 2
                    then     description
                when voided = 4
                    then     description
                when voided = 6
                    then     description
                when voided = 7 or voided = 17
                    then     ".$db->concat("SUBSTR(".$db->concat('description', 'space(30)','').", 1, 30)"
                        , 'space(14)'
                        , 'right('.$db->concat( 'space(8)', 'FORMAT(unitPrice, 2)','').', 8)'
                        , 'right('.$db->concat( 'space(4)', 'status','').', 4)','')."
                else
                    ".$db->concat("SUBSTR(".$db->concat('description', 'space(30)','').", 1, 30)"
                    , "' '" 
                    , "SUBSTR(".$db->concat('comment', 'space(13)','').", 1, 13)"
                    , 'right('.$db->concat('space(8)', 'FORMAT(total, 2)','').', 8)'
                    , 'right('.$db->concat('space(4)', 'status','').', 4)','')."
            end
            as linetoprint
            from ltt_receipt
            order by trans_id";
        if($type == 'mssql'){
            $rV = "CREATE  view receipt as
            select top 100 percent
            case 
                when trans_type = 'T'
                    then     right((space(44) + upper(rtrim(Description))), 44) 
                        + right((space(8) + convert(varchar, (-1 * Total))), 8) 
                        + right((space(4) + status), 4)
                when voided = 3 
                    then     left(Description + space(30), 30) 
                        + space(9) 
                        + 'TOTAL' 
                        + right(space(8) + convert(varchar, UnitPrice), 8)
                when voided = 2
                    then     description
                when voided = 4
                    then     description
                when voided = 6
                    then     description
                when voided = 7 or voided = 17
                    then     left(Description + space(30), 30) 
                        + space(14) 
                        + right(space(8) + convert(varchar, UnitPrice), 8) 
                        + right(space(4) + status, 4)
                when sequence < 1000
                    then     description
                else
                    left(Description + space(30), 30)
                    + ' ' 
                    + left(Comment + space(13), 13) 
                    + right(space(8) + convert(varchar, Total), 8) 
                    + right(space(4) + status, 4)
            end
            as linetoprint,
            sequence
            from ltt_receipt
            order by sequence";
        }
        elseif($type == 'pdolite'){
            $rV = str_replace('right(','str_right(',$rV);
            $rV = str_replace('FORMAT(','ROUND(',$rV);
        }

        if(!$db->table_exists('receipt',$name)){
            self::dbStructureModify($db,'receipt',$rV,$errors);
        }

        $rplttR = "CREATE view rp_ltt_receipt as 
            select
            register_no,
            emp_no,
            trans_no,
            l.description as description,
            case 
                when voided = 5 
                    then 'Discount'
                when trans_status = 'M'
                    then 'Mbr special'
                when trans_status = 'S'
                    then 'Staff special'
                when unitPrice = 0.01
                    then ''
                when scale <> 0 and quantity <> 0 
                    then ".$db->concat('quantity', "' @ '", 'unitPrice','')."
                when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
                    then ".$db->concat('volume', "' / '", 'unitPrice','')."
                when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
                    then ".$db->concat('quantity', "' @ '", 'volume', "' /'", 'unitPrice','')."
                when abs(itemQtty) > 1 and discounttype = 3
                    then ".$db->concat('ItemQtty', "' / '", 'unitPrice','')."
                when abs(itemQtty) > 1
                    then ".$db->concat('quantity', "' @ '", 'unitPrice','')."
                when matched > 0
                    then '1 w/ vol adj'
                else ''
            end
            as comment,
            total,
            case 
                when trans_status = 'V' 
                    then 'VD'
                when trans_status = 'R'
                    then 'RF'
                WHEN (tax = 1 and foodstamp <> 0)
                    THEN 'TF'
                WHEN (tax = 1 and foodstamp = 0)
                    THEN 'T' 
                WHEN (tax > 1 and foodstamp <> 0)
                    THEN ".$db->concat('SUBSTR(t.description,1,1)',"'F'",'')."
                WHEN (tax > 1 and foodstamp = 0)
                    THEN SUBSTR(t.description,1,1)
                when tax = 0 and foodstamp <> 0
                    then 'F'
                when tax = 0 and foodstamp = 0
                    then '' 
            end
            as Status,
            trans_type,
            unitPrice,
            voided,
            trans_id
            from localtranstoday as l
            left join taxrates as t
            on l.tax = t.id
            where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
            AND trans_type <> 'L'
            AND datetime >= CURRENT_DATE
            order by emp_no, trans_no, trans_id";
        if($type == 'mssql'){
            $rplttR = "CREATE view rp_ltt_receipt as 
                select
                register_no,
                emp_no,
                trans_no,
                description,
                case 
                    when voided = 5 
                        then 'Discount'
                    when trans_status = 'M'
                        then 'Mbr special'
                    when trans_status = 'S'
                        then 'Staff special'
                    when unitPrice = 0.01
                        then ''
                    when scale <> 0 and quantity <> 0 
                        then quantity+ ' @ '+ unitPrice
                    when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
                        then volume+ ' /'+ unitPrice
                    when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
                        then Quantity+ ' @ '+ Volume+ ' /'+ unitPrice
                    when abs(itemQtty) > 1 and discounttype = 3
                        then ItemQtty+' /'+ UnitPrice
                    when abs(itemQtty) > 1
                        then quantity+ ' @ '+ unitPrice
                    when matched > 0
                        then '1 w/ vol adj'
                    else ''
                end
                as comment,
                total,
                case 
                    when trans_status = 'V' 
                        then 'VD'
                    when trans_status = 'R'
                        then 'RF'
                    WHEN (tax = 1 and foodstamp <> 0)
                        THEN 'TF'
                    WHEN (tax = 1 and foodstamp = 0)
                        THEN 'T' 
                    WHEN (tax > 1 and foodstamp <> 0)
                        THEN LEFT(t.description,1)+'F'
                    WHEN (tax > 1 and foodstamp = 0)
                        THEN LEFT(t.description,1)
                    when tax = 0 and foodstamp <> 0
                        then 'F'
                    when tax = 0 and foodstamp = 0
                        then '' 
                end
                as Status,
                trans_type,
                unitPrice,
                voided,
                trans_id
                from localtranstoday as l
                left join taxrates as t
                on l.tax = t.id
                where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
                AND trans_type <> 'L'
                AND datetime >= CURRENT_DATE
                order by emp_no, trans_no, trans_id";
        }
        self::dbStructureModify($db,'rp_ltt_receipt','DROP VIEW rp_ltt_receipt',$errors);
        if(!$db->table_exists('rp_ltt_receipt',$name)){
            self::dbStructureModify($db,'rp_ltt_receipt',$rplttR,$errors);
        }

        $rprV = "CREATE view rp_receipt  as
            select
            register_no,
            emp_no,
            trans_no,
            case 
                when trans_type = 'T'
                    then     ".$db->concat( "SUBSTR(".$db->concat('UPPER(TRIM(description))','space(44)','').", 1, 44)" 
                        , "right(".$db->concat( 'space(8)', 'FORMAT(-1 * total, 2)','').", 8)" 
                        , "right(".$db->concat( 'space(4)', 'status','').", 4)",'')."
                when voided = 3 
                    then     ".$db->concat("SUBSTR(".$db->concat('description', 'space(30)','').", 1, 30)"
                        , 'space(9)'
                        , "'TOTAL'"
                        , 'right('.$db->concat( 'space(8)', 'FORMAT(unitPrice, 2)','').', 8)','')."
                when voided = 2
                    then     description
                when voided = 4
                    then     description
                when voided = 6
                    then     description
                when voided = 7 or voided = 17
                    then     ".$db->concat("SUBSTR(".$db->concat('description', 'space(30)','').", 1, 30)"
                        , 'space(14)'
                        , 'right('.$db->concat( 'space(8)', 'FORMAT(unitPrice, 2)','').', 8)'
                        , 'right('.$db->concat( 'space(4)', 'status','').', 4)','')."
                else
                    ".$db->concat("SUBSTR(".$db->concat('description', 'space(30)','').", 1, 30)"
                    , "' '" 
                    , "SUBSTR(".$db->concat('comment', 'space(13)','').", 1, 13)"
                    , 'right('.$db->concat('space(8)', 'FORMAT(total, 2)','').', 8)'
                    , 'right('.$db->concat('space(4)', 'status','').', 4)','')."
            end
            as linetoprint,
            trans_id
            from rp_ltt_receipt";
        if($type == 'mssql'){
            $rprV = "CREATE view rp_receipt  as
            select
            register_no,
            emp_no,
            trans_no,
            case 
                when trans_type = 'T'
                    then     right((space(44) + upper(rtrim(Description))), 44) 
                        + right((space(8) + convert(varchar, (-1 * Total))), 8) 
                        + right((space(4) + status), 4)
                when voided = 3 
                    then     left(Description + space(30), 30) 
                        + space(9) 
                        + 'TOTAL' 
                        + right(space(8) + convert(varchar, UnitPrice), 8)
                when voided = 2
                    then     description
                when voided = 4
                    then     description
                when voided = 6
                    then     description
                when voided = 7 or voided = 17
                    then     left(Description + space(30), 30) 
                        + space(14) 
                        + right(space(8) + convert(varchar, UnitPrice), 8) 
                        + right(space(4) + status, 4)
                else
                    left(Description + space(30), 30)
                    + ' ' 
                    + left(Comment + space(13), 13) 
                    + right(space(8) + convert(varchar, Total), 8) 
                    + right(space(4) + status, 4)
            end
            as linetoprint,
            trans_id
            from rp_ltt_receipt";
        }
        elseif($type == 'pdolite'){
            $rprV = str_replace('right(','str_right(',$rprV);
            $rprV = str_replace('FORMAT(','ROUND(',$rprV);
        }
        if(!$db->table_exists('rp_receipt',$name)){
            self::dbStructureModify($db,'rp_receipt',$rprV,$errors);
        }

        /* Lookup database collation
          For reasons that make zero sense, when creating views
          in the travis-ci test database, MySQL *sometimes* uses
          a collation other than the default when casting numbers
          to strings. This then results in weird collation errors.
          Looking up the local DB server setting ensures that CORE
          still abides by local preferences when explicitly requesting
          a specific collation. None of this should be necessary
          but finding the bug or undocumented "feature" of MySQL 
          causing this is a waste of time.
        */
        $mysql_collation = $db->query('SELECT @@collation_database', $name);
        $mysql_collation = $db->fetch_row($mysql_collation);
        $mysql_collation = $mysql_collation[0];


        $lttG = "CREATE  view ltt_grouped as
        select     upc,description,trans_type,trans_subtype,sum(itemQtty)as itemqtty,
            discounttype,volume,
            trans_status,
            case when voided=1 then 0 else voided end as voided,
            department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
            scale,
            sum(unitprice) as unitprice, 
            CAST(sum(total) AS decimal(10,2)) as total,
            sum(regPrice) as regPrice,tax,foodstamp,charflag,
            case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
        from localtemptrans
        where description not like '** YOU SAVED %' and trans_status = 'M'
        group by upc,description,trans_type,trans_subtype,discounttype,volume,
            trans_status,
            department,scale,case when voided=1 then 0 else voided end,
            matched,tax,foodstamp,charflag,
            case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

        union all

        select     upc,case when numflag=1 then ".$db->concat('description',"'*'",'')." else description end as description,
            trans_type,trans_subtype,sum(itemQtty)as itemqtty,discounttype,volume,
            trans_status,
            case when voided=1 then 0 else voided end as voided,
            department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
            scale,unitprice,CAST(sum(total) AS decimal(10,2)) as total,regPrice,tax,foodstamp,charflag,
            case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
        from localtemptrans
        where description not like '** YOU SAVED %' and trans_status !='M'
        AND trans_type <> 'L'
        group by upc,description,trans_type,trans_subtype,discounttype,volume,
            trans_status,
            department,scale,case when voided=1 then 0 else voided end,
            unitprice,regPrice,matched,tax,foodstamp,charflag,
            case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

        union all

        select     upc,
            case when discounttype=1 then
            ".$db->concat("' > you saved \$'",'CAST(CAST(sum(quantity*regprice-quantity*unitprice) AS decimal(10,2)) AS char(20)) COLLATE ' . $mysql_collation,"'  <'",'')."
            when discounttype=2 then
            ".$db->concat("' > you saved \$'",'CAST(CAST(sum(quantity*regprice-quantity*unitprice) AS decimal(10,2)) AS char(20)) COLLATE ' . $mysql_collation,"'  Member Special <'",'')."
            end as description,
            trans_type,'0' as trans_subtype,0 as itemQtty,discounttype,volume,
            'D' as trans_status,
            2 as voided,
            department,0 as quantity,matched,min(trans_id)+1 as trans_id,
            scale,0 as unitprice,
            0 as total,
            0 as regPrice,0 as tax,0 as foodstamp,charflag,
            case when trans_status='d' or scale=1 then trans_id else scale end as grouper
        from localtemptrans
        where description not like '** YOU SAVED %' and (discounttype=1 or discounttype=2)
        AND trans_type <> 'L'
        group by upc,description,trans_type,trans_subtype,discounttype,volume,
            department,scale,matched,
            case when trans_status='d' or scale=1 then trans_id else scale end
        having CAST(sum(quantity*regprice-quantity*unitprice) AS decimal(10,2))<>0";
        if($type == 'mssql'){
            $lttG = "CREATE   view ltt_grouped as
            select     upc,description,trans_type,trans_subtype,sum(itemQtty)as itemqtty,
                discounttype,volume,
                trans_status,
                case when voided=1 then 0 else voided end as voided,
                department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
                scale,
                sum(unitprice) as unitprice, 
                sum(total) as total,
                sum(regPrice) as regPrice,tax,foodstamp,charflag,
                case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
            from localtemptrans
            where description not like '** YOU SAVED %' and trans_status = 'M'
            group by upc,description,trans_type,trans_subtype,discounttype,volume,
                trans_status,
                department,scale,case when voided=1 then 0 else voided end,
                matched,tax,foodstamp,charflag,
                case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

            union all

            select     upc,case when numflag=1 then description+'*' else description end as description,
                trans_type,trans_subtype,sum(itemQtty)as itemqtty,discounttype,volume,
                trans_status,
                case when voided=1 then 0 else voided end as voided,
                department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
                scale,unitprice,sum(total) as total,regPrice,tax,foodstamp,charflag,
                case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
            from localtemptrans
            where description not like '** YOU SAVED %' and trans_status !='M'
            AND trans_type <> 'L'
            group by upc,description,trans_type,trans_subtype,discounttype,volume,
                trans_status,
                department,scale,case when voided=1 then 0 else voided end,
                unitprice,regPrice,matched,tax,foodstamp,charflag,
                case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

            union all

            select     upc,
                case when discounttype=1 then
                ' > you saved $'+convert(varchar(20),convert(money,sum(quantity*regprice-quantity*unitprice)))+'  <'
                when discounttype=2 then
                ' > you saved $'+convert(varchar(20),convert(money,sum(quantity*regprice-quantity*unitprice)))+'  Member Special <'
                end as description,
                trans_type,'0' as trans_subtype,0 as itemQtty,discounttype,volume,
                'D' as trans_status,
                2 as voided,
                department,0 as quantity,matched,min(trans_id)+1 as trans_id,
                scale,0 as unitprice,
                0 as total,
                0 as regPrice,0 as tax,0 as foodstamp,charflag,
                case when trans_status='d' or scale=1 then trans_id else scale end as grouper
            from localtemptrans
            where description not like '** YOU SAVED %' and (discounttype=1 or discounttype=2)
            AND trans_type <> 'L'
            group by upc,description,trans_type,trans_subtype,discounttype,volume,
                department,scale,matched,
                case when trans_status='d' or scale=1 then trans_id else scale end
            having convert(money,sum(quantity*regprice-quantity*unitprice))<>0";
        }
        self::dbStructureModify($db,'ltt_grouped','DROP VIEW ltt_grouped',$errors);
        if(!$db->table_exists('ltt_grouped',$name)){
            self::dbStructureModify($db,'ltt_grouped',$lttG,$errors);
        }

        $lttreorderG = "CREATE   view ltt_receipt_reorder_g as
        select 
        l.description as description,
        case 
            when voided = 5 
                then 'Discount'
            when trans_status = 'M'
                then 'Mbr special'
            when trans_status = 'S'
                then 'Staff special'
            when unitPrice = 0.01
                then ''
            when charflag = 'SO'
                then ''
            when scale <> 0 and quantity <> 0 
                then ".$db->concat('CAST(quantity AS char)',"' @ '",'CAST(unitPrice AS char)','')."
            when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
                then ".$db->concat('CAST(volume AS char)',"' / '",'CAST(unitPrice AS char)','')."
            when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
                then ".$db->concat('CAST(quantity AS char)',"' @ '",'CAST(volume AS char)',"' /'",'CAST(unitPrice AS char)','')."
            when abs(itemQtty) > 1 and discounttype = 3
                then ".$db->concat('CAST(ItemQtty AS char)',"' / '",'CAST(unitPrice AS char)','')."
            when abs(itemQtty) > 1
                then ".$db->concat('CAST(quantity AS char)',"' @ '",'CAST(unitPrice AS char)','')."
            when matched > 0
                then '1 w/ vol adj'
            else ''
        end
        as comment,
        total,
        case 
            when trans_status = 'V' 
                then 'VD'
            when trans_status = 'R'
                then 'RF'
            when tax = 1 and foodstamp <> 0
                then 'TF'
            when tax = 1 and foodstamp = 0
                then 'T' 
            WHEN (tax > 1 and foodstamp <> 0)
                THEN ".$db->concat('SUBSTR(t.description,1,1)',"'F'",'')."
            WHEN (tax > 1 and foodstamp = 0)
                THEN SUBSTR(t.description,1,1)
            when tax = 0 and foodstamp <> 0
                then 'F'
            when tax = 0 and foodstamp = 0
                then '' 
        end
        as status,
        case when trans_subtype='CM' or voided in (10,17)
            then 'CM' else trans_type
        end
        as trans_type,
        unitPrice,
        voided,
        trans_id + 1000 as sequence,
        department,
        upc,
        trans_subtype
        from ltt_grouped as l
        left join taxrates as t
        on l.tax = t.id
        where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
        AND trans_type <> 'L'
        and not (trans_status='M' and total=CAST('0.00' AS decimal(10,2)))

        union all

        select
        '  ' as description,
        ' ' as comment,
        0 as total,
        ' ' as Status,
        ' ' as trans_type,
        0 as unitPrice,
        0 as voided,
        999 as sequence,
        '' as department,
        '' as upc,
        '' as trans_subtype";

        if($type == 'mssql'){
            $lttreorderG = "CREATE view ltt_receipt_reorder_g as
            select top 100 percent
            l.description,
            case 
                when voided = 5 
                    then 'Discount'
                when trans_status = 'M'
                    then 'Mbr special'
                when trans_status = 'S'
                    then 'Staff special'
                when unitPrice = 0.01
                    then ''
                when scale <> 0 and quantity <> 0 
                    then convert(varchar, quantity) + ' @ ' + convert(varchar, unitPrice)
                when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
                    then convert(varchar, volume) + ' /' + convert(varchar, unitPrice)
                when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
                    then convert(varchar, Quantity) + ' @ ' + convert(varchar, Volume) + ' /' + convert(varchar, unitPrice)
                when abs(itemQtty) > 1 and discounttype = 3
                    then convert(varchar,ItemQtty) + ' /' + convert(varchar, UnitPrice)
                when abs(itemQtty) > 1
                    then convert(varchar, quantity) + ' @ ' + convert(varchar, unitPrice)    
                when matched > 0
                    then '1 w/ vol adj'
                else ''
            end
            as comment,
            total,
            case 
                when trans_status = 'V' 
                    then 'VD'
                when trans_status = 'R'
                    then 'RF'
                WHEN (tax = 1 and foodstamp <> 0)
                    THEN 'TF'
                WHEN (tax = 1 and foodstamp = 0)
                    THEN 'T' 
                WHEN (tax > 1 and foodstamp <> 0)
                    THEN LEFT(t.description,1)+'F'
                WHEN (tax > 1 and foodstamp = 0)
                    THEN LEFT(t.description,1)
                when tax = 0 and foodstamp <> 0
                    then 'F'
                when tax = 0 and foodstamp = 0
                    then '' 
            end
            as Status,
            case when trans_subtype='CM' or voided in (10,17)
                then 'CM' else trans_type
            end
            as trans_type,
            unitPrice,
            voided,
            trans_id + 1000 as sequence,
            department,
            upc,
            trans_subtype
            from ltt_grouped as l
            left join taxrates as t
            on l.tax = t.id
            where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
            AND trans_type <> 'L'
            and not (trans_status='M' and total=convert(money,'0.00'))

            union all

            select
            '  ' as description,
            ' ' as comment,
            0 as total,
            ' ' as Status,
            ' ' as trans_type,
            0 as unitPrice,
            0 as voided,
            999 as sequence,
            '' as department,
            '' as upc,
            '' as trans_subtype";
        }
        self::dbStructureModify($db,'ltt_receipt_reorder_g','DROP VIEW ltt_receipt_reorder_g',$errors);
        if(!$db->table_exists('ltt_receipt_reorder_g',$name)){
            self::dbStructureModify($db,'ltt_receipt_reorder_g',$lttreorderG,$errors);
        }

        $reorderG = "CREATE   view receipt_reorder_g as
            select 
            case 
                when trans_type = 'T' 
                    then     
                        case when trans_subtype = 'CP' and upc<>'0'
                        then    ".$db->concat(
                            "SUBSTR(".$db->concat('description','space(30)','').",1,30)",
                            "' '",
                            "SUBSTR(".$db->concat('comment','space(12)','').",1,12)",
                            "right(".$db->concat('space(8)','CAST(total AS char)','').",8)",
                            "right(".$db->concat('space(4)','status','').",4)",'')." 
                        else     ".$db->concat( 
                            "right(".$db->concat('space(44)','upper(description)','').",44)", 
                            "right(".$db->concat('space(8)','CAST((-1*total) AS char)','').",8)",
                            "right(".$db->concat('space(4)','status','').",4)",'')." 
                        end 
                when voided = 3 
                    then     ".$db->concat( 
                        "SUBSTR(".$db->concat('description','space(30)','').",1,30)",
                        "space(9)", 
                        "'TOTAL'", 
                        "right(".$db->concat('space(8)','CAST(unitPrice AS char)','').",8)",'')."
                when voided = 2
                    then     description
                when voided = 4
                    then     description
                when voided = 6
                    then     description
                when voided = 7 or voided = 17
                    then     ".$db->concat(
                        "SUBSTR(".$db->concat('description','space(30)','').",1,30)",
                        "space(14)", 
                        "right(".$db->concat('space(8)','CAST(unitPrice AS char)','').",8)",
                        "right(".$db->concat('space(4)','status','').",4)",'')." 
                when sequence < 1000
                    then     description
                else
                    ".$db->concat(
                        "SUBSTR(".$db->concat('description','space(30)','').",1,30)",
                        "' '",
                        "SUBSTR(".$db->concat('comment','space(12)','').",1,12)",
                        "right(".$db->concat('space(8)','CAST(total AS char)','').",8)",
                        "right(".$db->concat('space(4)','status','').",4)",'')." 
                end as linetoprint,
            sequence,
            department,
            super_name as dept_name,
            trans_type,
            upc
            from ltt_receipt_reorder_g r
            left outer join ".CoreLocal::get('pDatabase').".MasterSuperDepts d on r.department=d.dept_ID
            where r.total<>0 or r.unitPrice=0
            order by sequence";
        
        if($type == 'mssql'){
            $reorderG = "CREATE view receipt_reorder_g as
            select top 100 percent
            case 
                when trans_type = 'T' 
                    then     
                        case when trans_subtype = 'CP' and upc<>'0'
                        then    left(Description + space(30), 30)
                            + ' ' 
                            + left(Comment + space(12), 12) 
                            + right(space(8) + convert(varchar, Total), 8) 
                            + right(space(4) + status, 4) 
                        else     right((space(44) + upper(rtrim(Description))), 44) 
                            + right((space(8) + convert(varchar, (-1 * Total))), 8) 
                            + right((space(4) + status), 4) 
                        end 
                when voided = 3 
                    then     left(Description + space(30), 30) 
                        + space(9) 
                        + 'TOTAL' 
                        + right(space(8) + convert(varchar, UnitPrice), 8)
                when voided = 2
                    then     description
                when voided = 4
                    then     description
                when voided = 6
                    then     description
                when voided = 7 or voided = 17
                    then     left(Description + space(30), 30) 
                        + space(14) 
                        + right(space(8) + convert(varchar, UnitPrice), 8) 
                        + right(space(4) + status, 4)
                when sequence < 1000
                    then     description
                else
                    left(Description + space(30), 30)
                    + ' ' 
                    + left(Comment + space(12), 12) 
                    + right(space(8) + convert(varchar, Total), 8) 
                    + right(space(4) + status, 4)
                end
                as linetoprint,
                sequence,
                department,
                dept_name,
                trans_type,
                upc
                from ltt_receipt_reorder_g r
                left outer join ".CoreLocal::get('pDatabase')."dbo.MasterSuperDepts
                       d on r.department=d.dept_ID
                where r.total<>0 or r.unitprice=0
                order by sequence";
        }
        elseif($type == 'pdolite'){
            $reorderG = str_replace('right(','str_right(',$reorderG);
        }
        if(!$db->table_exists('receipt_reorder_g',$name)){
            self::dbStructureModify($db,'receipt_reorder_g',$reorderG,$errors);
        }


        $unionsG = "CREATE view receipt_reorder_unions_g as
        select linetoprint,
        sequence,dept_name,1 as ordered,upc
        from receipt_reorder_g
        where (department<>0 or trans_type IN ('CM','I'))
        and linetoprint not like 'member discount%'

        union all

        select replace(replace(replace(r1.linetoprint,'** T',' = t'),' **',' = '),'W','w') as linetoprint,
        r1.sequence,r2.dept_name,1 as ordered,r2.upc
        from receipt_reorder_g as r1 join receipt_reorder_g as r2 on r1.sequence+1=r2.sequence
        where r1.linetoprint like '** T%' and r2.dept_name is not null and r1.linetoprint<>'** Tare Weight 0 **'

        union all

        select
        ".$db->concat(
        "SUBSTR(".$db->concat("'** '","trim(CAST(percentDiscount AS char))","'% Discount Applied **'",'space(30)','').",1,30)",
        "' '", 
        "space(13)",
        "right(".$db->concat('space(8)',"CAST((-1*transDiscount) AS char)",'').",8)",
        "space(4)",'')." as linetoprint,
        0 as sequence,null as dept_name,2 as ordered,
        '' as upc
        from subtotals
        where percentDiscount<>0

        union all

        select linetoprint,sequence,null as dept_name,2 as ordered,upc
        from receipt_reorder_g
        where linetoprint like 'member discount%'

        union all

        select 
        ".$db->concat(
        "right(".$db->concat('space(44)',"'SUBTOTAL'",'').",44)",
        "right(".$db->concat('space(8)',"CAST(round(l.runningTotal-s.taxTotal-l.tenderTotal,2) AS char)",'').",8)",
        "space(4)",'')." as linetoprint,1 as sequence,null as dept_name,3 as ordered,'' as upc
        from lttsummary as l, subtotals as s

        union all

        select 
        ".$db->concat(
        "right(".$db->concat('space(44)',"'TAX'",'').",44)",
        "right(".$db->concat('space(8)',"CAST(round(taxTotal,2) AS char)",'').",8)", 
        "space(4)",'')." as linetoprint,
        2 as sequence,null as dept_name,3 as ordered,'' as upc
        from subtotals

        union all

        select 
        ".$db->concat(
        "right(".$db->concat('space(44)',"'TOTAL'",'').",44)",
        "right(".$db->concat('space(8)',"CAST(runningTotal-tenderTotal AS char)",'').",8)", 
        "space(4)",'')." as linetoprint,3 as sequence,null as dept_name,3 as ordered,'' as upc
        from lttsummary

        union all

        select linetoprint,sequence,dept_name,4 as ordered,upc
        from receipt_reorder_g
        where (trans_type='T' and department = 0)
        or (department = 0 and trans_type NOT IN ('CM','I')
        and linetoprint NOT LIKE '** %'
        and linetoprint NOT LIKE 'Subtotal%') 

        union all

        select 
        ".$db->concat(
        "right(".$db->concat('space(44)',"'CURRENT AMOUNT DUE'",'').",44)",
        "right(".$db->concat('space(8)',"CAST(runningTotal-transDiscount AS char)",'').",8)", 
        "space(4)",'')." as linetoprint,
        5 as sequence,
        null as dept_name,
        5 as ordered,'' as upc
        from subtotals where runningTotal <> 0 ";

        if($type == 'mssql'){
            $unionsG = "CREATE view receipt_reorder_unions_g as
            select linetoprint,
            sequence,dept_name,1 as ordered,upc
            from receipt_reorder_g
            where (department<>0 or trans_type IN ('CM','I'))
            and linetoprint not like 'member discount%'

            union all

            select replace(replace(replace(r1.linetoprint,'** T',' = T'),' **',' = '),'W','w') as linetoprint,
            r1.[sequence],r2.dept_name,1 as ordered,r2.upc
            from receipt_reorder_g r1 join receipt_reorder_g r2 on r1.[sequence]+1=r2.[sequence]
            where r1.linetoprint like '** T%' and r2.dept_name is not null and r1.linetoprint<>'** Tare Weight 0 **'

            union all

            select
            left('** '+rtrim(convert(char,percentdiscount))+'% Discount Applied **' + space(30), 30)
            + ' ' 
            + left('' + space(13), 13) 
            + right(space(8) + convert(varchar, (-1*transDiscount)), 8) 
            + right(space(4) + '', 4),
            0 as sequence,null as dept_name,2 as ordered,
            '' as upc
            from subtotals
            where percentdiscount<>0

            union all

            select linetoprint,sequence,null as dept_name,2 as ordered,upc
            from receipt_reorder_g
            where linetoprint like 'member discount%'

            union all

            select 
            right((space(44) + upper(rtrim('SUBTOTAL'))), 44) 
            + right((space(8) + convert(varchar,round(l.runningTotal-s.taxTotal-l.tenderTotal,2))),8)
            + right((space(4) + ''), 4) as linetoprint,1 as sequence,null as dept_name,3 as ordered,'' as upc
            from lttsummary as l, subtotals as s

            union all

            select 
            right((space(44) + upper(rtrim('TAX'))), 44) 
            + right((space(8) + convert(varchar,round(taxtotal,2))), 8) 
            + right((space(4) + ''), 4) as linetoprint,
            2 as sequence,null as dept_name,3 as ordered,'' as upc
            from subtotals

            union all

            select 
            right((space(44) + upper(rtrim('TOTAL'))), 44) 
            + right((space(8) +convert(varchar,runningtotal-tendertotal)),8)
            + right((space(4) + ''), 4) as linetoprint,3 as sequence,null as dept_name,3 as ordered,'' as upc
            from lttsummary

            union all

            select linetoprint,sequence,dept_name,4 as ordered,upc
            from receipt_reorder_g
            where (trans_type='T' and department = 0)
            or (department = 0 and trans_type NOT IN ('CM','I') and linetoprint like '%Coupon%')

            union all

            select 
            right((space(44) + upper(rtrim('Current Amount Due'))), 44) 
            +right((space(8) + convert(varchar,subtotal)),8)
            + right((space(4) + ''), 4) as linetoprint,
            5 as sequence,
            null as dept_name,
            5 as ordered,'' as upc
            from subtotals where runningtotal <> 0 ";
        }
        elseif($type == 'pdolite'){
            $unionsG = str_replace('right(','str_right(',$unionsG);
        }
        self::dbStructureModify($db,'receipt_reorder_unions_g','DROP VIEW receipt_reorder_unions_g',$errors);
        if(!$db->table_exists('receipt_reorder_unions_g',$name)){
            self::dbStructureModify($db,'receipt_reorder_unions_g',$unionsG,$errors);
        }

        $rplttG = "CREATE     view rp_ltt_grouped as
            select     register_no,emp_no,trans_no,card_no,
                upc,description,trans_type,trans_subtype,sum(itemQtty)as itemqtty,
                discounttype,volume,
                trans_status,
                case when voided=1 then 0 else voided end as voided,
                department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
                scale,
                sum(unitprice) as unitprice, 
                CAST(sum(total) AS decimal(10,2)) as total,
                sum(regPrice) as regPrice,tax,foodstamp,
                case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
            from localtranstoday
            where description not like '** YOU SAVED %' and trans_status = 'M'
            AND datetime >= CURRENT_DATE
            group by register_no,emp_no,trans_no,card_no,
                upc,description,trans_type,trans_subtype,discounttype,volume,
                trans_status,
                department,scale,case when voided=1 then 0 else voided end,
                matched,tax,foodstamp,
                case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

            union all

            select     register_no,emp_no,trans_no,card_no,
                upc,case when numflag=1 then ".$db->concat('description',"'*'",'')." else description end as description,
                trans_type,trans_subtype,sum(itemQtty)as itemqtty,discounttype,volume,
                trans_status,
                case when voided=1 then 0 else voided end as voided,
                department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
                scale,unitprice,CAST(sum(total) AS decimal(10,2)) as total,regPrice,tax,foodstamp,
                case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
            from localtranstoday
            where description not like '** YOU SAVED %' and trans_status !='M'
            AND datetime >= CURRENT_DATE
            AND trans_type <> 'L'
            group by register_no,emp_no,trans_no,card_no,
                upc,description,trans_type,trans_subtype,discounttype,volume,
                trans_status,
                department,scale,case when voided=1 then 0 else voided end,
                unitprice,regPrice,matched,tax,foodstamp,
                case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

            union all

            select     register_no,emp_no,trans_no,card_no,
                upc,
                case when discounttype=1 then
                ".$db->concat("' > you saved \$'",'CAST(CAST(sum(quantity*regprice-quantity*unitprice) AS decimal(10,2)) AS char(20)) COLLATE ' . $mysql_collation,"'  <'",'')."
                when discounttype=2 then
                ".$db->concat("' > you saved \$'",'CAST(CAST(sum(quantity*regprice-quantity*unitprice) AS decimal(10,2)) AS char(20)) COLLATE ' . $mysql_collation,"'  Member Special <'",'')."
                end as description,
                trans_type,'0' as trans_subtype,0 as itemQtty,discounttype,volume,
                'D' as trans_status,
                2 as voided,
                department,0 as quantity,matched,min(trans_id)+1 as trans_id,
                scale,0 as unitprice,
                0 as total,
                0 as regPrice,0 as tax,0 as foodstamp,
                case when trans_status='d' or scale=1 then trans_id else scale end as grouper
            from localtranstoday
            where description not like '** YOU SAVED %' and (discounttype=1 or discounttype=2)
            AND datetime >= CURRENT_DATE
            AND trans_type <> 'L'
            group by register_no,emp_no,trans_no,card_no,
                upc,description,trans_type,trans_subtype,discounttype,volume,
                department,scale,matched,
                case when trans_status='d' or scale=1 then trans_id else scale end
            having CAST(sum(quantity*regprice-quantity*unitprice) AS decimal(10,2))<>0";
        if($type == 'mssql'){
            $rplttG = "CREATE      view rp_ltt_grouped as
            select     register_no,emp_no,trans_no,card_no,
                upc,description,trans_type,trans_subtype,sum(itemQtty)as itemqtty,
                discounttype,volume,
                trans_status,
                case when voided=1 then 0 else voided end as voided,
                department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
                scale,
                sum(unitprice) as unitprice, 
                sum(total) as total,
                sum(regPrice) as regPrice,tax,foodstamp,
                case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
            from localtranstoday
            where description not like '** YOU SAVED %' and trans_status = 'M'
            AND datetime >= CURRENT_DATE
            group by register_no,emp_no,trans_no,card_no,
                upc,description,trans_type,trans_subtype,discounttype,volume,
                trans_status,
                department,scale,case when voided=1 then 0 else voided end,
                matched,tax,foodstamp,
                case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

            union all

            select     register_no,emp_no,trans_no,card_no,
                upc,case when numflag=1 then description+'*' else description end as description,
                trans_type,trans_subtype,sum(itemQtty)as itemqtty,discounttype,volume,
                trans_status,
                case when voided=1 then 0 else voided end as voided,
                department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
                scale,unitprice,sum(total) as total,regPrice,tax,foodstamp,
                case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
            from localtranstoday
            where description not like '** YOU SAVED %' and trans_status !='M'
            AND datetime >= CURRENT_DATE
            AND trans_type <> 'L'
            group by register_no,emp_no,trans_no,card_no,
                upc,description,trans_type,trans_subtype,discounttype,volume,
                trans_status,
                department,scale,case when voided=1 then 0 else voided end,
                unitprice,regPrice,matched,tax,foodstamp,
                case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

            union all

            select     register_no,emp_no,trans_no,card_no,
                upc,
                case when discounttype=1 then
                ' > YOU SAVED $'+convert(varchar(20),convert(money,sum(quantity*regprice-quantity*unitprice)))+'  <'
                when discounttype=2 then
                ' > YOU SAVED $'+convert(varchar(20),convert(money,sum(quantity*regprice-quantity*unitprice)))+'  Member Special <'
                end as description,
                trans_type,'0' as trans_subtype,0 as itemQtty,discounttype,volume,
                'D' as trans_status,
                2 as voided,
                department,0 as quantity,matched,min(trans_id)+1 as trans_id,
                scale,0 as unitprice,
                0 as total,
                0 as regPrice,0 as tax,0 as foodstamp,
                case when trans_status='d' or scale=1 then trans_id else scale end as grouper
            from localtranstoday
            where description not like '** YOU SAVED %' and (discounttype=1 or discounttype=2)
            AND datetime >= CURRENT_DATE
            AND trans_type <> 'L'
            group by register_no,emp_no,trans_no,card_no,
                upc,description,trans_type,trans_subtype,discounttype,volume,
                department,scale,matched,
                case when trans_status='d' or scale=1 then trans_id else scale end
            having convert(money,sum(quantity*regprice-quantity*unitprice))<>0";
        }    
        self::dbStructureModify($db,'rp_ltt_grouped','DROP VIEW rp_ltt_grouped',$errors);
        if(!$db->table_exists('rp_ltt_grouped',$name)){
            self::dbStructureModify($db,'rp_ltt_grouped',$rplttG,$errors);
        }

        $rpreorderG = "CREATE    view rp_ltt_receipt_reorder_g as
            select 
            register_no,emp_no,trans_no,card_no,
            l.description as description,
            case 
                when voided = 5 
                    then 'Discount'
                when trans_status = 'M'
                    then 'Mbr special'
                when trans_status = 'S'
                    then 'Staff special'
                when unitPrice = 0.01
                    then ''
                when scale <> 0 and quantity <> 0 
                    then ".$db->concat('CAST(quantity AS char)',"' @ '",'CAST(unitPrice AS char)','')."
                when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
                    then ".$db->concat('CAST(volume AS char)',"' / '",'CAST(unitPrice AS char)','')."
                when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
                    then ".$db->concat('CAST(quantity AS char)',"' @ '",'CAST(volume AS char)',"' /'",'CAST(unitPrice AS char)','')."
                when abs(itemQtty) > 1 and discounttype = 3
                    then ".$db->concat('CAST(ItemQtty AS char)',"' / '",'CAST(unitPrice AS char)','')."
                when abs(itemQtty) > 1
                    then ".$db->concat('CAST(quantity AS char)',"' @ '",'CAST(unitPrice AS char)','')."
                when matched > 0
                    then '1 w/ vol adj'
                else ''
            end
            as comment,
            total,
            case 
                when trans_status = 'V' 
                    then 'VD'
                when trans_status = 'R'
                    then 'RF'
                WHEN (tax = 1 and foodstamp <> 0)
                    THEN 'TF'
                WHEN (tax = 1 and foodstamp = 0)
                    THEN 'T' 
                WHEN (tax > 1 and foodstamp <> 0)
                    THEN ".$db->concat('SUBSTR(t.description,1,1)',"'F'",'')."
                WHEN (tax > 1 and foodstamp = 0)
                    THEN SUBSTR(t.description,1,1)
                when tax = 0 and foodstamp <> 0
                    then 'F'
                when tax = 0 and foodstamp = 0
                    then '' 
            end
            as status,
            trans_type,
            unitPrice,
            voided,
            trans_id + 1000 as sequence,
            department,
            upc,
            trans_subtype
            from rp_ltt_grouped as l
            left join taxrates as t
            on l.tax=t.id
            where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
            AND trans_type <> 'L'
            and not (trans_status='M' and total=CAST('0.00' AS decimal))

            union all

            select
            0 as register_no, 0 as emp_no,0 as trans_no,0 as card_no,
            '  ' as description,
            ' ' as comment,
            0 as total,
            ' ' as Status,
            ' ' as trans_type,
            0 as unitPrice,
            0 as voided,
            999 as sequence,
            '' as department,
            '' as upc,
            '' as trans_subtype";
        if($type == 'mssql'){
            $rpreorderG = "CREATE     view rp_ltt_receipt_reorder_g as
            select top 100 percent
            register_no,emp_no,trans_no,card_no,
            l.description,
            case 
                when voided = 5 
                    then 'Discount'
                when trans_status = 'M'
                    then 'Mbr special'
                when trans_status = 'S'
                    then 'Staff special'
                when unitPrice = 0.01
                    then ''
                when scale <> 0 and quantity <> 0 
                    then convert(varchar, quantity) + ' @ ' + convert(varchar, unitPrice)
                when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
                    then convert(varchar, volume) + ' /' + convert(varchar, unitPrice)
                when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
                    then convert(varchar, Quantity) + ' @ ' + convert(varchar, Volume) + ' /' + convert(varchar, unitPrice)
                when abs(itemQtty) > 1 and discounttype = 3
                    then convert(varchar,ItemQtty) + ' /' + convert(varchar, UnitPrice)
                when abs(itemQtty) > 1
                    then convert(varchar, quantity) + ' @ ' + convert(varchar, unitPrice)    
                when matched > 0
                    then '1 w/ vol adj'
                else ''
            end
            as comment,
            total,
            case 
                when trans_status = 'V' 
                    then 'VD'
                when trans_status = 'R'
                    then 'RF'
                WHEN (tax = 1 and foodstamp <> 0)
                    THEN 'TF'
                WHEN (tax = 1 and foodstamp = 0)
                    THEN 'T' 
                WHEN (tax > 1 and foodstamp <> 0)
                    THEN LEFT(t.description,1)+'F'
                WHEN (tax > 1 and foodstamp = 0)
                    THEN LEFT(t.description,1)
                when tax = 0 and foodstamp <> 0
                    then 'F'
                when tax = 0 and foodstamp = 0
                    then '' 
            end
            as Status,
            trans_type,
            unitPrice,
            voided,
            trans_id + 1000 as sequence,
            department,
            upc,
            trans_subtype
            from rp_ltt_grouped as l
            left join taxrates as t
            on l.tax=t.id
            where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
            AND trans_type <> 'L'
            and not (trans_status='M' and total=convert(money,'0.00'))

            union all

            select
            0 as register_no, 0 as emp_no,0 as trans_no,0 as card_no,
            '  ' as description,
            ' ' as comment,
            0 as total,
            ' ' as Status,
            ' ' as trans_type,
            0 as unitPrice,
            0 as voided,
            999 as sequence,
            '' as department,
            '' as upc,
            '' as trans_subtype";
        }    
        self::dbStructureModify($db,'rp_ltt_receipt_reorder_g','DROP VIEW rp_ltt_receipt_reorder_g',$errors);
        if(!$db->table_exists("rp_ltt_receipt_reorder_g",$name)){
            self::dbStructureModify($db,'rp_ltt_receipt_reorder_g',$rpreorderG,$errors);
        }
        
        $rpG = "CREATE    view rp_receipt_reorder_g as
            select 
            register_no,emp_no,trans_no,card_no,
            case 
                when trans_type = 'T' 
                    then     
                        case when trans_subtype = 'CP' and upc<>'0'
                        then    ".$db->concat(
                            "SUBSTR(".$db->concat('description','space(30)','').",1,30)",
                            "' '",
                            "SUBSTR(".$db->concat('comment','space(12)','').",1,12)",
                            "right(".$db->concat('space(8)','CAST(total AS char)','').",8)",
                            "right(".$db->concat('space(4)','status','').",4)",'')." 
                        else     ".$db->concat( 
                            "right(".$db->concat('space(44)','upper(description)','').",44)", 
                            "right(".$db->concat('space(8)','CAST((-1*total) AS char)','').",8)",
                            "right(".$db->concat('space(4)','status','').",4)",'')." 
                        end 
                when voided = 3 
                    then     ".$db->concat( 
                        "SUBSTR(".$db->concat('description','space(30)','').",1,30)",
                        "space(9)", 
                        "'TOTAL'", 
                        "right(".$db->concat('space(8)','CAST(unitPrice AS char)','').",8)",'')."
                when voided = 2
                    then     description
                when voided = 4
                    then     description
                when voided = 6
                    then     description
                when voided = 7 or voided = 17
                    then     ".$db->concat(
                        "SUBSTR(".$db->concat('description','space(30)','').",1,30)",
                        "space(14)", 
                        "right(".$db->concat('space(8)','CAST(unitPrice AS char)','').",8)",
                        "right(".$db->concat('space(4)','status','').",4)",'')." 
                when sequence < 1000
                    then     description
                else
                    ".$db->concat(
                        "SUBSTR(".$db->concat('description','space(30)','').",1,30)",
                        "' '",
                        "SUBSTR(".$db->concat('comment','space(12)','').",1,12)",
                        "right(".$db->concat('space(8)','CAST(total AS char)','').",8)",
                        "right(".$db->concat('space(4)','status','').",4)",'')." 
            end
            as linetoprint,
            sequence,
            department,
            super_name as dept_name,
            case when trans_subtype='CM' or voided in (10,17)
                then 'CM' else trans_type
            end
            as trans_type,
            upc

            from rp_ltt_receipt_reorder_g r
            left outer join ".CoreLocal::get('pDatabase').".MasterSuperDepts d 
            on r.department=d.dept_ID
            where r.total<>0 or r.unitPrice=0
            order by register_no,emp_no,trans_no,card_no,sequence";
        if($type == 'mssql'){
            $rpG = "CREATE     view rp_receipt_reorder_g as
            select top 100 percent
            register_no,emp_no,trans_no,card_no,
            case 
                when trans_type = 'T' 
                    then     
                        case when trans_subtype = 'CP' and upc<>'0'
                        then    left(Description + space(30), 30)
                            + ' ' 
                            + left(Comment + space(12), 12) 
                            + right(space(8) + convert(varchar, Total), 8) 
                            + right(space(4) + status, 4) 
                        else     right((space(44) + upper(rtrim(Description))), 44) 
                            + right((space(8) + convert(varchar, (-1 * Total))), 8) 
                            + right((space(4) + status), 4) 
                        end 
                when voided = 3 
                    then     left(Description + space(30), 30) 
                        + space(9) 
                        + 'TOTAL' 
                        + right(space(8) + convert(varchar, UnitPrice), 8)
                when voided = 2
                    then     description
                when voided = 4
                    then     description
                when voided = 6
                    then     description
                when voided = 7 or voided = 17
                    then     left(Description + space(30), 30) 
                        + space(14) 
                        + right(space(8) + convert(varchar, UnitPrice), 8) 
                        + right(space(4) + status, 4)
                when sequence < 1000
                    then     description
                else
                    left(Description + space(30), 30)
                    + ' ' 
                    + left(Comment + space(12), 12) 
                    + right(space(8) + convert(varchar, Total), 8) 
                    + right(space(4) + status, 4)
            end
            as linetoprint,
            sequence,
            department,
            dept_name,
            case when trans_subtype='CM' or voided in (10,17)
                then 'CM' else trans_type
            end
            as trans_type,
            upc

            from rp_ltt_receipt_reorder_g r
            left outer join ".CoreLocal::get('pDatabase').".dbo.MasterSuperDepts d 
            on r.department=d.dept_ID
            where r.total<>0 or r.unitprice=0
            order by register_no,emp_no,trans_no,card_no,sequence";
        }
        elseif($type == 'pdolite'){
            $rpG = str_replace('right(','str_right(',$rpG);
        }
        if(!$db->table_exists('rp_receipt_reorder_g',$name)){
            self::dbStructureModify($db,'rp_receipt_reorder_g',$rpG,$errors);
        }

        $rpunionsG = "CREATE     view rp_receipt_reorder_unions_g as
            select linetoprint,
            emp_no,register_no,trans_no,
            sequence,dept_name,1 as ordered,upc
            from rp_receipt_reorder_g
            where (department<>0 or trans_type='CM')
            and linetoprint not like 'member discount%'

            union all

            select replace(replace(r1.linetoprint,'** T',' = T'),' **',' = ') as linetoprint,
            r1.emp_no,r1.register_no,r1.trans_no,
            r1.sequence,r2.dept_name,1 as ordered,r2.upc
            from rp_receipt_reorder_g r1 join rp_receipt_reorder_g r2 on r1.sequence+1=r2.sequence
            and r1.register_no=r2.register_no and r1.emp_no=r2.emp_no and r1.trans_no=r2.trans_no
            where r1.linetoprint like '** T%' and r2.dept_name is not null and r1.linetoprint<>'** Tare Weight 0 **'

            union all

            select
            ".$db->concat(
            "SUBSTR(".$db->concat("'** '","trim(CAST(percentDiscount AS char))","'% Discount Applied **'",'space(30)','').",1,30)",
            "' '", 
            "space(13)",
            "right(".$db->concat('space(8)',"CAST((-1*transDiscount) AS char)",'').",8)",
            "space(4)",'')." as linetoprint,
            emp_no,register_no,trans_no,
            0 as sequence,null as dept_name,2 as ordered,
            '' as upc
            from rp_subtotals
            where percentDiscount<>0

            union all

            select linetoprint,
            emp_no,register_no,trans_no,
            sequence,null as dept_name,2 as ordered,upc
            from rp_receipt_reorder_g
            where linetoprint like 'member discount%'

            union all

            select 
            ".$db->concat(
            "right(".$db->concat('space(44)',"'SUBTOTAL'",'').",44)",
            "right(".$db->concat('space(8)',"CAST(round(l.runningTotal-s.taxTotal-l.tenderTotal,2) AS char)",'').",8)",
            'space(4)','')." as linetoprint,
            l.emp_no,l.register_no,l.trans_no,
            1 as sequence,null as dept_name,3 as ordered,'' as upc
            from rp_lttsummary as l, rp_subtotals as s
            WHERE l.emp_no = s.emp_no and
            l.register_no = s.register_no and
            l.trans_no = s.trans_no

            union all

            select 
            ".$db->concat(
            "right(".$db->concat('space(44)',"'TAX'",'').",44)",
            "right(".$db->concat('space(8)',"CAST(round(taxTotal,2) AS char)",'').",8)", 
            "space(4)",'')." as linetoprint,
            emp_no,register_no,trans_no,
            2 as sequence,null as dept_name,3 as ordered,'' as upc
            from rp_subtotals

            union all

            select 
            ".$db->concat(
            "right(".$db->concat('space(44)',"'TOTAL'",'').",44)",
            "right(".$db->concat('space(8)',"CAST(runningTotal-tenderTotal AS char)",'').",8)", 
            'space(4)','')." as linetoprint,
            emp_no,register_no,trans_no,
            3 as sequence,null as dept_name,3 as ordered,'' as upc
            from rp_lttsummary

            union all

            select linetoprint,
            emp_no,register_no,trans_no,
            sequence,dept_name,4 as ordered,upc
            from rp_receipt_reorder_g
            where (trans_type='T' and department = 0)
            or (department = 0 and linetoprint like '%Coupon%')

            union all

            select 
            ".$db->concat(
            "right(".$db->concat('space(44)',"'CURRENT AMOUNT DUE'",'').",44)",
            "right(".$db->concat('space(8)',"CAST(runningTotal-transDiscount AS char)",'').",8)", 
            "space(4)",'')." as linetoprint,
            emp_no,register_no,trans_no,
            5 as sequence,
            null as dept_name,
            5 as ordered,'' as upc
            from rp_subtotals where runningTotal <> 0 ";
        if($type == 'mssql'){
            $rpunionsG = "CREATE view rp_receipt_reorder_unions_g as
            select linetoprint,
            emp_no,register_no,trans_no,
            sequence,dept_name,1 as ordered,upc
            from rp_receipt_reorder_g
            where (department<>0 or trans_type='CM')
            and linetoprint not like 'member discount%'

            union all

            select replace(replace(r1.linetoprint,'** T',' = T'),' **',' = ') as linetoprint,
            r1.emp_no,r1.register_no,r1.trans_no,
            r1.[sequence],r2.dept_name,1 as ordered,r2.upc
            from rp_receipt_reorder_g r1 join rp_receipt_reorder_g r2 on r1.[sequence]+1=r2.[sequence]
            and r1.emp_no=r2.emp_no and r1.register_no=r2.register_no and r1.trans_no=r2.trans_no
            where r1.linetoprint like '** T%' and r2.dept_name is not null and r1.linetoprint<>'** Tare Weight 0 **'

            union all

            select
            left('** '+rtrim(convert(char,percentdiscount))+'% Discount Applied **' + space(30), 30)
            + ' ' 
            + left('' + space(13), 13) 
            + right(space(8) + convert(varchar, (-1*transDiscount)), 8) 
            + right(space(4) + '', 4),
            emp_no,register_no,trans_no,
            0 as sequence,null as dept_name,2 as ordered,
            '' as upc
            from rp_subtotals
            where percentdiscount<>0

            union all

            select linetoprint,
            emp_no,register_no,trans_no,
            sequence,null as dept_name,2 as ordered,upc
            from rp_receipt_reorder_g
            where linetoprint like 'member discount%'

            union all

            select 
            right((space(44) + upper(rtrim('SUBTOTAL'))), 44) 
            + right((space(8) + convert(varchar,l.runningTotal-s.taxTotal-l.tenderTotal)),8)
            + right((space(4) + ''), 4) as linetoprint,
            l.emp_no,l.register_no,l.trans_no,
            1 as sequence,null as dept_name,3 as ordered,'' as upc
            from rp_lttsummary as l, rp_subtotals as s
            WHERE l.emp_no = s.emp_no and
            l.register_no = s.register_no and
            l.trans_no = s.trans_no

            union all

            select 
            right((space(44) + upper(rtrim('TAX'))), 44) 
            + right((space(8) + convert(varchar,taxtotal)), 8) 
            + right((space(4) + ''), 4) as linetoprint,
            emp_no,register_no,trans_no,
            2 as sequence,null as dept_name,3 as ordered,'' as upc
            from rp_subtotals

            union all

            select 
            right((space(44) + upper(rtrim('TOTAL'))), 44) 
            + right((space(8) +convert(varchar,runningtotal-tendertotal)),8)
            + right((space(4) + ''), 4) as linetoprint,
            emp_no,register_no,trans_no,
            3 as sequence,null as dept_name,3 as ordered,'' as upc
            from rp_lttsummary

            union all

            select linetoprint,
            emp_no,register_no,trans_no,
            sequence,dept_name,4 as ordered,upc
            from rp_receipt_reorder_g
            where (trans_type='T' and department = 0)
            or (department = 0 and linetoprint like '%Coupon%')

            union all

            select 
            right((space(44) + upper(rtrim('Current Amount Due'))), 44) 
            +right((space(8) + convert(varchar,subtotal)),8)
            + right((space(4) + ''), 4) as linetoprint,
            emp_no,register_no,trans_no,
            5 as sequence,
            null as dept_name,
            5 as ordered,'' as upc
            from rp_subtotals where runningtotal <> 0"; 
        }
        elseif($type == 'pdolite'){
            $rpunionsG = str_replace('right(','str_right(',$rpunionsG);
        }
        if(!$db->table_exists('rp_receipt_reorder_unions_g',$name)){
            self::dbStructureModify($db,'rp_receipt_reorder_unions_g',$rpunionsG,$errors);
        }

        return $errors;
    }
}


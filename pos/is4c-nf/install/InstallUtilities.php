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
        global $CORE_LOCAL;
        // do nothing if page isn't a form submit (i.e. user didn't press save)
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }
        // lane #0 is the server config editor
        // no ini.php file to write values to
        if ($CORE_LOCAL->get('laneno') == 0) {
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
            if ($written_global && $key != 'laneno') {
                self::confToDb($key, $value);
            }
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
            if ($added_global && $key != 'laneno') {
                self::confToDb($key, $value);
            }
            return true;	// successfully appended somewhere relevant
        }

        return false;	// didn't manage to write anywhere!
    }

    static public function confExists($key, $local=False)
    {
        $path_global = '../ini.php';
        $path_local = '../ini-local.php';

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
        global $CORE_LOCAL;
        $sql = self::dbTestConnect($CORE_LOCAL->get('localhost'),
                $CORE_LOCAL->get('DBMS'),
                $CORE_LOCAL->get('pDatabase'),
                $CORE_LOCAL->get('localUser'),
                $CORE_LOCAL->get('localPass'));

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

        /** temp
        if ($sql !== false) {
            $prep = $sql->prepare_statement('SELECT param_value FROM parameters
                                        WHERE param_key=? AND lane_id=?');
            $exists = $sql->exec_statement($prep, array($key, $CORE_LOCAL->get('laneno')));
            if ($sql->num_rows($exists)) {
                $prep = $sql->prepare_statement('UPDATE parameters SET param_value=?,
                                        is_array=? WHERE param_key=? AND lane_id=?');
                $sql->exec_statement($prep, array($value, $save_as_array, $key, $CORE_LOCAL->get('laneno')));
            } else {
                $prep = $sql->prepare_statement('INSERT INTO parameters (store_id, lane_id, param_key,
                                        param_value, is_array) VALUES (0, ?, ?, ?, ?)');
                $sql->exec_statement($prep, array($CORE_LOCAL->get('laneno'), $key, $value, $save_as_array));
            }
        }
        */

        // maintain ini.php value too
        if (True || self::confExists($key)) {
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
    }

    /**
      Save value to opdata.lane_config
      @param $key string key
      @param $value string value

      Called automatically by InstallUtilities::confsave().
    */
    static public function confToDb($key, $value)
    {
        global $CORE_LOCAL;
        $sql = self::dbTestConnect($CORE_LOCAL->get('localhost'),
                $CORE_LOCAL->get('DBMS'),
                $CORE_LOCAL->get('pDatabase'),
                $CORE_LOCAL->get('localUser'),
                $CORE_LOCAL->get('localPass'));
        if ($sql !== False){
            $q = $sql->prepare_statement("SELECT value FROM lane_config 
                WHERE keycode=?");
            $r = $sql->exec_statement($q, array($key));
            if ($r === False) return False; // old table format
            if ($sql->num_rows($r) == 0){
                $ins = $sql->prepare_statement('INSERT INTO lane_config
                    (keycode, value) VALUES (?, ?)');
                $sql->exec_statement($ins, array($key, $value));
            }
            else {
                $up = $sql->prepare_statement('UPDATE lane_config SET
                    value=? WHERE keycode=?');
                $sql->exec_statement($up, array($value, $key));
            }
        }
    }

    /**
      Rewrite ini.php with values from
      opdata.lane_config
    */
    static public function writeConfFromDb()
    {
        global $CORE_LOCAL;
        $sql = self::dbTestConnect($CORE_LOCAL->get('localhost'),
                $CORE_LOCAL->get('DBMS'),
                $CORE_LOCAL->get('pDatabase'),
                $CORE_LOCAL->get('localUser'),
                $CORE_LOCAL->get('localPass'));
        if ($sql !== false) {
            $q = 'SELECT keycode, value FROM lane_config';
            $r = $sql->query($q);
            while($w = $db->fetch_row($r)) {
                InstallUtilities::confsave($w['keycode'], $w['value']);
            }
        }
    }

    /**
      Copy values from opdata.lane_config to the
      server's lane_config table.
    */
    static public function sendConfToServer()
    {
        global $CORE_LOCAL;
        $sql = self::dbTestConnect($CORE_LOCAL->get('localhost'),
                $CORE_LOCAL->get('DBMS'),
                $CORE_LOCAL->get('pDatabase'),
                $CORE_LOCAL->get('localUser'),
                $CORE_LOCAL->get('localPass'));
        $mine = array();
        if ($sql !== false) {
            $q = 'SELECT keycode, value FROM lane_config';
            $r = $sql->query($q);
            while($w = $sql->fetch_row($r)) {
                $mine[$w['keycode']] = $w['value'];
            }
        }

        $sql = self::dbTestConnect($CORE_LOCAL->get('mServer'),
                $CORE_LOCAL->get('mDBMS'),
                $CORE_LOCAL->get('mDatabase'),
                $CORE_LOCAL->get('mUser'),
                $CORE_LOCAL->get('mPass'));
        if ($sql !== false) {
            $chk = $sql->prepare_statement('SELECT value FROM
                    lane_config WHERE keycode=?');
            $ins = $sql->prepare_statement('INSERT INTO lane_config
                    (keycode, value) VALUES (?, ?)');
            $up = $sql->prepare_statement('UPDATE lane_config SET
                    value=? WHERE keycode=?');
            foreach($mine as $key => $value) {
                $exists = $sql->exec_statement($chk, array($key));
                if ($sql->num_rows($exists) == 0) {
                    $sql->exec_statement($ins, array($key, $value));
                } else {
                    $sql->exec_statement($up, array($value, $key));
                }
            }
        }
    }

    /**
      Fetch values from the server's lane_config table
      Write them to ini.php and opdata.lane_config.
    */
    static public function getConfFromServer()
    {
        global $CORE_LOCAL;
        $sql = self::dbTestConnect($CORE_LOCAL->get('mServer'),
                $CORE_LOCAL->get('mDBMS'),
                $CORE_LOCAL->get('mDatabase'),
                $CORE_LOCAL->get('mUser'),
                $CORE_LOCAL->get('mPass'));
        $theirs = array();
        if ($sql !== false) {
            $q = 'SELECT keycode, value FROM lane_config';
            $r = $sql->query($q);
            while($w = $sql->fetch_row($r)) {
                $theirs[$w['keycode']] = $w['value'];
            }
        }

        foreach($theirs as $key => $value){
            InstallUtilities::confsave($key, $value);
        }
    }

    static public function loadSampleData($sql, $table)
    {
        $success = true; 
        $loaded = 0;
        echo "Loading `$table` ";
        if (file_exists("data/$table.sql")) {
            echo "from data/$table.sql<br>\n";
            $fp = fopen("data/$table.sql","r");
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
        } else if (file_exists("data/$table.csv")) {
            echo "from data/$table.csv ";
            $path = realpath("data/$table.csv");
            $query = "LOAD DATA LOCAL INFILE
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
                $fp = fopen("data/$table.csv",'r');
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

        foreach($queries as $query) {
            ob_start();
            $try = @$sql->query($query);
            ob_end_clean();
            if ($try === false){
                if (stristr($query, "DROP ") && stristr($query,"VIEW ")) {
                    /* suppress unimportant errors
                    $errors[] = array(
                    'struct' => $struct_name,
                    'query' => $query,
                    'important' => False
                    );
                    */
                } else {
                    $errors[] = array(
                    'struct'=>$struct_name,
                    'query'=>$query,
                    'details'=>$sql->error(),
                    'important'=>True
                    );
                }
            }
        }

        return $errors;
    }

    public static function normalizeDbName($name)
    {
        global $CORE_LOCAL;
        if ($name == 'op') {
            return $CORE_LOCAL->get('pDatabase');
        } else if ($name == 'trans') {
            return $CORE_LOCAL->get('tDatabase');
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
        global $CORE_LOCAL;
        $current_value = $CORE_LOCAL->get($name);
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

        $CORE_LOCAL->set($name, $current_value);
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
        
        $style = isset($attributes['style']) ? $attributes['style'] : '';
        if ($storage == self::INI_SETTING) {
            $style .= 'background-color: #ffff66';
        } elseif (self::confExists($name)) {
            $style .= 'background-color: #66ff33';
        } else {
            $style .= 'background-color: #3399ff';
        }
        $attributes['style'] = $style;

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
        global $CORE_LOCAL;
        $current_value = $CORE_LOCAL->get($name);
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
        
        $CORE_LOCAL->set($name, $current_value);
        if ($storage == self::INI_SETTING) {
            if (!is_numeric($current_value) && strtolower($current_value) !== 'true' && strtolower($current_value !== 'false')) {
                self::confsave($name, "'" . $current_value . "'");
            } else {
                self::confsave($name, $current_value);
            }
        } else {
            self::paramSave($name, $current_value);
        }

        $style = isset($attributes['style']) ? $attributes['style'] : '';
        if ($storage == self::INI_SETTING) {
            $style .= 'background-color: #ffff66;';
        } elseif (self::confExists($name)) {
            $style .= 'background-color: #66ff33;';
        } else {
            $style .= 'background-color: #3399ff;';
        }
        $attributes['style'] = $style;

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
        global $CORE_LOCAL;
        $current_value = $CORE_LOCAL->get($name);
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

        $CORE_LOCAL->set($name, $current_value);
        if ($storage == self::INI_SETTING) {
            if (!is_numeric($current_value) && strtolower($current_value) !== 'true' && strtolower($current_value !== 'false')) {
                self::confsave($name, "'" . $current_value . "'");
            } else {
                self::confsave($name, $current_value);
            }
        } else {
            self::paramSave($name, $current_value);
        }

        $color = '';
        if ($storage == self::INI_SETTING) {
            $color .= '#ffff66';
        } elseif (self::confExists($name)) {
            $color .= '#66ff33';
        } else {
            $color .= '#3399ff';
        }

        $ret = '<fieldset class="toggle">' . "\n";
        $ret .= sprintf('<input type="checkbox" name="%s" id="%s" value="%s" %s />',
                    $name, $name, $choices[1],
                    ($current_value == $choices[1] ? 'checked' : '')
        );
        $ret .= "\n";
        $ret .= sprintf('<label for="%s" onclick="">%s: </label>', $name, $label);
        $ret .= "\n";
        $ret .= '<span class="toggle-button" style="border: solid 3px black; background:' . $color . ';"></span></fieldset>' . "\n";

        return $ret;
    }

}


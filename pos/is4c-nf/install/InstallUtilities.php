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

    static private function initWritableFile($filename, $template)
    {

        $fptr = fopen($filename,'w');
        if ($fptr) {
            if ($template !== False) {
                switch($template) {
                    case 'PHP':
                        fwrite($fptr,"<?php\n");
                        break;
                    case 'JSON':
                        fwrite($fptr, CoreLocal::convertIniPhpToJson());
                        break;
                }
            }
            fclose($fptr);
        }
    }

    static public function checkWritable($filename, $optional=False, $template=False)
    {
        $basename = basename($filename);
        $failure = ($optional) ? 'blue' : 'red';
        $status = ($optional) ? 'Optional' : 'Warning';

        if (!file_exists($filename) && !$optional) {
            self::initWritableFile($filename, $template);
        }

        $real_file = realpath(dirname($filename)) . DIRECTORY_SEPARATOR . $basename;

        if (!file_exists($filename)) {
            echo "<span style=\"color:$failure;\"><b>$status</b>: $basename does not exist</span><br />";
            if (!$optional) {
                echo "<b>Advice</b>: <div style=\"font-face:mono;background:#ccc;padding:8px;\">
                    touch \"". $real_file ."\"<br />
                    chown ".self::whoami()." \"". $real_file ."\"</div>";
            }
        } elseif (is_writable($filename)) {
            echo "<span style=\"color:green;\">$basename is writeable</span><br />";
        } else {
            echo "<span style=\"color:red;\"><b>Warning</b>: $basename is not writeable</span><br />";
            echo "<b>Advice</b>: <div style=\"font-face:mono;background:#ccc;padding:8px;\">
                chown ".self::whoami()." \"". $real_file ."\"<br />
                chmod 600 \"". $real_file ."\"</div>";
        }
    }

    /**
      Save entry to config file(s)
      @param $key string key
      @param $value string value
      @param $prefer_local use ini-local if it exists
      @return boolean success

      Values are written to a file and must be valid
      PHP code. For example, a PHP string should include
      single or double quote delimiters in $value.
    */
    static public function confsave($key, $value, $prefer_local=false)
    {
        // lane #0 is the server config editor
        // no ini.php file to write values to
        if (CoreLocal::get('laneno') == 0) {
            return null;
        }

        $ini_php = dirname(__FILE__) . '/../ini.php';
        $ini_json = dirname(__FILE__) . '/../ini.json';
        $save_php = null;
        $save_json = null;
        if (file_exists($ini_php)) {
            $save_php = self::phpConfSave($key, $value, $prefer_local);
        }
        if (file_exists($ini_json)) {
            $save_json = self::jsonConfSave($key, $value);
        }

        if ($save_php === false || $save_json === false) {
            // error occurred saving
            return false;
        } elseif ($save_php === null || $save_json === null) {
            // neither config file found
            return false;
        } else {
            return true;
        }
    }

    static private function addOpenTag($str)
    {
        if (strstr($str,'<?php') === false) {
            $str = "<?php\n" . $str;
        }
        return $str;
    }

    static private function rewritePhpSetting($key, $value, $content, $file)
    {
        $orig_setting = '|\$CORE_LOCAL->set\([\'"]'.$key.'[\'"],\s*(.+)\);[\r\n]|';
        $new_setting = "\$CORE_LOCAL->set('{$key}',{$value}, True);\n";
        $found = false;
        $new = preg_replace($orig_setting, $new_setting, $content,
                    -1, $found);

        if ($found) {
            preg_match($orig_setting, $content, $matches);
            if ($matches[1] === $value.', True') {// found with exact same value
                return true;    // no need to bother rewriting it
            } elseif (is_writable($file)) {
                return file_put_contents($file, $new) ? true : false;
            }
        } else {
            return false;
        }
    }

    static private function addPhpSetting($key, $value, $content, $file)
    {
        $new_setting = "\$CORE_LOCAL->set('{$key}',{$value}, True);\n";
        $found = false;
        $new = preg_replace("|(\?>)?\s*$|", $new_setting.'?>', $content,
                        1, $found);
        if (preg_match('/^<\?php[^\s]/', $new)) {
            $new = preg_replace('/^<\?php/', "<?php\n", $new);
        }

        return file_put_contents($file, $new) ? true : false;
    }

    /**
      Save entry to ini.php or ini-local.php.
      This is called by confsave() if ini.php exists.
      Should not be called directly
      @param $key string key
      @param $value string value
      @param $prefer_local use ini-local if it exists
      @return boolean success
    */
    static private function phpConfSave($key, $value, $prefer_local=false)
    {
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

        $orig_global = file_get_contents($path_global);
        $orig_global = self::addOpenTag($orig_global);
        $orig_local = $writeable_local ? file_get_contents($path_local) : '';
        $orig_local = self::addOpenTag($orig_local);

        $written_global = self::rewritePhpSetting($key, $value, $orig_global, $path_global);
        $written_local = self::rewritePhpSetting($key, $value, $orig_local, $path_local);

        if ($written_global || $written_local) {
            return true;    // successfully written somewhere relevant
        }

        if (!$writeable_local || !$prefer_local) {
            return self::addPhpSetting($key, $value, $orig_global, $path_global);
        } else {
            return self::addPhpSetting($key, $value, $orig_local, $path_local);
        }
    }

    /**
      Save entry to ini.json
      This is called by confsave() if ini.json exists.
      Should not be called directly
      @param $key string key
      @param $value string value
      @return boolean success
    */
    static public function jsonConfSave($key, $value)
    {
        $ini_json = dirname(__FILE__) . '/../ini.json';
        if (!is_writable($ini_json)) {
            return false;
        }

        $json = json_decode(file_get_contents($ini_json), true);
        if (!is_array($json)) {
            if (trim(file_get_contents($ini_json)) === '') {
                $json = array();
            } else {
                return false;
            }
        }

        /**
          ini.php expects string delimiters. Take them
          off if present.
        */
        if (strlen($value) >= 2 && substr($value, 0, 1) == "'" && substr($value, -1) == "'") {
            $value = substr($value, 1, strlen($value)-2);
        }

        $json[$key] = $value;
        $saved = file_put_contents($ini_json, JsonLib::prettyJSON(json_encode($json)));

        return ($saved === false) ? false : true;
    }

    /**
      Remove a value from config file(s)
      @param $key string key
      @param $local boolean optional default false
        remove from ini-local.php if applicable
      @return boolean success
    */
    static public function confRemove($key, $local=false)
    {
        $ini_php = dirname(__FILE__) . '/../ini.php';
        $ini_json = dirname(__FILE__) . '/../ini.json';
        $save_php = null;
        $save_json = null;
        if (file_exists($ini_php)) {
            $save_php = self::phpConfRemove($key, $local);
        }
        if (file_exists($ini_json)) {
            $save_json = self::jsonConfRemove($key);
        }
        
        if ($save_php === false || $save_json === false) {
            // error occurred saving
            return false;
        } elseif ($save_php === null || $save_json === null) {
            // neither config file found
            return false;
        } else {
            return true;
        }
    }

    /**
      Remove a value from ini.php or ini-local.php
      Called by confRemove() if ini.php exists.
      Should not be called directly.
      @param $key string key
      @param $local boolean optional default false
        remove from ini-local.php if applicable
      @return boolean success
    */
    static private function phpConfRemove($key, $local=false)
    {
        $path_global = dirname(__FILE__) . '/../ini.php';
        $path_local = dirname(__FILE__) .  '/../ini-local.php';
        $ret = false;

        $orig_setting = '|\$CORE_LOCAL->set\([\'"]'.$key.'[\'"],\s*(.+)\);[\r\n]|';
        $file = $local ? $path_local : $path_global;
        $current_conf = file_get_contents($file);
        if (preg_match($orig_setting, $current_conf) == 1) {
            $removed = preg_replace($orig_setting, '', $current_conf);
            $ret = file_put_contents($file, $removed);
        }

        return ($ret === false) ? false : true;
    }

    /**
      Remove a value from ini.json
      Called by confRemove() if ini.json exists.
      Should not be called directly.
      @param $key string key
      @return boolean success
    */
    static private function jsonConfRemove($key)
    {
        $ini_json = dirname(__FILE__) . '/../ini.json';
        if (!is_writable($ini_json)) {
            return false;
        }

        $json = json_decode(file_get_contents($ini_json), true);
        if (!is_array($json)) {
            return false;
        }

        unset($json[$key]);
        $saved = file_put_contents($ini_json, JsonLib::prettyJSON(json_encode($json)));

        return ($saved === false) ? false : true;
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

    static private function paramValueToArray($value)
    {
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

        return array($value, $save_as_array);
    }

    static private function paramValueToPhp($value, $save_as_array)
    {
        // tweak value for safe output to ini.php
        if ($save_as_array === 1 && $value !== '') {
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
        } elseif ($save_as_array === 1 && $value === '') {
            $value = 'array()';
        } elseif (strtoupper($value) === 'TRUE'){
            $value = 'True';
        } elseif (strtoupper($value) === 'FALSE'){
            $value = 'False';
        } elseif (!is_numeric($value) || (strlen($value)>1 && substr($value,0,1) == '0')) {
            $value = "'".$value."'";
        }

        return $value;
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

        list($value, $save_as_array) = self::paramValueToArray($value);

        $saved = false;
        if ($sql !== false) {
            $prep = $sql->prepare('SELECT param_value FROM parameters
                                        WHERE param_key=? AND lane_id=?');
            $exists = $sql->execute($prep, array($key, CoreLocal::get('laneno')));
            if ($sql->num_rows($exists)) {
                $prep = $sql->prepare('
                    UPDATE parameters 
                    SET param_value=?,
                        is_array=?,
                        store_id=0
                    WHERE param_key=? 
                        AND lane_id=?');
                $saved = $sql->execute($prep, array($value, $save_as_array, $key, CoreLocal::get('laneno')));
            } else {
                $prep = $sql->prepare('INSERT INTO parameters (store_id, lane_id, param_key,
                                        param_value, is_array) VALUES (0, ?, ?, ?, ?)');
                $saved = $sql->execute($prep, array(CoreLocal::get('laneno'), $key, $value, $save_as_array));
            }
        }

        // maintain ini.php value too
        if (self::confExists($key)) {
            self::confsave($key, self::paramValueToPhp($value));
        }

        return ($saved) ? true : false;
    }

    static private function loadFromSql($sql, $table)
    {
        $loaded = 0;
        echo "from data/$table.sql<br>\n";
        $fptr = fopen(dirname(__FILE__) . "/data/$table.sql","r");
        $success = true;
        while($line = fgets($fptr)) {
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
        fclose($fptr);
        echo ($success? ' success!' : "<br>\n'$table' load " . ($loaded? 'partial success;' : 'failed;'))
            . " $loaded " . ($loaded == 1? 'record was' : 'records were') . " loaded.<br>\n";

        return $success;
    }

    static private function loadFromCsv($sql, $table, $path)
    {
        $LOCAL = 'LOCAL';
        if (CoreLocal::get('localhost') == '127.0.0.1' || CoreLocal::get('localhost') == 'localhost') {
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
                . (strlen($error)? $error : 'Unknown error')
                . " executing:<br><code>$query</code><br></span><br>\n";
        }

        return $try;
    }

    static private function loadCsvLines($sql, $table, $path)
    {
        $loaded = 0;
        echo "line-by-line<br>\n";
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
                        . (strlen($error)? $error : 'Unknown error')
                        . " preparing:<br><code>$query</code></span><br>\n";
                    break;
                }
            }
            $try = $sql->execute($stmt, $line);
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
        fclose($fptr);
        echo ($success? ' success!' : "<br>\n'$table' load " . ($loaded? 'partial success;' : 'failed;'))
            . " $loaded " . ($loaded == 1? 'record was' : 'records were') . " loaded.<br>\n";

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
        echo "Loading `$table` ";
        if (file_exists(dirname(__FILE__) . "/data/$table.sql")) {
            $success = self::loadFromSql($sql, $table);
        } elseif (file_exists(dirname(__FILE__) . "/data/$table.csv")) {
            echo "from data/$table.csv ";
            $path = realpath(dirname(__FILE__) . "/data/$table.csv");
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
                            echo 'File not found: ' . $path . '<br />';
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
                echo "succeeded!<br>\n";
                $success = true;
            } else {
                $success = self::loadCsvLines($sql, $table, $path);
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
            $sql =  new \COREPOS\pos\lib\SQLManager($host,$type,$db,$user,$pw);
        } catch(Exception $ex) {}

        if ($sql === false || $sql->isConnected($db) === false) {
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
        } elseif ($name == 'trans') {
            return CoreLocal::get('tDatabase');
        } elseif (substr($name, 0, 7) == 'plugin:') {
            $pluginDbKey = substr($name, 7);
            if (CoreLocal::get("$pluginDbKey",'') !== '') {
                return CoreLocal::get("$pluginDbKey");
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    static private function getCurrentValue($name, $default_value, $quoted)
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
            if (is_array($default_value) && !is_array($current_value)) {
                if ($current_value === '') {
                    $current_value = array();
                } elseif ($quoted) {
                    $current_value = preg_split('/[\s,;]+/', $current_value); 
                } else {
                    $current_value = preg_split('/\D+/', $current_value); 
                }
            }
        }

        return $current_value;
    }

    static private function storageAttribute($name, $storage)
    {
        if ($storage == self::INI_SETTING) {
            return 'Stored in ini.php';
        } elseif (self::confExists($name)) {
            return 'Stored in ini and DB';
        } else {
            return 'Stored in opdata.parameters';
        }
    }

    static private function attributesToStr($attributes)
    {
        $ret = '';
        foreach ($attributes as $name => $value) {
            if ($name == 'name' || $name == 'value') {
                continue;
            }
            $ret .= ' ' . $name . '="' . $value . '"';
        }

        return $ret;
    }

    static private function writeInput($name, $current_value, $storage)
    {
        if ($storage == self::INI_SETTING) {
            if (!is_numeric($current_value) && strtolower($current_value) !== 'true' && strtolower($current_value !== 'false')) {
                self::confsave($name, "'" . $current_value . "'");
            } else {
                self::confsave($name, $current_value);
            }
        } else {
            self::paramSave($name, $current_value);
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
    static public function installTextField($name, $default_value='', $storage=self::EITHER_SETTING, $quoted=true, $attributes=array(), $area=false)
    {
        $current_value = self::getCurrentValue($name, $default_value, $quoted);

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
            $current_value = self::sanitizeString($current_value);
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
        
        $attributes['title'] = self::storageAttribute($name, $storage);

        if ($area) {
            $ret = sprintf('<textarea name="%s"', $name);
            $ret .= self::attributesToStr($attributes);
            $ret .= '>' . $current_value . '</textarea>';
        } else {
            $ret = sprintf('<input name="%s" value="%s"',
                $name, $current_value);
            if (!isset($attributes['type'])) {
                $attributes['type'] = 'text';
            }
            $ret .= self::attributesToStr($attributes);
            $ret .= " />\n";
        }

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
        $current_value = self::getCurrentValue($name, $default_value, $quoted);

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
        $current_value = self::sanitizeValue($current_value, $is_array, $quoted);
        
        CoreLocal::set($name, $current_value);
        self::writeInput($name, $current_value, $storage);

        $attributes['title'] = self::storageAttribute($name, $storage);

        $ret = '<select name="' . $name . ($is_array ? '[]' : '') . '" ';
        $ret .= self::attributesToStr($attributes);
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

    static public function installCheckboxField($name, $label, $default_value=0, $storage=self::EITHER_SETTING, $choices=array(0, 1), $attributes=array())
    {
        $current_value = self::getCurrentValue($name, $default_value, $quoted);

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
        self::writeInput($name, $current_value, $storage);

        $attributes['title'] = self::storageAttribute($name, $storage);

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

    private static function sanitizeValue($current_value, $is_array, $quoted)
    {
        if (!$is_array && !$quoted) {
            // unquoted must be a number or boolean
            if (!is_numeric($current_value) && strtolower($current_value) !== 'true' && strtolower($current_value) !== 'false') {
                $current_value = (int)$current_value;
            }
        } else if (!$is_array && $quoted) {
            $current_value = self::sanitizeString($current_value);
        } else if ($is_array && !is_array($current_value)) {
            $current_value = $default_value;
        }

        return $current_value;
    }

    private static function sanitizeString($current_value)
    {
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

        return $current_value;
    }

    private static function checkParameter($param, $checked, $wrong)
    {
        $p_value = $param->materializeValue();
        $checked[$param->param_key()] = true;
        $i_value = CoreLocal::get($param->param_key());
        if (isset($checked[$param->param_key()])) {
            // setting has a lane-specific parameters
        } elseif (is_numeric($i_value) && is_numeric($p_value) && $i_value == $p_value) {
            // allow loose comparison on numbers
            // i.e., permit integer 1 equal string '1'
        } elseif ($p_value !== $i_value) {
            printf('<span style="color:red;">Setting mismatch for</span>
                <a href="" onclick="$(this).next().toggle(); return false;">%s</a>
                <span style="display:none;"> parameters says %s, ini.php says %s</span></p>',
                $param->param_key(), print_r($p_value, true), print_r($i_value, true)
            );
            $wrong[$param->param_key()] = $p_value;
        }

        return array($checked, $wrong);
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
        $parameters = new \COREPOS\pos\lib\models\op\ParametersModel($dbc);
        $parameters->store_id(0);
        $parameters->lane_id($CORE_LOCAL->get('laneno'));
        $checked = array();
        $wrong = array();
        foreach ($parameters->find() as $param) {
            list($checked, $wrong) = self::checkParameter($param, $checked, $wrong);
        }

        /**
          Now check global parameters
        */
        $parameters->reset();
        $parameters->store_id(0);
        $parameters->lane_id(0);
        foreach ($parameters->find() as $param) {
            list($checked, $wrong) = self::checkParameter($param, $checked, $wrong);
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

    private static $op_models = array(
        '\\COREPOS\\pos\\lib\\models\\op\\AutoCouponsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\CouponCodesModel',
        '\\COREPOS\\pos\\lib\\models\\op\\CustdataModel',
        '\\COREPOS\\pos\\lib\\models\\op\\CustomerNotificationsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\CustPreferencesModel',
        '\\COREPOS\\pos\\lib\\models\\op\\CustReceiptMessageModel',
        '\\COREPOS\\pos\\lib\\models\\op\\CustomReceiptModel',
        '\\COREPOS\\pos\\lib\\models\\op\\DateRestrictModel',
        '\\COREPOS\\pos\\lib\\models\\op\\DepartmentsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\DisableCouponModel',
        '\\COREPOS\\pos\\lib\\models\\op\\DrawerOwnerModel',
        '\\COREPOS\\pos\\lib\\models\\op\\EmployeesModel',
        '\\COREPOS\\pos\\lib\\models\\op\\GlobalValuesModel',
        '\\COREPOS\\pos\\lib\\models\\op\\HouseCouponsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\HouseCouponItemsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\HouseVirtualCouponsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\IgnoredBarcodesModel',
        '\\COREPOS\\pos\\lib\\models\\op\\MasterSuperDeptsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\MemberCardsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\MemtypeModel',
        '\\COREPOS\\pos\\lib\\models\\op\\ParametersModel',
        '\\COREPOS\\pos\\lib\\models\\op\\ProductsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\ShrinkReasonsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\SpecialDeptMapModel',
        '\\COREPOS\\pos\\lib\\models\\op\\SubDeptsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\TendersModel',
        '\\COREPOS\\pos\\lib\\models\\op\\UnpaidArTodayModel',
        // depends on custdata
        '\\COREPOS\\pos\\lib\\models\\op\\MemberCardsViewModel',
    );

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

        foreach (self::$op_models as $class) {
            $obj = new $class($db);
            $errors[] = $obj->createIfNeeded($name);
        }
        
        $sample_data = array(
            'couponcodes',
            'customReceipt',
            'globalvalues',
            'parameters',
            'tenders',
        );

        foreach ($sample_data as $table) {
            $chk = $db->query('SELECT * FROM ' . $table, $name);
            if ($db->numRows($chk) === 0) {
                $loaded = self::loadSampleData($db, $table, true);
                if (!$loaded) {
                    $errors[] = array(
                        'struct' => $table,
                        'query' => 'None',
                        'details' => 'Failed loading sample data',
                    );
                }
            } else {
                $db->endQuery($chk);
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

    private static $trans_models = array(
        '\\COREPOS\\pos\\lib\\models\\trans\\DTransactionsModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\LocalTransModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\LocalTransArchiveModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\LocalTransTodayModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\LocalTempTransModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\SuspendedModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\TaxRatesModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\CouponAppliedModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\EfsnetRequestModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\EfsnetRequestModModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\EfsnetResponseModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\EfsnetTokensModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\PaycardTransactionsModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\CapturedSignatureModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\EmvReceiptModel',
        // placeholder,
        '__LTT__',
        // Views
        '\\COREPOS\\pos\\lib\\models\\trans\\CcReceiptViewModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\MemDiscountAddModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\MemDiscountRemoveModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\StaffDiscountAddModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\StaffDiscountRemoveModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\ScreenDisplayModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\TaxViewModel',
    );

    /**
      Create translog tables and views
      @param $db [SQLManager] database connection
      @param $name [string] database name
      @return [array] of error messages
    */
    public static function createTransDBs($db, $name)
    {
        $errors = array();
        $type = $db->dbmsName();

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

        foreach (self::$trans_models as $class) {
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
        $lttR = "CREATE view rp_ltt_receipt as 
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
            END AS trans_id,
            l.emp_no,
            l.register_no,
            l.trans_no
            from localtranstoday as l
            left join taxrates as t
            on l.tax = t.id
            where voided <> 5 and UPC <> 'TAX'
            AND trans_type <> 'L'";
        self::dbStructureModify($db,'rp_ltt_receipt','DROP VIEW rp_ltt_receipt',$errors);
        if(!$db->tableExists('rp_ltt_receipt',$name)){
            self::dbStructureModify($db,'rp_ltt_receipt',$lttR,$errors);
        }

        $receiptV = "CREATE VIEW rp_receipt AS
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
            as linetoprint,
            emp_no,
            register_no,
            trans_no,
            trans_id
            from rp_ltt_receipt
            order by trans_id";
        if ($type == 'mssql') {
            $receiptV = "CREATE  VIEW rp_receipt AS
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
            sequence,
            emp_no,
            register_no,
            trans_no,
            trans_id
            from rp_ltt_receipt
            order by sequence";
        } elseif($type == 'pdolite'){
            $receiptV = str_replace('right(','str_right(',$receiptV);
            $receiptV = str_replace('FORMAT(','ROUND(',$receiptV);
        }

        self::dbStructureModify($db,'rp_receipt','DROP VIEW rp_receipt',$errors);
        if(!$db->tableExists('rp_receipt',$name)){
            self::dbStructureModify($db,'rp_receipt',$receiptV,$errors);
        }

        return $errors;
    }

    public static function createMinServer($db, $name)
    {
        $errors = array();
        $type = $db->dbmsName();
        if (CoreLocal::get('laneno') == 0) {
            $errors[] = array(
                'struct' => 'No structures created for lane #0',
                'query' => 'None',
                'details' => 'Zero is reserved for server',
            );

            return $errors;
        }

        $models = array(
            '\COREPOS\pos\lib\models\trans\DTransactionsModel',
            '\COREPOS\pos\lib\models\trans\SuspendedModel',
            '\COREPOS\pos\lib\models\trans\EfsnetRequestModel',
            '\COREPOS\pos\lib\models\trans\EfsnetRequestModModel',
            '\COREPOS\pos\lib\models\trans\EfsnetResponseModel',
            '\COREPOS\pos\lib\models\trans\EfsnetTokensModel',
            '\COREPOS\pos\lib\models\trans\PaycardTransactionsModel',
            '\COREPOS\pos\lib\models\trans\CapturedSignatureModel',
            // Views
            '\COREPOS\pos\lib\models\trans\CcReceiptViewModel',
        );
        foreach ($models as $class) {
            $obj = new $class($db);
            $errors[] = $obj->createIfNeeded($name);
        }

        $errors = self::createDlog($db, $name, $errors);
        $errors = self::createTTG($db, $name, $errors);

        return $errors;
    }

    private static function createDlog($db, $name, $errors)
    {
        $dlogQ = "CREATE VIEW dlog AS
            SELECT datetime AS tdate,
                register_no,
                emp_no,
                trans_no,
                upc,
                CASE 
                    WHEN trans_subtype IN ('CP','IC') OR upc LIKE '%000000052' THEN 'T' 
                    WHEN upc = 'DISCOUNT' THEN 'S' 
                    ELSE trans_type 
                END AS trans_type,
                CASE 
                    WHEN upc = 'MAD Coupon' THEN 'MA' 
                    WHEN upc LIKE '%00000000052' THEN 'RR' 
                    ELSE trans_subtype 
                END AS trans_subtype,
                trans_status,
                department,
                quantity,
                unitPrice,
                total,
                tax,
                foodstamp,
                ItemQtty,
                memType,
                staff,
                numflag,
                charflag,
                card_no,
                trans_id, "
                . $db->concat(
                    $db->convert('emp_no','char'),"'-'",
                    $db->convert('register_no','char'),"'-'",
                    $db->convert('trans_no','char'),
                    '') . " AS trans_num
            FROM dtransactions
            WHERE trans_status NOT IN ('D','X','Z')
                AND emp_no <> 9999 
                AND register_no <> 99";
        if (!$db->table_exists("dlog",$name)) {
            $errors = InstallUtilities::dbStructureModify($db,'dlog',$dlogQ,$errors);
        }

        return $errors;
    }

    private static function createTTG($db, $name, $errors)
    {
        $ttG = "
            CREATE VIEW TenderTapeGeneric AS
            SELECT tdate, 
                emp_no, 
                register_no,
                trans_no,
                CASE 
                    WHEN trans_subtype = 'CP' AND upc LIKE '%MAD%' THEN ''
                    WHEN trans_subtype IN ('EF','EC','TA') THEN 'EF'
                    ELSE trans_subtype
                END AS trans_subtype,
                CASE 
                    WHEN trans_subtype = 'ca' THEN
                        CASE WHEN total >= 0 THEN total ELSE 0 END
                    ELSE -1 * total
            END AS tender
            FROM dlog
            WHERE tdate >= " . $db->curdate() . "
                AND trans_subtype NOT IN ('0','')";
        if (!$db->table_exists("TenderTapeGeneric",$name)) {
            InstallUtilities::dbStructureModify($db,'TenderTapeGeneric',$ttG,$errors);
        }

        return $errors;
    }
}


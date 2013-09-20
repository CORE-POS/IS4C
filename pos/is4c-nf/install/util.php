<?php

function whoami(){
	if (function_exists('posix_getpwuid')){
		$chk = posix_getpwuid(posix_getuid());
		return $chk['name'];
	}
	else
		return get_current_user();
}

function check_writeable($filename, $optional=False, $template=False){
	$basename = basename($filename);
	$failure = ($optional) ? 'blue' : 'red';
	$status = ($optional) ? 'Optional' : 'Warning';

	if (!file_exists($filename) && !$optional && is_writable($filename)){
		$fp = fopen($filename,'w');
		if ($template !== False){
			switch($template){
			case 'PHP':
				fwrite($fp,"<?php\n");
				fwrite($fp,"?>\n");
				break;
			}
		}
		fclose($fp);
	}

	if (!file_exists($filename)){
		echo "<span style=\"color:$failure;\"><b>$status</b>: $basename does not exist</span><br />";
		if (!$optional){
			echo "<b>Advice</b>: <div style=\"font-face:mono;background:#ccc;padding:8px;\">
				touch \"".realpath(dirname($filename))."/".basename($filename)."\"<br />
				chown ".whoami()." \"".realpath(dirname($filename))."/".basename($filename)."\"</div>";
		}
	}
	elseif (is_writable($filename))
		echo "<span style=\"color:green;\">$basename is writeable</span><br />";
	else {
		echo "<span style=\"color:red;\"><b>Warning</b>: $basename is not writeable</span><br />";
		echo "<b>Advice</b>: <div style=\"font-face:mono;background:#ccc;padding:8px;\">
			chown ".whoami()." \"".realpath(dirname($filename))."/".basename($filename)."\"<br />
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
function confsave($key,$value,$prefer_local=False){

	// do nothing if page isn't a form submit (i.e. user didn't press save)
	if ($_SERVER['REQUEST_METHOD'] !== 'POST')
		return NULL;

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
		return False;

	$found_global = $found_local = False;
	$written_global = $written_local = False;
	$added_global = $added_local = False;

	$orig_setting = '|\$CORE_LOCAL->set\([\'"]'.$key.'[\'"],\s*(.+)\);[\r\n]|';
	$new_setting = "\$CORE_LOCAL->set('{$key}',{$value}, True);\n";

	$orig_global = file_get_contents($path_global);
	if (strstr($orig_global,'<?php') === False)
		$orig_global = "<?php\n".$orig_global;
	$orig_local = $writeable_local ? file_get_contents($path_local) : '';
	if ($writeable_local && strstr($orig_local,'<?php') === False)
		$orig_local = "<?php\n".$orig_local;

	$new_global = preg_replace($orig_setting, $new_setting, $orig_global,
					-1, $found_global);
	$new_local = preg_replace($orig_setting, $new_setting, $orig_local,
					-1, $found_local);
	if ($found_global) {
		preg_match($orig_setting, $orig_global, $matches);
		if ($matches[1] === $value.', True') // found with exact same value
			$written_global = True;	// no need to bother rewriting it
		elseif ($writeable_global)
			$written_global = file_put_contents($path_global, $new_global);
	}

	if ($found_local) {
		preg_match($orig_setting, $orig_local, $matches);
		if ($matches[1] === $value.', True') // found with exact same value
			$written_local = True;	// no need to bother rewriting it
		elseif ($writeable_local) {
			$written_local = file_put_contents($path_local, $new_local);
		}
	}

	if ($found_local && !$written_local)
		return False;	// ini-local.php is overriding ini.php with bad data!
	if ($written_global || $written_local){
		if ($written_global && $key != 'laneno')
			conf_to_db($key, $value);
		return True;	// successfully written somewhere relevant
	}

	if (!$found_global && !$found_local) {
		$append_path = ($prefer_local? $path_local : $path_global);
		$append_path = ($writeable_local? $append_path : $path_global);
		$append_path = ($writeable_global? $append_path : $path_local);
	}
	elseif (!$found_local && $writeable_local) {
		$append_path = $path_local;
	}
	if ($append_path === $path_global) {
		$new_global = preg_replace("|(\?>)?\s*$|", $new_setting.'?>', $orig_global,
						1, $found_global);
		$added_global = file_put_contents($append_path, $new_global);
	}
	else {
		$new_local = preg_replace("|(\?>)?\s*$|", $new_setting.'?>', $orig_local,
						1, $found_local);
		$added_local = file_put_contents($append_path, $new_local);
	}
	if ($added_global || $added_local){
		if ($added_global && $key != 'laneno')
			conf_to_db($key, $value);
		return True;	// successfully appended somewhere relevant
	}

	return False;	// didn't manage to write anywhere!
}

/**
  Save value to opdata.lane_config
  @param $key string key
  @param $value string value

  Called automatically by confsave().
*/
function conf_to_db($key, $value){
	global $CORE_LOCAL;
	$sql = db_test_connect($CORE_LOCAL->get('localhost'),
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
function write_conf_from_db(){
	global $CORE_LOCAL;
	$sql = db_test_connect($CORE_LOCAL->get('localhost'),
			$CORE_LOCAL->get('DBMS'),
			$CORE_LOCAL->get('pDatabase'),
			$CORE_LOCAL->get('localUser'),
			$CORE_LOCAL->get('localPass'));
	if ($sql !== False){
		$q = 'SELECT keycode, value FROM lane_config';
		$r = $sql->query($q);
		while($w = $db->fetch_row($r))
			confsave($w['keycode'], $w['value']);
	}
}

/**
  Copy values from opdata.lane_config to the
  server's lane_config table.
*/
function send_conf_to_server(){
	global $CORE_LOCAL;
	$sql = db_test_connect($CORE_LOCAL->get('localhost'),
			$CORE_LOCAL->get('DBMS'),
			$CORE_LOCAL->get('pDatabase'),
			$CORE_LOCAL->get('localUser'),
			$CORE_LOCAL->get('localPass'));
	$mine = array();
	if ($sql !== False){
		$q = 'SELECT keycode, value FROM lane_config';
		$r = $sql->query($q);
		while($w = $sql->fetch_row($r))
			$mine[$w['keycode']] = $w['value'];
	}

	$sql = db_test_connect($CORE_LOCAL->get('mServer'),
			$CORE_LOCAL->get('mDBMS'),
			$CORE_LOCAL->get('mDatabase'),
			$CORE_LOCAL->get('mUser'),
			$CORE_LOCAL->get('mPass'));
	if ($sql !== False){
		$chk = $sql->prepare_statement('SELECT value FROM
				lane_config WHERE keycode=?');
		$ins = $sql->prepare_statement('INSERT INTO lane_config
				(keycode, value) VALUES (?, ?)');
		$up = $sql->prepare_statement('UPDATE lane_config SET
				value=? WHERE keycode=?');
		foreach($mine as $key => $value){
			$exists = $sql->exec_statement($chk, array($key));
			if ($sql->num_rows($exists) == 0)
				$sql->exec_statement($ins, array($key, $value));
			else
				$sql->exec_statement($up, array($value, $key));
		}
	}
}

/**
  Fetch values from the server's lane_config table
  Write them to ini.php and opdata.lane_config.
*/
function get_conf_from_server(){
	global $CORE_LOCAL;
	$sql = db_test_connect($CORE_LOCAL->get('mServer'),
			$CORE_LOCAL->get('mDBMS'),
			$CORE_LOCAL->get('mDatabase'),
			$CORE_LOCAL->get('mUser'),
			$CORE_LOCAL->get('mPass'));
	$theirs = array();
	if ($sql !== False){
		$q = 'SELECT keycode, value FROM lane_config';
		$r = $sql->query($q);
		while($w = $sql->fetch_row($r))
			$theirs[$w['keycode']] = $w['value'];
	}

	foreach($theirs as $key => $value){
		confsave($key, $value);
	}
}

function load_sample_data($sql, $table){
	if (file_exists("data/$table.sql")){
		$fp = fopen("data/$table.sql","r");
		while($line = fgets($fp)){
			$sql->query("INSERT INTO $table VALUES $line");
		}
		fclose($fp);
	}
	else if (file_exists("data/$table.csv")){
		$path = realpath("data/$table.csv");
		$prep = $sql->prepare_statement("LOAD DATA LOCAL INFILE
			'$path'
			INTO TABLE $table
			FIELDS TERMINATED BY ','
			ESCAPED BY '\\\\'
			OPTIONALLY ENCLOSED BY '\"'
			LINES TERMINATED BY '\\r\\n'");
		$sql->exec_statement($prep);
	}
}

function db_test_connect($host,$type,$db,$user,$pw){
        $sql = False;
        try {
		if ($type == 'mysql')
			ini_set('mysql.connect_timeout',1);
		elseif ($type == 'mssql')
			ini_set('mssql.connect_timeout',1);
		ob_start();
                $sql = @ new SQLManager($host,$type,$db,$user,$pw);
		ob_end_clean();
        }
        catch(Exception $ex) {}

        if ($sql === False || $sql->connections[$db] === False)
                return False;
        else
                return $sql;
}

/* query to create another table with the same
	columns
*/
function duplicate_structure($dbms,$table1,$table2){
	if (strstr($dbms,"MYSQL")){
		return "CREATE TABLE `$table2` LIKE `$table1`";
	}
	elseif ($dbms == "MSSQL"){
		return "SELECT * INTO [$table2] FROM [$table1] WHERE 1=0";
	}
	elseif ($dbms == 'PDOLITE'){
		$path = realpath(dirname(__FILE__).'/sql');
		if (file_exists($path.'/op/'.$table1.'.php')){
			include($path.'/op/'.$table1.'.php');
			return str_replace($table1, $table2, $CREATE['op.'.$table1]);
		}
		elseif (file_exists($path.'/trans/'.$table1.'.php')){
			include($path.'/trans/'.$table1.'.php');
			return str_replace($table1, $table2, $CREATE['trans.'.$table1]);
		}
		else	
			return 'No table found';
	}
}

function create_if_needed($con, $dbms, $db_name, $table_name, $stddb, &$errors=array()){
	if ($con->table_exists($table_name,$db_name)) return $errors;
	$dbms = strtoupper($dbms);

	$fn = dirname(__FILE__)."/sql/$stddb/$table_name.php";
	if (!file_exists($fn)){
		$errors[] = array(
			'struct'=>$table_name,
			'query'=>'n/a',
			'details'=>'Missing file: '.$fn,
			'important'=>True
		);
		return $errors;
	}

	include($fn);
	if (!isset($CREATE["$stddb.$table_name"])){
		$errors[] = array(
			'struct'=>$table_name,
			'query'=>'n/a',
			'details'=>'No valid $CREATE in: '.$fn,
			'important'=>True
		);
		return $errors;
	}

	return db_structure_modify($con, $table_name, $CREATE["$stddb.$table_name"], $errors);
}

function db_structure_modify($sql, $struct_name, $query, &$errors=array()){
	ob_start();
	$try = @$sql->query($query);
	ob_end_clean();
	if ($try === False){
		if (stristr($query, "DROP ") && stristr($query,"VIEW ")){
			/* suppress unimportant errors
			$errors[] = array(
			'struct' => $struct_name,
			'query' => $query,
			'important' => False
			);
			*/
		}
		else {
			$errors[] = array(
			'struct'=>$struct_name,
			'query'=>$query,
			'details'=>$sql->error(),
			'important'=>True
			);
		}
	}
	return $errors;
}

?>

<?php

function check_writeable($filename){
	$basename = basename($filename);
	if (!file_exists($filename))
			echo "<span style=\"color:red;\"><b>Warning</b>: $basename does not exist</span><br />";
	elseif (is_writable($filename))
			echo "<span style=\"color:green;\">$basename is writeable</span><br />";
	else
			echo "<span style=\"color:red;\"><b>Warning</b>: $basename is not writeable</span><br />";
}

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
	$orig_local = $writeable_local ? file_get_contents($path_local) : '';

	$new_global = preg_replace($orig_setting, $new_setting, $orig_global,
					-1, $found_global);
	$new_local = preg_replace($orig_setting, $new_setting, $orig_local,
					-1, $found_local);
	if ($found_global) {
		preg_match($orig_setting, $orig_global, $matches);
		if ($key == 'discountEnforced')
			var_dump($value);
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
	if ($written_global || $written_local)
		return True;	// successfully written somewhere relevant

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
	if ($added_global || $added_local)
		return True;	// successfully appended somewhere relevant

	return False;	// didn't manage to write anywhere!
}

function load_sample_data($sql, $table){
	$fp = fopen("data/$table.sql","r");
	while($line = fgets($fp)){
		$sql->query("INSERT INTO $table VALUES $line");
	}
	fclose($fp);
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

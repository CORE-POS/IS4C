<?php

function confsave($key,$value){
	if (!is_writable('../ini.php')) return;

	/**
	  ini.php vs ini-local.php

	  Check ini-local.php first. If the setting
	  is found there, update the value. Check ini.php
	  next. If the setting was not in ini-local.php,
	  update or add it in ini.php.
	*/

	$found_local = False;
	if (is_writable('../ini-local.php')){
		$fp = fopen('../ini-local.php','r');
		$lines = array();
		while($line = fgets($fp)){
			if (strpos($line,'$CORE_LOCAL->set("'.$key.'"') === 0){
				$lines[] = sprintf("\$CORE_LOCAL->set(\"%s\",%s);\n",
						$key,$value);
				$found_local = True;
			}
			else{
				$lines[] = $line;
			}
		}
		fclose($fp);

		if ($found_local) {
			$fp = fopen('../ini-local.php','w');
			foreach($lines as $line)
				fwrite($fp,$line);
			fclose($fp);
		}
	}

	$fp = fopen('../ini.php','r');
	$lines = array();
	while($line = fgets($fp)){
		if (strpos($line,'$CORE_LOCAL->set("'.$key.'"') === 0){
			$lines[] = sprintf("\$CORE_LOCAL->set(\"%s\",%s);\n",
					$key,$value);
			$found = True;
		}
		elseif (strpos($line,'?>') === 0 && !$found){
			$lines[] = sprintf("\$CORE_LOCAL->set(\"%s\",%s);\n",
					$key,$value);
			$lines[] = "?>\n";
		}
		else{
			$lines[] = $line;
		}
	}
	fclose($fp);

	if (!$found_local) {
		$fp = fopen('../ini.php','w');
		foreach($lines as $line)
			fwrite($fp,$line);
		fclose($fp);
	}

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
                $sql = new SQLManager($host,$type,$db,$user,$pw);
        }
        catch(Exception $ex) {}

        if ($sql === False || $sql->connections[$db] === False)
                return False;
        else
                return $sql;
}

?>

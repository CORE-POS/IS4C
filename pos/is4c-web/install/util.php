<?php

function confsave($key,$value){
	if (!is_writable('../ini.php')) return;

	$fp = fopen('../ini.php','r');
	$lines = array();
	$found = False;
	while($line = fgets($fp)){
		if (strpos($line,'$IS4C_LOCAL->set("'.$key.'"') === 0){
			$lines[] = sprintf("\$IS4C_LOCAL->set(\"%s\",%s);\n",
					$key,$value);
			$found = True;
		}
		elseif (strpos($line,'?>') === 0 && !$found){
			$lines[] = sprintf("\$IS4C_LOCAL->set(\"%s\",%s);\n",
					$key,$value);
			$lines[] = "?>\n";
		}
		else{
			$lines[] = $line;
		}
	}
	fclose($fp);

	$fp = fopen('../ini.php','w');
	foreach($lines as $line)
		fwrite($fp,$line);
	fclose($fp);
}

function loadSampleData($sql, $table){
	$fp = fopen("data/$table.sql","r");
	while($line = fgets($fp)){
		$sql->query("INSERT INTO $table VALUES $line");
	}
	fclose($fp);
}

?>

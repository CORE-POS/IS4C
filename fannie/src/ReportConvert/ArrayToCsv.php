<?php

function ArrayToCsv($array){
	$ret = "";

	foreach($array as $row){
		foreach($row as $col){
			$r = "\"";
			if ( ($pos = strpos($col,chr(0))) !== False){
				$col = substr($col,0,$pos);
			}
			$r .= str_replace("\"","",$col);
			$r .= "\",";
			$ret .= $r;
		}
		$ret = rtrim($ret,",")."\r\n";
	}

	return $ret;
}

?>

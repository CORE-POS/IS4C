<?php
$CORE_PATH="../../pos/is4c-nf/";
print_docs("{$CORE_PATH}parser-class-lib/preparse/");
print_docs("{$CORE_PATH}parser-class-lib/parse/");

class Parser {
}

class PreParser { 
}

function print_docs($dir){
	global $CORE_PATH;
	$dh = opendir($dir);
	while(False !== ($file=readdir($dh))){
		
		if (substr($file,-4) != ".php") continue;

		$cn = substr($file,0,strlen($file)-4);

		if (!class_exists($cn))
			include_once($dir."/".$cn.".php");

		$instance = new $cn();
		print "<h3>$cn</h3>";
		print $instance->doc();
		print "<hr />";
	}
	closedir($dh);
}

?>

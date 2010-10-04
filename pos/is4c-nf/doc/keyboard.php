<?php

print_docs($_SERVER["DOCUMENT_ROOT"]."/parser-class-lib/preparse/");
print_docs($_SERVER["DOCUMENT_ROOT"]."/parser-class-lib/parse/");


function print_docs($dir){
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

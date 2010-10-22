<?php
if (!isset($_REQUEST['fn'])){
	echo "<h3>opdata</h3>";
	echo "This database contains relatively static information
		related to operations, such as products and employees";
	echo "<ul>";
	$dh = opendir("op");
	$op_files = array();
	while (($file = readdir($dh)) !== false) {
		if (is_file("op/".$file))
			$op_files[] = $file;
	}
	sort($op_files);
	foreach($op_files as $f){
		printf('<li><a href="magic-doc.php?fn=%s">%s</a></li>',
			urlencode("op/".$f),
			substr($f,0,-4)
		);
	}
	echo "</ul>";
}
else {
	$fn = urldecode($_REQUEST['fn']);
	if (!file_exists($fn)){
		echo "Error: bad file name: $fn";
		echo "<br />";
		echo "<a href=\"magic-doc.php\">Back</a>";
		exit;
	}

	$data = file_get_contents($fn);
	$tokens = token_get_all($data);
	$documentation = "";
	foreach($tokens as $t){
		if ($t[0] == T_COMMENT)
			$documentation .= (empty($documentation)?"\n":"").$t[1];
	}

	echo '<a href="magic-doc.php">Back</a><br />';
	if (empty($documentation))
		echo "<i>Someone forgot to comment their table...</i><br />";
	else
		printf("<pre>%s</pre>",$documentation);
	echo '<a href="magic-doc.php">Back</a><br />';
}
?>

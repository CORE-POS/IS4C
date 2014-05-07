<?php
/* --FUNCTIONALITY- - - - - - - - - - - - - - - - - - - - -

 Usage: magic-doc.php[?fn=op|trans/tablename.php]

 Display information from comments in the scripts that create MySQL tables.

 Two modes:
 1. If GET fn= not set, display a list of links-with-fn to this script
     for each .php in the op/ and trans/ subdirectories.
 2. If GET fn=[op|trans]/table.php extract and display the comment block
     from the named script.

*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - -

 12Mar14 EL Cloned from $FANNIE/install/sql for lane tables.
 23Jun12 EL Added COMMENTS, FUNCTIONALITY comment blocks.
            Added intro to Contents page.
            Fix "core_log" should be "core_trans".
            Option to display each page in a new tab (target=).
*/

/* If no argument display a list of links */
if (!isset($_REQUEST['fn'])){

	echo "<h3>Links to IS4C (Lane) per-table Help</H3>
<p>Each link displays the current contents of the comment block
<br>in the PHP script at
<i>docroot</i>/IS4C/pos/is4c-nf/install/sql/op|trans/<i>tablename</i>.php
<br>that creates the table.
</p>
";

	 /* Option to display each page in a new tab (target=). 1=do, 0=don't */
	$new_tab = 1;

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
		if ( $new_tab == 1 ) {
			printf('<li><a href="magic-doc.php?fn=%s" target="_%s">%s</a></li>',
				urlencode("op/".$f),
				$f,
				substr($f,0,-4)
			);
		}
		else {
			printf('<li><a href="magic-doc.php?fn=%s">%s</a></li>',
				urlencode("op/".$f),
				substr($f,0,-4)
			);
		}
	}
	echo "</ul>";

	echo "<h3>translog</h3>";
	echo "This database contains changing information,
		primarily transaction related";
	echo "<ul>";
	$dh = opendir("trans");
	$trans_files = array();
	while (($file = readdir($dh)) !== false) {
		if (is_file("trans/".$file))
			$trans_files[] = $file;
	}
	sort($trans_files);
	foreach($trans_files as $f){
/*		printf('<li><a href="magic-doc.php?fn=%s" target="_%s">%s</a></li>',
			urlencode("trans/".$f),
			$f,
			substr($f,0,-4)
		);
		*/
		if ( $new_tab == 1 ) {
			printf('<li><a href="magic-doc.php?fn=%s" target="_%s">%s</a></li>',
				urlencode("trans/".$f),
				$f,
				substr($f,0,-4)
			);
		}
		else {
			printf('<li><a href="magic-doc.php?fn=%s">%s</a></li>',
				urlencode("trans/".$f),
				substr($f,0,-4)
			);
		}
	}
	echo "</ul>";
}
/* Display the help for the named table-creation-script. */
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

<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of Fannie.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
include('../config.php');
include($FANNIE_ROOT.'auth/login.php');

if(!validateUserQuiet('admin')){
	$url = $FANNIE_URL.'auth/ui/loginform.php';
	$rd = $FANNIE_URL.'logs/';
	header("Location: $url?redirect=$rd");
	exit;	
}

$page_title = 'Fannie - Logs';
$header = 'View Logs';
include($FANNIE_ROOT.'src/header.html');

if (!isset($_REQUEST['logfile'])){
	echo "Choose a log file:<ul>";
	$dh = opendir(".");
	while(($file = readdir($dh)) !== false){
		if ($file == "." || $file == ".." || $file == "index.php")
			continue;
		if (is_numeric(substr($file,-1))) // log rotations
			continue;
		if (is_dir($file)) // someone put a directory here
			continue;
		printf('<li><a href="%s?logfile=%s">%s</a></li>',
			$_SERVER['PHP_SELF'],
			base64_encode($file),
			$file);
	}
	echo "</ul>";
}
else {
	$fn = base64_decode($_REQUEST['logfile']);
	if (isset($_REQUEST['rotate']))
		doRotate($fn,$FANNIE_LOG_COUNT);
	$fp = @file_get_contents($fn);

	echo '<a href="index.php">Back to listing</a>';
	if ($fp){
		echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		printf('<a href="index.php?logfile=%s&rotate=yes"
			onclick="return confirm(\'Are you sure?\');">Rotate
			log</a>',base64_encode($fn));
	}
	echo '<hr />';
	if ($fp === false) echo "<i>Error opening logfile</i><br />";
	elseif (empty($fp)) echo "<i>File is empty</i><br />";
	else {
		// force word wrap
		echo '<style type="text/css">
			pre {
				 white-space: pre-wrap;       /* css-3 */
				 white-space: -moz-pre-wrap !important;  /* Mozilla, since 1999 */
				 white-space: -pre-wrap;      /* Opera 4-6 */
				 white-space: -o-pre-wrap;    /* Opera 7 */
				 word-wrap: break-word;       /* Internet Explorer 5.5+ */
			}
		</style>';

		if ($FANNIE_PRETTY_LOGS != 0){
			echo '<script type="text/javascript"
				src="'.$FANNIE_URL.'src/jquery/jQuery-SyntaxHighlighter/scripts/jquery.syntaxhighlighter.min.js">
				</script>';
			echo '<script type="text/javascript">';
			printf('$(document).ready(function(){
					$.SyntaxHighlighter.init({
					\'baseUrl\' : \'%s\',
					\'prettifyBaseUrl\': \'%s\'		
					});
				});',$FANNIE_URL.'src/jquery/jQuery-SyntaxHighlighter',
				$FANNIE_URL.'src/jquery/jQuery-SyntaxHighlighter/prettify');
			echo '</script>';
		}
		
		echo '<pre class="highlight" style="width: 500px;">';
		echo $fp;
		echo '</pre>';
	}
}

include($FANNIE_ROOT.'src/footer.html');

function doRotate($fn,$limit){
	// don't rotate empty files
	if (filesize($fn) == 0) return false;

	for($i=$limit-1; $i>=0; $i--){
		if (file_exists($fn.".".$i))
			rename($fn.".".$i,$fn.".".($i+1));
	}

	if (file_exists($fn))
		rename($fn,$fn.".0");

	$fp = fopen($fn,"w");
	fclose($fp);

	return true;
}
?>

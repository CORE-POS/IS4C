<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
include('../../config.php');

$fn = isset($_REQUEST['fn'])?$_REQUEST['fn']:'';
if ($fn == ''){
	echo "No file specified";
	exit;
}

$fn = $FANNIE_ROOT.'cron/'.base64_decode($fn);

$data = file_get_contents($fn);
$tokens = token_get_all($data);
$doc = "";
foreach($tokens as $t){
	if ($t[0] == T_COMMENT){
		if (strstr($t[1],"HELP"))
			$doc .= $t[1]."\n";
	}
}

echo "<html><head><title>";
echo basename($fn);
echo "</title></head><body>";
echo "<pre>";
if (!empty($doc))
	echo $doc;
else
	echo "Sorry, no documentation for this script";
echo "</pre>";
echo "</body></html>";

?>

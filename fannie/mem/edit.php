<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
include('../config.php');
include('MemberModule.php');

$memNum = isset($_REQUEST['memNum'])?$_REQUEST['memNum']:False;

$page_title = "Fannie :: Member $memNum";
$header = "Member $memNum";

include($FANNIE_ROOT.'src/header.html');

$country = isset($FANNIE_COUNTRY)?$FANNIE_COUNTRY:"US";

if ($memNum !== False){
	echo '<form action="save.php" method="post">';
	printf('<input type="hidden" name="memNum" value="%d" />',$_REQUEST['memNum']);
	foreach($FANNIE_MEMBER_MODULES as $mm){
		include('modules/'.$mm.'.php');
		$instance = new $mm();
		echo '<div style="float:left;">';
		echo $instance->ShowEditForm($memNum, $country);
		echo '</div>';
	}
	echo '<div style="clear:left;"></div>';
	echo '<hr />';
	echo '<input type="submit" value="Save Changes" />';
	echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	echo '<input type="reset" value="Undo Changes" />';
	echo '</form>';
}

include($FANNIE_ROOT.'src/footer.html');

?>

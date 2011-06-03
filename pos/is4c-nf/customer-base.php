<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IT CORE.

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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html>
<head>
<script type="text/javascript">
if (opener.name != "main_frame"){
	self.blur();
}
</script>
</head>
<FRAMESET cols='675,118,*' frameborder='0'>
	<frameset rows="40,440,*" frameborder=0 scrolling=no>
		<FRAME src='/gui-modules/blank.html' name='cust_input' border='0' scrolling='no'>
		<FRAME src='/gui-modules/posCustDisplay.php' name='cust_main_frame' border='0' scrolling='no'>
	</FRAMESET>
	<!--
	<frameset rows="345,50,50,*" frameborder=0 scrolling=no>
		<frame src="scale.php" name="scale" border=0 scrolling=no>
		<frame src="endorse.php" name="endorse" border=0 scrolling=no>
		<frame src="end.php" name="end" border=0 scrolling=no>
	</frameset>
	-->
</FRAMESET>
</html>

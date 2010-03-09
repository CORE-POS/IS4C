<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IS4C.

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
 // session_start(); ?>

<TABLE border='0' cellpadding='0' cellspacing='0'>
<TR><TD width='200'>

</TD>
<TD align='right' valign='top' width='440'>
<?

$time = strftime("%m/%d/%y  %I:%M %p", time());

if ($_SESSION["training"] == 1) {
	echo "<FONT size='-1' face='arial' color='#004080'>training </FONT>"
	     ."<IMG src='graphics/BLUEDOT.GIF'>&nbsp;&nbsp:&nbsp;";
}
elseif ($_SESSION["standalone"] == 0) {
	echo "<IMG src='graphics/GREENDOT.GIF'>&nbsp;&nbsp;&nbsp;";
}
else {
	echo "<FONT size='-1' face='arial' color='#800000'>stand alone</FONT>"
	     ."<IMG src='graphics/REDDOT.GIF'>&nbsp;&nbsp;&nbsp;";
}

?>

<FONT face='arial' size='+1'><B><? echo $time; ?></B></FONT>
</TD></TR></TABLE>

</BODY>

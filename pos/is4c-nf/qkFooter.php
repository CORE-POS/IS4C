<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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
if (!isset($CORE_LOCAL)) include($_SESSION["INCLUDE_PATH"]."/lib/LocalStorage/conf.php");

if ($CORE_LOCAL->get("gui-scale") == "no")
        return;

?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="/pos.css">
<script type="text/javascript">
function trySubmit(inputstr){
	if (window.top.input.document.forms[0]){
		var cur = window.top.input.document.forms[0].reginput.value;
		window.top.input.document.forms[0].reginput.value=cur+inputstr;
		window.top.input.document.forms[0].submit();
	}
}
</script>
</head>
<body style="background: none;">
<div style="text-align: center;">
<input type="submit" value="Items"
	class="quick_button"
	style="margin: 0 10px 0 0;"
	onclick="trySubmit('QK0');" />
<input type="submit" value="Total"
	class="quick_button"
	style="margin: 0 10px 0 0;"
	onclick="trySubmit('QK4');" />
<input type="submit" value="Tender"
	class="quick_button"
	style="margin: 0 10px 0 0;"
	onclick="trySubmit('QK2');" />
<input type="submit" value="Member"
	class="quick_button"
	style="margin: 0 10px 0 0;"
	onclick="trySubmit('QK5');" />
<input type="submit" value="Misc"
	class="quick_button"
	style="margin: 0 10px 0 0;"
	onclick="trySubmit('QK6');" />
</div>
</body>
</html>

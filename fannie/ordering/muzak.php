<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
include('../config.php');

include($FANNIE_ROOT.'auth/login.php');
if (!checkLogin()){
    $url = $FANNIE_URL."auth/ui/loginform.php";
    $rd = $FANNIE_URL."ordering/";
    header("Location: $url?redirect=$rd");
    exit;
}

$page_title = "Fannie :: Special Orders";
$header = "Special Orders";
include($FANNIE_ROOT.'src/header.html');
?>
<script type="text/javascript">
function mplay(){
    var tag = document.getElementById('muzak');
    tag.currentTime=0;
    tag.play();
}
function mstop(){
    var tag = document.getElementById('muzak');
    tag.pause();
}
</script>
<audio id="muzak" loop src="frog.wav">
No audio support
</audio>
<input type="submit" value="Muzak ON" 
    onclick="mplay(); return false;" />
<p />
<p />
<input type="submit" value="Muzak OFF" 
    onclick="mstop(); return false;" />
<?php
include($FANNIE_ROOT.'src/footer.html');
?>

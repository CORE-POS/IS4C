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
ini_set('display_errors','1');
include('../config.php'); 
include('util.php');
include('db.php');
$FILEPATH = $FANNIE_ROOT;
if (isset($_REQUEST['ordering'])){
    $FANNIE_MEMBER_MODULES = array();
    foreach($_REQUEST['ordering'] as $o){
        if (!in_array($o,$FANNIE_MEMBER_MODULES)) 
            $FANNIE_MEMBER_MODULES[] = $o;
    }
    $saveStr = 'array(';
    foreach($FANNIE_MEMBER_MODULES as $t)
        $saveStr .= '"'.$t.'",';
    $saveStr = rtrim($saveStr,',').")";
    echo "<blockquote><i>Order Updated</i></blockquote>";
    confset('FANNIE_MEMBER_MODULES',$saveStr);
}
?>
<form action=memModDisplay.php method=post>
<h1>Member Module Display Order</h1>
<?php
$num = count($FANNIE_MEMBER_MODULES);
if ($num == 0){
    echo "<i>Error: no modules enabled</i><br />";
    echo '<a href="mem.php">Back to Member Settings</a>';
    exit;
}
for ($i=1;$i<=$num;$i++){
    echo "#$i: <select name=\"ordering[]\">";
    for($j=1;$j<=$num;$j++){
        printf("<option %s>%s</option>",
            ($i==$j?'selected':''),
            $FANNIE_MEMBER_MODULES[$j-1]);
    }
    echo "</select><p />";
}
?>
<input type="submit" value="Save Order" />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="submit" value="Back to Member Settings" 
    onclick="location='mem.php';return false;" />
</form>

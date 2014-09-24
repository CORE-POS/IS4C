<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op, Duluth, MN

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
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
$dbc = FannieDB::get($FANNIE_OP_DB);

$page_title = "Fannie :: Patronage Tools";
$header = "Calculate Rewards";

include($FANNIE_ROOT.'src/header.html');

if (isset($_REQUEST['rewardsubmit'])){

    $types = "";
    $list = preg_split("/\W+/",$_REQUEST['upcs'],-1,PREG_SPLIT_NO_EMPTY);
    $args = array();
    foreach($list as $l){
        $types .= '?,';
        $args[] = $l;
    }
    $types = substr($types,0,strlen($types)-1);

    $fetchQ = sprintf("SELECT card_no,SUM(total) as total
        FROM %s%sdlog_patronage
        WHERE trans_type='T'
        AND trans_subtype IN (%s)
        GROUP BY card_no",$FANNIE_TRANS_DB,$dbc->sep(),$types);
    $prep = $dbc->prepare_statement($fetchQ);
    $fetchR = $dbc->exec_statement($prep,$args);

    $upP = $dbc->prepare_statement("UPDATE patronage_workingcopy
        SET rewards=? WHERE cardno=?");
    while($fetchW = $dbc->fetch_row($fetchR)){
        if ($fetchW['total']==0) continue;
        $dbc->exec_statement($upP,array($fetchW['total'],$fetchW['card_no']));
    }
    
    echo '<i>Rewards loaded</i>';
}
else {
    echo '<blockquote><i>';
    echo 'Step three: calculate additonal member rewards based on tender type.';
    echo '</i></blockquote>';
    echo '<form action="rewards.php" method="get">';
    echo '<b>Tender Type(s)</b>: ';
    echo '<input type="text" name="upcs" />';
    echo '<br /><br />';
    echo '<input type="submit" name="rewardsubmit" value="Calculate Rewards" />';
    echo '</form>';
}

echo '<br /><br />';
echo '<a href="index.php">Patronage Menu</a>';

include($FANNIE_ROOT.'src/footer.html');

?>

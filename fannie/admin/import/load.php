<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename($_SERVER['PHP_SELF']) != basename(__FILE__)) {
    return;
}
$dbc = FannieDB::get($FANNIE_OP_DB);

if (!isset($_REQUEST['lc_col'])){
    $tpath = sys_get_temp_dir()."/vendorupload/";
    $fp = fopen($tpath."lcimp.csv","r");
    echo '<h3>Select columns</h3>';
    echo '<form action="load.php" method="post">';
    echo '<table cellpadding="4" cellspacing="0" border="1">';
    $width = 0;
    $table = "";
    for($i=0;$i<5;$i++){
        $data = fgetcsv($fp);
        $table .= '<tr><td>&nbsp;</td>';
        $j=0;
        foreach($data as $d){
            $table .='<td>'.$d.'</td>';
            $j++;
        }
        if ($j > $width) $width = $j;
        $table .= '</tr>';
    }
    echo '<tr><th>LC</th>';
    for($i=0;$i<$width;$i++){
        echo '<td><input type="radio" name="lc_col" value="'.$i.'" /></td>';
    }
    echo '</tr>';
    echo '<tr><th>Description</th>';
    for($i=0;$i<$width;$i++){
        echo '<td><input type="radio" name="desc_col" value="'.$i.'" /></td>';
    }
    echo '</tr>';
    echo '<tr><th>Origin</th>';
    for($i=0;$i<$width;$i++){
        echo '<td><input type="radio" name="origin_col" value="'.$i.'" /></td>';
    }
    echo '</tr>';
    echo $table;
    echo '</table>';
    echo '<input type="submit" value="Continue" />';
    echo '</form>';
    exit;
}

$LC = (isset($_REQUEST['lc_col'])) ? (int)$_REQUEST['lc_col'] : 0;
$DESC = (isset($_REQUEST['desc_col'])) ? (int)$_REQUEST['desc_col'] : 2;
$ORIGIN = (isset($_REQUEST['origin_col'])) ? (int)$_REQUEST['origin_col'] : 4;

$tpath = sys_get_temp_dir()."/vendorupload/";
$fp = fopen($tpath."lcimp.csv","r");
$chkP = $dbc->prepare_statement("SELECT p.upc FROM products AS p INNER JOIN
    upcLike AS u ON p.upc=u.upc WHERE
    u.likeCode=? AND p.upc NOT IN (
    select upc from productUser)");
$ins = $dbc->prepare_statement("INSERT INTO productUser (upc) VALUES (?)");
$up = $dbc->prepare_statement("UPDATE productUser AS p INNER JOIN
    upcLike AS u ON p.upc=u.upc
    SET p.description=?,
    p.brand=? WHERE u.likeCode=?");
while(!feof($fp)){
    $data = fgetcsv($fp);
    if (!is_array($data)) continue;
    if (count($data) < 3) continue;

    if (!isset($data[$LC])) continue;
    if (!isset($data[$DESC])) continue;
    if (!isset($data[$ORIGIN])) continue;

    $l = $data[$LC];
    $d = $data[$DESC];
    $o = $data[$ORIGIN];
    if (!is_numeric($l) || $l != (int)$l) continue;

    $r  = $dbc->exec_statement($chkP,array($l));
    while($w = $dbc->fetch_row($r)){
        $dbc->exec_statement($ins,array($w['upc']));
    }

    $dbc->exec_statement($up,array($d,$o,$l));

}
fclose($fp);
unlink($tpath."lcimp.csv");

$page_title = "Fannie - Data import";
$header = "Upload Completed";
include($FANNIE_ROOT."src/header.html");

echo "Data import complete<p />";

include($FANNIE_ROOT."src/footer.html");

?>

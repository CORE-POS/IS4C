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
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_TRANS_DB);

$page_title = "Fannie :: Special Order Receiving";
$header = "Special Order Receiving";
include($FANNIE_ROOT.'src/header.html');
?>
<script type="text/javascript">
function refilter(f){
    var o = $('#orderSetting').val();
    var s = $('#sS').val();
    location = "receivingReport.php?f="+f+"&s="+s+"&order="+o;
}
function resort(o){
    var f= $('#sF').val();
    location = "receivingReport.php?f="+f+"&order="+o;
}
</script>
<?
$status = array(
    ""=> "Any",
    0 => "New",
    2 => "Pending",
    4 => "Placed"
);

$order = isset($_REQUEST['order'])?$_REQUEST['order']:'mixMatch';
$filter = isset($_REQUEST['f'])?$_REQUEST['f']:4;
$supp = isset($_REQUEST['s'])?$_REQUEST['s']:'';
if ($filter !== '') $filter = (int)$filter;

echo '<select id="sF" onchange="refilter($(this).val());">';
foreach($status as $k=>$v){
    printf('<option value="%s" %s>%s</option>',
        $k,($k===$filter?'selected':''),$v);
}
echo '</select>';
echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

$suppliers = array('');
$q = $dbc->prepare_statement("SELECT mixMatch FROM PendingSpecialOrder WHERE trans_type='I'
    GROUP BY mixMatch ORDER BY mixMatch");
$r = $dbc->exec_statement($q);
while($w = $dbc->fetch_row($r)){
    $suppliers[] = $w[0];
}
echo '<select id="sS" onchange="refilter($(\'#sF\').val());">';
echo '<option value="">Supplier...</option>';
foreach($suppliers as $s){
    printf('<option %s>%s</option>',
        ($s==$supp?'selected':''),$s);
}
echo '</select><br /><br />';
printf('<input type="hidden" id="orderSetting" value="%s" />',$order);

$where = "p.trans_type = 'I'";
$args = array();
if (!empty($filter)){
    $where .= " AND s.statusFlag=? ";
    $args[] = ((int)$filter);
}
if (!empty($supp)){
    $where .= " AND mixMatch=? ";
    $args[] = $supp;
}
$sql_order = 'mixMatch,upc';
if ($order == 'upc')
    $sql_order = 'upc';
elseif($order == 'description')
    $sql_order = 'description,upc';
elseif($order == 'ItemQtty')
    $sql_order = 'ItemQtty,upc';
elseif($order == 'subStatus')
    $sql_order = 'subStatus,upc';

$q = "SELECT upc,description,ItemQtty,mixMatch,subStatus
    FROM PendingSpecialOrder AS p
    LEFT JOIN SpecialOrders as s
    ON p.order_id=s.specialOrderID
    WHERE $where
    ORDER BY $sql_order";
$p = $dbc->prepare_statement($q);
$r = $dbc->exec_statement($q, $args);
echo '<table cellspacing="0" cellpadding="4" border="1"><tr>';
echo '<th><a href="" onclick="resort(\'upc\');return false;">UPC</a></td>';
echo '<th><a href="" onclick="resort(\'description\');return false;">Desc.</a></td>';
echo '<th><a href="" onclick="resort(\'ItemQtty\');return false;"># Cases</a></td>';
echo '<th><a href="" onclick="resort(\'mixMatch\');return false;">Supplier</a></td>';
echo '<th><a href="" onclick="resort(\'subStatus\');return false;">Status Updated</a></td>';
echo '</tr>';
while ($w = $dbc->fetch_row($r)){
    printf('<tr><td>%s</td><td>%s</td><td>%d</td><td>%s</td><td>%s</td></tr>',
        $w['upc'],$w['description'],$w['ItemQtty'],$w['mixMatch'],
        ($w['subStatus']==0?'Unknown':date('m/d/Y',$w['subStatus'])));
}
echo '</table>';

include($FANNIE_ROOT.'src/footer.html');
?>

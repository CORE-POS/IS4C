<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
use COREPOS\Fannie\API\lib\Store;
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}
$edit = FannieAuth::validateUserQuiet('ordering_edit');
if (Store::getIdByIp() == 2 || $edit || FannieConfig::config('SO_UI') === 'bootstrap') {
    $url = 'NewSpecialOrdersPage.php';
    if (isset($_REQUEST['card_no'])) {
        $url .= sprintf('?card_no=%d', $_REQUEST['card_no']);
    }
    header('Location: ' . $url);
    return;
}
if (!function_exists('checkLogin')) {
    include(__DIR__ . '/../auth/login.php');
}
$dbc = FannieDB::get($FANNIE_OP_DB);

$TRANS = ($FANNIE_SERVER_DBMS == "MSSQL") ? $FANNIE_TRANS_DB.".dbo." : $FANNIE_TRANS_DB.".";

$username = checkLogin();
if (!$username){
    $url = $FANNIE_URL."auth/ui/loginform.php";
    $rd = $FANNIE_URL."ordering/clearinghouse.php";
    header("Location: $url?redirect=$rd");
    return;
}


$cachepath = sys_get_temp_dir()."/ordercache/";

if (!is_dir($cachepath))
    mkdir($cachepath);
$key = dechex(str_replace(" ","",str_replace(".","",microtime())));
$prints = array();
if (file_exists("{$cachepath}{$username}.prints"))
    $prints = unserialize(file_get_contents("{$cachepath}{$username}.prints"));
else {
    $fp = fopen("{$cachepath}{$username}.prints",'w');
    fwrite($fp,serialize($prints));
    fclose($fp);
}

$page_title = "Special Order :: Management";
$header = "Manage Special Orders";
if (isset($_REQUEST['card_no']) && is_numeric($_REQUEST['card_no'])){
    $header = "Special Orders for Member #".((int)$_REQUEST['card_no']);
}
//include(__DIR__ . '/../src/header.html');
echo '<html>
    <head><title>'.$page_title.'</title>
    <link rel="STYLESHEET" href="'.$FANNIE_URL.'src/style.css" type="text/css">
    <link rel="STYLESHEET" href="'.$FANNIE_URL.'src/css/configurable.php" type="text/css">
    <link rel="STYLESHEET" href="'.$FANNIE_URL.'src/javascript/jquery-ui.css" type="text/css">
    <script type="text/javascript" src="'.$FANNIE_URL.'src/javascript/jquery.js">
    </script>
    <script type="text/javascript" src="'.$FANNIE_URL.'src/javascript/jquery-ui.js">
    </script>
    </head>
    <body id="bodytag">';
echo '<h3>'.$header.'</h3>';
$new = 'NewSpecialOrdersPage.php';
if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']) {
    $new .= '?' . $_SERVER['QUERY_STRING'];
}
echo '<div style="text-align: center; background: #00aa00;" class="alert alert-info"><a style="color:#fff" href="'.$new.'">Newer Version</a></div>';
if (isset($_REQUEST['card_no'])){
    printf('(<a href="clearinghouse.php?f1=%s&f2=%s&f3=%s&order=%s">Back to All Owners</a>)<br />',
        (isset($_REQUEST['f1'])?$_REQUEST['f1']:''),    
        (isset($_REQUEST['f2'])?$_REQUEST['f2']:''),    
        (isset($_REQUEST['f3'])?$_REQUEST['f3']:''),    
        (isset($_REQUEST['order'])?$_REQUEST['order']:'')
    );
}

$status = array(
    0 => "Ready to Order",
    3 => "Call before Ordering",
    1 => "Called/waiting",
    2 => "Pending",
    4 => "Placed",
    5 => "Arrived"
);

$stores = array(
    0 => 'All',
    1 => 'Hillside',
    2 => 'Denfeld',
);
$myStore = COREPOS\Fannie\API\lib\Store::getIdByIp();

$assignments = array();
$q = "SELECT superID,super_name FROM MasterSuperDepts
    GROUP BY superID,super_name ORDER BY superID";
$r = $dbc->query($q);
while($w = $dbc->fetch_row($r))
    $assignments[$w[0]] = $w[1];
unset($assignments[0]); 

$suppliers = array('');
$q = "SELECT mixMatch FROM {$TRANS}PendingSpecialOrder WHERE trans_type='I'
    GROUP BY mixMatch ORDER BY mixMatch";
$r = $dbc->query($q);
while($w = $dbc->fetch_row($r)){
    $suppliers[] = $w[0];
}

$f1 = (isset($_REQUEST['f1']) && $_REQUEST['f1'] !== '')?(int)$_REQUEST['f1']:'';
$f2 = (isset($_REQUEST['f2']) && $_REQUEST['f2'] !== '')?$_REQUEST['f2']:'';
$f3 = (isset($_REQUEST['f3']) && $_REQUEST['f3'] !== '')?$_REQUEST['f3']:'';
$f4 = (isset($_REQUEST['f4']) && $_REQUEST['f4'] !== '')?$_REQUEST['f4']:$myStore;

$filterstring = "";
if ($f1 !== ''){
    $filterstring = sprintf("WHERE statusFlag=%d",$f1);
}
if ($f4) {
    if (empty($filterstring)) {
        $filterstring .= sprintf(' WHERE o.storeID=%d ', $f4);
    } else {
        $filterstring .= sprintf(' AND o.storeID=%d ', $f4);
    }
}

echo '<a href="index.php">Main Menu</a>';
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo "Current Orders";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo sprintf('<a href="historical.php%s">Old Orders</a>',
    (isset($_REQUEST['card_no'])?'?card_no='.$_REQUEST['card_no']:'')
);
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo '<input type="checkbox" id="acbx" onclick="$(\'tr.arrived\').each(function(){$(this).toggle();});" />';
echo '<label for="acbx">Hide Printed</label>';
echo '<p />';

echo "<b>Status</b>: ";
echo '<select id="f_1" onchange="refilter();">';
echo '<option value="">All</option>';
foreach($status as $k=>$v){
    printf("<option %s value=\"%d\">%s</option>",
        ($k===$f1?'selected':''),$k,$v);
}
echo '</select>';
echo '&nbsp;';
echo '<b>Buyer</b>: <select id="f_2" onchange="refilter();">';
echo '<option value="">All</option>';
foreach($assignments as $k=>$v){
    printf("<option %s value=\"%d\">%s</option>",
        ($k==$f2?'selected':''),$k,$v);
}
printf('<option %s value="20">Spices</option>',($f2=="20"?'selected':''));
printf('<option %s value="2%%2C8">Meat+Cool</option>',($f2=="2,8"?'selected':''));
echo '</select>';
echo '&nbsp;';
echo '<b>Supplier</b>: <select id="f_3" onchange="refilter();">';
foreach($suppliers as $v){
    printf("<option %s>%s</option>",
        ($v===$f3?'selected':''),$v);
}
echo '</select>';
echo '&nbsp;';
echo '<b>Store</b>: <select id="f_4" onchange="refilter();">';
foreach($stores as $k => $v){
    printf("<option %s value=\"%s\">%s</option>",
        ($k==$f4?'selected':''),$k, $v);
}
echo '</select>';
echo '<hr />';

if (isset($_REQUEST['card_no']) && is_numeric($_REQUEST['card_no'])){
    if (empty($filterstring))
        $filterstring .= sprintf("WHERE p.card_no=%d",$_REQUEST['card_no']);
    else
        $filterstring .= sprintf(" AND p.card_no=%d",$_REQUEST['card_no']);
    printf('<input type="hidden" id="cardno" value="%d" />',$_REQUEST['card_no']);
}
$order = isset($_REQUEST['order'])?$_REQUEST['order']:'';
printf('<input type="hidden" id="orderSetting" value="%s" />',$order);
if ($order !== '') $order = base64_decode($order);
else $order = 'min(datetime)';

$q = "SELECT min(datetime) as orderDate,p.order_id,sum(total) as value,
    count(*)-1 as items,
    o.statusFlag AS status_flag,
    o.subStatus AS sub_status,
    CASE WHEN MAX(p.card_no)=0 THEN MAX(o.lastName) ELSE MAX(c.LastName) END as name,
    MIN(CASE WHEN trans_type='I' THEN charflag ELSE 'ZZZZ' END) as charflag,
    MAX(p.card_no) AS card_no,
    MAX(s.description) AS storeName
    FROM {$TRANS}PendingSpecialOrder as p
        LEFT JOIN custdata AS c ON c.CardNo=p.card_no AND personNum=p.voided
        LEFT JOIN {$TRANS}SpecialOrders AS o ON p.order_id=o.specialOrderID
        LEFT JOIN Stores AS s ON o.storeID=s.storeID
    $filterstring
    GROUP BY p.order_id,statusFlag,subStatus
    HAVING 
        count(*) > 1 OR
        SUM(CASE WHEN o.notes LIKE '' THEN 0 ELSE 1 END) > 0
    ORDER BY $order";
$r = $dbc->query($q);

$orders = array();
$valid_ids = array();
while($w = $dbc->fetch_row($r)){
    $orders[] = $w;
    $valid_ids[$w['order_id']] = True;
}

if ($f2 !== '' || $f3 !== ''){
    $filter2 = ($f2!==''?sprintf("AND (m.superID IN (%s) OR o.noteSuperID IN (%s))",$f2,$f2):'');
    $supers = ($f2 !== '' ? 'superdepts' : 'MasterSuperDepts');
    $filter3 = ($f3!==''?sprintf("AND p.mixMatch=%s",$dbc->escape($f3)):'');
    $q = "SELECT p.order_id FROM {$TRANS}PendingSpecialOrder AS p
        LEFT JOIN {$supers} AS m ON p.department=m.dept_ID
        LEFT JOIN {$TRANS}SpecialOrders AS o ON p.order_id=o.specialOrderID
        WHERE 1=1 $filter2 $filter3
        GROUP BY p.order_id";
    $r = $dbc->query($q);
    $valid_ids = array();
    while($w = $dbc->fetch_row($r))
        $valid_ids[$w['order_id']] = True;

    if ($f2 !== '' && $f3 === ''){
        $q2 = sprintf("SELECT o.specialOrderID 
                       FROM {$TRANS}SpecialOrders AS o
                        INNER JOIN {$TRANS}PendingSpecialOrder AS p ON p.order_id=o.specialOrderID
                       WHERE o.noteSuperID IN (%d)
                       GROUP BY o.specialOrderID", $f2);
        $r2 = $dbc->query($q2);
        while($w2 = $dbc->fetch_row($r2))
            $valid_ids[$w2['specialOrderID']] = True;
    }
}

$oids = "(";
foreach($valid_ids as $id=>$nonsense)
    $oids .= $id.",";
$oids = rtrim($oids,",").")";

if ($oids == '()') $oids = '(-1)';
$itemsQ = "SELECT order_id,description,mixMatch FROM {$TRANS}PendingSpecialOrder WHERE order_id IN $oids
    AND trans_id > 0";
$itemsR = $dbc->query($itemsQ);
$items = array();
$suppliers = array();
while($itemsW = $dbc->fetch_row($itemsR)){
    if (!isset($items[$itemsW['order_id']]))
        $items[$itemsW['order_id']] = $itemsW['description'];
    else
        $items[$itemsW['order_id']] .= "; ".$itemsW['description'];
    if (!empty($itemsW['mixMatch'])){
        if (!isset($suppliers[$itemsW['order_id']]))
            $suppliers[$itemsW['order_id']] = $itemsW['mixMatch'];
        else
            $suppliers[$itemsW['order_id']] .= "; ".$itemsW['mixMatch'];
    }
}
$lenLimit = 10;
foreach($items as $id=>$desc){
    if (strlen($desc) <= $lenLimit) continue;

    $min = substr($desc,0,$lenLimit);
    $rest = substr($desc,$lenLimit);
    
    $desc = sprintf('%s<span id="exp%d" style="display:none;">%s</span>
            <a href="" onclick="$(\'#exp%d\').toggle();return false;">+</a>',
            $min,$id,$rest,$id);
    $items[$id] = $desc;
}
$lenLimit = 10;
foreach($suppliers as $id=>$desc){
    if (strlen($desc) <= $lenLimit) continue;

    $min = substr($desc,0,$lenLimit);
    $rest = substr($desc,$lenLimit);
    
    $desc = sprintf('%s<span id="sup%d" style="display:none;">%s</span>
            <a href="" onclick="$(\'#sup%d\').toggle();return false;">+</a>',
            $min,$id,$rest,$id);
    $suppliers[$id] = $desc;
}

$ret = '<form id="pdfform" action="tagpdf.php" method="get">';
$ret .= sprintf('<table cellspacing="0" cellpadding="4" border="1">
    <tr>
    <th><a href="" onclick="resort(\'%s\');return false;">Order Date</a></th>
    <th><a href="" onclick="resort(\'%s\');return false;">Store</a></th>
    <th><a href="" onclick="resort(\'%s\');return false;">Name</a></th>
    <th>Desc</th>
    <th>Supplier</th>
    <th><a href="" onclick="resort(\'%s\');return false;">Items</a>
    (<a href="" onclick="resort(\'%s\');return false;">$</a>)</th>
    <th><a href="" onclick="resort(\'%s\');return false;">Status</a></th>
    <th>Printed</th>',
    base64_encode("min(datetime)"),
    base64_encode("MAX(s.description)"),
    base64_encode("CASE WHEN MAX(p.card_no)=0 THEN MAX(o.lastName) ELSE MAX(c.LastName) END"),
    base64_encode("sum(total)"),
    base64_encode("count(*)-1"),
    base64_encode("statusFlag")
);
$ret .= sprintf('<td><img src="%s" alt="Print" 
        onclick="$(\'#pdfform\').submit();" /></td>',
        $FANNIE_URL.'src/img/buttons/action_print.gif');
$ret .= '</tr>';
$fp = fopen($cachepath.$key,"w");
foreach($orders as $w){
    if (!isset($valid_ids[$w['order_id']])) continue;

    $ret .= sprintf('<tr class="%s"><td><a href="view.php?orderID=%d&k=%s">%s</a></td>
        <td>%s</td>
        <td><a href="" onclick="applyMemNum(%d);return false;">%s</a></td>
        <td style="font-size:75%%;">%s</td>
        <td style="font-size:75%%;">%s</td>
        <td align=center>%d (%.2f)</td>',
        ($w['charflag']=='P'?'arrived':'notarrived'),
        $w['order_id'],$key,
        array_shift(explode(' ',$w['orderDate'])),
        $w['storeName'],
        $w['card_no'],$w['name'],
        (isset($items[$w['order_id']])?$items[$w['order_id']]:'&nbsp;'),
        (isset($suppliers[$w['order_id']])?$suppliers[$w['order_id']]:'&nbsp;'),
        $w['items'],$w['value']);
    $ret .= '<td><select id="s_status" onchange="updateStatus('.$w['order_id'].',$(this).val());">';
    foreach($status as $k=>$v){
        $ret .= sprintf('<option %s value="%d">%s</option>',
            ($w['status_flag']==$k?'selected':''),
            $k,$v);
    }
    $ret .= "</select> <span id=\"statusdate{$w['order_id']}\">".($w['sub_status']==0?'No Date':date('m/d/Y',$w['sub_status']))."</span></td>";
    $ret .= "<td align=center>".($w['charflag']=='P'?'Yes':'No')."</td>";
    $ret .= sprintf('<td><input type="checkbox" %s name="oids[]" value="%d" 
            onclick="togglePrint(\'%s\',%d);" /></td></tr>',
            (isset($prints[$w['order_id']])?'checked':''),
            $w['order_id'],$username,$w['order_id']);
    fwrite($fp,$w['order_id']."\n");
}
fclose($fp);
$ret .= "</table>";

echo $ret;
?>
<script type="text/javascript">
function refilter(){
    var f1 = $('#f_1').val();
    var f2 = $('#f_2').val();
    var f3 = $('#f_3').val();
    var f4 = $('#f_4').val();

    var loc = 'clearinghouse.php?f1='+f1+'&f2='+f2+'&f3='+f3+'&f4='+f4;
    if ($('#cardno').length!=0)
        loc += '&card_no='+$('#cardno').val();
    if ($('#orderSetting').length!=0)
        loc += '&order='+$('#orderSetting').val();
    
    location = loc;
}
function resort(o){
    $('#orderSetting').val(o);
    refilter();
}
function applyMemNum(n){
    if ($('#cardno').length==0) 
        $('#bodytag').append('<input type="hidden" id="cardno" />');
    $('#cardno').val(n);
    refilter();
}
function updateStatus(oid,val){
    $.ajax({
    url: 'OrderAjax.php',
    type: 'post',
    data: 'id='+oid+'&status='+val,
    dataType: 'json',
    cache: false,
    success: function(resp){
        $('#statusdate'+oid).html(resp.tdate);
    }
    });
}
function togglePrint(username,oid){
    $.ajax({
        url: 'OrderViewPage.php',
        type: 'post',
        data: 'orderID='+oid+'&togglePrint=1',
        cache: false
    });
}
</script>
<?php
//include(__DIR__ . '/../src/footer.html');

<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
$dbc = FannieDB::get($FANNIE_OP_DB);
$TRANS = ($FANNIE_SERVER_DBMS == "MSSQL") ? $FANNIE_TRANS_DB.".dbo." : $FANNIE_TRANS_DB.".";

include($FANNIE_ROOT.'auth/login.php');
$username = checkLogin();
if (!$username){
    $url = $FANNIE_URL."auth/ui/loginform.php";
    $rd = $FANNIE_URL."ordering/historical.php";
    header("Location: $url?redirect=$rd");
    exit;
}

$page_title = "Special Order :: Management";
$header = "Manage Special Orders";
if (isset($_REQUEST['card_no']) && is_numeric($_REQUEST['card_no'])){
    $header = "Special Orders for Member #".((int)$_REQUEST['card_no']);
}
//include($FANNIE_ROOT.'src/header.html');
echo '<html>
    <head><title>'.$page_title.'</title>
    <link rel="STYLESHEET" href="'.$FANNIE_URL.'src/style.css" type="text/css">
    <link rel="STYLESHEET" href="'.$FANNIE_URL.'src/javascript/jquery-ui.css" type="text/css">
    <script type="text/javascript" src="'.$FANNIE_URL.'src/javascript/jquery.js">
    </script>
    <script type="text/javascript" src="'.$FANNIE_URL.'src/javascript/jquery-ui.js">
    </script>
    </head>
    <body id="bodytag">';
echo '<h3>'.$header.'</h3>';
if (isset($_REQUEST['card_no'])){
    printf('(<a href="historical.php?f1=%s&f2=%s&f3=%s&order=%s">Back to All Owners</a>)<br />',
        (isset($_REQUEST['f1'])?$_REQUEST['f1']:''),    
        (isset($_REQUEST['f2'])?$_REQUEST['f2']:''),    
        (isset($_REQUEST['f3'])?$_REQUEST['f3']:''),    
        (isset($_REQUEST['order'])?$_REQUEST['order']:'')
    );
}

$status = array(
    0 => "New",
    3 => "New, Call",
    1 => "Called/waiting",
    2 => "Pending",
    4 => "Placed",
    5 => "Arrived",
    7 => "Completed",
    8 => "Canceled",
    9 => "Inquiry"
);

$assignments = array();
$q = $dbc->prepare_statement("SELECT superID,super_name FROM MasterSuperDepts
    GROUP BY superID,super_name ORDER BY superID");
$r = $dbc->exec_statement($q);
while($w = $dbc->fetch_row($r))
    $assignments[$w[0]] = $w[1];
unset($assignments[0]); 

$suppliers = array('');
$q = $dbc->prepare_statement("SELECT mixMatch FROM {$TRANS}CompleteSpecialOrder WHERE trans_type='I'
    GROUP BY mixMatch ORDER BY mixMatch");
$r = $dbc->exec_statement($q);
while($w = $dbc->fetch_row($r)){
    $suppliers[] = $w[0];
}

$f1 = (isset($_REQUEST['f1']) && $_REQUEST['f1'] !== '')?(int)$_REQUEST['f1']:'';
$f2 = (isset($_REQUEST['f2']) && $_REQUEST['f2'] !== '')?$_REQUEST['f2']:'';
$f3 = (isset($_REQUEST['f3']) && $_REQUEST['f3'] !== '')?$_REQUEST['f3']:'';

$filterstring = "";
$filterargs = array();
if ($f1 !== ''){
    $filterstring = 'WHERE statusFlag=?';
    $filterargs[] = $f1;
}

echo '<a href="index.php">Main Menu</a>';
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo sprintf('<a href="clearinghouse.php%s">Current Orders</a>',
    (isset($_REQUEST['card_no'])?'?card_no='.$_REQUEST['card_no']:'')
);
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo "Old Orders";
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
printf('<option %s value="2%%2C8">Meat+Cool</option>',($f2=="2,8"?'selected':''));
echo '</select>';
echo '&nbsp;';
echo '<b>Supplier</b>: <select id="f_3" onchange="refilter();">';
foreach($suppliers as $v){
    printf("<option %s>%s</option>",
        ($v===$f3?'selected':''),$v);
}
echo '</select>';
echo '<hr />';

if (isset($_REQUEST['card_no']) && is_numeric($_REQUEST['card_no'])){
    if (empty($filterstring)){
        $filterstring .= "WHERE p.card_no=?";
    }
    else{
        $filterstring .= " AND p.card_no=?";
    }
    $filterargs[] = $_REQUEST['card_no'];
    printf('<input type="hidden" id="cardno" value="%d" />',$_REQUEST['card_no']);
}
$page = isset($_REQUEST['page'])?$_REQUEST['page']:1;
$page = (int)$page;
$order = isset($_REQUEST['order'])?$_REQUEST['order']:'';
printf('<input type="hidden" id="orderSetting" value="%s" />',$order);
$orderby = 'min(datetime) desc';
if ($order === 'date')
    $orderby = "min(datetime)";
elseif($order === 'name')
    $orderby = "CASE WHEN MAX(p.card_no)=0 THEN MAX(o.lastName) ELSE MAX(c.LastName) END";
elseif($order === 'ttl')
    $orderby = "sum(total)";
elseif($order === 'qty')
    $orderby = "count(*)-1";
elseif($order === 'status')
    $orderby = "statusFlag";

$p = $dbc->prepare_statement("SELECT min(datetime) as orderDate,p.order_id,sum(total) as value,
    count(*)-1 as items,
    statusFlag AS status_flag,
    subStatus AS sub_status,
    CASE WHEN MAX(p.card_no)=0 THEN MAX(o.lastName) ELSE MAX(c.LastName) END as name,
    MIN(CASE WHEN trans_type='I' THEN charflag ELSE 'ZZZZ' END) as charflag,
    MAX(p.card_no) AS card_no
    FROM {$TRANS}CompleteSpecialOrder as p
    LEFT JOIN custdata AS c ON c.CardNo=p.card_no AND personNum=p.voided
    LEFT JOIN {$TRANS}SpecialOrders AS o ON p.order_id=o.specialOrderID
    $filterstring
    GROUP BY p.order_id,statusFlag,subStatus
    HAVING (count(*) > 1 OR
        SUM(CASE WHEN o.notes LIKE '' THEN 0 ELSE 1 END) > 0
        )
    AND ".$dbc->monthdiff($dbc->now(),'min(datetime)')." >= ((?-1)*3)
    AND ".$dbc->monthdiff($dbc->now(),'min(datetime)')." < (?*3)
    ORDER BY $orderby");
$filterargs[] = $page;
$filterargs[] = $page; // again
$r = $dbc->exec_statement($p,$filterargs);

$orders = array();
$valid_ids = array();
while($w = $dbc->fetch_row($r)){
    $orders[] = $w;
    $valid_ids[$w['order_id']] = True;
}

if ($f2 !== '' || $f3 !== ''){
    $filter = "";
    $args = array();
    if ($f2 !== ''){
        $filter .= "AND (m.superID IN (?) OR o.noteSuperID IN (?))";
        $args = array($f2,$f2);
    }
    if ($f3 !== ''){
        $filter .= "AND p.mixMatch=?";
        $args[] = $f3;
    }
    $p = $dbc->prepare_statement("SELECT p.order_id FROM {$TRANS}CompleteSpecialOrder AS p
        LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
        LEFT JOIN {$TRANS}SpecialOrders AS o ON p.order_id=o.specialOrderID
        WHERE 1=1 $filter
        GROUP BY p.order_id");
    $r = $dbc->exec_statement($p,$args);
    $valid_ids = array();
    while($w = $dbc->fetch_row($r))
        $valid_ids[$w['order_id']] = True;

    if ($f2 !== '' && $f3 === ''){
        $q2 = $dbc->prepare_statement("SELECT o.specialOrderID FROM 
                {$TRANS}SpecialOrders AS o
                INNER JOIN {$TRANS}CompleteSpecialOrder AS p
                ON p.order_id=o.specialOrderID
                WHERE o.noteSuperID IN (?)
                GROUP BY o.specialOrderID");
        $r2 = $dbc->exec_statement($q2, array($f2));
        while($w2 = $dbc->fetch_row($r2))
            $valid_ids[$w2['specialOrderID']] = True;
    }
}

$oids = "(";
$oargs = array();
foreach($valid_ids as $id=>$nonsense){
    $oids .= "?,";
    $oargs[] = $id;
}
$oids = rtrim($oids,",").")";
if (empty($oargs)){
    $oids = '(?)';
    $oargs = array(-1);
    // avoid invalid query
}

$itemsQ = $dbc->prepare_statement("SELECT order_id,description,mixMatch FROM 
    {$TRANS}CompleteSpecialOrder WHERE order_id IN $oids
    AND trans_id > 0");
$itemsR = $dbc->exec_statement($itemsQ, $oargs);
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

$ret = '<table cellspacing="0" cellpadding="4" border="1">
    <tr>
    <th><a href="" onclick="resort(\'date\');return false;">Order Date</a></th>
    <th><a href="" onclick="resort(\'name\');return false;">Name</a></th>
    <th>Desc</th>
    <th>Supplier</th>
    <th><a href="" onclick="resort(\'qty\');return false;">Items</a>
    (<a href="" onclick="resort(\'ttl\');return false;">$</a>)</th>
    <th><a href="" onclick="resort(\'status\');return false;">Status</a></th>
    </tr>';
$key = "";
foreach($orders as $w){
    if (!isset($valid_ids[$w['order_id']])) continue;

    $ret .= sprintf('<tr class="%s"><td><a href="review.php?orderID=%d&k=%s">%s</a></td>
        <td><a href="" onclick="applyMemNum(%d);return false;">%s</a></td>
        <td style="font-size:75%%;">%s</td>
        <td style="font-size:75%%;">%s</td>
        <td align=center>%d (%.2f)</td>',
        ($w['charflag']=='P'?'arrived':'notarrived'),
        $w['order_id'],$key,
        array_shift(explode(' ',$w['orderDate'])),
        $w['card_no'],$w['name'],
        (isset($items[$w['order_id']])?$items[$w['order_id']]:'&nbsp;'),
        (isset($suppliers[$w['order_id']])?$suppliers[$w['order_id']]:'&nbsp;'),
        $w['items'],$w['value']);
    $ret .= '<td>';
    foreach($status as $k=>$v){
        if ($w['status_flag']==$k) $ret .= $v;
    }
    $ret .= " <span id=\"statusdate{$w['order_id']}\">".($w['sub_status']==0?'No Date':date('m/d/Y',$w['sub_status']))."</span></td></tr>";
}
$ret .= "</table>";

$url = $_SERVER['REQUEST_URI'];
if (!strstr($url,"page=")){
    if (substr($url,-4)==".php")
        $url .= "?page=".$page;
    else
        $url .= "&page=".$page;
}
if ($page > 1){
    $prev = $page-1;
    $prev_url = preg_replace('/page=\d+/','page='.$prev,$url);
    $ret .= sprintf('<a href="%s">Previous</a>&nbsp;&nbsp;||&nbsp;&nbsp;',
            $prev_url);
}
$next = $page+1;
$next_url = preg_replace('/page=\d+/','page='.$next,$url);
$ret .= sprintf('<a href="%s">Next</a>',$next_url);

echo $ret;
?>
<script type="text/javascript">
function refilter(){
    var f1 = $('#f_1').val();
    var f2 = $('#f_2').val();
    var f3 = $('#f_3').val();

    var loc = 'historical.php?f1='+f1+'&f2='+f2+'&f3='+f3;
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
    url: 'ajax-calls.php',
    type: 'post',
    data: 'action=UpdateStatus&orderID='+oid+'&val='+val,
    cache: false,
    success: function(resp){
        $('#statusdate'+oid).html(resp);    
    }
    });
}
function togglePrint(username,oid){
    $.ajax({
    url: 'ajax-calls.php',
    type: 'post',
    data: 'action=UpdatePrint&orderID='+oid+'&user='+username,
    cache: false,
    success: function(resp){}
    });
}
</script>
<?php
//include($FANNIE_ROOT.'src/footer.html');
?>

<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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

/* HELP
 
   nightly.specialorder.php

   This script checks for special order items that have
   been picked up and moves them from PendingSpecialOrder
   to CompleteSpecialOrder

*/

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}
if (!function_exists('cron_msg')) {
    include(__DIR__ . '/../src/cron_msg.php');
}
if (!class_exists('SoPoBridge')) {
    include(__DIR__ . '/../ordering/SoPoBridge.php');
}

set_time_limit(0);

// clean cache
$cachepath = sys_get_temp_dir()."/ordercache/";
if (file_exists($cachepath)) {
    $dh = opendir($cachepath);
    while (($file = readdir($dh)) !== false) {
        if ($file == "." || $file == "..") continue;
        if (!is_file($cachepath.$file)) continue;
        unlink($cachepath.$file);
    }
    closedir($dh);
}

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
$bridge = new SoPoBridge($sql, FannieConfig::factory());

// auto-close called/waiting after 30 days
$subquery = "select p.order_id from PendingSpecialOrder as p
    left join SpecialOrders as s
    on p.order_id=s.specialOrderID
    where p.trans_id=0 and s.statusFlag=1
    and ".$sql->datediff($sql->now(),'datetime')." > 30";
$cwIDs = "(";
$r = $sql->query($subquery);
while($w = $sql->fetch_row($r)){
    $cwIDs .= $w['order_id'].",";
}
$cwIDs = rtrim($cwIDs,",").")";
if (strlen($cwIDs) > 2){
    // transfer to completed orders
    $copyQ = "INSERT INTO CompleteSpecialOrder
        SELECT p.* FROM PendingSpecialOrder AS p
        WHERE p.order_id IN $cwIDs";
    $copyR = $sql->query($copyQ);

    $itemQ = "SELECT s.storeID, p.order_id, p.trans_id
        FROM PendingSpecialOrder AS p
            LEFT JOIN SpecialOrders AS s ON p.order_id=s.specialOrderID
        WHERE p.trans_id > 0
            AND p.order_id IN {$cwIDs}";
    $itemR = $sql->query($itemQ);
    while ($itemW = $sql->fetchRow($itemR)) {
        $bridge->removeItemFromPurchaseOrder($itemW['order_id'], $itemW['trans_id']);
    }

    // make note in history table
    $historyQ = "INSERT INTO SpecialOrderHistory
                (order_id, entry_date, entry_type, entry_value)
                SELECT p.order_id,
                    " . $sql->now() . ",
                    'AUTOCLOSE',
                    'Call/Waiting 30'
                FROM PendingSpecialOrder AS p
                WHERE p.order_id IN $cwIDs
                GROUP BY p.order_id";
    $sql->query($historyQ);

    // clear from pending
    $sql->query($copyQ);
    $delQ = "DELETE FROM PendingSpecialOrder
        WHERE order_id IN $cwIDs";
    $sql->query($delQ);
}
// end auto-close

// auto-close all after 90 days
$subquery = "select p.order_id from PendingSpecialOrder as p
    left join SpecialOrders as s
    on p.order_id=s.specialOrderID
    where p.trans_id=0 
    and ".$sql->datediff($sql->now(),'datetime')." > 90";
$allIDs = "(";
$r = $sql->query($subquery);
while($w = $sql->fetch_row($r)){
    $allIDs .= $w['order_id'].",";
}
$allIDs = rtrim($allIDs,",").")";
if (strlen($allIDs) > 2){
    // copy to completed orders
    $copyQ = "INSERT INTO CompleteSpecialOrder
        SELECT p.* FROM PendingSpecialOrder AS p
        WHERE p.order_id IN $allIDs";
    $sql->query($copyQ);

    $itemQ = "SELECT s.storeID, p.order_id, p.trans_id
        FROM PendingSpecialOrder AS p
            LEFT JOIN SpecialOrders AS s ON p.order_id=s.specialOrderID
        WHERE p.trans_id > 0
            AND p.order_id IN {$allIDs}";
    $itemR = $sql->query($itemQ);
    while ($itemW = $sql->fetchRow($itemR)) {
        $bridge->removeItemFromPurchaseOrder($itemW['order_id'], $itemW['trans_id']);
    }

    // make note in history table
    $historyQ = "INSERT INTO SpecialOrderHistory
                (order_id, entry_date, entry_type, entry_value)
                SELECT p.order_id,
                    " . $sql->now() . ",
                    'AUTOCLOSE',
                    '90 Days'
                FROM PendingSpecialOrder AS p
                WHERE p.order_id IN $allIDs
                GROUP BY p.order_id";
    $sql->query($historyQ);

    // remove from pending orders
    $delQ = "DELETE FROM PendingSpecialOrder
        WHERE order_id IN $allIDs";
    $sql->query($delQ);
}
// end auto-close

$query = "SELECT CASE WHEN matched > 10 THEN matched ELSE mixMatch END as mixMatch,
            CASE WHEN matched > 10 THEN mixMatch ELSE matched END AS matched,
            MAX(datetime) as tdate,
            MAX(emp_no) as emp,
            MAX(register_no) AS reg,
            MAX(trans_no) AS trans 
          FROM transarchive
          WHERE charflag='SO' 
            AND emp_no <> 9999 
            AND register_no <> 99 
            AND trans_status NOT IN ('X','Z')
          GROUP BY mixMatch,matched
          HAVING sum(total) <> 0";
$result = $sql->query($query);

$checkP = $sql->prepare("SELECT order_id
                         FROM SpecialOrderHistory
                         WHERE order_id=?
                            AND entry_type='PURCHASED'
                            AND entry_date=?
                            AND entry_value=?");
$historyP = $sql->prepare("INSERT INTO SpecialOrderHistory
                            (order_id, entry_date, entry_type, entry_value)
                           VALUES
                            (?, ?, 'PURCHASED', ?)");

$order_ids = array();
$trans_ids = array();
while($row = $sql->fetch_row($result)) {
    $order_ids[] = (int)$row['mixMatch'];
    $trans_ids[] = (int)$row['matched'];

    // log to history if entry doesn't already exist
    $args = array(
        (int)$row['mixMatch'],
        $row['tdate'],
        $row['emp'] . '-' . $row['reg'] . '-' . $row['trans'],
    );
    $checkR = $sql->execute($checkP, $args);
    if ($checkR && $sql->num_rows($checkR) == 0) {
        $sql->execute($historyP, $args);
    }
}

$where = "( ";
for($i=0;$i<count($order_ids);$i++){
    $where .= "(order_id=".$order_ids[$i]." AND trans_id=".$trans_ids[$i].") ";
    if ($i < count($order_ids)-1)
        $where .= " OR ";
}
$where .= ")";

echo cron_msg("Found ".count($order_ids)." order items");

// copy item rows to completed and delete from pending
$copyQ = "INSERT INTO CompleteSpecialOrder SELECT * FROM PendingSpecialOrder WHERE $where";
$copyR = $sql->query($copyQ);
$delQ = "DELETE FROM PendingSpecialOrder WHERE $where";
$delR = $sql->query($delQ);

$chkQ = "SELECT * FROM PendingSpecialOrder WHERE $where";
$chkR = $sql->query($chkQ);
echo cron_msg("Missed on ".$sql->num_rows($chkR)." items");

// the trans_id=0 line contains additional, non-item order info
// this determines where applicable trans_id=0 lines have already
// been copied to CompletedSpecialOrder
// this could occur if the order contained multiple items picked up
// over multiple days
$oids = "(";
foreach($order_ids as $o)
    $oids .= $o.",";
$oids = rtrim($oids,",").")";
$checkQ = "SELECT order_id FROM CompleteSpecialOrder WHERE trans_id=0 AND order_id IN $oids";
$checkR = $sql->query($checkQ);
$done_oids = array();
while($row = $sql->fetch_row($checkR))
    $done_oids[] = (int)$row['order_id'];
$todo = array_diff($order_ids,$done_oids);

echo cron_msg("Found ".count($todo)." new order headers");

if (count($todo) > 0){
    $copy_oids = "(";
    foreach($todo as $o)
        $copy_oids .= $o.",";
    $copy_oids = rtrim($copy_oids,",").")";
    //echo "Headers: ".$copy_oids."\n";
    $copyQ = "INSERT INTO CompleteSpecialOrder SELECT * FROM PendingSpecialOrder
        WHERE trans_id=0 AND order_id IN $copy_oids";
    $copyR = $sql->query($copyQ);
}

// remove "empty" orders from pending
$cleanupQ = sprintf("
    SELECT p.order_id 
    FROM PendingSpecialOrder AS p 
        LEFT JOIN SpecialOrders AS o ON p.order_id=o.specialOrderID
    WHERE p.deleted=0 AND (
        (
            o.specialOrderID IS NULL
            OR %s(o.notes)=0
        )
        OR p.order_id IN (
            SELECT order_id FROM CompleteSpecialOrder
            WHERE trans_id=0
            GROUP BY order_id
        )
    )
    GROUP BY p.order_id
    HAVING MAX(trans_id)=0",
($FANNIE_SERVER_DBMS=="MSSQL" ? 'datalength' : 'length'));
$cleanupR = $sql->query($cleanupQ);
$empty = "(";
$clean=0;
while($row = $sql->fetch_row($cleanupR)){
    $empty .= $row['order_id'].",";
    $clean++;
}
$empty = rtrim($empty,",").")";

echo cron_msg("Finishing $clean orders");

if (strlen($empty) > 2){
    //echo "Empties: ".$empty."\n";
    $delQ = "DELETE FROM PendingSpecialOrder WHERE order_id IN $empty AND trans_id=0";
    $delR = $sql->query($delQ);
}

$superIDs = array(3, 6);
foreach ($superIDs as $superID) {
    $yesterday = date('Y-m-d', strtotime('yesterday'));

    $prep = $sql->prepare("SELECT order_id FROM PendingSpecialOrder WHERE trans_id=0
        AND datetime BETWEEN ? AND ?");
    $oIDs = $sql->getAllValues($prep, array($yesterday, $yesterday . ' 23:59:59'));

    list($inStr, $args) = $sql->safeInClause($oIDs, array($superID));
    $prep = $sql->prepare("SELECT specialOrderID FROM SpecialOrders
        WHERE noteSuperID=? AND specialOrderID IN ({$inStr})");
    $matches = $sql->getAllValues($prep, $args);

    list($inStr, $args) = $sql->safeInClause($oIDs, array($superID));
    $prep = $sql->prepare("SELECT order_id FROM PendingSpecialOrder AS p
        INNER JOIN " . FannieDB::fqn('MasterSuperDepts', 'op') . " AS m ON p.department=m.dept_ID
        WHERE m.superID = ? AND p.order_id IN ({$inStr})");
    $matches2 = $sql->getAllValues($prep, $args);

    $all = array_merge($matches, $matches2);

    list($inStr, $args) = $sql->safeInClause($all);
    $prep = $sql->prepare("SELECT specialOrderID, o.storeID, s.description FROM SpecialOrders AS o
        LEFT JOIN " . FannieDB::fqn('Stores', 'op') . " AS s ON s.storeID = o.storeID
        WHERE specialOrderID IN ({$inStr})
        GROUP BY specialOrderID, o.storeID");
    $orders = $sql->getAllRows($prep, $args);
    foreach ($orders as $row) {
        $storeName = $row['description'];
        $addrP = $sql->prepare("SELECT emailAddress FROM " . FannieDB::fqn('superDeptEmails', 'op') . " WHERE superID=?");
        $addr = $sql->getValue($addrP, array($superID));
        $msg_body = 'New Special Order' . "\n\n";
        $msg_body .= "http://" . FannieConfig::config('HTTP_HOST') . FannieConfig::config('URL')
            . "ordering/OrderViewPage.php?orderID=".$row['specialOrderID']."\n\n";
        $subject = "New Special Order $storeName";
        mail($addr,$subject,$msg_body);
    }
}


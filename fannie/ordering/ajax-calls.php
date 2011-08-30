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
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/tmp_dir.php');
include($FANNIE_ROOT.'auth/login.php');

$canEdit = false;
if (validateUserQuiet('ordering_edit'))
	$canEdit = true;

if (!isset($_REQUEST['action'])) exit;
$orderID = isset($_REQUEST['orderID'])?(int)$_REQUEST['orderID']:'';

switch ($_REQUEST['action']){
case 'loadCustomer':
	if (isset($_REQUEST['nonForm']))
		echo getCustomerNonForm($orderID);
	else
		echo getCustomerForm($orderID);
	break;
case 'reloadMem':
	echo getCustomerForm($orderID,$_REQUEST['memNum']);
	break;
case 'loadItems':
	if (isset($_REQUEST['nonForm']))
		echo getItemNonForm($orderID);
	else
		echo getItemForm($orderID);
	break;
case 'newUPC':
	$qty = is_numeric($_REQUEST['cases'])?(int)$_REQUEST['cases']:1;
	$result = addUPC($orderID,$_REQUEST['memNum'],$_REQUEST['upc'],$qty);
	if (!is_numeric($_REQUEST['upc']))
		echo getDeptForm($orderID,$result[1],$result[2]);
	else if ($result[0] === False)
		echo getItemForm($orderID);
	else 
		echo getQtyForm($orderID,$result[0],$result[1],$result[2]);
	break;
case 'deleteID':
	$delQ = sprintf("DELETE FROM PendingSpecialOrder WHERE order_id=%d
		AND trans_id=%d",$_REQUEST['orderID'],$_REQUEST['transID']);
	$delR = $dbc->query($delQ);
	echo getItemForm($_REQUEST['orderID']);
	break;
case 'deleteUPC':
	$upc = str_pad($_REQUEST['upc'],13,'0',STR_PAD_LEFT);
	$delQ = sprintf("DELETE FROM PendingSpecialOrder WHERE order_id=%d
		AND upc=%s",$_REQUEST['orderID'],$dbc->escape($upc));
	$delR = $dbc->query($delQ);
	echo getItemForm($_REQUEST['orderID']);
	break;
case 'saveDesc':
	$desc = $_REQUEST['desc'];
	$desc = rtrim($desc,' SO');
	$desc = substr($desc,0,32)." SO";
	$upQ = sprintf("UPDATE PendingSpecialOrder SET
		description=%s WHERE order_id=%d AND trans_id=%d",
		$dbc->escape($desc),$_REQUEST['orderID'],
		$_REQUEST['transID']);
	$dbc->query($upQ);
	break;
case 'saveCtC':
	$upQ = sprintf("UPDATE PendingSpecialOrder SET
		numflag=%d WHERE order_id=%d AND trans_id=0",
		$_REQUEST['val'],$_REQUEST['orderID']);
	$dbc->query($upQ);
	if ($_REQUEST['val'] == 1){
		$statusQ = sprintf("UPDATE SpecialOrderStatus SET status_flag=3,sub_status=%d
			WHERE order_id=%d AND status_flag in (0,3)",
			time(),$_REQUEST['orderID']);
		$dbc->query($statusQ);
	}
	else if ($_REQUEST['val'] == 0){
		$statusQ = sprintf("UPDATE SpecialOrderStatus SET status_flag=0,sub_status=%d
			WHERE order_id=%d AND status_flag in (0,3)",
			time(),$_REQUEST['orderID']);
		$dbc->query($statusQ);
	}
	break;
case 'savePrice':
	$upQ = sprintf("UPDATE PendingSpecialOrder SET
		total=%f WHERE order_id=%d AND trans_id=%d",
		$_REQUEST['price'],$_REQUEST['orderID'],
		$_REQUEST['transID']);
	$dbc->query($upQ);
	$fetchQ = sprintf("SELECT ROUND(100*((regPrice-total)/regPrice),0)
		FROM PendingSpecialOrder WHERE trans_id=%d AND order_id=%d",
		$_REQUEST['transID'],$_REQUEST['orderID']);
	$fetchR = $dbc->query($fetchQ);
	echo array_pop($dbc->fetch_row($fetchR));
	break;
case 'saveSRP':
	$srp = $_REQUEST['srp'];
	if (strstr($srp,'*')){
		$tmp = explode('*',$srp);
		$srp = 1;
		foreach($tmp as $t) $srp *= $t;
	}
	
	$upQ = sprintf("UPDATE PendingSpecialOrder SET
		regPrice=%f WHERE order_id=%d AND trans_id=%d",
		$srp,$_REQUEST['orderID'],
		$_REQUEST['transID']);
	$info = reprice($_REQUEST['orderID'],$_REQUEST['transID'],$srp);
	$fetchQ = sprintf("SELECT ROUND(100*((regPrice-total)/regPrice),0)
		FROM PendingSpecialOrder WHERE trans_id=%d AND order_id=%d",
		$_REQUEST['transID'],$_REQUEST['orderID']);
	$fetchR = $dbc->query($fetchQ);
	echo array_pop($dbc->fetch_row($fetchR));
	echo '`'.$info['regPrice'].'`'.$info['total'];
	break;
case 'saveQty':
	$upQ = sprintf("UPDATE PendingSpecialOrder SET
		quantity=%f WHERE order_id=%d AND trans_id=%d",
		$_REQUEST['qty'],$_REQUEST['orderID'],
		$_REQUEST['transID']);
	$dbc->query($upQ);
	$info = reprice($_REQUEST['orderID'],$_REQUEST['transID']);
	echo $info['regPrice'].'`'.$info['total'];
	break;
case 'saveUnit':
	$upQ = sprintf("UPDATE PendingSpecialOrder SET
		unitPrice=%f WHERE order_id=%d AND trans_id=%d",
		$_REQUEST['unitPrice'],$_REQUEST['orderID'],
		$_REQUEST['transID']);
	$dbc->query($upQ);
	$info = reprice($_REQUEST['orderID'],$_REQUEST['transID']);
	echo $info['regPrice'].'`'.$info['total'];
	break;
case 'newQty':
	$upQ = sprintf("UPDATE PendingSpecialOrder SET
		quantity=%f WHERE order_id=%d AND trans_id=%d",
		$_REQUEST['qty'],$_REQUEST['orderID'],
		$_REQUEST['transID']);
	$dbc->query($upQ);
	$info = reprice($_REQUEST['orderID'],$_REQUEST['transID']);
	echo getItemForm($_REQUEST['orderID']);
	break;
case 'newDept':
	$upQ = sprintf("UPDATE PendingSpecialOrder SET
		department=%d WHERE order_id=%d AND trans_id=%d",
		$_REQUEST['dept'],$_REQUEST['orderID'],
		$_REQUEST['transID']);
	$dbc->query($upQ);
	echo getItemForm($_REQUEST['orderID']);
	break;
case 'saveDept':
	$upQ = sprintf("UPDATE PendingSpecialOrder SET
		department=%d WHERE order_id=%d AND trans_id=%d",
		$_REQUEST['dept'],$_REQUEST['orderID'],
		$_REQUEST['transID']);
	$dbc->query($upQ);
	break;
case 'saveVendor':
	$upQ = sprintf("UPDATE PendingSpecialOrder SET
		mixMatch=%s WHERE order_id=%d AND trans_id=%d",
		$dbc->escape($_REQUEST['vendor']),
		$_REQUEST['orderID'],
		$_REQUEST['transID']);
	$dbc->query($upQ);
	break;
case 'saveAddr':
	if (canSaveAddress($orderID) == True){
		$addr = $_REQUEST['addr1'];
		if (!empty($_REQUEST['addr2']))
			$addr .= "\n".$_REQUEST['addr2'];
		$dbc->query(sprintf("UPDATE SpecialOrderContact
			SET street=%s WHERE card_no=%d",
			$dbc->escape($addr),$orderID));
	}
	break;
case 'saveFN':
	if (canSaveAddress($orderID) == True){
		$dbc->query(sprintf("UPDATE SpecialOrderContact
			SET first_name=%s WHERE card_no=%d",
			$dbc->escape($_REQUEST['fn']),$orderID));
	}
	break;
case 'saveLN':
	if (canSaveAddress($orderID) == True){
		$dbc->query(sprintf("UPDATE SpecialOrderContact
			SET last_name=%s WHERE card_no=%d",
			$dbc->escape($_REQUEST['ln']),$orderID));
	}
	break;
case 'saveCity':
	if (canSaveAddress($orderID) == True){
		$dbc->query(sprintf("UPDATE SpecialOrderContact
			SET city=%s WHERE card_no=%d",
			$dbc->escape($_REQUEST['city']),$orderID));
	}
	break;
case 'saveState':
	if (canSaveAddress($orderID) == True){
		$dbc->query(sprintf("UPDATE SpecialOrderContact
			SET state=%s WHERE card_no=%d",
			$dbc->escape($_REQUEST['state']),$orderID));
	}
	break;
case 'saveZip':
	if (canSaveAddress($orderID) == True){
		$dbc->query(sprintf("UPDATE SpecialOrderContact
			SET zip=%s WHERE card_no=%d",
			$dbc->escape($_REQUEST['zip']),$orderID));
	}
	break;
case 'savePh':
	if (canSaveAddress($orderID) == True){
		$dbc->query(sprintf("UPDATE SpecialOrderContact
			SET phone=%s WHERE card_no=%d",
			$dbc->escape($_REQUEST['ph']),$orderID));
	}
	break;
case 'savePh2':
	if (canSaveAddress($orderID) == True){
		$dbc->query(sprintf("UPDATE SpecialOrderContact
			SET email_2=%s WHERE card_no=%d",
			$dbc->escape($_REQUEST['ph2']),$orderID));
	}
	break;
case 'saveEmail':
	if (canSaveAddress($orderID) == True){
		$dbc->query(sprintf("UPDATE SpecialOrderContact
			SET email_1=%s WHERE card_no=%d",
			$dbc->escape($_REQUEST['email']),$orderID));
	}
	break;
case 'UpdateStatus':
	$q = sprintf("UPDATE SpecialOrderStatus SET
		status_flag=%d,sub_status=%d WHERE order_id=%d",
		$_REQUEST['val'],time(),$orderID);
	$dbc->query($q);
	echo date("m/d/Y");
	break;
case 'saveNoteDept':
	$q = sprintf("UPDATE SpecialOrderNotes SET
		superID=%d WHERE order_id=%d",
		$_REQUEST['val'],
		$orderID);
	$dbc->query($q);
	break;
case 'saveText':
	$q = sprintf("UPDATE SpecialOrderNotes SET
		notes=%s WHERE order_id=%d",
		$dbc->escape($_REQUEST['val']),
		$orderID);
	$dbc->query($q);
	break;
case 'confirmOrder':
	$q = sprintf("INSERT INTO SpecialOrderHistory VALUES
		(%d,'CONFIRMED',%s,'')",$_REQUEST['orderID'],
		$dbc->now());
	$dbc->query($q);
	echo date("M j Y g:ia");
	break;
case 'unconfirmOrder':
	$q = sprintf("DELETE FROM SpecialOrderHistory WHERE
		order_id=%d AND entry_type='CONFIRMED'",
		$_REQUEST['orderID']);
	$dbc->query($q);
	break;
case 'savePN':
	$v = (int)$_REQUEST['val'];
	if ($v == 0) $v = 1;
	$q = sprintf("UPDATE PendingSpecialOrder SET
		voided=%d WHERE order_id=%d",$v,
		$_REQUEST['orderID']);
	$dbc->query($q);
	break;
case 'closeOrder':
	$q = sprintf("UPDATE SpecialOrderStatus SET
		status_flag=%d WHERE order_id=%d",
		$_REQUEST['status'],$_REQUEST['orderID']);
	$dbc->query($q);

	$moveQ = sprintf("INSERT INTO CompleteSpecialOrder
			SELECT * FROM PendingSpecialOrder
			WHERE order_id=%d",$_REQUEST['orderID']);
	$dbc->query($moveQ);
	
	$cleanQ = sprintf("DELETE FROM PendingSpecialOrder
			WHERE order_id=%d",$_REQUEST['orderID']);
	$dbc->query($cleanQ);
	break;
case 'copyOrder':
	$oid = sprintf("%d",$_REQUEST['orderID']);
	$nid = DuplicateOrder($oid);
	echo $nid;
	break;
case 'SplitOrder':
	$oid = sprintf("%d",$_REQUEST['orderID']);
	$tid = sprintf("%d",$_REQUEST['transID']);
	SplitOrder($oid,$tid);
	echo getItemForm($oid);
	break;
case 'UpdatePrint':
	$user = $_REQUEST['user'];
	$cachepath = sys_get_temp_dir()."/ordercache/";
	$prints = unserialize(file_get_contents("{$cachepath}{$user}.prints"));
	if (isset($prints[$_REQUEST['orderID']]))
		unset($prints[$_REQUEST['orderID']]);
	else
		$prints[$_REQUEST['orderID']] = array();
	$fp = fopen("{$cachepath}{$user}.prints",'w');
	fwrite($fp,serialize($prints));
	fclose($fp);
	break;
}

function canSaveAddress($orderID){
	global $dbc;

	$chk = $dbc->query(sprintf("SELECT card_no FROM PendingSpecialOrder
			WHERE order_id=%d",$orderID));
	if ($dbc->num_rows($chk) == 0){
		return False;
	}
	$row = $dbc->fetch_row($chk);
	if ($row['card_no'] != 0 && False) return False;

	$chk = $dbc->query(sprintf("SELECT card_no FROM SpecialOrderContact
			WHERE card_no=%d",$orderID));
	if ($dbc->num_rows($chk) == 0)
		CreateContactRow($orderID);
	return True;
}

function addUPC($orderID,$memNum,$upc,$num_cases=1){
	global $dbc;

	$sku = str_pad($upc,6,'0',STR_PAD_LEFT);
	if (is_numeric($upc))
		$upc = str_pad($upc,13,'0',STR_PAD_LEFT);

	$manualSKU = False;
	if (isset($upc[0]) && $upc[0] == "+"){
		$sku = substr($upc,1);
		$upc = "zimbabwe";
		$manualSKU = True;
	}
	
	$ins_array = genericRow($orderID);
	$ins_array['upc'] = "'$upc'";
	if ($manualSKU) 
		$ins_array['upc'] = str_pad($sku,13,'0',STR_PAD_LEFT);
	$ins_array['card_no'] = "'$memNum'";
	$ins_array['trans_type'] = "'I'";

	$caseSize = 1;
	$vendor = "";
	$vendor_desc = (!is_numeric($upc)?$upc:"");
	$srp = 0.00;
	$vendor_upc = (!is_numeric($upc)?'0000000000000':"");
	$caseQ = "SELECT units,vendorName,description,srp,i.upc FROM vendorItems as i
			LEFT JOIN vendors AS v ON
			i.vendorID=v.vendorID LEFT JOIN
			vendorSRPs AS s ON i.upc=s.upc AND i.vendorID=s.vendorID
		WHERE i.upc='$upc' OR i.sku='$sku' OR i.sku='0$sku'
		ORDER BY i.vendorID";
	$caseR = $dbc->query($caseQ);
	if ($dbc->num_rows($caseR) > 0)
		list($caseSize,$vendor,$vendor_desc,$srp,$vendor_upc) = $dbc->fetch_row($caseR);
	if (!empty($vendor_upc)) $ins_array['upc'] = "'$vendor_upc'";
	$ins_array['quantity'] = $caseSize;
	$ins_array['ItemQtty'] = $num_cases;
	$ins_array['mixMatch'] = $dbc->escape(substr($vendor,0,26));
	$ins_array['description'] = $dbc->escape(substr($vendor_desc,0,32)." SO");

	$mempricing = False;
	if ($memNum != 0 && !empty($memNum)){
		$r = $dbc->query("SELECT type,memType FROM custdata WHERE CardNo=$memNum");
		$w = $dbc->fetch_row($r);
		if ($w['type'] == 'PC') $mempricing = True;
		elseif($w['memType'] == 9) $mempricing = True;
	}

	$pdQ = "SELECT normal_price,special_price,department,discounttype,
		description,discount FROM products WHERE upc='$upc'";
	$pdR = $dbc->query($pdQ);
	$qtyReq = False;
	if ($dbc->num_rows($pdR) > 0){
		$pdW = $dbc->fetch_row($pdR);

		$ins_array['department'] = $pdW['department'];
		$ins_array['discountable'] = $pdW['discount'];
		$mapQ = "SELECT map_to FROM SpecialOrderDeptMap WHERE dept_ID=".$pdW['department'];
		$mapR = $dbc->query($mapQ);
		if ($dbc->num_rows($mapR) > 0)
			$ins_array['department'] = array_pop($dbc->fetch_row($mapR));

		$superQ = "SELECT superID FROM superDepts WHERE dept_ID=".$ins_array['department'];
		$superR = $dbc->query($superQ);
		while($superW = $dbc->fetch_row($superR)){
			if ($superW[0] == 5) $qtyReq = 3;
			if ($qtyReq !== False){
				$caseSize = $qtyReq;
				$ins_array['quantity'] = $qtyReq;
				break;
			}
		}
		
		// only calculate prices for items that exist in 
		// vendorItems (i.e., have known case size)
		if ($dbc->num_rows($caseR) > 0 || True){
			$ins_array['total'] = $pdW['normal_price']*$caseSize*$num_cases;
			$ins_array['regPrice'] = $pdW['normal_price']*$caseSize*$num_cases;
			$ins_array['unitPrice'] = $pdW['normal_price'];
			if ($pdW['discounttype'] == 1){
				$ins_array['total'] = $pdW['special_price']*$caseSize*$num_cases;
				$ins_array['unitPrice'] = $pdW['special_price'];
			}
			elseif ($mempricing){
				if ($pdW['discounttype'] == 2){
					$ins_array['total'] = $pdW['special_price']*$caseSize*$num_cases;
					$ins_array['unitPrice'] = $pdW['special_price'];
				}
				else
					$ins_array['total'] = $pdW['normal_price']*$caseSize*$num_cases*0.85;
			}
		}
		$ins_array['description'] = "'".substr($pdW['description'],0,32)." SO'";
		$ins_array['discounttype'] = $pdW['discounttype'];
	}
	elseif ($srp != 0){
		// use vendor SRP if applicable
		$ins_array['regPrice'] = $srp*$caseSize*$num_cases;
		$ins_array['total'] = $srp*$caseSize*$num_cases;
		if ($mempricing)
			$ins_array['total'] *= 0.85;
	}


	$tidQ = "SELECT MAX(trans_id),MAX(voided),MAX(numflag) FROM PendingSpecialOrder WHERE order_id=".$orderID;
	$tidR = $dbc->query($tidQ);
	$tidW = $dbc->fetch_row($tidR);
	$ins_array['trans_id'] = $tidW[0]+1;
	$ins_array['voided'] = $tidW[1];
	$ins_array['numflag'] = $tidW[2];

	$dbc->smart_insert('PendingSpecialOrder',$ins_array);
	
	return array($qtyReq,$ins_array['trans_id'],$ins_array['description']);
}

function CreateContactRow($orderID){
	global $dbc;

	$vals = array(
		'card_no'=>$orderID,
		'last_name'=>"''",
		'first_name'=>"''",
		'othlast_name'=>"''",
		'othfirst_name'=>"''",
		'street'=>"''",
		'city'=>"''",
		'state'=>"''",
		'zip'=>"''",
		'phone'=>"''",
		'email_1'=>"''",
		'email_2'=>"''",
		'ads_OK'=>1
	);
	$dbc->smart_insert('SpecialOrderContact',$vals);
}

function SplitOrder($orderID,$transID){
	global $dbc;
	// copy entire order
	$newID = DuplicateOrder($orderID,'PendingSpecialOrder');	
	
	// remove all items except desired one
	$cleanQ = sprintf("DELETE FROM PendingSpecialOrder WHERE
		order_id=%d AND trans_id > 0 AND trans_id<>%d",
		$newID,$transID);
	$dbc->query($cleanQ);

	// remove the item from original order
	$cleanQ2 = sprintf("DELETE FROM PendingSpecialOrder WHERE
			order_id=%d AND trans_id=%d",
			$orderID,$transID);
	$dbc->query($cleanQ2);

	// fix trans_id on the new order
	$cleanQ3 = sprintf("UPDATE PendingSpecialOrder SET trans_id=1
			WHERE order_id=%d AND trans_id=%d",
			$newID,$transID);
	$dbc->query($cleanQ3);
}

function DuplicateOrder($old_id,$from='CompleteSpecialOrder'){
	global $dbc;
	$new_id = CreateEmptyOrder();
	$delQ = "DELETE FROM PendingSpecialOrder WHERE order_id=$new_id";
	$dbc->query($delQ);

	$copyQ = "INSERT INTO PendingSpecialOrder
		SELECT $new_id,".$dbc->now().",
		register_no,emp_no,trans_no,upc,description,
		trans_type,trans_subtype,trans_status,
		department,quantity,scale,cost,unitPrice,
		total,regPrice,tax,foodstamp,discount,
		memDiscount,discountable,discounttype,
		voided,percentDiscount,ItemQtty,volDiscType,
		volume,VolSpecial,mixMatch,matched,memtype,
		isStaff,0,'',card_no,trans_id
		FROM $from WHERE order_id=$old_id";	
	$dbc->query($copyQ);

	$contactQ = "INSERT INTO SpecialOrderContact
		SELECT $new_id,last_name,first_name,othlast_name,othfirst_name,
		street,city,state,zip,phone,email_1,email_2,ads_OK FROM
		SpecialOrderContact WHERE card_no=$old_id";
	$dbc->query($contactQ);

	$notesQ = "INSERT INTO SpecialOrderNotes
		SELECT $new_id,notes,superID FROM
		SpecialOrderNotes WHERE order_id=$old_id";
	$dbc->query("DELETE FROM SpecialOrderNotes WHERE order_id=".$new_id);
	$dbc->query($notesQ);

	$user = checkLogin();
	$userQ = sprintf("UPDATE PendingSpecialOrder SET mixMatch=%s
			WHERE order_id=%d AND trans_id=0",
			$dbc->escape($user),$new_id);
	$userR = $dbc->query($userQ);

	$statusQ = "SELECT numflag FROM PendingSpecialOrder
		WHERE order_id=$new_id";
	$statusR = $dbc->query($statusQ);
	$st = array_pop($dbc->fetch_row($statusR));
	if ($st == 1){
		$statusQ = sprintf("UPDATE SpecialOrderStatus SET status_flag=3,sub_status=%d
			WHERE order_id=%d",time(),$new_id);
		$dbc->query($statusQ);
	}
	else if ($st == 0){
		$statusQ = sprintf("UPDATE SpecialOrderStatus SET status_flag=0,sub_status=%d
			WHERE order_id=%d",time(),$new_id);
		$dbc->query($statusQ);
	}
	
	return $new_id;
}

function CreateEmptyOrder(){
	global $dbc;
	$user = checkLogin();
	$orderID = 1;
	$dbc->query("INSERT SpecialOrderID DEFAULT VALUES");
	$orderID = $dbc->insert_id();

	$ins_array = genericRow($orderID);
	$ins_array['numflag'] = 1;
	$ins_array['mixMatch'] = $dbc->escape($user);
	$dbc->smart_insert('PendingSpecialOrder',$ins_array);

	$vals = array(
		'order_id'=>$orderID,
		'notes'=>"''",
		'superID'=>0
	);
	$dbc->smart_insert("SpecialOrderNotes",$vals);

	$vals = array(
		'order_id'=>$orderID,
		'status_flag'=>3,
		'sub_status'=>time()
	);
	$dbc->smart_insert("SpecialOrderStatus",$vals);

	CreateContactRow($orderID);

	return $orderID;
}

function genericRow($orderID){
	global $dbc;
	return array(
	'order_id'=>$orderID,
	'datetime'=>$dbc->now(),
	'emp_no'=>1001,
	'register_no'=>30,
	'trans_no'=>$orderID,
	'upc'=>'0',
	'description'=>"'SPECIAL ORDER'",
	'trans_type'=>"'C'",
	'trans_subtype'=>"''",
	'trans_status'=>"''",
	'department'=>0,
	'quantity'=>0,
	'scale'=>0,
	'cost'=>0,
	'unitPrice'=>0,
	'total'=>0,
	'regPrice'=>0,
	'tax'=>0,
	'foodstamp'=>0,
	'discount'=>0,
	'memDiscount'=>0,
	'discountable'=>1,
	'discounttype'=>0,
	'voided'=>0,
	'percentDiscount'=>0,
	'ItemQtty'=>0,
	'volDiscType'=>0,
	'volume'=>0,
	'VolSpecial'=>0,
	'mixMatch'=>0,
	'matched'=>0,
	'memType'=>0,
	'isStaff'=>0,
	'numflag'=>0,
	'charflag'=>"''",	
	'card_no'=>0,
	'trans_id'=>0
	);
}

function getCustomerForm($orderID,$memNum="0"){
	global $dbc;

	if (empty($orderID)) $orderID = CreateEmptyOrder();

	$names = array();
	$pn = 1;
	$fn = "";
	$ln = "";
	$contact_row = array(
		'street'=>'',
		'city'=>'',
		'state'=>'',
		'zip'=>'',
		'phone'=>'',
		'email_1'=>'',
		'email_2'=>''
	);
	$status_row = array(
		'type' => 'REG',
		'status' => ''
	);

	$notes = "";
	$noteDept = 0;
	
	$table = "PendingSpecialOrder";

	// detect member UPC entry
	if ($memNum > 9999999){
		$q = sprintf("SELECT card_no FROM memberCards WHERE upc=%s",
			$dbc->escape(str_pad($memNum,13,'0',STR_PAD_LEFT)));
		$r = $dbc->query($q);
		if ($dbc->num_rows($r) > 0)
			$memNum = array_pop($dbc->fetch_row($r));
		else
			$memNum = "";
	}

	// look up member id if applicable
	if ($memNum === "0"){
		$findMem = "SELECT card_no,voided FROM $table WHERE order_id=$orderID";
		$memR = $dbc->query($findMem);
		if ($dbc->num_rows($memR) > 0){
			$memW = $dbc->fetch_row($memR);
			$memNum = $memW['card_no'];
			$pn = $memW['voided'];
		}
	}
	else if ($memNum == ""){
		$q = sprintf("UPDATE PendingSpecialOrder SET card_no=%d,voided=0
			WHERE order_id=%d",0,$orderID);
		$r = $dbc->query($q);
	}
	else {
		
		$q = sprintf("UPDATE PendingSpecialOrder SET card_no=%d
			WHERE order_id=%d",$memNum,$orderID);
		$r = $dbc->query($q);
		
		// look up personnum, correct if it hasn't been set
		$pQ = sprintf("SELECT voided FROM PendingSpecialOrder
			WHERE order_id=%d",$orderID);
		$pR = $dbc->query($pQ);
		$pn = array_pop($dbc->fetch_row($pR));
		if ($pn == 0){
			$pn = 1;
			$upQ = sprintf("UPDATE PendingSpecialOrder SET
				voided=%d WHERE order_id=%d",$pn,$orderID);
			$upR = $dbc->query($upQ);
		}
	}

	if ($memNum != 0){
		$namesQ = sprintf("SELECT personNum,FirstName,LastName FROM custdata
			WHERE CardNo=%d ORDER BY personNum",$memNum);
		$namesR = $dbc->query($namesQ);
		while($namesW = $dbc->fetch_row($namesR))
			$names[$namesW['personNum']] = array($namesW['FirstName'],$namesW['LastName']);

		// load member contact info into SpecialOrderContact
		// on first go so it can be edited separately
		$testQ = "SELECT street FROM SpecialOrderContact WHERE card_no=".$orderID;
		$testR = $dbc->query($testQ);
		$testW = $dbc->fetch_row($testR);
		if (empty($testW['street'])){
			$contactQ = sprintf("SELECT street,city,state,zip,phone,email_1,email_2
					FROM meminfo WHERE card_no=%d",$memNum);
			$contactR = $dbc->query($contactQ);
			$contact_row = $dbc->fetch_row($contactR);
			
			$upQ = sprintf("UPDATE SpecialOrderContact SET street=%s,city=%s,state=%s,zip=%s,
					phone=%s,email_1=%s,email_2=%s WHERE card_no=%d",
					$dbc->escape($contact_row['street']),
					$dbc->escape($contact_row['city']),
					$dbc->escape($contact_row['state']),
					$dbc->escape($contact_row['zip']),
					$dbc->escape($contact_row['phone']),
					$dbc->escape($contact_row['email_1']),
					$dbc->escape($contact_row['email_2']),
					$orderID);
			$upR = $dbc->query($upQ);
		}
		else {
			$contactQ = sprintf("SELECT street,city,state,zip,phone,email_1,email_2
					FROM SpecialOrderContact WHERE card_no=%d",$orderID);
			$contactR = $dbc->query($contactQ);
			$contact_row = $dbc->fetch_row($contactR);
		}

		$statusQ = sprintf("SELECT type FROM custdata WHERE CardNo=%d",$memNum);
		$statusR = $dbc->query($statusQ);
		$status_row  = $dbc->fetch_row($statusR);
		if ($status_row['type'] == 'INACT')
			$status_row['status'] = 'Inactive';
		if ($status_row['type'] == 'INACT2')
			$status_row['status'] = 'Inactive';
		elseif ($status_row['type'] == 'TERM')
			$status_row['status'] = 'Terminated';
	}
	else {
		$q = "SELECT last_name,first_name,street,city,state,zip,phone,email_1,email_2
			FROM SpecialOrderContact WHERE card_no=$orderID";
		$r = $dbc->query($q);	
		if ($dbc->num_rows($r) > 0){
			$contact_row = $dbc->fetch_row($r);
			$fn = $contact_row['first_name'];
			$ln = $contact_row['last_name'];
		}
	}

	$q = "SELECT notes,superID FROM SpecialOrderNotes WHERE order_id=$orderID";
	$r = $dbc->query($q);
	if ($dbc->num_rows($r) > 0)
		list($notes,$noteDept) = $dbc->fetch_row($r);

	$q = "SELECT entry_date FROM SpecialOrderHistory WHERE order_id=$orderID AND entry_type='CONFIRMED'";
	$r = $dbc->query($q);
	$confirm_date = "";
	if ($dbc->num_rows($r) > 0)	
		$confirm_date = array_pop($dbc->fetch_row($r));

	$callback = 1;
	$user = 'Unknown';
	$orderDate = "";
	$q = "SELECT datetime,numflag,mixMatch FROM PendingSpecialOrder WHERE order_id=$orderID AND trans_id=0";
	$r = $dbc->query($q);
	if ($dbc->num_rows($r) > 0)
		list($orderDate,$callback,$user) = $dbc->fetch_row($r);

	$ret = "";
	$ret .= '<table width="95%" cellpadding="4" cellspacing=4" border="0">';
	$ret .= '<tr><td align="left" valign="top">';
	$ret .= sprintf('<input type="hidden" id="orderID" value="%d" />',$orderID);
	$ret .= sprintf('<b>Owner Number</b>: <input type="text" size="6"
			id="memNum" value="%s" onchange="memNumEntered();"
			/>',($memNum==0?'':$memNum));
	$ret .= '<br />';
	$ret .= '<b>Owner</b>: '.($status_row['type']=='PC'?'Yes':'No');
	$ret .= sprintf('<input type="hidden" id="isMember" value="%s" />',
			$status_row['type']);
	$ret .= '<br />';
	if (!empty($status_row['status'])){
		$ret .= '<b>Account status</b>: '.$status_row['status'];
		$ret .= '<br />';
	}
	$ret .= '</td><td align="right" valign="top">';
	$ret .= "<input type=\"submit\" value=\"Done\"
		onclick=\"validateAndHome();return false;\" />";
	$username = checkLogin();
	$prints = array();
	$cachepath = sys_get_temp_dir()."/ordercache/";
	if (file_exists("{$cachepath}{$username}.prints")){
		$prints = unserialize(file_get_contents("{$cachepath}{$username}.prints"));
	}
	else {
		$fp = fopen("{$cachepath}{$username}.prints",'w');
		fwrite($fp,serialize($prints));
		fclose($fp);
	}
	$ret .= sprintf('<br />Queue tags <input type="checkbox" %s onclick="togglePrint(\'%s\',%d);" />',
			(isset($prints[$orderID])?'checked':''),
			$username,$orderID
		);
	$ret .= sprintf('<br /><a href="tagpdf.php?oids[]=%d" target="_tags%d">Print Now</a>',
			$orderID,$orderID);
	$ret .= '</td></tr></table>';

	$extra = "";	
	$extra .= '<table width="95%" cellpadding="4" cellspacing=4" border="0">';
	$extra .= '<tr><td align="left" valign="top">';
	$extra .= "<b>Taken by</b>: ".$user."<br />";
	$extra .= "<b>On</b>: ".date("M j, Y g:ia",strtotime($orderDate))."<br />";
	$extra .= '</td><td align="right" valign="top">';
	$extra .= '<b>Call to Confirm</b>: ';
	$extra .= '<select id="ctcselect" onchange="saveCtC(this.value,'.$orderID.');">';
	if ($callback == 1){
		$extra .= '<option value="1" selected>Yes</option>';	
		$extra .= '<option value="0">No</option>';	
	}
	else if ($callback == 0){
		$extra .= '<option value="1">Yes</option>';	
		$extra .= '<option value="0" selected>No</option>';	
	}
	$extra .= '</select><br />';	
	$extra .= '<span id="confDateSpan">'.(!empty($confirm_date)?'Confirmed '.$confirm_date:'Not confirmed')."</span> ";
	$extra .= '<input type="checkbox" onclick="saveConfirmDate(this.checked,'.$orderID.');" ';
	if (!empty($confirm_date)) $extra .= "checked";
	$extra .= ' /><br />';

	$extra .= "<input type=\"submit\" value=\"Done\"
		onclick=\"validateAndHome();return false;\" />";
	$extra .= '</td></tr></table>';

	$ret .= '<table cellspacing="0" cellpadding="4" border="1">';

	// names
	if (empty($names)){
		$ret .= sprintf('<tr><th>First Name</th><td>
				<input type="text" id="t_firstName" 
				value="%s" onchange="saveFN(%d,this.value);" 
				/></td>',$fn,$orderID);
		$ret .= sprintf('<th>Last Name</th><td><input 
				type="text" id="t_lastName" value="%s"
				onchange="saveLN(%d,this.value);" /></td>',
				$ln,$orderID);
	}
	else {
		$ret .= sprintf('<tr><th>Name</th><td colspan="2"><select id="s_personNum"
			onchange="savePN(%d,this.value);">',$orderID);
		foreach($names as $p=>$n){
			$ret .= sprintf('<option value="%d" %s>%s %s</option>',
				$p,($p==$pn?'selected':''),
				$n[0],$n[1]);
		}
		$ret .= '</select></td>';
		$ret .= '<td>&nbsp;</td>';
	}
	$ret .= sprintf('<td colspan="4">For Department:
		<select id="nDept" onchange="saveNoteDept(%d,$(this).val());">
		<option value="0">Choose...</option>',$orderID);
	$sQ = "select superID,super_name from MasterSuperDepts
		where superID > 0
		group by superID,super_name
		order by super_name";
	$sR = $dbc->query($sQ);
	while($sW = $dbc->fetch_row($sR)){
		$ret .= sprintf('<option value="%d" %s>%s</option>',
			$sW['superID'],
			($sW['superID']==$noteDept?'selected':''),
			$sW['super_name']);
	}
	$ret .= "</select></td></tr>";

	// address
	if(strstr($contact_row['street'],"\n")){
		$tmp = explode("\n",$contact_row['street']);	
		$contact_row['street'] = $tmp[0];
		$contact_row['street2'] = $tmp[1];
	}
	else
		$contact_row['street2'] = '';

	$ret .= sprintf('<tr><th>Address</th><td><input type="text" id="t_addr1" value="%s" 
		onchange="saveAddr(%d);" /></td><th>E-mail</th><td><input type="text" 
		id="t_email" value="%s" onchange="saveEmail(%d,this.value);" /></td>
		<td rowspan="2" colspan="4">
		<textarea id="nText" rows="5" cols="25" 
		onchange="saveText(%d,this.value);">%s</textarea>
		</td></tr>
		<tr><th>Addr (2)</th><td><input type="text" id="t_addr2" value="%s" 
		onchange="saveAddr(%d);" /></td><th>City</th><td><input type="text" id="t_city" 
		value="%s" size="10" onchange="saveCity(%d,this.value);" /></td></tr>
		<tr><th>Phone</th><td><input 
		type="text" id="t_ph1" value="%s" onchange="savePh(%d,this.value);" /></td>
		<th>Alt. Phone</th><td><input type="text" id="t_ph2" value="%s" 
		onchange="savePh2(%d,this.value);" /></td>
		<th>State</th>
		<td><input type="text" id="t_state" value="%s" size="2" onchange="saveState(%d,this.value);"
		/></td><th>Zip</th><td><input type="text" id="t_zip" value="%s" size="5" 
		onchange="saveZip(%d,this.value); " /></td></tr>',
		$contact_row['street'],$orderID,
		$contact_row['email_1'],$orderID,
		$orderID,$notes,
		$contact_row['street2'],$orderID,
		$contact_row['city'],$orderID,
		$contact_row['phone'],$orderID,
		$contact_row['email_2'],$orderID,
		$contact_row['state'],$orderID,
		$contact_row['zip'],$orderID);

		
	$ret .= '</table>';

	return $ret."`".$extra;;
}

function getCustomerNonForm($orderID){
	global $dbc;

	$names = array();
	$pn = 1;
	$fn = "";
	$ln = "";
	$contact_row = array(
		'street'=>'',
		'city'=>'',
		'state'=>'',
		'zip'=>'',
		'phone'=>'',
		'email_1'=>'',
		'email_2'=>''
	);
	$status_row = array(
		'type' => 'REG',
		'status' => ''
	);

	$notes = "";
	$noteDept = 0;
	
	// look up member id 
	$memNum = 0;
	$findMem = "SELECT card_no,voided FROM CompleteSpecialOrder WHERE order_id=$orderID";
	$memR = $dbc->query($findMem);
	if ($dbc->num_rows($memR) > 0){
		$memW = $dbc->fetch_row($memR);
		$memNum = $memW['card_no'];
		$pn = $memW['voided'];
	}

	// Get member info from custdata, non-member info from SpecialOrderContact
	if ($memNum != 0){
		$namesQ = sprintf("SELECT personNum,FirstName,LastName FROM custdata
			WHERE CardNo=%d ORDER BY personNum",$memNum);
		$namesR = $dbc->query($namesQ);
		while($namesW = $dbc->fetch_row($namesR))
			$names[$namesW['personNum']] = array($namesW['FirstName'],$namesW['LastName']);

		$contactQ = sprintf("SELECT street,city,state,zip,phone,email_1,email_2
				FROM meminfo WHERE card_no=%d",$memNum);
		$contactR = $dbc->query($contactQ);
		$contact_row = $dbc->fetch_row($contactR);

		$statusQ = sprintf("SELECT type FROM custdata WHERE CardNo=%d",$memNum);
		$statusR = $dbc->query($statusQ);
		$status_row  = $dbc->fetch_row($statusR);
		if ($status_row['type'] == 'INACT')
			$status_row['status'] = 'Inactive';
		if ($status_row['type'] == 'INACT2')
			$status_row['status'] = 'Inactive';
		elseif ($status_row['type'] == 'TERM')
			$status_row['status'] = 'Terminated';
	}
	else {
		$q = "SELECT last_name,first_name,street,city,state,zip,phone,email_1,email_2
			FROM SpecialOrderContact WHERE card_no=$orderID";
		$r = $dbc->query($q);	
		if ($dbc->num_rows($r) > 0){
			$contact_row = $dbc->fetch_row($r);
			$fn = $contact_row['first_name'];
			$ln = $contact_row['last_name'];
		}
	}

	$q = "SELECT notes,superID FROM SpecialOrderNotes WHERE order_id=$orderID";
	$r = $dbc->query($q);
	if ($dbc->num_rows($r) > 0)
		list($notes,$noteDept) = $dbc->fetch_row($r);

	$q = "SELECT entry_date FROM SpecialOrderHistory WHERE order_id=$orderID AND entry_type='CONFIRMED'";
	$r = $dbc->query($q);
	$confirm_date = "";
	if ($dbc->num_rows($r) > 0)	
		$confirm_date = array_pop($dbc->fetch_row($r));

	$callback = 1;
	$user = 'Unknown';
	$q = "SELECT numflag,mixMatch FROM CompleteSpecialOrder WHERE order_id=$orderID AND trans_id=0";
	$r = $dbc->query($q);
	if ($dbc->num_rows($r) > 0)
		list($callback,$user) = $dbc->fetch_row($r);

	$ret = "";
	$ret .= sprintf('<input type="hidden" id="orderID" value="%d" />',$orderID);
	$ret .= '<table width="95%" cellpadding="4" cellspacing=4" border="0">';
	$ret .= '<tr><td align="left" valign="top">';
	$ret .= sprintf('<b>Owner Number</b>: %s',
			($memNum==0?'':$memNum));
	$ret .= '<br />';
	$ret .= '<b>Owner</b>: '.($status_row['type']=='PC'?'Yes':'No');
	$ret .= '<br />';
	if (!empty($status_row['status'])){
		$ret .= '<b>Account status</b>: '.$status_row['status'];
		$ret .= '<br />';
	}
	$ret .= "<b>Taken by</b>: ".$user."<br />";
	$ret .= '</td><td align="right" valign="top">';
	$ret .= '<b>Call to Confirm</b>: ';
	if ($callback == 1)
		$ret .= 'Yes';
	else 
		$ret .= 'No';
	$ret .= '<br />';	
	$ret .= '<span id="confDateSpan">'.(!empty($confirm_date)?'Confirmed '.$confirm_date:'Not confirmed')."</span> ";
	$ret .= '<br />';

	$ret .= "<input type=\"submit\" value=\"Done\"
		onclick=\"location='index.php';\" />";
	$ret .= '</td></tr></table>';

	$ret .= '<table cellspacing="0" cellpadding="4" border="1">';

	// names
	if (empty($names)){
		$ret .= sprintf('<tr><th>First Name</th><td>%s
				</td>',$fn,$orderID);
		$ret .= sprintf('<th>Last Name</th><td>%s
				</td>',
				$ln,$orderID);
	}
	else {
		$ret .= '<tr><th>Name</th><td colspan="2">';
		foreach($names as $p=>$n){
			if ($p == $pn) $ret .= $n[0].' '.$n[1];
		}
		$ret .= '</td>';
		$ret .= '<td>&nbsp;</td>';
	}
	$ret .= sprintf('<td colspan="4">Notes for:
		<select id="nDept">
		<option value="0">Choose...</option>',$orderID);
	$sQ = "select superID,super_name from MasterSuperDepts
		where superID > 0
		group by superID,super_name
		order by super_name";
	$sR = $dbc->query($sQ);
	while($sW = $dbc->fetch_row($sR)){
		$ret .= sprintf('<option value="%d" %s>%s</option>',
			$sW['superID'],
			($sW['superID']==$noteDept?'selected':''),
			$sW['super_name']);
	}
	$ret .= "</select></td></tr>";

	// address
	if(strstr($contact_row['street'],"\n")){
		$tmp = explode("\n",$contact_row['street']);	
		$contact_row['street'] = $tmp[0];
		$contact_row['street2'] = $tmp[1];
	}
	else
		$contact_row['street2'] = '';

	$ret .= sprintf('<tr><th>Address</th><td>%s
		</td><th>E-mail</th><td>%s</td>
		<td rowspan="2" colspan="4">%s
		</td></tr>
		<tr><th>Addr (2)</th><td>%s
		</td><th>City</th><td>%s
		</td></tr>
		<tr><th>Phone</th><td>%s</td>
		<th>Alt. Phone</th><td>%s</td>
		<th>State</th>
		<td>%s</td>
		<th>Zip</th><td>%s</td></tr>',
		$contact_row['street'],
		$contact_row['email_1'],
		$notes,
		$contact_row['street2'],
		$contact_row['city'],
		$contact_row['phone'],
		$contact_row['email_2'],
		$contact_row['state'],
		$contact_row['zip']);
		
	$ret .= '</table>';

	return $ret;
}

function getQtyForm($orderID,$default,$transID,$description){
	global $dbc;
	$ret = '<i>This item ('.$description.') requires a quantity</i><br />';
	$ret .= "<form onsubmit=\"newQty($orderID,$transID);return false;\">";
	$ret .= '<b>Qty</b>: <input type="text" id="newqty" value="'.$default.'" maxlength="3" size="4" />';
	$ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	$ret .= '<input type="submit" value="Enter Qty" />';
	$ret .= '</form>';
	return $ret;
}

function getDeptForm($orderID,$transID,$description){
	global $dbc;
	$ret = '<i>This item ('.$description.') requires a department</i><br />';
	$ret .= "<form onsubmit=\"newDept($orderID,$transID);return false;\">";
	$ret .= '<select id="newdept">';
	$q = "select super_name,
		CASE WHEN MIN(map_to) IS NULL THEN MIN(m.dept_ID) ELSE MIN(map_to) END
		from MasterSuperDepts
		as m left join SpecialOrderDeptMap as s
		on m.dept_ID=s.dept_ID
		where m.superID > 0
		group by super_name ORDER BY super_name";
	$r = $dbc->query($q);
	while($w = $dbc->fetch_row($r))
		$ret .= sprintf('<option value="%d">%s</option>',$w[1],$w[0]);
	$ret .= "</select>";
	$ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	$ret .= '<input type="submit" value="Enter Dept" />';
	$ret .= '</form>';
	return $ret;
}

function getItemForm($orderID){
	global $dbc,$canEdit;
	
	$ret = "<form onsubmit=\"addUPC();return false;\">";
	$ret .= '<b>UPC</b>: <input type="text" id="newupc" maxlength="35" />';
	$ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	$ret .= '<b>Cases</b>: <input id="newcases" maxlength="2" value="1" size="3" />';
	$ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	$ret .= '<input type="submit" value="Add Item" />';
	$ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	$ret .= '<input type="submit" onclick="searchWindow();return false;" value="Search" />';
	$ret .= '</form>';

	$ret .= '<p />';

	// find the order in pending or completed table
	$table = "PendingSpecialOrder";
	if ($table == "PendingSpecialOrder" && $canEdit)
		$ret .= editableItemList($orderID);
	else
		$ret .= itemList($orderID,$table);

	// disable manual-close for now
	if ($canEdit && True){
		$ret .= '<p />';
		$ret .= '<b><a href="" onclick="$(\'#manualclosebuttons\').toggle();return false;">Manually close order</a></b>';
		$ret .= sprintf('<span id="manualclosebuttons" style="display:none;"> as:
				<input type="submit" value="Completed"
				onclick="confirmC(%d,7);return false;" />
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="submit" value="Canceled"
				onclick="confirmC(%d,8);return false;" />
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="submit" value="Inquiry"
				onclick="confirmC(%d,9);return false;" />
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br />
				<b style="color:red;">Closing an order means slips for these
				items will no longer scan at the registers</b></span>',
				$orderID,$orderID,$orderID);
	}
	return $ret;
}

function editableItemList($orderID){
	global $dbc;

	$dQ = "SELECT dept_no,dept_name FROM departments order by dept_no";
	$dR = $dbc->query($dQ);
	$depts = array(0=>'Unassigned');
	while($dW = $dbc->fetch_row($dR))
		$depts[$dW['dept_no']] = $dW['dept_name'];

	$ret = '<table cellspacing="0" cellpadding="4" border="1">';
	$ret .= '<tr><th>UPC</th><th>SKU</th><th>Description</th><th>Cases</th><th>SRP</th><th>Actual</th><th>Qty</th><th>Dept</th><th>&nbsp;</th></tr>';
	$q = "SELECT o.upc,o.description,total,quantity,department,v.sku,ItemQtty,regPrice,o.discounttype,o.charflag,o.mixMatch,
		o.trans_id,o.unitPrice FROM PendingSpecialOrder as o
		left join vendorItems as v on o.upc=v.upc AND vendorID=1
		WHERE order_id=$orderID AND trans_type='I' 
		ORDER BY trans_id DESC";
	$r = $dbc->query($q);
	$num_rows = $dbc->num_rows($r);
	$prev_id = 0;
	while($w = $dbc->fetch_row($r)){
		if ($w['trans_id'] == $prev_id) continue;
		$ret .= sprintf('<tr>
				<td>%s</td>
				<td>%s</td>
				<td><input onchange="saveDesc($(this).val(),%d);return false;" value="%s" /></td>
				<td>%d</td>
				<td><input size="5" id="srp%d" onchange="saveSRP($(this).val(),%d);return false;" value="%.2f" /></td>
				<td><input size="5" id="act%d" onchange="savePrice($(this).val(),%d);return false;" value="%.2f" /></td>
				<td><input size="4" onchange="saveQty($(this).val(),%d);return false;" value="%.2f" /></td>
				<td><select class="editDept" onchange="saveDept($(this).val(),%d);return false;">',
				$w['upc'],
				(!empty($w['sku'])?$w['sku']:'&nbsp;'),
				$w['trans_id'],$w['description'],
				$w['ItemQtty'],
				$w['trans_id'],$w['trans_id'],$w['regPrice'],
				$w['trans_id'],$w['trans_id'],$w['total'],
				$w['trans_id'],$w['quantity'],
				$w['trans_id']
			);
		foreach($depts as $id=>$name){
			$ret .= sprintf('<option value="%d" %s>%d %s</option>',
				$id,
				($id==$w['department']?'selected':''),
				$id,$name);
		}
		$ret .= sprintf('</select></td>
				<td>[<a href="" onclick="deleteID(%d,%d);return false;">X</a>]</td>
				</tr>',
				$orderID,$w['trans_id']
		);
		$ret .= '<tr>';
		$ret .= sprintf('<td colspan="2" align="right">Unit Price: 
			<input type="text" size="4" value="%.2f" id="unitp%d"
			onchange="saveUnit($(this).val(),%d);" /></td>',
			$w['unitPrice'],$w['trans_id'],$w['trans_id']);
		$ret .= sprintf('<td>Supplier: <input type="text" value="%s" size="12" 
				maxlength="26" onchange="saveVendor($(this).val(),%d);" 
				/></td>',$w['mixMatch'],$w['trans_id']);
		$ret .= '<td>Discount</td>';
		if ($w['discounttype'] == 1 || $w['discounttype'] == 2)
			$ret .= '<td id="discPercent'.$w['trans_id'].'">Sale</td>';
		else if ($w['regPrice'] != $w['total']){
			$ret .= sprintf('<td id="discPercent%d">%d%%</td>',$w['upc'],
				round(100*(($w['regPrice']-$w['total'])/$w['regPrice'])));
		}
		else {
			$ret .= '<td id="discPercent'.$w['upc'].'">0%</td>';
		}
		$ret .= sprintf('<td colspan="2">Printed: %s</td>',
				($w['charflag']=='P'?'Yes':'No'));
		if ($num_rows > 1){
			$ret .= sprintf('<td colspan="2"><input type="submit" value="Split Item to New Order"
				onclick="doSplit(%d,%d);return false;" /></td>',
				$orderID,$w['trans_id']);
		}
		else
			$ret .= '<td colspan="2"></td>';
		$ret .= '</tr>';
		$ret .= '<tr><td colspan="9"><span style="font-size:1;">&nbsp;</span></td></tr>';
		$prev_id=$w['trans_id'];
	}
	$ret .= '</table>';
	return $ret;
}

function itemList($orderID,$table="PendingSpecialOrder"){
	global $dbc;

	$ret = '<table cellspacing="0" cellpadding="4" border="1">';
	$ret .= '<tr><th>UPC</th><th>Description</th><th>Cases</th><th>Pricing</th><th>&nbsp;</th></tr>';
		//<th>Est. Price</th>
		//<th>Qty</th><th>Est. Savings</th><th>&nbsp;</th></tr>';
	$q = "SELECT o.upc,o.description,total,quantity,department,regPrice,ItemQtty,discounttype,trans_id FROM $table as o
		WHERE order_id=$orderID AND trans_type='I'";
	$r = $dbc->query($q);
	while($w = $dbc->fetch_row($r)){
		$pricing = "Regular";
		if ($w['discounttype'] == 1)
			$pricing = "Sale";
		elseif($w['regPrice'] != $w['total']){
			if ($w['discounttype']==2)
				$pricing = "Sale";
			else
				$pricing = "% Discount";
		}
		$ret .= sprintf('<tr>
				<td>%s</td>
				<td>%s</td>
				<td>%d</td>
				<td>%s</td>
				<td><a href="" onclick="deleteID(%d,%d);return false;">Delete</a>
				</tr>',
				$w['upc'],
				$w['description'],
				$w['ItemQtty'],
				$pricing,
				$orderID,$w['trans_id']
			);
	}
	$ret .= '</table>';
	return $ret;
}

function getItemNonForm($orderID){
	global $dbc;

	$dQ = "SELECT dept_no,dept_name FROM departments order by dept_no";
	$dR = $dbc->query($dQ);
	$depts = array(0=>'Unassigned');
	while($dW = $dbc->fetch_row($dR))
		$depts[$dW['dept_no']] = $dW['dept_name'];

	$ret = '<table cellspacing="0" cellpadding="4" border="1">';
	$ret .= '<tr><th>UPC</th><th>SKU</th><th>Description</th><th>Cases</th><th>SRP</th><th>Actual</th><th>Qty</th><th>Dept</th></tr>';
	$q = "SELECT o.upc,o.description,total,quantity,department,sku,ItemQtty,regPrice,o.discounttype,o.charflag,o.mixMatch FROM CompleteSpecialOrder as o
		left join vendorItems as v on o.upc=v.upc
		WHERE order_id=$orderID AND trans_type='I' AND (vendorID=1 or vendorID is null)
		ORDER BY trans_id DESC";
	$r = $dbc->query($q);
	while($w = $dbc->fetch_row($r)){
		$ret .= sprintf('<tr>
				<td>%s</td>
				<td>%s</td>
				<td>%s</td>
				<td>%d</td>
				<td>%.2f</td>
				<td>%.2f</td>
				<td>%.2f</td>
				<td><select>',
				$w['upc'],
				(!empty($w['sku'])?$w['sku']:'&nbsp;'),
				$w['description'],
				$w['ItemQtty'],
				$w['regPrice'],
				$w['total'],
				$w['quantity']
			);
			foreach($depts as $id=>$name){
				$ret .= sprintf('<option value="%d" %s>%d %s</option>',
					$id,
					($id==$w['department']?'selected':''),
					$id,$name);
			}
			$ret .= '</select></td></tr>';
			$ret .= '<tr>';
			$ret .= sprintf('<td colspan="2" align="right">Unit Price: $%.2f</td>',
				($w['regPrice']/$w['ItemQtty']/$w['quantity']));
			$ret .= sprintf('<td>From: %s</td>',$w['mixMatch']);
			$ret .= '<td>Discount</td>';
			if ($w['discounttype'] == 1 || $w['discounttype'] == 2)
				$ret .= '<td id="discPercent'.$w['upc'].'">Sale</td>';
			else if ($w['regPrice'] != $w['total']){
				$ret .= sprintf('<td id="discPercent%s">%d%%</td>',$w['upc'],
					round(100*(($w['regPrice']-$w['total'])/$w['regPrice'])));
			}
			else {
				$ret .= '<td id="discPercent'.$w['upc'].'">0%</td>';
			}
			$ret .= sprintf('<td colspan="4">Printed: %s</td>',
					($w['charflag']=='P'?'Yes':'No'));
			$ret .= '</tr>';
			$ret .= '<tr><td colspan="8"><span style="font-size:1;">&nbsp;</span></td></tr>';
	}
	$ret .= '</table>';
	return $ret;
}

function reprice($oid,$tid,$reg=False){
	global $dbc;

	$query = sprintf("SELECT o.unitPrice,o.itemQtty,o.quantity,o.discounttype,
		c.type,c.memType,o.regPrice,o.total
		FROM PendingSpecialOrder AS o LEFT JOIN custdata AS c ON
		o.card_no=c.CardNo AND c.personNum=1
		WHERE order_id=%d AND trans_id=%d",$oid,$tid);
	$response = $dbc->query($query);
	$row = $dbc->fetch_row($response);

	$regPrice = $row['itemQtty']*$row['quantity']*$row['unitPrice'];
	if ($reg)
		$regPrice = $reg;
	$total = $regPrice;
	if (($row['type'] == 'PC' || $row['memType'] == 9) && $row['discounttype'] == 0){
		$total *= 0.85;
	}

	if ($row['unitPrice'] == 0 || $row['quantity'] == 0){
		$regPrice = $row['regPrice'];
		$total = $row['total'];
	}

	$query = sprintf("UPDATE PendingSpecialOrder SET
			total=%.2f,regPrice=%.2f
			WHERE order_id=%d AND trans_id=%d",
			$total,$regPrice,$oid,$tid);
	$dbc->query($query);
	return array(
		'regPrice'=>sprintf("%.2f",$regPrice),
		'total'=>sprintf("%.2f",$total)
	);
}


?>

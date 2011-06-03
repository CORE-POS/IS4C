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
	addUPC($orderID,$_REQUEST['memNum'],$_REQUEST['upc'],$qty);
	echo getItemForm($orderID);
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
	$upc = str_pad($_REQUEST['upc'],13,'0',STR_PAD_LEFT);
	$desc = $_REQUEST['desc'];
	$desc = rtrim($desc,' SO');
	$desc = substr($desc,0,32)." SO";
	$upQ = sprintf("UPDATE PendingSpecialOrder SET
		description=%s WHERE order_id=%d AND upc=%s",
		$dbc->escape($desc),$_REQUEST['orderID'],
		$dbc->escape($upc));
	$dbc->query($upQ);
	break;
case 'saveCtC':
	$upQ = sprintf("UPDATE PendingSpecialOrder SET
		numflag=%d WHERE order_id=%d AND trans_id=0",
		$_REQUEST['val'],$_REQUEST['orderID']);
	$dbc->query($upQ);
	break;
case 'savePrice':
	$upc = str_pad($_REQUEST['upc'],13,'0',STR_PAD_LEFT);
	$upQ = sprintf("UPDATE PendingSpecialOrder SET
		total=%f WHERE order_id=%d AND upc=%s",
		$_REQUEST['price'],$_REQUEST['orderID'],
		$dbc->escape($upc));
	$dbc->query($upQ);
	$fetchQ = sprintf("SELECT ROUND(100*((regPrice-total)/regPrice),0)
		FROM PendingSpecialOrder WHERE upc=%s AND order_id=%d",
		$dbc->escape($upc),$_REQUEST['orderID']);
	$fetchR = $dbc->query($fetchQ);
	echo array_pop($dbc->fetch_row($fetchR));
	break;
case 'saveSRP':
	$upc = str_pad($_REQUEST['upc'],13,'0',STR_PAD_LEFT);
	$upQ = sprintf("UPDATE PendingSpecialOrder SET
		regPrice=%f WHERE order_id=%d AND upc=%s",
		$_REQUEST['srp'],$_REQUEST['orderID'],
		$dbc->escape($upc));
	$dbc->query($upQ);
	$fetchQ = sprintf("SELECT ROUND(100*((regPrice-total)/regPrice),0)
		FROM PendingSpecialOrder WHERE upc=%s AND order_id=%d",
		$dbc->escape($upc),$_REQUEST['orderID']);
	$fetchR = $dbc->query($fetchQ);
	echo array_pop($dbc->fetch_row($fetchR));
	break;
case 'saveQty':
	$upc = str_pad($_REQUEST['upc'],13,'0',STR_PAD_LEFT);
	$upQ = sprintf("UPDATE PendingSpecialOrder SET
		quantity=%f WHERE order_id=%d AND upc=%s",
		$_REQUEST['qty'],$_REQUEST['qty'],$_REQUEST['orderID'],
		$dbc->escape($upc));
	$dbc->query($upQ);
	break;
case 'saveDept':
	$upc = str_pad($_REQUEST['upc'],13,'0',STR_PAD_LEFT);
	$upQ = sprintf("UPDATE PendingSpecialOrder SET
		department=%d WHERE order_id=%d AND upc=%s",
		$_REQUEST['dept'],$_REQUEST['orderID'],
		$dbc->escape($upc));
	$dbc->query($upQ);
	break;
case 'saveVendor':
	$upc = str_pad($_REQUEST['upc'],13,'0',STR_PAD_LEFT);
	$upQ = sprintf("UPDATE PendingSpecialOrder SET
		mixMatch=%s WHERE order_id=%d AND upc=%s",
		$dbc->escape($_REQUEST['vendor']),
		$_REQUEST['orderID'],
		$dbc->escape($upc));
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
		status_flag=%d WHERE order_id=%d",
		$_REQUEST['val'],$orderID);
	$dbc->query($q);
	break;
case 'UpdateSub':
	$q = sprintf("UPDATE SpecialOrderStatus SET
		sub_status=%d WHERE order_id=%d",
		$_REQUEST['val'],$orderID);
	$dbc->query($q);
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
}

function canSaveAddress($orderID){
	global $dbc;

	$chk = $dbc->query(sprintf("SELECT card_no FROM PendingSpecialOrder
			WHERE order_id=%d",$orderID));
	if ($dbc->num_rows($chk) == 0){
		return False;
	}
	$row = $dbc->fetch_row($chk);
	if ($row['card_no'] != 0) return False;

	$chk = $dbc->query(sprintf("SELECT card_no FROM SpecialOrderContact
			WHERE card_no=%d",$orderID));
	if ($dbc->num_rows($chk) == 0)
		CreateContactRow($orderID);	
	return True;
}

function addUPC($orderID,$memNum,$upc,$num_cases=1){
	global $dbc;

	$sku = str_pad($upc,6,'0',STR_PAD_LEFT);
	$upc = str_pad($upc,13,'0',STR_PAD_LEFT);
	
	$ins_array = genericRow($orderID);
	$ins_array['upc'] = "'$upc'";
	$ins_array['card_no'] = "'$memNum'";
	$ins_array['trans_type'] = "'I'";

	$caseSize = 1;
	$vendor = "";
	$vendor_desc = "";
	$srp = 0.00;
	$vendor_upc = "";
	$caseQ = "SELECT units,vendorName,description,srp,i.upc FROM vendorItems as i
			LEFT JOIN vendors AS v ON
			i.vendorID=v.vendorID LEFT JOIN
			vendorSRPs AS s ON i.upc=s.upc AND i.vendorID=s.vendorID
		WHERE i.upc='$upc' OR i.sku='$sku' OR i.sku='0$sku'";
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
		$r = $dbc->query("SELECT type FROM custdata WHERE CardNo=$memNum");
		$w = $dbc->fetch_row($r);
		if ($w['type'] == 'PC') $mempricing = True;
	}

	$pdQ = "SELECT normal_price,special_price,department,discounttype,
		description,discount FROM products WHERE upc='$upc'";
	$pdR = $dbc->query($pdQ);
	if ($dbc->num_rows($pdR) > 0){
		$pdW = $dbc->fetch_row($pdR);

		$ins_array['department'] = $pdW['department'];
		$ins_array['discountable'] = $pdW['discount'];
		$mapQ = "SELECT map_to FROM SpecialOrderDeptMap WHERE dept_ID=".$pdW['department'];
		$mapR = $dbc->query($mapQ);
		if ($dbc->num_rows($mapR) > 0)
			$ins_array['department'] = array_pop($dbc->fetch_row($mapR));
		
		// only calculate prices for items that exist in 
		// vendorItems (i.e., have known case size)
		if ($dbc->num_rows($caseR) > 0){
			$ins_array['total'] = $pdW['normal_price']*$caseSize*$num_cases;
			$ins_array['regPrice'] = $pdW['normal_price']*$caseSize*$num_cases;
			if ($pdW['discounttype'] == 1){
				$ins_array['total'] = $pdW['special_price']*$caseSize*$num_cases;
			}
			elseif ($mempricing){
				if ($pdW['discounttype'] == 2)
					$ins_array['total'] = $pdW['special_price']*$caseSize*$num_cases;
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

function DuplicateOrder($old_id){
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
		FROM CompleteSpecialOrder WHERE order_id=$old_id";	
	$dbc->query($copyQ);
	
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
		'status_flag'=>0,
		'sub_status'=>0
	);
	$dbc->smart_insert("SpecialOrderStatus",$vals);

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
	$q = "SELECT numflag,mixMatch FROM PendingSpecialOrder WHERE order_id=$orderID AND trans_id=0";
	$r = $dbc->query($q);
	if ($dbc->num_rows($r) > 0)
		list($callback,$user) = $dbc->fetch_row($r);

	$ret = "";
	$ret .= '<table width="95%" cellpadding="4" cellspacing=4" border="0">';
	$ret .= '<tr><td align="left" valign="top">';
	$ret .= sprintf('<input type="hidden" id="orderID" value="%d" />',$orderID);
	$ret .= sprintf('<b>Owner Number</b>: <input type="text" size="4"
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
	$ret .= "<b>Taken by</b>: ".$user."<br />";
	$ret .= '</td><td align="right" valign="top">';
	$ret .= '<b>Call to Confirm</b>: ';
	$ret .= '<select onchange="saveCtC(this.value,'.$orderID.');">';
	if ($callback == 1){
		$ret .= '<option value="1" selected>Yes</option>';	
		$ret .= '<option value="0">No</option>';	
	}
	else {
		$ret .= '<option value="1">Yes</option>';	
		$ret .= '<option value="0" selected>No</option>';	
	}
	$ret .= '</select><br />';	
	$ret .= '<span id="confDateSpan">'.(!empty($confirm_date)?'Confirmed '.$confirm_date:'Not confirmed')."</span> ";
	$ret .= '<input type="checkbox" onclick="saveConfirmDate(this.checked,'.$orderID.');" ';
	if (!empty($confirm_date)) $ret .= "checked";
	$ret .= ' /><br />';

	$ret .= "<input type=\"submit\" value=\"Done\"
		onclick=\"validateAndHome();return false;\" />";
	$ret .= '</td></tr></table>';

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

	return $ret;
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
	$q = "SELECT numflag,mixMatch FROM PendingSpecialOrder WHERE order_id=$orderID AND trans_id=0";
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

function getItemForm($orderID){
	global $dbc,$canEdit;
	
	$ret = '<b>UPC</b>: <input type="text" id="newupc" maxlength="13" />';
	$ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	$ret .= '<b>Cases</b>: <input id="newcases" maxlength="2" value="1" size="3" />';
	$ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	$ret .= '<input type="submit" onclick="addUPC();return false;" value="Add Item" />';
	$ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	$ret .= '<input type="submit" onclick="searchWindow();return false;" value="Search" />';

	$ret .= '<p />';

	// find the order in pending or completed table
	$table = "PendingSpecialOrder";
	if ($table == "PendingSpecialOrder" && $canEdit)
		$ret .= editableItemList($orderID);
	else
		$ret .= itemList($orderID,$table);

	if ($canEdit){
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
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>',
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
		o.trans_id FROM PendingSpecialOrder as o
		left join vendorItems as v on o.upc=v.upc AND vendorID=1
		WHERE order_id=$orderID AND trans_type='I' 
		ORDER BY trans_id DESC";
	$r = $dbc->query($q);
	while($w = $dbc->fetch_row($r)){
		$ret .= sprintf('<tr>
				<td>%s</td>
				<td>%s</td>
				<td><input onchange="saveDesc($(this).val(),%s);return false;" value="%s" /></td>
				<td>%d</td>
				<td><input size="5" onchange="saveSRP($(this).val(),%s);return false;" value="%.2f" /></td>
				<td><input size="5" onchange="savePrice($(this).val(),%s);return false;" value="%.2f" /></td>
				<td><input size="4" onchange="saveQty($(this).val(),%s);return false;" value="%.2f" /></td>
				<td><select onchange="saveDept($(this).val(),%s);return false;">',
				$w['upc'],
				(!empty($w['sku'])?$w['sku']:'&nbsp;'),
				"'".$w['upc']."'",$w['description'],
				$w['ItemQtty'],
				"'".$w['upc']."'",$w['regPrice'],
				"'".$w['upc']."'",$w['total'],
				"'".$w['upc']."'",$w['quantity'],
				"'".$w['upc']."'"
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
			$ret .= sprintf('<td colspan="2" align="right">Unit Price: $%.2f</td>',
				($w['regPrice']/$w['ItemQtty']/$w['quantity']));
			$ret .= sprintf('<td>From: <input type="text" value="%s" size="12" 
					maxlength="26" onchange="saveVendor($(this).val(),%s);" 
					/></td>',$w['mixMatch'],"'".$w['upc']."'");
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
			$ret .= '<tr><td colspan="9"><span style="font-size:1;">&nbsp;</span></td></tr>';
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
	$q = "SELECT o.upc,o.description,total,quantity,department,regPrice,ItemQtty,discounttype FROM $table as o
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
				<td><a href="" onclick="deleteUPC(%d,%s);return false;">Delete</a>
				</tr>',
				$w['upc'],
				$w['description'],
				$w['ItemQtty'],
				$pricing,
				$orderID,"'".$w['upc']."'"
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


?>

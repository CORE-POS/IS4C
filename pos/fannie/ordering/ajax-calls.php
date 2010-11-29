<?php
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
	echo getCustomerForm($orderID);
	break;
case 'reloadMem':
	echo getCustomerForm($orderID,$_REQUEST['memNum']);
	break;
case 'loadItems':
	echo getItemForm($orderID);
	break;
case 'newUPC':
	$qty = is_numeric($_REQUEST['cases'])?(int)$_REQUEST['cases']:1;
	addUPC($orderID,$_REQUEST['memNum'],$_REQUEST['upc'],$qty);
	echo getItemForm($orderID);
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
	break;
case 'saveSRP':
	$upc = str_pad($_REQUEST['upc'],13,'0',STR_PAD_LEFT);
	$upQ = sprintf("UPDATE PendingSpecialOrder SET
		regPrice=%f WHERE order_id=%d AND upc=%s",
		$_REQUEST['srp'],$_REQUEST['orderID'],
		$dbc->escape($upc));
	$dbc->query($upQ);
	break;
case 'saveQty':
	$upc = str_pad($_REQUEST['upc'],13,'0',STR_PAD_LEFT);
	$upQ = sprintf("UPDATE PendingSpecialOrder SET
		quantity=%f,ItemQtty=%f WHERE order_id=%d AND upc=%s",
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
case 'saveSate':
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

	$upc = str_pad($upc,13,'0',STR_PAD_LEFT);
	
	$ins_array = genericRow($orderID);
	$ins_array['upc'] = "'$upc'";
	$ins_array['card_no'] = "'$memNum'";
	$ins_array['trans_type'] = "'I'";

	$caseSize = 1;
	$caseQ = "SELECT units FROM vendorItems WHERE
		upc='$upc'";
	$caseR = $dbc->query($caseQ);
	if ($dbc->num_rows($caseR) > 0)
		$caseSize = array_pop($dbc->fetch_row($caseR));
	$ins_array['quantity'] = $caseSize;
	$ins_array['ItemQtty'] = $num_cases;

	$mempricing = False;
	if ($memNum != 0 && !empty($memNum)){
		$r = $dbc->query("SELECT type FROM custdata WHERE CardNo=$memNum");
		$w = $dbc->fetch_row($r);
		if ($w['type'] == 'PC') $mempricing = True;
	}

	$pdQ = "SELECT normal_price,special_price,department,discounttype,
		description FROM products WHERE upc='$upc'";
	$pdR = $dbc->query($pdQ);
	if ($dbc->num_rows($pdR) > 0){
		$pdW = $dbc->fetch_row($pdR);
		$ins_array['department'] = $pdW['department'];
		$ins_array['total'] = $pdW['normal_price']*$caseSize*$num_cases;
		$ins_array['regPrice'] = $pdW['normal_price']*$caseSize*$num_cases;
		if ($mempricing){
			if ($pdW['discounttype'] == 2)
				$ins_array['total'] = $pdW['special_price']*$caseSize*$num_cases;
			else
				$ins_array['total'] = $pdW['normal_price']*$caseSize*$num_cases*0.85;
		}
		$ins_array['description'] = "'".substr($pdW['description'],0,32)." SO'";
	}

	$tidQ = "SELECT MAX(trans_id) FROM PendingSpecialOrder WHERE order_id=".$orderID;
	$tidR = $dbc->query($tidQ);
	$tidW = $dbc->fetch_row($tidR);
	$ins_array['trans_id'] = $tidW[0]+1;

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

function CreateEmptyOrder(){
	global $dbc;
	$orderID = 1;
	$r = $dbc->query("SELECT MAX(order_id) FROM PendingSpecialOrder");
	if ($dbc->num_rows($r) > 0){
		$max = array_pop($dbc->fetch_row($r));
		if (!empty($max)) $orderID = $max+1;
	}

	$ins_array = genericRow($orderID);
	$ins_array['numflag'] = 1;
	$dbc->smart_insert('PendingSpecialOrder',$ins_array);

	$vals = array(
		'order_id'=>$orderID,
		'notes'=>"''",
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
	'discountable'=>0,
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
	
	$table = "PendingSpecialOrder";

	// look up member id if applicable
	if ($memNum === "0"){
		$findMem = "SELECT card_no FROM $table WHERE order_id=$orderID";
		$memR = $dbc->query($findMem);
		if ($dbc->num_rows($memR) > 0)
			$memNum = array_pop($dbc->fetch_row($memR));
	}
	else if ($memNum == ""){
		$q = sprintf("UPDATE PendingSpecialOrder SET card_no=%d
			WHERE order_id=%d",0,$orderID);
		$r = $dbc->query($q);
	}
	else {
		$q = sprintf("UPDATE PendingSpecialOrder SET card_no=%d
			WHERE order_id=%d",$memNum,$orderID);
		$r = $dbc->query($q);
	}

	if ($memNum != 0){
		$namesQ = sprintf("SELECT FirstName,LastName FROM custdata
			WHERE CardNo=%d ORDER BY personNum",$memNum);
		$namesR = $dbc->query($namesQ);
		while($namesW = $dbc->fetch_row($namesR))
			$names[] = array($namesW['FirstName'],$namesW['LastName']);

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

	$q = "SELECT notes FROM SpecialOrderNotes WHERE order_id=$orderID";
	$r = $dbc->query($q);
	if ($dbc->num_rows($r) > 0)
		$notes = array_pop($dbc->fetch_row($r));

	$q = "SELECT entry_date FROM SpecialOrderHistory WHERE order_id=$orderID AND entry_type='CONFIRMED'";
	$r = $dbc->query($q);
	$confirm_date = "";
	if ($dbc->num_rows($r) > 0)	
		$confirm_date = array_pop($dbc->fetch_row($r));

	$callback = 1;
	$q = "SELECT numflag FROM PendingSpecialOrder WHERE order_id=$orderID AND trans_id=0";
	$r = $dbc->query($q);
	if ($dbc->num_rows($r) > 0)
		$callback = array_pop($dbc->fetch_row($r));

	$ret = "";
	$ret .= '<table width="95%" cellpadding="4" cellspacing=4" border="0">';
	$ret .= '<tr><td align="left" valign="top">';
	$ret .= sprintf('<input type="hidden" id="orderID" value="%d" />',$orderID);
	$ret .= sprintf('<b>Member Number</b>: <input type="text" size="4"
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
	$ret .= '<input type="checkbox" onclick="saveConfirmDate(this.checked,';
	$ret .= $orderID.');" ';
	if (!empty($confirm_date)) $ret .= "checked";
	$ret .= ' /><br />';

	$ret .= "<input type=\"submit\" value=\"Done\"
		onclick=\"location='index.php';return false;\" />";
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
		$ret .= '<tr><th>Name</th><td colspan="3"><select id="s_personNum">';
		foreach($names as $n){
			$ret .= sprintf('<option>%s %s</option>',$n[0],$n[1]);
		}
		$ret .= '</select></td>';
	}

	$ret .= '<td rowspan="3" colspan="4">';
	$ret .= '<textarea rows="5" cols="25" 
		onchange="saveText('.$orderID.',this.value);">';
	$ret .= $notes;
	$ret .= '</textarea>';
	$ret .= '</td></tr>';

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
		id="t_email" value="%s" onchange="saveEmail(%d,this.value);" /></td></tr>
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
		$contact_row['street2'],$orderID,
		$contact_row['city'],$orderID,
		$contact_row['phone'],$orderID,
		$contact_row['email_2'],$orderID,
		$contact_row['state'],$orderID,
		$contact_row['zip'],$orderID);
		
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

	$ret .= '<p />';

	// find the order in pending or completed table
	$table = "PendingSpecialOrder";
	$find1Q = "SELECT order_id FROM PendingSpecialOrder WHERE order_id=$orderID";
	$find1R = $dbc->query($find1Q);
	if ($dbc->num_rows($find1R)==0){
		$find2Q = "SELECT order_id FROM CompleteSpecialOrder WHERE order_id=$orderID";
		$find2R = $dbc->query($find2Q);
		if ($dbc->num_rows($find2R) > 0)
			$table = "CompleteSpecialOrder";
	}

	if ($table == "PendingSpecialOrder" && $canEdit)
		$ret .= editableItemList($orderID);
	else
		$ret .= itemList($orderID,$table);

	return $ret;
}

function editableItemList($orderID){
	global $dbc;

	$ret = '<table cellspacing="0" cellpadding="4" border="1">';
	$ret .= '<tr><th>UPC</th><th>SKU</th><th>Description</th><th>Cases</th><th>SRP</th><th>Actual</th><th>Qty</th><th>Dept</th><th>&nbsp;</th></tr>';
	$q = "SELECT o.upc,o.description,total,quantity,department,sku,ItemQtty,regPrice FROM PendingSpecialOrder as o
		left join vendorItems as v on o.upc=v.upc
		WHERE order_id=$orderID AND trans_type='I'";
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
				<td><input size="4" onchange="saveDept($(this).val(),%s);return false;" value="%d" /></td>
				<td>[<a href="" onclick="deleteUPC(%d,%s);return false;">X</a>]</td>
				</tr>',
				$w['upc'],
				(!empty($w['sku'])?$w['sku']:'&nbsp;'),
				"'".$w['upc']."'",$w['description'],
				$w['ItemQtty'],
				"'".$w['upc']."'",$w['regPrice'],
				"'".$w['upc']."'",$w['total'],
				"'".$w['upc']."'",$w['quantity'],
				"'".$w['upc']."'",$w['department'],
				$orderID,"'".$w['upc']."'"
			);
	}
	$ret .= '</table>';
	return $ret;
}

function itemList($orderID,$table="CompleteSpecialOrder"){
	global $dbc;

	$ret = '<table cellspacing="0" cellpadding="4" border="1">';
	$ret .= '<tr><th>UPC</th><th>Description</th><th>Cases</th><th>Est. Price</th>
		<th>Qty</th><th>Est. Savings</th><th>&nbsp;</th></tr>';
	$q = "SELECT o.upc,o.description,total,quantity,department,sku,regPrice,ItemQtty FROM $table as o
		left join vendorItems as v on o.upc=v.upc
		WHERE order_id=$orderID AND trans_type='I'";
	$r = $dbc->query($q);
	while($w = $dbc->fetch_row($r)){
		$ret .= sprintf('<tr>
				<td>%s</td>
				<td>%s</td>
				<td>%d</td>
				<td>%.2f</td>
				<td>%.2f</td>
				<td>%.2f</td>		
				<td><a href="" onclick="deleteUPC(%d,%s);return false;">Delete</a>
				</tr>',
				$w['upc'],
				$w['description'],
				$w['ItemQtty'],
				$w['total'],
				$w['quantity'],
				($w['regPrice'] - $w['total']),
				$orderID,"'".$w['upc']."'"
			);
	}
	$ret .= '</table>';
	return $ret;
}


?>

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
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/tmp_dir.php');
include($FANNIE_ROOT.'auth/login.php');

$TRANS = $FANNIE_TRANS_DB.$dbc->sep();

$canEdit = false;
if (validateUserQuiet('ordering_edit')) {
	$canEdit = true;
}

if (!isset($_REQUEST['action'])) {
    exit;
}

$orderID = isset($_REQUEST['orderID'])?(int)$_REQUEST['orderID']:'';

switch ($_REQUEST['action']) {
    case 'loadCustomer':
        if (isset($_REQUEST['nonForm'])) {
            echo getCustomerNonForm($orderID);
        } else {
            echo getCustomerForm($orderID);
        }
        break;
    case 'reloadMem':
        echo getCustomerForm($orderID,$_REQUEST['memNum']);
        break;
    case 'loadItems':
        if (isset($_REQUEST['nonForm'])) {
            echo getItemNonForm($orderID);
        } else {
            echo getItemForm($orderID);
        }
        break;
    case 'newUPC':
        $qty = is_numeric($_REQUEST['cases'])?(int)$_REQUEST['cases']:1;
        $result = addUPC($orderID,$_REQUEST['memNum'],$_REQUEST['upc'],$qty);
        if (!is_numeric($_REQUEST['upc'])) {
            echo getDeptForm($orderID,$result[1],$result[2]);
        } else if ($result[0] === false) {
            echo getItemForm($orderID);
        } else {
            echo getQtyForm($orderID,$result[0],$result[1],$result[2]);
        }
        break;
    case 'deleteID':
        $delP = $dbc->prepare_statement("DELETE FROM {$TRANS}PendingSpecialOrder WHERE order_id=?
            AND trans_id=?");
        $delR = $dbc->exec_statement($delP, array($_REQUEST['orderID'],$_REQUEST['transID']));
        echo getItemForm($_REQUEST['orderID']);
        break;
    case 'deleteUPC':
        $upc = BarcodeLib::padUPC($_REQUEST['upc']);
        $delP = $dbc->prepare_statement("DELETE FROM {$TRANS}PendingSpecialOrder WHERE order_id=?
            AND upc=?");
        $delR = $dbc->exec_statement($delP, array($_REQUEST['orderID'],$_REQUEST['upc']));
        echo getItemForm($_REQUEST['orderID']);
        break;
    case 'saveDesc':
        $desc = $_REQUEST['desc'];
        $desc = rtrim($desc,' SO');
        $desc = substr($desc,0,32)." SO";
        $upP = $dbc->prepare_statement("UPDATE {$TRANS}PendingSpecialOrder SET
            description=? WHERE order_id=? AND trans_id=?");
        $dbc->exec_statement($upP, array($desc,$_REQUEST['orderID'],$_REQUEST['transID']));
        break;
    case 'saveCtC':
        if (sprintf("%d",$_REQUEST['val']) == "2") {
            break; // don't save with no selection
        }
        $upP = $dbc->prepare_statement("UPDATE {$TRANS}PendingSpecialOrder SET
            numflag=? WHERE order_id=? AND trans_id=0");
        $dbc->exec_statement($upP, array($_REQUEST['val'],$_REQUEST['orderID']));
        $statusP = $dbc->prepare_statement("UPDATE {$TRANS}SpecialOrderStatus SET status_flag=?,sub_status=?
            WHERE order_id=? AND status_flag in (0,3)");
        if ($_REQUEST['val'] == 1) {
            $dbc->exec_statement($statusP,array(3,time(),$_REQUEST['orderID']));
        } else if ($_REQUEST['val'] == 0) {
            $dbc->exec_statement($statusP,array(0,time(),$_REQUEST['orderID']));
        }
        break;
    case 'savePrice':
        $upP = $dbc->prepare_statement("UPDATE {$TRANS}PendingSpecialOrder SET
            total=? WHERE order_id=? AND trans_id=?");
        $dbc->exec_statement($upP, array($_REQUEST['price'],$_REQUEST['orderID'],$_REQUEST['transID']));
        $fetchP = $dbc->prepare_statement("SELECT ROUND(100*((regPrice-total)/regPrice),0)
            FROM {$TRANS}PendingSpecialOrder WHERE trans_id=? AND order_id=?");
        $fetchR = $dbc->exec_statement($fetchP, array($_REQUEST['transID'],$_REQUEST['orderID']));
        echo array_pop($dbc->fetch_row($fetchR));
        break;
    case 'saveSRP':
        $srp = $_REQUEST['srp'];
        if (strstr($srp,'*')) {
            $tmp = explode('*',$srp);
            $srp = 1;
            foreach($tmp as $t) $srp *= $t;
        }
        
        $info = reprice($_REQUEST['orderID'],$_REQUEST['transID'],$srp);
        $fetchP = $dbc->prepare_statement("SELECT ROUND(100*((regPrice-total)/regPrice),0)
            FROM {$TRANS}PendingSpecialOrder WHERE trans_id=? AND order_id=?");
        $fetchR = $dbc->exec_statement($fetchP, array($_REQUEST['transID'],$_REQUEST['orderID']));
        echo array_pop($dbc->fetch_row($fetchR));
        echo '`'.$info['regPrice'].'`'.$info['total'];
        break;
    case 'saveQty':
        $upP = $dbc->prepare_statement("UPDATE {$TRANS}PendingSpecialOrder SET
            quantity=? WHERE order_id=? AND trans_id=?");
        $dbc->exec_statement($upP, array($_REQUEST['qty'],$_REQUEST['orderID'],$_REQUEST['transID']));
        $info = reprice($_REQUEST['orderID'],$_REQUEST['transID']);
        echo $info['regPrice'].'`'.$info['total'];
        break;
    case 'saveUnit':
        $upP = $dbc->prepare_statement("UPDATE {$TRANS}PendingSpecialOrder SET
            unitPrice=? WHERE order_id=? AND trans_id=?");
        $dbc->exec_statement($upP, array($_REQUEST['unitPrice'],$_REQUEST['orderID'],$_REQUEST['transID']));
        $info = reprice($_REQUEST['orderID'],$_REQUEST['transID']);
        echo $info['regPrice'].'`'.$info['total'];
        break;
    case 'newQty':
        $upP = $dbc->prepare_statement("UPDATE {$TRANS}PendingSpecialOrder SET
            quantity=? WHERE order_id=? AND trans_id=?");
        $dbc->exec_statement($upP, array($_REQUEST['qty'],$_REQUEST['orderID'],$_REQUEST['transID']));
        $info = reprice($_REQUEST['orderID'],$_REQUEST['transID']);
        echo getItemForm($_REQUEST['orderID']);
        break;
    case 'newDept':
        $upP = $dbc->prepare_statement("UPDATE {$TRANS}PendingSpecialOrder SET
            department=? WHERE order_id=? AND trans_id=?");
        $dbc->exec_statement($upP, array($_REQUEST['dept'],$_REQUEST['orderID'],$_REQUEST['transID']));
        echo getItemForm($_REQUEST['orderID']);
        break;
    case 'saveDept':
        $upP = $dbc->prepare_statement("UPDATE {$TRANS}PendingSpecialOrder SET
            department=? WHERE order_id=? AND trans_id=?");
        $dbc->exec_statement($upP, array($_REQUEST['dept'],$_REQUEST['orderID'],$_REQUEST['transID']));
        break;
    case 'saveVendor':
        $upP = $dbc->prepare_statement("UPDATE {$TRANS}PendingSpecialOrder SET
            mixMatch=? WHERE order_id=? AND trans_id=?");
        $dbc->exec_statement($upP, array($_REQUEST['vendor'],$_REQUEST['orderID'],$_REQUEST['transID']));
        break;
    case 'saveAddr':
        if (canSaveAddress($orderID) == true) {
            $addr = $_REQUEST['addr1'];
            if (!empty($_REQUEST['addr2'])) {
                $addr .= "\n".$_REQUEST['addr2'];
            }
            $p = $dbc->prepare_statement("UPDATE {$TRANS}SpecialOrderContact
                SET street=? WHERE card_no=?");
            $dbc->exec_statement($p, array($addr,$orderID));
        }
        break;
    case 'saveFN':
        if (canSaveAddress($orderID) == true) {
            $p = $dbc->prepare_statement("UPDATE {$TRANS}SpecialOrderContact
                SET first_name=? WHERE card_no=?");
            $dbc->exec_statement($p, array($_REQUEST['fn'],$orderID));
        }
        break;
    case 'saveLN':
        if (canSaveAddress($orderID) == true) {
            $p = $dbc->prepare_statement("UPDATE {$TRANS}SpecialOrderContact
                SET last_name=? WHERE card_no=?");
            $dbc->exec_statement($p, array($_REQUEST['ln'],$orderID));
        }
        break;
    case 'saveCity':
        if (canSaveAddress($orderID) == true) {
            $p = $dbc->prepare_statement("UPDATE {$TRANS}SpecialOrderContact
                SET city=? WHERE card_no=?");
            $dbc->exec_statement($p, array($_REQUEST['city'],$orderID));
        }
        break;
    case 'saveState':
        if (canSaveAddress($orderID) == true) {
            $p = $dbc->prepare_statement("UPDATE {$TRANS}SpecialOrderContact
                SET state=? WHERE card_no=?");
            $dbc->exec_statement($p, array($_REQUEST['state'],$orderID));
        }
        break;
    case 'saveZip':
        if (canSaveAddress($orderID) == true) {
            $p = $dbc->prepare_statement("UPDATE {$TRANS}SpecialOrderContact
                SET zip=? WHERE card_no=?");
            $dbc->exec_statement($p, array($_REQUEST['zip'],$orderID));
        }
        break;
    case 'savePh':
        if (canSaveAddress($orderID) == true) {
            $p = $dbc->prepare_statement("UPDATE {$TRANS}SpecialOrderContact
                SET phone=? WHERE card_no=?");
            $dbc->exec_statement($p, array($_REQUEST['ph'],$orderID));
        }
        break;
    case 'savePh2':
        if (canSaveAddress($orderID) == true) {
            $p = $dbc->prepare_statement("UPDATE {$TRANS}SpecialOrderContact
                SET email_2=? WHERE card_no=?");
            $dbc->exec_statement($p, array($_REQUEST['ph2'],$orderID));
        }
        break;
    case 'saveEmail':
        if (canSaveAddress($orderID) == true) {
            $p = $dbc->prepare_statement("UPDATE {$TRANS}SpecialOrderContact
                SET email_1=? WHERE card_no=?");
            $dbc->exec_statement($p, array($_REQUEST['email'],$orderID));
        }
        break;
    case 'UpdateStatus':
        $p = $dbc->prepare_statement("UPDATE {$TRANS}SpecialOrderStatus SET
            status_flag=?,sub_status=? WHERE order_id=?");
        $dbc->exec_statement($p, array($_REQUEST['val'],time(),$orderID));
        echo date("m/d/Y");
        break;
    case 'saveNoteDept':
        $p = $dbc->prepare_statement("UPDATE {$TRANS}SpecialOrderNotes SET
            superID=? WHERE order_id=?");
        $dbc->exec_statement($p,array($_REQUEST['val'],$orderID));
        break;
    case 'saveText':
        $p = $dbc->prepare_statement("UPDATE {$TRANS}SpecialOrderNotes SET
            notes=? WHERE order_id=?");
        $dbc->exec_statement($p,array($_REQUEST['val'],$orderID));
        break;
    case 'confirmOrder':
        $p = $dbc->prepare_statement("INSERT INTO {$TRANS}SpecialOrderHistory VALUES
            (?,'CONFIRMED',".$dbc->now().",'')");
        $dbc->exec_statement($p,array($_REQUEST['orderID']));
        echo date("M j Y g:ia");
        break;
    case 'unconfirmOrder':
        $p = $dbc->prepare_statement("DELETE FROM {$TRANS}SpecialOrderHistory WHERE
            order_id=? AND entry_type='CONFIRMED'");
        $dbc->exec_statement($p,array($_REQUEST['orderID']));
        break;
    case 'savePN':
        $v = (int)$_REQUEST['val'];
        if ($v == 0) $v = 1;
        $p = $dbc->prepare_statement("UPDATE {$TRANS}PendingSpecialOrder SET
            voided=? WHERE order_id=?");
        $dbc->exec_statement($p,array($v,$_REQUEST['orderID']));
        break;
    case 'closeOrder':
        $p = $dbc->prepare_statement("UPDATE {$TRANS}SpecialOrderStatus SET
            status_flag=? WHERE order_id=?");
        $dbc->exec_statement($p, array($_REQUEST['status'],$_REQUEST['orderID']));

        $moveP = $dbc->prepare_statement("INSERT INTO {$TRANS}CompleteSpecialOrder
                SELECT * FROM {$TRANS}PendingSpecialOrder
                WHERE order_id=?");
        $dbc->exec_statement($moveP, array($_REQUEST['orderID']));
        
        $cleanP = $dbc->prepare_statement("DELETE FROM {$TRANS}PendingSpecialOrder
                WHERE order_id=?");
        $dbc->exec_statement($cleanP, array($_REQUEST['orderID']));
        break;
    case 'copyOrder':
        $oid = sprintf("%d",$_REQUEST['orderID']);
        $nid = duplicateOrder($oid);
        echo $nid;
        break;
    case 'splitOrder':
        $oid = sprintf("%d",$_REQUEST['orderID']);
        $tid = sprintf("%d",$_REQUEST['transID']);
        splitOrder($oid,$tid);
        echo getItemForm($oid);
        break;
    case 'UpdatePrint':
        $user = $_REQUEST['user'];
        $cachepath = sys_get_temp_dir()."/ordercache/";
        $prints = unserialize(file_get_contents("{$cachepath}{$user}.prints"));
        if (isset($prints[$_REQUEST['orderID']])) {
            unset($prints[$_REQUEST['orderID']]);
        } else {
            $prints[$_REQUEST['orderID']] = array();
        }
        $fp = fopen("{$cachepath}{$user}.prints",'w');
        fwrite($fp,serialize($prints));
        fclose($fp);
        break;
    case 'UpdateItemO':
        $oid = sprintf("%d",$_REQUEST['orderID']);
        $tid = sprintf("%d",$_REQUEST['transID']);
        $p = $dbc->prepare_statement("UPDATE {$TRANS}PendingSpecialOrder SET
                memType=(memType+1)%2 WHERE order_id=?
                AND trans_id=?");
        $dbc->exec_statement($p, array($oid,$tid));
        break;
    case 'UpdateItemA':
        $oid = sprintf("%d",$_REQUEST['orderID']);
        $tid = sprintf("%d",$_REQUEST['transID']);
        $p = $dbc->prepare_statement("UPDATE {$TRANS}PendingSpecialOrder SET
                staff=(staff+1)%2 WHERE order_id=?
                AND trans_id=?");	
        $dbc->exec_statement($p, array($oid,$tid));
        break;
}

function canSaveAddress($orderID)
{
	global $dbc,$TRANS;

	$chkP = $dbc->prepare_statement("SELECT card_no FROM {$TRANS}PendingSpecialOrder
			WHERE order_id=?");
	$chk = $dbc->exec_statement($chkP, array($orderID));
	if ($dbc->num_rows($chk) == 0) {
		return false;
	}
	$row = $dbc->fetch_row($chk);
	if ($row['card_no'] != 0 && false) return false;

	$chkP = $dbc->prepare_statement("SELECT card_no FROM {$TRANS}SpecialOrderContact
			WHERE card_no=?");
	$chk = $dbc->exec_statement($chkP, array($orderID));
	if ($dbc->num_rows($chk) == 0) {
		createContactRow($orderID);
    }

	return true;
}

function addUPC($orderID,$memNum,$upc,$num_cases=1)
{
	global $dbc, $TRANS;

	$sku = str_pad($upc,6,'0',STR_PAD_LEFT);
	if (is_numeric($upc)) {
		$upc = BarcodeLib::padUPC($upc);
    }

	$manualSKU = false;
	if (isset($upc[0]) && $upc[0] == "+") {
		$sku = substr($upc,1);
		$upc = "zimbabwe"; // string that will not match
		$manualSKU = true;
	}
	
	$ins_array = genericRow($orderID);
	$ins_array['upc'] = "'$upc'";
	if ($manualSKU) {
		$ins_array['upc'] = BarcodeLib::padUPC($sku);
    }
	$ins_array['card_no'] = "'$memNum'";
	$ins_array['trans_type'] = "'I'";

	$caseSize = 1;
	$vendor = "";
	$vendor_desc = (!is_numeric($upc)?$upc:"");
	$srp = 0.00;
	$vendor_upc = (!is_numeric($upc)?'0000000000000':"");
	$skuMatch=0;
	$caseP = $dbc->prepare_statement("SELECT units,vendorName,description,srp,i.upc,
			CASE WHEN i.upc=? THEN 0 ELSE 1 END as skuMatch FROM vendorItems as i
			LEFT JOIN vendors AS v ON
			i.vendorID=v.vendorID LEFT JOIN
			vendorSRPs AS s ON i.upc=s.upc AND i.vendorID=s.vendorID
		WHERE i.upc=? OR i.sku=? OR i.sku=?
		ORDER BY i.vendorID");
	$caseR = $dbc->exec_statement($caseP, array($upc,$upc,$sku,'0'.$sku));
	if ($dbc->num_rows($caseR) > 0) {
		list($caseSize,$vendor,$vendor_desc,$srp,$vendor_upc,$skuMatch) = $dbc->fetch_row($caseR);
    }
	if (!empty($vendor_upc)) $ins_array['upc'] = "'$vendor_upc'";
	if ($skuMatch == 1) {
		$ins_array['upc'] = "'$vendor_upc'";
		$upc = $vendor_upc;
	}
	$ins_array['quantity'] = $caseSize;
	$ins_array['ItemQtty'] = $num_cases;
	$ins_array['mixMatch'] = $dbc->escape(substr($vendor,0,26));
	$ins_array['description'] = $dbc->escape(substr($vendor_desc,0,32)." SO");

	$mempricing = false;
	if ($memNum != 0 && !empty($memNum)) {
		$p = $dbc->prepare_statement("SELECT Type,memType FROM custdata WHERE CardNo=?");
		$r = $dbc->exec_statement($p, array($memNum));
		$w = $dbc->fetch_row($r);
		if ($w['Type'] == 'PC') {
            $mempricing = true;
		} elseif($w['memType'] == 9) {
            $mempricing = true;
        }
	}

	$pdP = $dbc->prepare_statement("SELECT normal_price,special_price,department,discounttype,
		description,discount FROM products WHERE upc=?");
	$pdR = $dbc->exec_statement($pdP, array($upc));
	$qtyReq = False;
	if ($dbc->num_rows($pdR) > 0) {
		$pdW = $dbc->fetch_row($pdR);

		$ins_array['department'] = $pdW['department'];
		$ins_array['discountable'] = $pdW['discount'];
		$mapP = $dbc->prepare_statement("SELECT map_to FROM 
				{$TRANS}SpecialOrderDeptMap WHERE dept_ID=?");
		$mapR = $dbc->exec_statement($mapP, array($pdW['department']));
		if ($dbc->num_rows($mapR) > 0) {
			$ins_array['department'] = array_pop($dbc->fetch_row($mapR));
        }

		$superP = $dbc->prepare_statement("SELECT superID 
				FROM superdepts WHERE dept_ID=?");
		$superR = $dbc->exec_statement($superP, array($ins_array['department']));
		while($superW = $dbc->fetch_row($superR)) {
			if ($superW[0] == 5) $qtyReq = 3;
			if ($qtyReq !== false) {
				$caseSize = $qtyReq;
				$ins_array['quantity'] = $qtyReq;
				break;
			}
		}
		
		// only calculate prices for items that exist in 
		// vendorItems (i.e., have known case size)
		$ins_array['discounttype'] = $pdW['discounttype'];
		if ($dbc->num_rows($caseR) > 0 || true) { // test always do this
			$ins_array['total'] = $pdW['normal_price']*$caseSize*$num_cases;
			$ins_array['regPrice'] = $pdW['normal_price']*$caseSize*$num_cases;
			$ins_array['unitPrice'] = $pdW['normal_price'];
			if ($pdW['discount'] != 0 && $pdW['discounttype'] == 1) {
				$ins_array['total'] = $pdW['special_price']*$caseSize*$num_cases;
				$ins_array['unitPrice'] = $pdW['special_price'];
			} elseif ($mempricing){
				if ($pdW['discounttype'] == 2) {
					$ins_array['total'] = $pdW['special_price']*$caseSize*$num_cases;
					$ins_array['unitPrice'] = $pdW['special_price'];
				} elseif ($pdW['discounttype'] == 3) {
					$ins_array['unitPrice'] = $pdW['normal_price']*(1-$pdW['special_price']);
					$ins_array['total'] = $ins_array['unitPrice']*$caseSize*$num_cases;
				} elseif ($pdW['discounttype'] == 5) {
					$ins_array['unitPrice'] = $pdW['normal_price']-$pdW['special_price'];
					$ins_array['total'] = $ins_array['unitPrice']*$caseSize*$num_cases;
				}

				if($pdW['discount'] != 0 && ($pdW['normal_price']*$caseSize*$num_cases*0.85) < $ins_array['total']) {
					$ins_array['total'] = $pdW['normal_price']*$caseSize*$num_cases*0.85;
					$ins_array['discounttype'] = 0;
					$ins_array['unitPrice'] = $pdW['normal_price'];
				}
			}
		}
		$ins_array['description'] = "'".substr($pdW['description'],0,32)." SO'";
	} elseif ($srp != 0) {
		// use vendor SRP if applicable
		$ins_array['regPrice'] = $srp*$caseSize*$num_cases;
		$ins_array['total'] = $srp*$caseSize*$num_cases;
		$ins_array['unitPrice'] = $srp;
		if ($mempricing) {
			$ins_array['total'] *= 0.85;
        }
	}

	$tidP = $dbc->prepare_statement("SELECT MAX(trans_id),MAX(voided),MAX(numflag) 
			FROM {$TRANS}PendingSpecialOrder WHERE order_id=?");
	$tidR = $dbc->exec_statement($tidP,array($orderID));
	$tidW = $dbc->fetch_row($tidR);
	$ins_array['trans_id'] = $tidW[0]+1;
	$ins_array['voided'] = $tidW[1];
	$ins_array['numflag'] = $tidW[2];

	$dbc->smart_insert("{$TRANS}PendingSpecialOrder",$ins_array);
	
	return array($qtyReq,$ins_array['trans_id'],$ins_array['description']);
}

function createContactRow($orderID)
{
	global $dbc,$TRANS;

	$testP = $dbc->prepare_statement("SELECT card_no FROM {$TRANS}SpecialOrderContact
		WHERE card_no=?");
	$testR = $dbc->exec_statement($testP,array($orderID));
	if ($dbc->num_rows($testR) > 0) return true;

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
	$dbc->smart_insert("{$TRANS}SpecialOrderContact",$vals);
}

function splitOrder($orderID,$transID)
{
	global $dbc, $TRANS;
	// copy entire order
	$newID = duplicateOrder($orderID,'PendingSpecialOrder');	
	
	// remove all items except desired one
	$cleanP = $dbc->prepare_statement("DELETE FROM {$TRANS}PendingSpecialOrder WHERE
		order_id=? AND trans_id > 0 AND trans_id<>?");
	$dbc->exec_statement($cleanP,array($newID,$transID));

	// remove the item from original order
	$cleanP2 = $dbc->prepare_statement("DELETE FROM {$TRANS}PendingSpecialOrder WHERE
			order_id=? AND trans_id=?");
	$dbc->exec_statement($cleanP2,array($orderID,$transID));

	// fix trans_id on the new order
	$cleanP3 = $dbc->prepare_statement("UPDATE {$TRANS}PendingSpecialOrder SET trans_id=1
			WHERE order_id=? AND trans_id=?");
	$dbc->exec_statement($cleanP3,array($newID,$transID));
}

function duplicateOrder($old_id,$from='CompleteSpecialOrder')
{
	global $dbc, $TRANS;
	$new_id = createEmptyOrder();
	$delQ = $dbc->prepare_statement("DELETE FROM {$TRANS}PendingSpecialOrder 
			WHERE order_id=?");
	$dbc->exec_statement($delQ,array($new_id));

	$copyQ = $dbc->prepare_statement("INSERT INTO {$TRANS}PendingSpecialOrder
		SELECT ?,".$dbc->now().",
		register_no,emp_no,trans_no,upc,description,
		trans_type,trans_subtype,trans_status,
		department,quantity,scale,cost,unitPrice,
		total,regPrice,tax,foodstamp,discount,
		memDiscount,discountable,discounttype,
		voided,percentDiscount,ItemQtty,volDiscType,
		volume,VolSpecial,mixMatch,matched,memtype,
		staff,0,'',card_no,trans_id
		FROM {$TRANS}$from WHERE order_id=?");
	$dbc->exec_statement($copyQ, array($new_id,$old_id));

	$delP = $dbc->prepare_statement("DELETE FROM {$TRANS}SpecialOrderContact WHERE card_no=?");
	$dbc->exec_statement($delP,array($new_id));
	$contactQ = $dbc->prepare_statement("INSERT INTO {$TRANS}SpecialOrderContact
		SELECT ?,last_name,first_name,othlast_name,othfirst_name,
		street,city,state,zip,phone,email_1,email_2,ads_OK FROM
		{$TRANS}SpecialOrderContact WHERE card_no=?");
	$dbc->exec_statement($contactQ, array($new_id,$old_id));

	$delP = $dbc->prepare_statement("DELETE FROM {$TRANS}SpecialOrderNotes WHERE order_id=?");
	$dbc->exec_statement($delP,array($new_id));
	$notesQ = $dbc->prepare_statement("INSERT INTO {$TRANS}SpecialOrderNotes
		SELECT ?,notes,superID FROM
		{$TRANS}SpecialOrderNotes WHERE order_id=?");
	$dbc->exec_statement($notesQ,array($new_id,$old_id));

	$user = checkLogin();
	$userQ = $dbc->prepare_statement("UPDATE {$TRANS}PendingSpecialOrder SET mixMatch=?
			WHERE order_id=? AND trans_id=0");
	$userR = $dbc->exec_statement($userQ, array($user,$new_id));

	$statusQ = $dbc->prepare_statement("SELECT numflag FROM {$TRANS}PendingSpecialOrder
		WHERE order_id=?");
	$statusR = $dbc->exec_statement($statusQ,array($new_id));
	$st = array_pop($dbc->fetch_row($statusR));
	$stP = $dbc->prepare_statement("UPDATE {$TRANS}SpecialOrderStatus SET status_flag=?,sub_status=?
		WHERE order_id=?");
	if ($st == 1) {
		$dbc->exec_statement($stP,array(3,time(),$new_id));
	} else if ($st == 0) {
		$dbc->exec_statement($stP,array(0,time(),$new_id));
	}
	
	return $new_id;
}

function createEmptyOrder()
{
	global $dbc,$TRANS,$FANNIE_SERVER_DBMS;
	$user = checkLogin();
	$orderID = 1;
	$val = ($FANNIE_SERVER_DBMS != "MSSQL" ? "VALUES()" : "DEFAULT VALUES");
	$dbc->query("INSERT {$TRANS}SpecialOrderID $val");
	$orderID = $dbc->insert_id();

	$ins_array = genericRow($orderID);
	$ins_array['numflag'] = 2;
	$ins_array['mixMatch'] = $dbc->escape($user);
	$dbc->smart_insert("{$TRANS}PendingSpecialOrder",$ins_array);

	$vals = array(
		'order_id'=>$orderID,
		'notes'=>"''",
		'superID'=>0
	);
	$dbc->smart_insert("{$TRANS}SpecialOrderNotes",$vals);

	$vals = array(
		'order_id'=>$orderID,
		'status_flag'=>3,
		'sub_status'=>time()
	);
	$dbc->smart_insert("{$TRANS}SpecialOrderStatus",$vals);

	createContactRow($orderID);

	return $orderID;
}

function genericRow($orderID)
{
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
	'staff'=>0,
	'numflag'=>0,
	'charflag'=>"''",	
	'card_no'=>0,
	'trans_id'=>0
	);
}

function getCustomerForm($orderID,$memNum="0")
{
	global $dbc, $TRANS;

	if (empty($orderID)) $orderID = createEmptyOrder();

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
		'Type' => 'REG',
		'status' => ''
	);

	$notes = "";
	$noteDept = 0;
	
	$table = "PendingSpecialOrder";

	// detect member UPC entry
	if ($memNum > 9999999) {
		$p = $dbc->prepare_statement("SELECT card_no FROM memberCards WHERE upc=?");
		$r = $dbc->exec_statement($p,array(BarcodeLib::padUPC($memNum)));
		if ($dbc->num_rows($r) > 0) {
			$memNum = array_pop($dbc->fetch_row($r));
		} else {
			$memNum = "";
        }
	}

	// look up member id if applicable
	if ($memNum === "0") {
		$findMem = $dbc->prepare_statement("SELECT card_no,voided FROM {$TRANS}$table WHERE order_id=?");
		$memR = $dbc->exec_statement($findMem, array($orderID));
		if ($dbc->num_rows($memR) > 0) {
			$memW = $dbc->fetch_row($memR);
			$memNum = $memW['card_no'];
			$pn = $memW['voided'];
		}
	} else if ($memNum == "") {
		$p = $dbc->prepare_statement("UPDATE {$TRANS}PendingSpecialOrder SET card_no=?,voided=0
			WHERE order_id=?");
		$r = $dbc->exec_statement($p,array(0,$orderID));
	} else {
		
		$p = $dbc->prepare_statement("UPDATE {$TRANS}PendingSpecialOrder SET card_no=?
			WHERE order_id=?");
		$r = $dbc->exec_statement($p,array($memNum,$orderID));

		$p = $dbc->prepare_statement("UPDATE {$TRANS}SpecialOrderContact SET
			street='',phone='' WHERE card_no=?");
		$r = $dbc->exec_statement($p, array($orderID));
		
		// look up personnum, correct if it hasn't been set
		$pQ = $dbc->prepare_statement("SELECT voided FROM {$TRANS}PendingSpecialOrder
			WHERE order_id=?");
		$pR = $dbc->exec_statement($pQ,array($orderID));
		$pn = array_pop($dbc->fetch_row($pR));
		if ($pn == 0) {
			$pn = 1;
			$upP = $dbc->prepare_statement("UPDATE {$TRANS}PendingSpecialOrder SET voided=?
				WHERE order_id=?");
			$upR = $dbc->exec_statement($upP,array($pn,$orderID));
		}
	}

	if ($memNum != 0) {
		$namesP = $dbc->prepare_statement("SELECT personNum,FirstName,LastName FROM custdata
			WHERE CardNo=? ORDER BY personNum");
		$namesR = $dbc->exec_statement($namesP,array($memNum));
		while($namesW = $dbc->fetch_row($namesR)) {
			$names[$namesW['personNum']] = array($namesW['FirstName'],$namesW['LastName']);
        }

		// load member contact info into SpecialOrderContact
		// on first go so it can be edited separately
		$testQ = $dbc->prepare_statement("SELECT street,phone FROM 
				{$TRANS}SpecialOrderContact WHERE card_no=?");
		$testR = $dbc->exec_statement($testQ,array($orderID));
		$testW = $dbc->fetch_row($testR);
		if (empty($testW['street']) && empty($testW['phone'])) {
			$contactQ = $dbc->prepare_statement("SELECT street,city,state,zip,phone,email_1,email_2
					FROM meminfo WHERE card_no=?");
			$contactR = $dbc->exec_statement($contactQ, array($memNum));
            if ($dbc->num_rows($contactR) > 0) {
                $contact_row = $dbc->fetch_row($contactR);
			
                $upP = $dbc->prepare_statement("UPDATE {$TRANS}SpecialOrderContact SET street=?,city=?,state=?,zip=?,
                        phone=?,email_1=?,email_2=? WHERE card_no=?");
                $upR = $dbc->exec_statement($upP,array(
                    $contact_row['street'],
                    $contact_row['city'],
                    $contact_row['state'],
                    $contact_row['zip'],
                    $contact_row['phone'],
                    $contact_row['email_1'],
                    $contact_row['email_2'],
                    $orderID
                ));
            }
		} else {
			$contactQ = $dbc->prepare_statement("SELECT street,city,state,zip,phone,email_1,email_2
					FROM {$TRANS}SpecialOrderContact WHERE card_no=?");
			$contactR = $dbc->exec_statement($contactQ, array($orderID));
            if ($dbc->num_rows($contactR) > 0) {
                $contact_row = $dbc->fetch_row($contactR);
            }
		}

		$statusQ = $dbc->prepare_statement("SELECT Type FROM custdata WHERE CardNo=?");
		$statusR = $dbc->exec_statement($statusQ,array($memNum));
		$status_row  = $dbc->fetch_row($statusR);
		if ($status_row['Type'] == 'INACT') {
			$status_row['status'] = 'Inactive';
		} elseif ($status_row['Type'] == 'INACT2') {
			$status_row['status'] = 'Inactive';
		} elseif ($status_row['Type'] == 'TERM') {
			$status_row['status'] = 'Terminated';
        }
	} else {
		$contactQ = $dbc->prepare_statement("SELECT last_name,first_name,street,city,
				state,zip,phone,email_1,email_2
				FROM {$TRANS}SpecialOrderContact WHERE card_no=?");
		$contactR = $dbc->exec_statement($contactQ, array($orderID));
		if ($dbc->num_rows($contactR) > 0) {
			$contact_row = $dbc->fetch_row($contactR);
			$fn = $contact_row['first_name'];
			$ln = $contact_row['last_name'];
		}
	}

	$q = $dbc->prepare_statement("SELECT notes,superID FROM {$TRANS}SpecialOrderNotes WHERE order_id=?");
	$r = $dbc->exec_statement($q, array($orderID));
	if ($dbc->num_rows($r) > 0) {
		list($notes,$noteDept) = $dbc->fetch_row($r);
    }

	$q = $dbc->prepare_statement("SELECT entry_date FROM {$TRANS}SpecialOrderHistory 
			WHERE order_id=? AND entry_type='CONFIRMED'");
	$r = $dbc->exec_statement($q, array($orderID));
	$confirm_date = "";
	if ($dbc->num_rows($r) > 0)	{
		$confirm_date = array_pop($dbc->fetch_row($r));
    }

	$callback = 2;
	$user = 'Unknown';
	$orderDate = "";
	$q = $dbc->prepare_statement("SELECT datetime,numflag,mixMatch FROM 
			{$TRANS}PendingSpecialOrder WHERE order_id=? AND trans_id=0");
	$r = $dbc->exec_statement($q, array($orderID));
	if ($dbc->num_rows($r) > 0) {
		list($orderDate,$callback,$user) = $dbc->fetch_row($r);
    }

	$ret = "";
	$ret .= '<table width="95%" cellpadding="4" cellspacing=4" border="0">';
	$ret .= '<tr><td align="left" valign="top">';
	$ret .= sprintf('<input type="hidden" id="orderID" value="%d" />',$orderID);
	$ret .= sprintf('<b>Owner Number</b>: <input type="text" size="6"
			id="memNum" value="%s" onchange="memNumEntered();"
			/>',($memNum==0?'':$memNum));
	$ret .= '<br />';
	$ret .= '<b>Owner</b>: '.($status_row['Type']=='PC'?'Yes':'No');
	$ret .= sprintf('<input type="hidden" id="isMember" value="%s" />',
			$status_row['Type']);
	$ret .= '<br />';
	if (!empty($status_row['status'])) {
		$ret .= '<b>Account status</b>: '.$status_row['status'];
		$ret .= '<br />';
	}
	$ret .= '</td><td align="right" valign="top">';
	$ret .= "<input type=\"submit\" value=\"Done\"
		onclick=\"validateAndHome();return false;\" />";
	$username = checkLogin();
	$prints = array();
	$cachepath = sys_get_temp_dir()."/ordercache/";
	if (file_exists("{$cachepath}{$username}.prints")) {
		$prints = unserialize(file_get_contents("{$cachepath}{$username}.prints"));
	} else {
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
	$extra .= '<option value="2"></option>';
	if ($callback == 1) {
		$extra .= '<option value="1" selected>Yes</option>';	
		$extra .= '<option value="0">No</option>';	
	} else if ($callback == 0) {
		$extra .= '<option value="1">Yes</option>';	
		$extra .= '<option value="0" selected>No</option>';	
	} else {
		$extra .= '<option value="1">Yes</option>';	
		$extra .= '<option value="0">No</option>';	
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
	if (empty($names)) {
		$ret .= sprintf('<tr><th>First Name</th><td>
				<input type="text" id="t_firstName" 
				value="%s" onchange="saveFN(%d,this.value);" 
				/></td>',$fn,$orderID);
		$ret .= sprintf('<th>Last Name</th><td><input 
				type="text" id="t_lastName" value="%s"
				onchange="saveLN(%d,this.value);" /></td>',
				$ln,$orderID);
	} else {
		$ret .= sprintf('<tr><th>Name</th><td colspan="2"><select id="s_personNum"
			onchange="savePN(%d,this.value);">',$orderID);
		foreach($names as $p=>$n) {
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
	$sQ = $dbc->prepare_statement("select superID,super_name from MasterSuperDepts
		where superID > 0
		group by superID,super_name
		order by super_name");
	$sR = $dbc->exec_statement($sQ);
	while($sW = $dbc->fetch_row($sR)) {
		$ret .= sprintf('<option value="%d" %s>%s</option>',
			$sW['superID'],
			($sW['superID']==$noteDept?'selected':''),
			$sW['super_name']);
	}
	$ret .= "</select></td></tr>";

	// address
	if(strstr($contact_row['street'],"\n")) {
		$tmp = explode("\n",$contact_row['street']);	
		$contact_row['street'] = $tmp[0];
		$contact_row['street2'] = $tmp[1];
	} else {
		$contact_row['street2'] = '';
    }

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

	return $ret."`".$extra;
}

function getCustomerNonForm($orderID)
{
	global $dbc, $TRANS;

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
		'Type' => 'REG',
		'status' => ''
	);

	$notes = "";
	$noteDept = 0;
	
	// look up member id 
	$memNum = 0;
	$findMem = $dbc->prepare_statement("SELECT card_no,voided FROM {$TRANS}CompleteSpecialOrder WHERE order_id=?");
	$memR = $dbc->exec_statement($findMem, array($orderID));
	if ($dbc->num_rows($memR) > 0) {
		$memW = $dbc->fetch_row($memR);
		$memNum = $memW['card_no'];
		$pn = $memW['voided'];
	}

	// Get member info from custdata, non-member info from SpecialOrderContact
	if ($memNum != 0) {
		$namesP = $dbc->prepare_statement("SELECT personNum,FirstName,LastName FROM custdata
			WHERE CardNo=? ORDER BY personNum");
		$namesR = $dbc->exec_statement($namesP,array($memNum));
		while($namesW = $dbc->fetch_row($namesR)) {
			$names[$namesW['personNum']] = array($namesW['FirstName'],$namesW['LastName']);
        }

		$contactQ = $dbc->prepare_statement("SELECT street,city,state,zip,phone,email_1,email_2
				FROM meminfo WHERE card_no=?");
		$contactR = $dbc->exec_statement($contactQ, array($memNum));
		$contact_row = $dbc->fetch_row($contactR);

		$statusQ = $dbc->prepare_statement("SELECT Type FROM custdata WHERE CardNo=?");
		$statusR = $dbc->exec_statement($statusQ,array($memNum));
		$status_row  = $dbc->fetch_row($statusR);
		if ($status_row['Type'] == 'INACT') {
			$status_row['status'] = 'Inactive';
		} elseif ($status_row['Type'] == 'INACT2') {
			$status_row['status'] = 'Inactive';
		} elseif ($status_row['Type'] == 'TERM') {
			$status_row['status'] = 'Terminated';
        }
	} else {
		$contactQ = $dbc->prepare_statement("SELECT last_name,first_name,street,city,
				state,zip,phone,email_1,email_2
				FROM {$TRANS}SpecialOrderContact WHERE card_no=?");
		$contactR = $dbc->exec_statement($contactQ, array($orderID));
		if ($dbc->num_rows($contactR) > 0) {
			$contact_row = $dbc->fetch_row($contactR);
			$fn = $contact_row['first_name'];
			$ln = $contact_row['last_name'];
		}
	}

	$q = $dbc->prepare_statement("SELECT notes,superID FROM {$TRANS}SpecialOrderNotes WHERE order_id=?");
	$r = $dbc->exec_statement($q, array($orderID));
	if ($dbc->num_rows($r) > 0) {
		list($notes,$noteDept) = $dbc->fetch_row($r);
    }

	$q = $dbc->prepare_statement("SELECT entry_date FROM {$TRANS}SpecialOrderHistory 
			WHERE order_id=? AND entry_type='CONFIRMED'");
	$r = $dbc->exec_statement($q, array($orderID));
	$confirm_date = "";
	if ($dbc->num_rows($r) > 0)	{
		$confirm_date = array_pop($dbc->fetch_row($r));
    }

	$callback = 1;
	$user = 'Unknown';
	$orderDate = '';
	$q = $dbc->prepare_statement("SELECT datetime,numflag,mixMatch FROM 
			{$TRANS}CompleteSpecialOrder WHERE order_id=? AND trans_id=0");
	$r = $dbc->exec_statement($q, array($orderID));
	if ($dbc->num_rows($r) > 0) {
		list($orderDate,$callback,$user) = $dbc->fetch_row($r);
    }

	$ret = "";
	$ret .= sprintf('<input type="hidden" id="orderID" value="%d" />',$orderID);
	$ret .= '<table width="95%" cellpadding="4" cellspacing=4" border="0">';
	$ret .= '<tr><td align="left" valign="top">';
	$ret .= sprintf('<b>Owner Number</b>: %s',
			($memNum==0?'':$memNum));
	$ret .= '<br />';
	$ret .= '<b>Owner</b>: '.($status_row['Type']=='PC'?'Yes':'No');
	$ret .= '<br />';
	if (!empty($status_row['status'])) {
		$ret .= '<b>Account status</b>: '.$status_row['status'];
		$ret .= '<br />';
	}
	$ret .= "<b>Taken by</b>: ".$user."<br />";
	$ret .= "<b>On</b>: ".date("M j, Y g:ia",strtotime($orderDate))."<br />";
	$ret .= '</td><td align="right" valign="top">';
	$ret .= '<b>Call to Confirm</b>: ';
	if ($callback == 1) {
		$ret .= 'Yes';
	} else {
		$ret .= 'No';
    }
	$ret .= '<br />';	
	$ret .= '<span id="confDateSpan">'.(!empty($confirm_date)?'Confirmed '.$confirm_date:'Not confirmed')."</span> ";
	$ret .= '<br />';

	$ret .= "<input type=\"submit\" value=\"Done\"
		onclick=\"location='index.php';\" />";
	$ret .= '</td></tr></table>';

	$ret .= '<table cellspacing="0" cellpadding="4" border="1">';

	// names
	if (empty($names)) {
		$ret .= sprintf('<tr><th>First Name</th><td>%s
				</td>',$fn,$orderID);
		$ret .= sprintf('<th>Last Name</th><td>%s
				</td>',
				$ln,$orderID);
	} else {
		$ret .= '<tr><th>Name</th><td colspan="2">';
		foreach($names as $p=>$n) {
			if ($p == $pn) $ret .= $n[0].' '.$n[1];
		}
		$ret .= '</td>';
		$ret .= '<td>&nbsp;</td>';
	}
	$ret .= sprintf('<td colspan="4">Notes for:
		<select id="nDept">
		<option value="0">Choose...</option>',$orderID);
	$sQ = $dbc->prepare_statement("select superID,super_name 
		from MasterSuperDepts
		where superID > 0
		group by superID,super_name
		order by super_name");
	$sR = $dbc->exec_statement($sQ);
	while($sW = $dbc->fetch_row($sR)) {
		$ret .= sprintf('<option value="%d" %s>%s</option>',
			$sW['superID'],
			($sW['superID']==$noteDept?'selected':''),
			$sW['super_name']);
	}
	$ret .= "</select></td></tr>";

	// address
	if(strstr($contact_row['street'],"\n")) {
		$tmp = explode("\n",$contact_row['street']);	
		$contact_row['street'] = $tmp[0];
		$contact_row['street2'] = $tmp[1];
	} else {
		$contact_row['street2'] = '';
    }

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

function getQtyForm($orderID,$default,$transID,$description)
{
	global $dbc;
	$ret = '<i>This item ('.$description.') requires a quantity</i><br />';
	$ret .= "<form onsubmit=\"newQty($orderID,$transID);return false;\">";
	$ret .= '<b>Qty</b>: <input type="text" id="newqty" value="'.$default.'" maxlength="3" size="4" />';
	$ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	$ret .= '<input type="submit" value="Enter Qty" />';
	$ret .= '</form>';

	return $ret;
}

function getDeptForm($orderID,$transID,$description)
{
	global $dbc, $TRANS;
	$ret = '<i>This item ('.$description.') requires a department</i><br />';
	$ret .= "<form onsubmit=\"newDept($orderID,$transID);return false;\">";
	$ret .= '<select id="newdept">';
	$q = $dbc->prepare_statement("select super_name,
		CASE WHEN MIN(map_to) IS NULL THEN MIN(m.dept_ID) ELSE MIN(map_to) END
		from MasterSuperDepts
		as m left join {$TRANS}SpecialOrderDeptMap as s
		on m.dept_ID=s.dept_ID
		where m.superID > 0
		group by super_name ORDER BY super_name");
	$r = $dbc->exec_statement($q);
	while($w = $dbc->fetch_row($r)) {
		$ret .= sprintf('<option value="%d">%s</option>',$w[1],$w[0]);
    }
	$ret .= "</select>";
	$ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	$ret .= '<input type="submit" value="Enter Dept" />';
	$ret .= '</form>';
    
	return $ret;
}

function getItemForm($orderID)
{
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
	if ($table == "PendingSpecialOrder" && $canEdit) {
		$ret .= editableItemList($orderID);
	} else {
		$ret .= itemList($orderID,$table);
    }

	// disable manual-close for now
	if ($canEdit && true) {
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

function editableItemList($orderID)
{
	global $dbc,$TRANS;

	$dQ = $dbc->prepare_statement("SELECT dept_no,dept_name FROM departments order by dept_no");
	$dR = $dbc->exec_statement($dQ);
	$depts = array(0=>'Unassigned');
	while($dW = $dbc->fetch_row($dR)) {
		$depts[$dW['dept_no']] = $dW['dept_name'];
    }

	$ret = '<table cellspacing="0" cellpadding="4" border="1">';
	$ret .= '<tr><th>UPC</th><th>SKU</th><th>Description</th><th>Cases</th><th>SRP</th><th>Actual</th><th>Qty</th><th>Dept</th><th>&nbsp;</th></tr>';
	$q = $dbc->prepare_statement("SELECT o.upc,o.description,total,quantity,department,
		v.sku,ItemQtty,regPrice,o.discounttype,o.charflag,o.mixMatch,
		o.trans_id,o.unitPrice,o.memType,o.staff
		FROM {$TRANS}PendingSpecialOrder as o
		left join vendorItems as v on o.upc=v.upc AND vendorID=1
		WHERE order_id=? AND trans_type='I' 
		ORDER BY trans_id DESC");
	$r = $dbc->exec_statement($q, array($orderID));
	$num_rows = $dbc->num_rows($r);
	$prev_id = 0;
	while($w = $dbc->fetch_row($r)) {
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
		foreach($depts as $id=>$name) {
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
		if ($w['discounttype'] == 1 || $w['discounttype'] == 2) {
			$ret .= '<td id="discPercent'.$w['trans_id'].'">Sale</td>';
		} else if ($w['regPrice'] != $w['total']) {
			$ret .= sprintf('<td id="discPercent%d">%d%%</td>',$w['upc'],
				round(100*(($w['regPrice']-$w['total'])/$w['regPrice'])));
		} else {
			$ret .= '<td id="discPercent'.$w['upc'].'">0%</td>';
		}
		$ret .= sprintf('<td colspan="2">Printed: %s</td>',
				($w['charflag']=='P'?'Yes':'No'));
		if ($num_rows > 1) {
			$ret .= sprintf('<td colspan="2"><input type="submit" value="Split Item to New Order"
				onclick="doSplit(%d,%d);return false;" /><br />
				O <input type="checkbox" id="itemChkO" %s onclick="toggleO(%d,%d);" />&nbsp;&nbsp;&nbsp;&nbsp;
				A <input type="checkbox" id="itemChkA" %s onclick="toggleA(%d,%d);" />
				</td>',
				$orderID,$w['trans_id'],
				($w['memType']>0?'checked':''),$orderID,$w['trans_id'],
				($w['staff']>0?'checked':''),$orderID,$w['trans_id']);
		} else {
			$ret .= '<td colspan="2"></td>';
        }
		$ret .= '</tr>';
		$ret .= '<tr><td colspan="9"><span style="font-size:1;">&nbsp;</span></td></tr>';
		$prev_id=$w['trans_id'];
	}
	$ret .= '</table>';

	return $ret;
}

function itemList($orderID,$table="PendingSpecialOrder")
{
	global $dbc,$TRANS;

	$ret = '<table cellspacing="0" cellpadding="4" border="1">';
	$ret .= '<tr><th>UPC</th><th>Description</th><th>Cases</th><th>Pricing</th><th>&nbsp;</th></tr>';
		//<th>Est. Price</th>
		//<th>Qty</th><th>Est. Savings</th><th>&nbsp;</th></tr>';
	$q = $dbc->prepare_statement("SELECT o.upc,o.description,total,quantity,
		department,regPrice,ItemQtty,discounttype,trans_id FROM {$TRANS}$table as o
		WHERE order_id=? AND trans_type='I'");
	$r = $dbc->exec_statement($q, array($orderID));
	while($w = $dbc->fetch_row($r)) {
		$pricing = "Regular";
		if ($w['discounttype'] == 1) {
			$pricing = "Sale";
		} elseif($w['regPrice'] != $w['total']) {
			if ($w['discounttype']==2) {
				$pricing = "Sale";
			} else {
				$pricing = "% Discount";
            }
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

function getItemNonForm($orderID)
{
	global $dbc,$TRANS;

	$dQ = $dbc->prepare_statement("SELECT dept_no,dept_name FROM departments order by dept_no");
	$dR = $dbc->exec_statement($dQ);
	$depts = array(0=>'Unassigned');
	while($dW = $dbc->fetch_row($dR)) {
		$depts[$dW['dept_no']] = $dW['dept_name'];
    }

	$ret = '<table cellspacing="0" cellpadding="4" border="1">';
	$ret .= '<tr><th>UPC</th><th>SKU</th><th>Description</th><th>Cases</th><th>SRP</th><th>Actual</th><th>Qty</th><th>Dept</th></tr>';
	$q = $dbc->prepare_statement("SELECT o.upc,o.description,total,quantity,department,
		sku,ItemQtty,regPrice,o.discounttype,o.charflag,o.mixMatch FROM {$TRANS}CompleteSpecialOrder as o
		left join vendorItems as v on o.upc=v.upc
		WHERE order_id=? AND trans_type='I' AND (vendorID=1 or vendorID is null)
		ORDER BY trans_id DESC");
	$r = $dbc->exec_statement($q, array($orderID));
	while($w = $dbc->fetch_row($r)) {
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
			foreach($depts as $id=>$name) {
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
			if ($w['discounttype'] == 1 || $w['discounttype'] == 2) {
				$ret .= '<td id="discPercent'.$w['upc'].'">Sale</td>';
			} else if ($w['regPrice'] != $w['total']) {
				$ret .= sprintf('<td id="discPercent%s">%d%%</td>',$w['upc'],
					round(100*(($w['regPrice']-$w['total'])/$w['regPrice'])));
			} else {
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

function reprice($oid,$tid,$reg=false)
{
	global $dbc,$TRANS;

	$query = $dbc->prepare_statement("SELECT o.unitPrice,o.itemQtty,o.quantity,o.discounttype,
		c.type,c.memType,o.regPrice,o.total,o.discountable
		FROM {$TRANS}PendingSpecialOrder AS o LEFT JOIN custdata AS c ON
		o.card_no=c.CardNo AND c.personNum=1
		WHERE order_id=? AND trans_id=?");
	$response = $dbc->exec_statement($query, array($oid,$tid));
	$row = $dbc->fetch_row($response);

	$regPrice = $row['itemQtty']*$row['quantity']*$row['unitPrice'];
	if ($reg) {
		$regPrice = $reg;
    }
	$total = $regPrice;
	if (($row['type'] == 'PC' || $row['memType'] == 9) && $row['discountable'] != 0 && $row['discounttype'] == 0) {
		$total *= 0.85;
	}

	if ($row['unitPrice'] == 0 || $row['quantity'] == 0) {
		$regPrice = $row['regPrice'];
		$total = $row['total'];
	}

	$query = $dbc->prepare_statement("UPDATE {$TRANS}PendingSpecialOrder SET
			total=?,regPrice=?
			WHERE order_id=? AND trans_id=?");
	$dbc->exec_statement($query, array($total,$regPrice,$oid,$tid));

	return array(
		'regPrice'=>sprintf("%.2f",$regPrice),
		'total'=>sprintf("%.2f",$total)
	);
}


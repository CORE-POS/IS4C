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

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!function_exists('checkLogin')) {
    include($FANNIE_ROOT.'auth/login.php');
}

$dbc = FannieDB::get($FANNIE_OP_DB);
$TRANS = $FANNIE_TRANS_DB.$dbc->sep();

$canEdit = false;
if (validateUserQuiet('ordering_edit')) {
    $canEdit = true;
}

if (FormLib::get('action') === '') {
    return;
}

$orderID = FormLib::get('orderID');

switch (FormLib::get('action')) {
    case 'loadCustomer':
        if (FormLib::get('nonForm') !== '') {
            echo getCustomerNonForm($orderID);
        } else {
            echo getCustomerForm($orderID);
        }
        break;
    case 'reloadMem':
        echo getCustomerForm($orderID,FormLib::get('memNum'));
        break;
    case 'loadItems':
        if (FormLib::get('nonForm') !== '') {
            echo getItemNonForm($orderID);
        } else {
            echo getItemForm($orderID);
        }
        break;
    case 'loadHistory':
        echo getOrderHistory($orderID);
        break;
    case 'newUPC':
        $qty = FormLib::get('cases', 1);
        $result = addUPC($orderID,FormLib::get('memNum'),FormLib::get('upc'),$qty);
        if (!is_numeric(FormLib::get('upc'))) {
            echo getDeptForm($orderID,$result[1],$result[2]);
        } else if ($result[0] === false) {
            echo getItemForm($orderID);
        } else {
            echo getQtyForm($orderID,$result[0],$result[1],$result[2]);
        }
        break;
    case 'deleteID':
        $delP = $dbc->prepare("DELETE FROM {$TRANS}PendingSpecialOrder WHERE order_id=?
            AND trans_id=?");
        $delR = $dbc->execute($delP, array($orderID,FormLib::get('transID')));
        echo getItemForm($orderID);
        break;
    case 'saveDesc':
        $desc = FormLib::get('desc');
        $desc = rtrim($desc,' SO');
        $desc = substr($desc,0,32)." SO";
        $upP = $dbc->prepare("UPDATE {$TRANS}PendingSpecialOrder SET
            description=? WHERE order_id=? AND trans_id=?");
        $dbc->execute($upP, array($desc,$orderID,FormLib::get('transID')));
        break;
    case 'savePrice':
        $upP = $dbc->prepare("UPDATE {$TRANS}PendingSpecialOrder SET
            total=? WHERE order_id=? AND trans_id=?");
        $dbc->execute($upP, array($_REQUEST['price'],$orderID,$_REQUEST['transID']));
        $fetchP = $dbc->prepare("SELECT ROUND(100*((regPrice-total)/regPrice),0)
            FROM {$TRANS}PendingSpecialOrder WHERE trans_id=? AND order_id=?");
        $fetchR = $dbc->execute($fetchP, array($_REQUEST['transID'],$orderID));
        echo array_pop($dbc->fetch_row($fetchR));
        break;
    case 'saveSRP':
        $srp = $_REQUEST['srp'];
        if (strstr($srp,'*')) {
            $tmp = explode('*',$srp);
            $srp = 1;
            foreach($tmp as $t) $srp *= $t;
        }
        
        $info = reprice($orderID,$_REQUEST['transID'],$srp);
        $fetchP = $dbc->prepare("SELECT ROUND(100*((regPrice-total)/regPrice),0)
            FROM {$TRANS}PendingSpecialOrder WHERE trans_id=? AND order_id=?");
        $fetchR = $dbc->execute($fetchP, array($_REQUEST['transID'],$orderID));
        echo array_pop($dbc->fetch_row($fetchR));
        echo '`'.$info['regPrice'].'`'.$info['total'];
        break;
    case 'saveQty':
        $upP = $dbc->prepare("UPDATE {$TRANS}PendingSpecialOrder SET
            quantity=? WHERE order_id=? AND trans_id=?");
        $dbc->execute($upP, array($_REQUEST['qty'],$orderID,$_REQUEST['transID']));
        $info = reprice($orderID,$_REQUEST['transID']);
        echo $info['regPrice'].'`'.$info['total'];
        break;
    case 'saveUnit':
        $upP = $dbc->prepare("UPDATE {$TRANS}PendingSpecialOrder SET
            unitPrice=? WHERE order_id=? AND trans_id=?");
        $dbc->execute($upP, array($_REQUEST['unitPrice'],$orderID,$_REQUEST['transID']));
        $info = reprice($orderID,$_REQUEST['transID']);
        echo $info['regPrice'].'`'.$info['total'];
        break;
    case 'newQty':
        $upP = $dbc->prepare("UPDATE {$TRANS}PendingSpecialOrder SET
            quantity=? WHERE order_id=? AND trans_id=?");
        $dbc->execute($upP, array($_REQUEST['qty'],$orderID,$_REQUEST['transID']));
        $info = reprice($orderID,$_REQUEST['transID']);
        echo getItemForm($orderID);
        break;
    case 'newDept':
        $upP = $dbc->prepare("UPDATE {$TRANS}PendingSpecialOrder SET
            department=? WHERE order_id=? AND trans_id=?");
        $dbc->execute($upP, array($_REQUEST['dept'],$orderID,$_REQUEST['transID']));
        echo getItemForm($orderID);
        break;
    case 'saveDept':
        $upP = $dbc->prepare("UPDATE {$TRANS}PendingSpecialOrder SET
            department=? WHERE order_id=? AND trans_id=?");
        $dbc->execute($upP, array($_REQUEST['dept'],$orderID,$_REQUEST['transID']));
        break;
    case 'saveVendor':
        $upP = $dbc->prepare("UPDATE {$TRANS}PendingSpecialOrder SET
            mixMatch=? WHERE order_id=? AND trans_id=?");
        $dbc->execute($upP, array(trim($_REQUEST['vendor']),$orderID,$_REQUEST['transID']));
        break;
    case 'saveAddr':
        $addr = FormLib::get('addr1');
        if (FormLib::get('addr2') !== '') {
            $addr .= "\n".FormLib::get('addr2');
        }
        saveContactField($orderID, 'street', $addr);
        break;
    case 'saveFN':
        saveContactField($orderID, 'firstName', FormLib::get('fn'));
        break;
    case 'saveLN':
        saveContactField($orderID, 'lastName', FormLib::get('ln'));
        break;
    case 'saveCity':
        saveContactField($orderID, 'city', FormLib::get('city'));
        break;
    case 'saveState':
        saveContactField($orderID, 'state', FormLib::get('state'));
        break;
    case 'saveZip':
        saveContactField($orderID, 'zip', FormLib::get('zip'));
        break;
    case 'savePh':
        saveContactField($orderID, 'phone', FormLib::get('ph'));
        break;
    case 'savePh2':
        saveContactField($orderID, 'altPhone', FormLib::get('ph2'));
        break;
    case 'saveEmail':
        saveContactField($orderID, 'email', FormLib::get('email'));
        break;
    case 'saveNoteDept':
        $dbc = FannieDB::get($FANNIE_TRANS_DB);
        $soModel = new SpecialOrdersModel($dbc);
        $soModel->specialOrderID($orderID);
        $soModel->noteSuperID($_REQUEST['val']);
        $soModel->save();
        $dbc = FannieDB::get($FANNIE_OP_DB);
        break;
    case 'saveText':
        $dbc = FannieDB::get($FANNIE_TRANS_DB);
        $soModel = new SpecialOrdersModel($dbc);
        $soModel->specialOrderID($orderID);
        $soModel->notes($_REQUEST['val']);
        $soModel->save();
        $dbc = FannieDB::get($FANNIE_OP_DB);
        break;
    case 'copyOrder':
        $oid = sprintf("%d",$orderID);
        $nid = duplicateOrder($oid);
        echo $nid;
        break;
    case 'SplitOrder':
        $oid = sprintf("%d",$orderID);
        $tid = sprintf("%d",$_REQUEST['transID']);
        splitOrder($oid,$tid);
        echo getItemForm($oid);
        break;
}

function addUPC($orderID,$memNum,$upc,$num_cases=1)
{
    global $FANNIE_OP_DB,$TRANS;
    $dbc = FannieDB::get($FANNIE_OP_DB);

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
    $ins_array['upc'] = "$upc";
    if ($manualSKU) {
        $ins_array['upc'] = BarcodeLib::padUPC($sku);
    }
    $ins_array['card_no'] = "$memNum";
    $ins_array['trans_type'] = "I";

    $caseSize = 1;
    $vendor = "";
    $vendor_desc = (!is_numeric($upc)?$upc:"");
    $srp = 0.00;
    $vendor_upc = (!is_numeric($upc)?'0000000000000':"");
    $skuMatch=0;
    $caseP = $dbc->prepare("
        SELECT units,
            vendorName,
            description,
            i.srp,
            i.upc,
            CASE WHEN i.upc=? THEN 0 ELSE 1 END as skuMatch 
        FROM vendorItems as i
            LEFT JOIN vendors AS v ON i.vendorID=v.vendorID 
        WHERE i.upc=? 
            OR i.sku=? 
            OR i.sku=?
        ORDER BY i.vendorID");
    $caseR = $dbc->execute($caseP, array($upc,$upc,$sku,'0'.$sku));
    if ($dbc->num_rows($caseR) > 0) {
        $row = $dbc->fetch_row($caseR);
        $caseSize = $row['units'];
        $vendor = $row['vendorName'];
        $vendor_desc = $row['description'];
        $srp = $row['srp'];
        $vendor_upc = $row['upc'];
        $skuMatch = $row['skuMatch'];
    }
    if (!empty($vendor_upc)) $ins_array['upc'] = "$vendor_upc";
    if ($skuMatch == 1) {
        $ins_array['upc'] = "$vendor_upc";
        $upc = $vendor_upc;
    }
    $ins_array['quantity'] = $caseSize;
    $ins_array['ItemQtty'] = $num_cases;
    $ins_array['mixMatch'] = substr($vendor,0,26);
    $ins_array['description'] = substr($vendor_desc,0,32)." SO";

    $mempricing = false;
    if ($memNum != 0 && !empty($memNum)) {
        $prep = $dbc->prepare("SELECT Type,memType FROM custdata WHERE CardNo=?");
        $res = $dbc->execute($prep, array($memNum));
        $row = $dbc->fetch_row($res);
        if ($row['Type'] == 'PC') {
            $mempricing = true;
        } elseif($row['memType'] == 9) {
            $mempricing = true;
        }
    }

    $pdP = $dbc->prepare("
        SELECT normal_price,
            special_price,
            department,
            discounttype,
            description,
            discount,
            default_vendor_id,
            r.priceRuleTypeID
        FROM products AS p
            LEFT JOIN PriceRules AS r ON p.price_rule_id=r.priceRuleID
        WHERE upc=?
            AND inUse=1
        ");
    $pdR = $dbc->execute($pdP, array($upc));
    $qtyReq = False;
    if ($dbc->num_rows($pdR) > 0) {
        $pdW = $dbc->fetch_row($pdR);

        $ins_array['department'] = $pdW['department'];
        $ins_array['discountable'] = $pdW['discount'];
        $mapP = $dbc->prepare("SELECT map_to FROM 
                {$TRANS}SpecialOrderDeptMap WHERE dept_ID=?");
        $mapR = $dbc->execute($mapP, array($pdW['department']));
        if ($dbc->num_rows($mapR) > 0) {
            $ins_array['department'] = array_pop($dbc->fetch_row($mapR));
        }

        $superP = $dbc->prepare("SELECT superID 
                FROM superdepts WHERE dept_ID=?");
        $superR = $dbc->execute($superP, array($ins_array['department']));
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
            if ($pdW['priceRuleTypeID'] == 6 || $pdW['priceRuleTypeID'] == 7 || $pdW['priceRuleTypeID'] == 8) {
                $pdW['discount'] = 0;
                $ins_array['discountable'] = 0;
            }
            if ($pdW['discounttype'] == 1) {
                /**
                  Only apply sale pricing from non-closeout batches
                  At WFC closeout happens to be batch type #11
                */
                $closeoutP = $dbc->prepare('
                    SELECT l.upc
                    FROM batchList AS l
                        INNER JOIN batches AS b ON l.batchID=b.batchID
                    WHERE l.upc=?
                        AND ' . $dbc->curdate() . ' >= b.startDate
                        AND ' . $dbc->curdate() . ' <= b.endDate
                        AND b.batchType IN (11, 12)
                ');
                $closeoutR = $dbc->execute($closeoutP, array($upc));
                if ($closeoutR && $dbc->num_rows($closeoutR) == 0) {
                    $ins_array['total'] = $pdW['special_price']*$caseSize*$num_cases;
                    $ins_array['unitPrice'] = $pdW['special_price'];
                }
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
        $ins_array['description'] = substr($pdW['description'],0,32);
        /**
          If product has a default vendor, lookup
          vendor name and add it
        */
        if ($pdW['default_vendor_id'] != 0) {
            $v = new VendorsModel($dbc);
            $v->vendorID($pdW['default_vendor_id']);
            if ($v->load()) {
                $ins_array['mixMatch'] = $v->vendorName();
            }
        }
        /**
          If no vendor name was found, try looking in prodExtra
        */
        if (empty($ins_array['mixMatch']) && $dbc->tableExists('prodExtra')) {
            $distP = $dbc->prepare('
                SELECT x.distributor
                FROM prodExtra AS x
                WHERE x.upc=?
            ');
            $distR = $dbc->execute($distP, array($upc));
            if ($distR && $dbc->num_rows($distR) > 0) {
                $distW = $dbc->fetch_row($distR);
                $ins_array['mixMatch'] = substr($distW['distributor'],0,26);
            }
        }
    } elseif ($srp != 0) {
        // use vendor SRP if applicable
        $ins_array['regPrice'] = $srp*$caseSize*$num_cases;
        $ins_array['total'] = $srp*$caseSize*$num_cases;
        $ins_array['unitPrice'] = $srp;
        if ($mempricing) {
            $ins_array['total'] *= 0.85;
        }
    }

    $tidP = $dbc->prepare("SELECT MAX(trans_id),MAX(voided),MAX(numflag) 
            FROM {$TRANS}PendingSpecialOrder WHERE order_id=?");
    $tidR = $dbc->execute($tidP,array($orderID));
    $tidW = $dbc->fetch_row($tidR);
    $ins_array['trans_id'] = $tidW[0]+1;
    $ins_array['voided'] = $tidW[1];
    $ins_array['numflag'] = $tidW[2];

    $dbc->smartInsert("{$TRANS}PendingSpecialOrder",$ins_array);
    
    return array($qtyReq,$ins_array['trans_id'],$ins_array['description']);
}

function createContactRow($orderID)
{
    global $FANNIE_OP_DB,$TRANS, $FANNIE_TRANS_DB;

    $dbc = FannieDB::get($FANNIE_TRANS_DB);
    $so = new SpecialOrdersModel($dbc);
    $so->specialOrderID($orderID);
    $so->firstName('');
    $so->lastName('');
    $so->street('');
    $so->city('');
    $so->state('');
    $so->zip('');
    $so->phone('');
    $so->altPhone('');
    $so->email('');
    $so->save();
    $dbc = FannieDB::get($FANNIE_OP_DB); // switch back to previous
}

function splitOrder($orderID,$transID)
{
    global $FANNIE_OP_DB,$TRANS;
    $dbc = FannieDB::get($FANNIE_OP_DB);
    // copy entire order
    $newID = duplicateOrder($orderID,'PendingSpecialOrder');    
    
    // remove all items except desired one
    $cleanP = $dbc->prepare("DELETE FROM {$TRANS}PendingSpecialOrder WHERE
        order_id=? AND trans_id > 0 AND trans_id<>?");
    $dbc->execute($cleanP,array($newID,$transID));

    // remove the item from original order
    $cleanP2 = $dbc->prepare("DELETE FROM {$TRANS}PendingSpecialOrder WHERE
            order_id=? AND trans_id=?");
    $dbc->execute($cleanP2,array($orderID,$transID));

    // fix trans_id on the new order
    $cleanP3 = $dbc->prepare("UPDATE {$TRANS}PendingSpecialOrder SET trans_id=1
            WHERE order_id=? AND trans_id=?");
    $dbc->execute($cleanP3,array($newID,$transID));
}

function duplicateOrder($old_id,$from='CompleteSpecialOrder')
{
    global $FANNIE_OP_DB,$TRANS, $FANNIE_TRANS_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);
    $new_id = createEmptyOrder();
    $delQ = $dbc->prepare("DELETE FROM {$TRANS}PendingSpecialOrder 
            WHERE order_id=?");
    $dbc->execute($delQ,array($new_id));

    $copyP = $dbc->prepare("
        INSERT INTO {$TRANS}PendingSpecialOrder
        SELECT ?,".$dbc->now().",
        register_no,emp_no,trans_no,upc,description,
        trans_type,trans_subtype,trans_status,
        department,quantity,scale,cost,unitPrice,
        total,regPrice,tax,foodstamp,discount,
        memDiscount,discountable,discounttype,
        voided,percentDiscount,ItemQtty,volDiscType,
        volume,VolSpecial,mixMatch,matched,memtype,
        staff,0,'',card_no,trans_id
        FROM {$TRANS}$from WHERE order_id=? AND trans_id=?");
    $dbc->execute($copyP, array($new_id,$old_id,0));

    /**
      Copy order items one at a time
      If the item exists in products or vendorItems, re-add it
      by UPC so that the new order reflects up to date pricing.
      Otherwise if the item is completely unknown, just copy it
      from the old order to the new one.
    */
    $prodP = $dbc->prepare('SELECT upc FROM products WHERE upc=?');
    $vendP = $dbc->prepare('SELECT upc FROM vendorItems WHERE upc=?');
    $itemP = $dbc->prepare('
        SELECT upc,
            ItemQtty,
            card_no,
            trans_id
        FROM ' . $TRANS . $from . '
        WHERE order_id=?
            AND trans_id > 0');
    $itemR = $dbc->execute($itemP, array($old_id));
    while ($itemW = $dbc->fetchRow($itemR)) {
        $prod = $dbc->execute($prodP, array($itemW['upc']));
        if ($itemW['upc'] != '0000000000000' && $prod && $dbc->numRows($prod)) {
            addUPC($new_id, $itemW['card_no'], $itemW['upc'], $itemW['ItemQtty']);
            continue;
        }
        $vend = $dbc->execute($vendP, array($itemW['upc']));
        if ($itemW['upc'] != '0000000000000' && $vend && $dbc->numRows($vend)) {
            addUPC($new_id, $itemW['card_no'], $itemW['upc'], $itemW['ItemQtty']);
            continue;
        }

        $dbc->execute($copyP, array($new_id,$old_id,$itemW['trans_id']));
    }

    $user = checkLogin();
    $userQ = $dbc->prepare("UPDATE {$TRANS}PendingSpecialOrder SET mixMatch=?
            WHERE order_id=? AND trans_id=0");
    $userR = $dbc->execute($userQ, array($user,$new_id));

    $statusQ = $dbc->prepare("SELECT numflag FROM {$TRANS}PendingSpecialOrder
        WHERE order_id=?");
    $statusR = $dbc->execute($statusQ,array($new_id));
    $statusW = $dbc->fetch_row($statusR);
    $st = $statusW['numflag'];
    $timestamp = time();

    $dbc = FannieDB::get($FANNIE_TRANS_DB);
    // load values from old order
    $soModel = new SpecialOrdersModel($dbc);
    $soModel->specialOrderID($old_id);
    $soModel->load();
    // update ID, status
    $soModel->specialOrderID($new_id);
    $soModel->statusFlag( ($st == 1) ? 3 : 0 );
    $soModel->subStatus($timestamp);
    // save with the new ID
    $soModel->save();
    $dbc = FannieDB::get($FANNIE_OP_DB);

    return $new_id;
}

function createEmptyOrder()
{
    global $FANNIE_OP_DB,$TRANS,$FANNIE_SERVER_DBMS, $FANNIE_TRANS_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);
    $user = checkLogin();
    $orderID = 1;
    $values = ($FANNIE_SERVER_DBMS != "MSSQL" ? "VALUES()" : "DEFAULT VALUES");
    $dbc->query('INSERT ' . $TRANS . 'SpecialOrders ' . $values);
    $orderID = $dbc->insertID();

    $ins_array = genericRow($orderID);
    $ins_array['numflag'] = 2;
    $ins_array['mixMatch'] = $user;
    $dbc->smartInsert("{$TRANS}PendingSpecialOrder",$ins_array);

    $note_vals = array(
        'order_id'=>$orderID,
        'notes'=>"",
        'superID'=>0
    );

    $status_vals = array(
        'order_id'=>$orderID,
        'status_flag'=>3,
        'sub_status'=>time()
    );

    $dbc = FannieDB::get($FANNIE_TRANS_DB);
    $so = new SpecialOrdersModel($dbc);
    $so->specialOrderID($orderID);
    $so->statusFlag($status_vals['status_flag']);
    $so->subStatus($status_vals['sub_status']);
    $so->notes(trim($note_vals['notes'],"'"));
    $so->noteSuperID($note_vals['superID']);
    $so->save();
    $dbc = FannieDB::get($FANNIE_OP_DB); // switch back to previous

    createContactRow($orderID);

    return $orderID;
}

function genericRow($orderID)
{
    global $FANNIE_OP_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);
    return array(
    'order_id'=>$orderID,
    'datetime'=>date('Y-m-d H:i:s'),
    'emp_no'=>1001,
    'register_no'=>30,
    'trans_no'=>$orderID,
    'upc'=>'0',
    'description'=>"SPECIAL ORDER",
    'trans_type'=>"C",
    'trans_subtype'=>"",
    'trans_status'=>"",
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
    'charflag'=>"",   
    'card_no'=>0,
    'trans_id'=>0
    );
}

function getCustomerForm($orderID,$memNum="0")
{
    global $FANNIE_OP_DB, $TRANS, $FANNIE_TRANS_DB, $canEdit;
    $dbc = FannieDB::get($FANNIE_OP_DB);

    if (empty($orderID)) $orderID = createEmptyOrder();

    $names = array();
    $pn = 1;
    $status_row = array(
        'Type' => 'REG',
        'status' => ''
    );

    $table = "PendingSpecialOrder";

    $dbc = FannieDB::get($FANNIE_TRANS_DB);
    $orderModel = new SpecialOrdersModel($dbc);
    $orderModel->specialOrderID($orderID);
    $orderModel->load();
    $dbc = FannieDB::get($FANNIE_OP_DB);

    // detect member UPC entry
    if ($memNum > 9999999) {
        $p = $dbc->prepare("SELECT card_no FROM memberCards WHERE upc=?");
        $res = $dbc->execute($p,array(BarcodeLib::padUPC($memNum)));
        if ($dbc->num_rows($res) > 0) {
            $w = $dbc->fetch_row($res);
            $memNum = $w['card_no'];
        } else {
            $memNum = "";
        }
    }

    // look up member id if applicable
    if ($memNum === "0") {
        $findMem = $dbc->prepare("SELECT card_no,voided FROM {$TRANS}$table WHERE order_id=?");
        $memR = $dbc->execute($findMem, array($orderID));
        if ($dbc->num_rows($memR) > 0) {
            $memW = $dbc->fetch_row($memR);
            $memNum = $memW['card_no'];
            $pn = $memW['voided'];
        }
    } else if ($memNum == "") {
        $prep = $dbc->prepare("UPDATE {$TRANS}PendingSpecialOrder SET card_no=?,voided=0
            WHERE order_id=?");
        $res = $dbc->execute($prep,array(0,$orderID));
    } else {
        
        $prep = $dbc->prepare("UPDATE {$TRANS}PendingSpecialOrder SET card_no=?
            WHERE order_id=?");
        $res = $dbc->execute($prep,array($memNum,$orderID));

        // clear contact fields if member number changed
        // so that defaults are reloaded from meminfo
        $dbc = FannieDB::get($FANNIE_TRANS_DB);
        $orderModel->street('');
        $orderModel->phone('');
        $orderModel->save();
        $orderModel->specialOrderID($orderID);
        $orderModel->load();
        $dbc = FannieDB::get($FANNIE_OP_DB);

        // look up personnum, correct if it hasn't been set
        $pnQ = $dbc->prepare("SELECT voided FROM {$TRANS}PendingSpecialOrder
            WHERE order_id=?");
        $pnR = $dbc->execute($pnQ,array($orderID));
        $pnW = $dbc->fetch_row($pnR);
        $pn = $pnW['voided'];
        if ($pn == 0) {
            $pn = 1;
            $upP = $dbc->prepare("UPDATE {$TRANS}PendingSpecialOrder SET voided=?
                WHERE order_id=?");
            $upR = $dbc->execute($upP,array($pn,$orderID));
        }
    }

    if ($memNum != 0) {
        $namesP = $dbc->prepare("SELECT personNum,FirstName,LastName FROM custdata
            WHERE CardNo=? ORDER BY personNum");
        $namesR = $dbc->execute($namesP,array($memNum));
        while($namesW = $dbc->fetch_row($namesR)) {
            $names[$namesW['personNum']] = array($namesW['FirstName'],$namesW['LastName']);
        }

        // load member contact info into order
        // on first go so it can be edited separately
        $current_street = $orderModel->street();
        $current_phone = $orderModel->phone();
        if (empty($current_street) && empty($current_phone)) {
            $contactQ = $dbc->prepare("SELECT street,city,state,zip,phone,email_1,email_2
                    FROM meminfo WHERE card_no=?");
            $contactR = $dbc->execute($contactQ, array($memNum));
            if ($dbc->num_rows($contactR) > 0) {
                $contact_row = $dbc->fetch_row($contactR);

                $dbc = FannieDB::get($FANNIE_TRANS_DB);
                $orderModel->street($contact_row['street']);
                $orderModel->city($contact_row['city']);
                $orderModel->state($contact_row['state']);
                $orderModel->zip($contact_row['zip']);
                $orderModel->phone($contact_row['phone']);
                $orderModel->altPhone($contact_row['email_2']);
                $orderModel->email($contact_row['email_1']);
                $orderModel->save();
                $orderModel->specialOrderID($orderID);
                $orderModel->load();
                $dbc = FannieDB::get($FANNIE_OP_DB);
            }
        }

        $statusQ = $dbc->prepare("SELECT Type FROM custdata WHERE CardNo=?");
        $statusR = $dbc->execute($statusQ,array($memNum));
        $status_row  = $dbc->fetch_row($statusR);
        if ($status_row['Type'] == 'INACT') {
            $status_row['status'] = 'Inactive';
        } elseif ($status_row['Type'] == 'INACT2') {
            $status_row['status'] = 'Inactive';
        } elseif ($status_row['Type'] == 'TERM') {
            $status_row['status'] = 'Terminated';
        }
    } 

    $prep = $dbc->prepare("SELECT entry_date FROM {$TRANS}SpecialOrderHistory 
            WHERE order_id=? AND entry_type='CONFIRMED'");
    $res = $dbc->execute($prep, array($orderID));
    $confirm_date = "";
    if ($dbc->num_rows($res) > 0) {
        $confirm_date = array_pop($dbc->fetch_row($res));
    }

    $callback = 2;
    $user = 'Unknown';
    $orderDate = "";
    $prep = $dbc->prepare("SELECT datetime,numflag,mixMatch FROM 
            {$TRANS}PendingSpecialOrder WHERE order_id=? AND trans_id=0");
    $res = $dbc->execute($prep, array($orderID));
    if ($dbc->num_rows($res) > 0) {
        list($orderDate,$callback,$user) = $dbc->fetch_row($res);
    }

    $status = array(
        0 => "New, No Call",
        3 => "New, Call",
        1 => "Called/waiting",
        2 => "Pending",
        4 => "Placed",
        5 => "Arrived"
    );
    $order_status = $orderModel->statusFlag();

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
    $ret .= '</td>';

    $ret .= '<td valign="top">';
    $ret .= '<b>Status</b>: ';
    if ($canEdit) {
        $ret .= sprintf('<select id="orderStatus" onchange="updateStatus(%d, this.value);">', $orderID);
        foreach($status as $k => $v) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                        ($k == $order_status ? 'selected' : ''),
                        $k, $v);
        }
        $ret .= '</select>';
    } else {
        foreach ($status as $k=>$v) {
            if ($k == $order_status) {
                $ret .= $v;
            }
        }
    }
    $ret .= '<p />';
    $ret .= '<b>Store</b>: ';
    $ret .= sprintf('<select id="oStoreID" onchange="updateStore(%d, this.value);">', $orderID);
    $stores = new StoresModel($dbc);
    $ret .= '<option value="0">Choose...</option>';
    $ret .= $stores->toOptions($orderModel->storeID());
    $ret .= '</select>';
    $ret .= '</td>';

    $ret .= '<td align="right" valign="top">';
    $ret .= "<input type=\"submit\" value=\"Done\"
        onclick=\"validateAndHome();return false;\" />";
    $username = checkLogin();
    $prints = array();
    $cachepath = sys_get_temp_dir()."/ordercache/";
    if (file_exists("{$cachepath}{$username}.prints")) {
        $prints = unserialize(file_get_contents("{$cachepath}{$username}.prints"));
    } else {
        $fptr = fopen("{$cachepath}{$username}.prints",'w');
        fwrite($fptr,serialize($prints));
        fclose($fptr);
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
                /></td>',$orderModel->firstName(),$orderID);
        $ret .= sprintf('<th>Last Name</th><td><input 
                type="text" id="t_lastName" value="%s"
                onchange="saveLN(%d,this.value);" /></td>',
                $orderModel->lastName(),$orderID);
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
    $msQ = $dbc->prepare("select superID,super_name from MasterSuperDepts
        where superID > 0
        group by superID,super_name
        order by super_name");
    $msR = $dbc->execute($msQ);
    while($msW = $dbc->fetch_row($msR)) {
        $ret .= sprintf('<option value="%d" %s>%s</option>',
            $msW['superID'],
            ($msW['superID']==$orderModel->noteSuperID()?'selected':''),
            $msW['super_name']);
    }
    $ret .= "</select></td></tr>";

    // address
    $street = $orderModel->street();
    $street2 = '';
    if(strstr($street,"\n")) {
        list($street, $street2) = explode("\n", $street, 2);
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
        $street, $orderID,
        $orderModel->email(), $orderID,
        $orderID, $orderModel->notes(),
        $street2, $orderID,
        $orderModel->city(), $orderID,
        $orderModel->phone(), $orderID,
        $orderModel->altPhone(), $orderID,
        $orderModel->state(), $orderID,
        $orderModel->zip(), $orderID
    );

    $ret .= '</table>';

    return $ret."`".$extra;
}

function getCustomerNonForm($orderID)
{
    global $FANNIE_OP_DB, $TRANS, $FANNIE_TRANS_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);

    $names = array();
    $pn = 1;
    $status_row = array(
        'Type' => 'REG',
        'status' => ''
    );

    $dbc = FannieDB::get($FANNIE_TRANS_DB);
    $orderModel = new SpecialOrdersModel($dbc);
    $orderModel->specialOrderID($orderID);
    $orderModel->load();
    $dbc = FannieDB::get($FANNIE_OP_DB);
    
    // look up member id 
    $memNum = 0;
    $findMem = $dbc->prepare("SELECT card_no,voided FROM {$TRANS}CompleteSpecialOrder WHERE order_id=?");
    $memR = $dbc->execute($findMem, array($orderID));
    if ($dbc->num_rows($memR) > 0) {
        $memW = $dbc->fetch_row($memR);
        $memNum = $memW['card_no'];
        $pn = $memW['voided'];
    }

    // Get member info from custdata, non-member info from SpecialOrders
    if ($memNum != 0) {
        $namesP = $dbc->prepare("SELECT personNum,FirstName,LastName FROM custdata
            WHERE CardNo=? ORDER BY personNum");
        $namesR = $dbc->execute($namesP,array($memNum));
        while($namesW = $dbc->fetch_row($namesR)) {
            $names[$namesW['personNum']] = array($namesW['FirstName'],$namesW['LastName']);
        }

        $statusQ = $dbc->prepare("SELECT Type FROM custdata WHERE CardNo=?");
        $statusR = $dbc->execute($statusQ,array($memNum));
        $status_row  = $dbc->fetch_row($statusR);
        if ($status_row['Type'] == 'INACT') {
            $status_row['status'] = 'Inactive';
        } elseif ($status_row['Type'] == 'INACT2') {
            $status_row['status'] = 'Inactive';
        } elseif ($status_row['Type'] == 'TERM') {
            $status_row['status'] = 'Terminated';
        }
    }

    $prep = $dbc->prepare("SELECT entry_date FROM {$TRANS}SpecialOrderHistory 
            WHERE order_id=? AND entry_type='CONFIRMED'");
    $res = $dbc->execute($prep, array($orderID));
    $confirm_date = "";
    if ($dbc->num_rows($res) > 0) {
        $confirm_date = array_pop($dbc->fetch_row($res));
    }

    $callback = 1;
    $user = 'Unknown';
    $orderDate = '';
    $prep = $dbc->prepare("SELECT datetime,numflag,mixMatch FROM 
            {$TRANS}CompleteSpecialOrder WHERE order_id=? AND trans_id=0");
    $res = $dbc->execute($prep, array($orderID));
    if ($dbc->num_rows($res) > 0) {
        list($orderDate,$callback,$user) = $dbc->fetch_row($res);
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
                </td>',$orderModel->firstName(),$orderID);
        $ret .= sprintf('<th>Last Name</th><td>%s
                </td>',
                $orderModel->lastName(),$orderID);
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
    $msQ = $dbc->prepare("select superID,super_name 
        from MasterSuperDepts
        where superID > 0
        group by superID,super_name
        order by super_name");
    $msR = $dbc->execute($msQ);
    while($msW = $dbc->fetch_row($msR)) {
        $ret .= sprintf('<option value="%d" %s>%s</option>',
            $msW['superID'],
            ($msW['superID']==$orderModel->noteSuperID()?'selected':''),
            $msW['super_name']);
    }
    $ret .= "</select></td></tr>";

    // address
    $street = $orderModel->street();
    $street2 = '';
    if(strstr($street,"\n")) {
        list($street, $street2) = explode("\n", $street, 2);
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
        $street,
        $orderModel->email(),
        $orderModel->notes(),
        $street2,
        $orderModel->city(),
        $orderModel->phone(),
        $orderModel->altPhone(),
        $orderModel->state(),
        $orderModel->zip()
    );
        
    $ret .= '</table>';

    return $ret;
}

function getQtyForm($orderID,$default,$transID,$description)
{
    global $FANNIE_OP_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);
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
    global $FANNIE_OP_DB, $TRANS;
    $dbc = FannieDB::get($FANNIE_OP_DB);
    $ret = '<i>This item ('.$description.') requires a department</i><br />';
    $ret .= "<form onsubmit=\"newDept($orderID,$transID);return false;\">";
    $ret .= '<select id="newdept">';
    $prep = $dbc->prepare("select super_name,
        CASE WHEN MIN(map_to) IS NULL THEN MIN(m.dept_ID) ELSE MIN(map_to) END
        from MasterSuperDepts
        as m left join {$TRANS}SpecialOrderDeptMap as s
        on m.dept_ID=s.dept_ID
        where m.superID > 0
        group by super_name ORDER BY super_name");
    $res = $dbc->execute($prep);
    while($row = $dbc->fetch_row($res)) {
        $ret .= sprintf('<option value="%d">%s</option>',$row[1],$row[0]);
    }
    $ret .= "</select>";
    $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    $ret .= '<input type="submit" value="Enter Dept" />';
    $ret .= '</form>';
    
    return $ret;
}

function getItemForm($orderID)
{
    global $FANNIE_OP_DB, $canEdit;
    $dbc = FannieDB::get($FANNIE_OP_DB);
    
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
    global $FANNIE_OP_DB, $TRANS;
    $dbc = FannieDB::get($FANNIE_OP_DB);

    $deptQ = $dbc->prepare("SELECT dept_no,dept_name FROM departments order by dept_no");
    $deptR = $dbc->execute($deptQ);
    $depts = array(0=>'Unassigned');
    while($deptW = $dbc->fetch_row($deptR)) {
        $depts[$deptW['dept_no']] = $deptW['dept_name'];
    }

    $ret = '<table cellspacing="0" cellpadding="4" border="1">';
    $ret .= '<tr><th>UPC</th><th>SKU</th><th>Description</th><th>Cases</th><th>SRP</th><th>Actual</th><th>Qty</th><th>Dept</th><th>&nbsp;</th></tr>';
    $prep = $dbc->prepare("SELECT o.upc,o.description,total,quantity,department,
        v.sku,ItemQtty,regPrice,o.discounttype,o.charflag,o.mixMatch,
        o.trans_id,o.unitPrice,o.memType,o.staff
        FROM {$TRANS}PendingSpecialOrder as o
        left join vendorItems as v on o.upc=v.upc AND vendorID=1
        WHERE order_id=? AND trans_type='I' 
        ORDER BY trans_id DESC");
    $res = $dbc->execute($prep, array($orderID));
    $num_rows = $dbc->num_rows($res);
    $prev_id = 0;
    while($w = $dbc->fetch_row($res)) {
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
    global $FANNIE_OP_DB, $TRANS;
    $dbc = FannieDB::get($FANNIE_OP_DB);

    $ret = '<table cellspacing="0" cellpadding="4" border="1">';
    $ret .= '<tr><th>UPC</th><th>Description</th><th>Cases</th><th>Pricing</th><th>&nbsp;</th></tr>';
        //<th>Est. Price</th>
        //<th>Qty</th><th>Est. Savings</th><th>&nbsp;</th></tr>';
    $prep = $dbc->prepare("SELECT o.upc,o.description,total,quantity,discountable,
        department,regPrice,ItemQtty,discounttype,trans_id FROM {$TRANS}$table as o
        WHERE order_id=? AND trans_type='I'");
    $res = $dbc->execute($prep, array($orderID));
    while($w = $dbc->fetch_row($res)) {
        $pricing = "Regular";
        if ($w['discounttype'] == 1) {
            $pricing = "Sale";
        } elseif($w['regPrice'] != $w['total']) {
            if ($w['discounttype']==2) {
                $pricing = "Sale";
            } else {
                $pricing = "% Discount";
            }
        } elseif ($w['discountable'] == 0) {
            $pricing = 'Basics';
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
    global $FANNIE_OP_DB, $TRANS;
    $dbc = FannieDB::get($FANNIE_OP_DB);

    $deptQ = $dbc->prepare("SELECT dept_no,dept_name FROM departments order by dept_no");
    $deptR = $dbc->execute($deptQ);
    $depts = array(0=>'Unassigned');
    while($deptW = $dbc->fetch_row($deptR)) {
        $depts[$deptW['dept_no']] = $deptW['dept_name'];
    }

    $ret = '<table cellspacing="0" cellpadding="4" border="1">';
    $ret .= '<tr><th>UPC</th><th>SKU</th><th>Description</th><th>Cases</th><th>SRP</th><th>Actual</th><th>Qty</th><th>Dept</th></tr>';
    $prep = $dbc->prepare("SELECT o.upc,o.description,total,quantity,department,
        sku,ItemQtty,regPrice,o.discounttype,o.charflag,o.mixMatch FROM {$TRANS}CompleteSpecialOrder as o
        left join vendorItems as v on o.upc=v.upc AND o.upc <> '0000000000000'
        WHERE order_id=? AND trans_type='I'
        ORDER BY trans_id DESC");
    $res = $dbc->execute($prep, array($orderID));
    while($w = $dbc->fetch_row($res)) {
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
    global $FANNIE_OP_DB, $TRANS;
    $dbc = FannieDB::get($FANNIE_OP_DB);

    $query = $dbc->prepare("SELECT o.unitPrice,o.itemQtty,o.quantity,o.discounttype,
        c.type,c.memType,o.regPrice,o.total,o.discountable
        FROM {$TRANS}PendingSpecialOrder AS o LEFT JOIN custdata AS c ON
        o.card_no=c.CardNo AND c.personNum=1
        WHERE order_id=? AND trans_id=?");
    $response = $dbc->execute($query, array($oid,$tid));
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

    $query = $dbc->prepare("UPDATE {$TRANS}PendingSpecialOrder SET
            total=?,regPrice=?
            WHERE order_id=? AND trans_id=?");
    $dbc->execute($query, array($total,$regPrice,$oid,$tid));

    return array(
        'regPrice'=>sprintf("%.2f",$regPrice),
        'total'=>sprintf("%.2f",$total)
    );
}

function getOrderHistory($orderID)
{
    global $FANNIE_OP_DB, $FANNIE_TRANS_DB;

    $dbc = FannieDB::get($FANNIE_OP_DB);
    $history = $FANNIE_TRANS_DB . $dbc->sep() . 'SpecialOrderHistory';

    $prep = $dbc->prepare("SELECT entry_date, entry_type, entry_value
                           FROM {$history}
                           WHERE order_id = ?
                            AND entry_type IN ('AUTOCLOSE', 'PURCHASED')
                           ORDER BY entry_date");
    $result = $dbc->execute($prep, array($orderID));

    $ret = '<table cellpadding="4" cellspacing="0" border="1">';
    $ret .= '<tr>
                <th>Date</th>
                <th>Action</th>
                <th>Details</th>
             </tr>';
    while($row = $dbc->fetch_row($result)) {
        if ($row['entry_type'] == 'PURCHASED') {
            $trans_num = $row['entry_value'];
            $tdate = date('Y-m-d', strtotime($row['entry_date']));
            $link = '../admin/LookupReceipt/RenderReceiptPage.php?date=' . $tdate . '&receipt=' . $trans_num;
            $row['entry_value'] = sprintf('<a href="%s" target="_%s">%s</a>', $link, $trans_num, $trans_num);
        }
        $ret .= sprintf('<tr>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                         </tr>',
                            $row['entry_date'],
                            $row['entry_type'],
                            $row['entry_value']
        );
    }
    $ret .= '</table>';

    return $ret;
}

function saveContactField($orderID, $field, $val)
{
    global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
    $dbc = FannieDB::get($FANNIE_TRANS_DB);
    $soModel = new SpecialOrdersModel($dbc);
    $soModel->specialOrderID($orderID);
    $soModel->$field($val);
    $soModel->save();
    $dbc = FannieDB::get($FANNIE_OP_DB);
}


<?php
/*******************************************************************************

    Copyright 2009,2010 Whole Foods Co-op

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
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!function_exists('checkLogin')) {
    include_once($FANNIE_ROOT . 'auth/login.php');
}
if (!function_exists("updateProductAllLanes")) include($FANNIE_ROOT.'item/laneUpdates.php');
if (!function_exists('forceBatch')) {
    include('forceBatch.php');
}

class BatchManagementTool extends FanniePage 
{
    protected $window_dressing = false;
    protected $must_authenticate = true;
    protected $auth_classes = array('batches','batches_audited');
    protected $title = 'Sales Batches Tool';
    protected $header = 'Sales Batches Tool';

    public $description = '[Sales Batches] is the primary tool for creating, editing, and managing 
    sale and price change batches.';

    private $audited = 1;
    private $con = null;
    private $batchtypes = array();
    private $owners = array();

    function preprocess()
    {
        global $FANNIE_OP_DB;
        // maintain user logins longer
        refreshSession();
        if (validateUserQuiet('batches')) {
            $this->audited = 0;
        }

        $this->con = FannieDB::get($FANNIE_OP_DB);

        $typesQ = $this->con->prepare_statement("select batchTypeID,typeDesc from batchType order by batchTypeID");
        $typesR = $this->con->exec_statement($typesQ);
        while ($typesW = $this->con->fetch_array($typesR)) {
            $this->batchtypes[$typesW[0]] = $typesW[1];
        }
            
        $ownersQ = $this->con->prepare_statement("SELECT super_name FROM MasterSuperDepts GROUP BY super_name ORDER BY super_name");
        $ownersR = $this->con->exec_statement($ownersQ);
        $this->owners = array('');
        while($ownersW = $this->con->fetch_row($ownersR)) {
            $this->owners[] = $ownersW[0];
        }
        $this->owners[] = 'IT';

        if (FormLib::get_form_value('action') !== '') {
            $this->ajax_response(FormLib::get_form_value('action'));
            return false;
        }

        // autoclear old data from clipboard table on intial page load
        $clipboard = $this->con->tableDefinition('batchCutPaste');
        if (isset($clipboard['tdate'])) {
            $this->con->query('DELETE FROM batchCutPaste WHERE tdate < ' . $this->con->curdate());
        }

        return true;
    }

    /** ajax responses 
     * $out is the output sent back
     * by convention, the request name ($_GET['action'])
     * is prepended to all output so the javascript receiver
     * can handle responses differently as needed.
     * a backtick separates request name from data
     */
    function ajax_response($action)
    {
        global $FANNIE_SERVER_DBMS, $FANNIE_URL;
        $out = '';
        $dbc = $this->con;
        // prepend request name & backtick
        $out = $action."`";
        // switch on request name
        switch ($action){
        case 'newBatch':
            $type = FormLib::get_form_value('type',0);
            $name = FormLib::get_form_value('name','');
            $startdate = FormLib::get_form_value('startdate',date('Y-m-d'))." 00:00:00";
            $enddate = FormLib::get_form_value('enddate',date('Y-m-d'))." 23:59:59";
            $owner = FormLib::get_form_value('owner','');
            $priority = FormLib::get_form_value('priority',0);
            
            $infoQ = $dbc->prepare_statement("select discType from batchType where batchTypeID=?");
            $infoR = $dbc->exec_statement($infoQ,array($type));
            $discounttype = 1; // if no match, assuming sale is probably safer
                               // than assuming price change
            if ($infoR && ($infoW = $dbc->fetch_row($infoR))) {
                $discounttype = $infoW['discType'];
            }
            
            $b = new BatchesModel($dbc);
            $b->startDate($startdate);
            $b->endDate($enddate);
            $b->batchName($name);
            $b->batchType($type);
            $b->discounttype($discounttype);
            $b->priority($priority);
            $b->owner($owner);
            $id = $b->save();
            
            if ($dbc->tableExists('batchowner')) {
                $insQ = $dbc->prepare_statement("insert batchowner values (?,?)");
                $insR = $dbc->exec_statement($insQ,array($id,$owner));
            }
            
            $out = $this->batchListDisplay();
            break;
        case 'deleteBatch':
            $id = FormLib::get_form_value('id',0);

            $unsaleQ = "UPDATE products AS p LEFT JOIN batchList as b
                ON p.upc=b.upc
                SET special_price=0,
                specialpricemethod=0,specialquantity=0,
                specialgroupprice=0,discounttype=0,
                start_date='1900-01-01',end_date='1900-01-01'
                WHERE b.upc NOT LIKE '%LC%'
                AND b.batchID=?";
            if ($FANNIE_SERVER_DBMS=="MSSQL"){
                $unsaleQ = "UPDATE products SET special_price=0,
                    specialpricemethod=0,specialquantity=0,
                    specialgroupprice=0,discounttype=0,
                    start_date='1900-01-01',end_date='1900-01-01'
                    FROM products AS p, batchList as b
                    WHERE p.upc=b.upc AND b.upc NOT LIKE '%LC%'
                    AND b.batchID=?";
            }
            $prep = $dbc->prepare_statement($unsaleQ);
            $unsaleR = $dbc->exec_statement($prep,array($id));

            $unsaleLCQ = "UPDATE products AS p LEFT JOIN
                upcLike AS v ON v.upc=p.upc LEFT JOIN
                batchList AS l ON l.upc=concat('LC',convert(v.likeCode,char))
                SET special_price=0,
                specialpricemethod=0,specialquantity=0,
                specialgroupprice=0,p.discounttype=0,
                start_date='1900-01-01',end_date='1900-01-01'
                WHERE l.upc LIKE '%LC%'
                AND l.batchID=?";
            if ($FANNIE_SERVER_DBMS=="MSSQL"){
                $unsaleLCQ = "UPDATE products
                    SET special_price=0,
                    specialpricemethod=0,specialquantity=0,
                    specialgroupprice=0,discounttype=0,
                    start_date='1900-01-01',end_date='1900-01-01'
                    FROM products AS p LEFT JOIN
                    upcLike AS v ON v.upc=p.upc LEFT JOIN
                    batchList AS l ON l.upc=concat('LC',convert(v.likeCode,char))
                    WHERE l.upc LIKE '%LC%'
                    AND l.batchID=?";
            }
            $prep = $dbc->prepare_statement($unsaleLCQ);
            $unsaleLCR = $dbc->exec_statement($prep,array($id));
            
            $delQ = $dbc->prepare_statement("delete from batches where batchID=?");
            $delR = $dbc->exec_statement($delQ,array($id));
            
            $delQ = $dbc->prepare_statement("delete from batchList where batchID=?");
            $delR = $dbc->exec_statement($delQ,array($id));

            $out = $this->batchListDisplay();
            break;
        case 'saveBatch':
            $id = FormLib::get_form_value('id',0);
            $name = FormLib::get_form_value('name','');
            $type = FormLib::get_form_value('type',0);
            $startdate = FormLib::get_form_value('startdate',date('Y-m-d')).' 00:00:00';
            $enddate = FormLib::get_form_value('enddate',date('Y-m-d')).' 23:59:59';
            $owner = FormLib::get_form_value('owner','');
            
            $infoQ = $dbc->prepare_statement("select discType from batchType where batchTypeID=?");
            $infoR = $dbc->exec_statement($infoQ,array($type));
            $discounttype = array_pop($dbc->fetch_array($infoR));

            $model = new BatchesModel($dbc);
            $model->batchID($id);
            $model->batchType($type);
            $model->batchName($name);
            $model->startDate($startdate);
            $model->endDate($enddate);
            $model->discounttype($discounttype);
            $model->owner($owner);
            $model->save();
            
            if ($dbc->tableExists('batchowner')) {
                $checkQ = $dbc->prepare_statement("select batchID from batchowner where batchID=?");
                $checkR = $dbc->exec_statement($checkQ,array($id));
                if($dbc->num_rows($checkR) == 0) {
                    $insQ = $dbc->prepare_statement("insert batchowner values (?,?)");
                    $insR = $dbc->exec_statement($insQ,array($id,$owner));
                } else {
                    $upQ = $dbc->prepare_statement("update batchowner set owner=? where batchID=?");
                    $upR = $dbc->exec_statement($upQ,array($owner,$id));
                }
            }
            
            break;
        case 'showBatch':
            $id = FormLib::get_form_value('id',0);
            $tag = FormLib::get_form_value('tag')=='true' ? True : False;
            
            $json = array();
            $json['input'] = $this->addItemUPCInput($tag);
            $json['display'] = $this->showBatchDisplay($id);
            $out = json_encode($json);
            
            break;
        case 'backToList':
            $json = array();
            $json['input'] = $this->newBatchInput();
            $json['display'] = $this->batchListDisplay();
            $out = json_encode($json);
            
            break;
        case 'addItemUPC':
            $id = FormLib::get_form_value('id',0);
            $upc = FormLib::get_form_value('upc','');
            $upc = BarcodeLib::padUPC($upc);

            $json = array(
                'error' => 0,
                'field' => '#addItemPrice',
            );

            $batch = new BatchesModel($dbc);
            $batch->batchID($id);
            $batch->load();
            $overlapP = $dbc->prepare('
                SELECT b.batchName,
                    b.startDate,
                    b.endDate,
                    b.batchID
                FROM batchList AS l
                    INNER JOIN batches AS b ON l.batchID=b.batchID
                WHERE l.batchID <> ?
                    AND l.upc = ?
                    AND ? <= b.endDate
                    AND ? >= b.startDate
                    AND b.discounttype <> 0
                    AND b.endDate >= ' . $dbc->curdate()
            );
            $args = array(
                $id,
                $upc,
                date('Y-m-d', strtotime($batch->startDate())),
                date('Y-m-d', strtotime($batch->endDate())),
            );
            $overlapR = $dbc->execute($overlapP, $args);
            if ($batch->discounttype() > 0 && $dbc->num_rows($overlapR) > 0) {
                $row = $dbc->fetch_row($overlapR);
                $error = 'Item already in concurrent batch: '
                    . '<a style="color:blue;" href="BatchManagementTool.php?startAt=' . $row['batchID'] . '">'
                    . $row['batchName'] . '</a> ('
                    . date('Y-m-d', strtotime($row['startDate'])) . ' - '
                    . date('Y-m-d', strtotime($row['endDate'])) . ')'
                    . '<br />'
                    . 'Either remove item from conflicting batch or change
                       dates so the batches do not overlap.';
                $json['error'] = $error;
                $json['content'] = $this->addItemUPCInput();
                $json['field'] = '#addItemUPC';
            } else {

                $tag = FormLib::get_form_value('tag')=='true' ? True : False;
                
                $json['content'] = $this->addItemPriceInput($upc,$tag);
            }
            echo json_encode($json);
            $out = '';
            break;
        case 'addItemLC':
            $id = FormLib::get_form_value('id',0);
            $lc = FormLib::get_form_value('lc',0);
            $json = array(
                'error' => 0,
                'content' => $this->addItemPriceLCInput($lc),
                'field' => '#addItemPrice',
            );
            echo json_encode($json);
            $out = '';
            break;
        case 'addItemPrice':
            $id = FormLib::get_form_value('id',0);
            $upc = FormLib::get_form_value('upc','');
            $upc = BarcodeLib::padUPC($upc);
            $price = FormLib::get_form_value('price','');
            $qty = FormLib::get_form_value('limit',0);
            
            if ($price != ""){

                $model = new BatchListModel($dbc);
                $model->upc($upc);
                $model->batchID($id);
                $model->salePrice($price);
                $model->quantity($qty);
                $model->pricemethod(0);
                $model->save();

                if (FormLib::get_form_value('audited') == '1') {
                    AuditLib::batchNotification($id, $upc, AuditLib::BATCH_ADD);
                }
            }
            
            $json = array();
            $json['input'] = $this->addItemUPCInput();
            $json['display'] = $this->showBatchDisplay($id);
            $out = json_encode($json);
            break;
        case 'addItemLCPrice':
            $id = FormLib::get_form_value('id',0);
            $lc = FormLib::get_form_value('lc',0);
            $price = FormLib::get_form_value('price','');
            $qty = FormLib::get_form_value('limit',0);
            
            if ($price != ""){

                $model = new BatchListModel($dbc);
                $model->upc('LC'.$lc);
                $model->batchID($id);
                $model->salePrice($price);
                $model->quantity($qty);
                $model->pricemethod(0);
                $model->save();

                if (FormLib::get_form_value('audited') == '1') {
                    AuditLib::batchNotification($id, $upc, AuditLib::BATCH_ADD, true);
                }
            }
            
            $json['input'] = $this->addItemLCInput();
            $json['display'] = $this->showBatchDisplay($id);
            $out = json_encode($json);
            break;
        case 'deleteItem':
            $id = FormLib::get_form_value('id',0);
            $upc = FormLib::get_form_value('upc','');
            
            if (substr($upc,0,2) != 'LC'){
                // take the item off sale if this batch is currently on sale
                $unsaleQ = "UPDATE products AS p LEFT JOIN batchList as b on p.upc=b.upc
                        set p.discounttype=0,special_price=0,start_date=0,end_date=0 
                        WHERE p.upc=? and b.batchID=?";
                if ($FANNIE_SERVER_DBMS == "MSSQL"){
                    $unsaleQ = "update products set discounttype=0,special_price=0,start_date=0,end_date=0 
                            from products as p, batches as b where
                            p.upc=? and b.batchID=? and b.startDate=p.start_date and b.endDate=p.end_date";
                }
                $unsaleP = $dbc->prepare_statement($unsaleQ);
                $unsaleR = $dbc->exec_statement($unsaleP,array($upc,$id));
                
                updateProductAllLanes($upc);
            }
            else {
                $lc = substr($upc,2);
                $unsaleQ = "UPDATE products AS p LEFT JOIN upcLike as u on p.upc=u.upc
                        LEFT JOIN batchList as b ON b.upc=concat('LC',convert(u.likeCode,char))
                        set p.discounttype=0,special_price=0,start_date=0,end_date=0 
                        WHERE u.likeCode=? and b.batchID=?";
                if ($FANNIE_SERVER_DBMS == "MSSQL"){
                    $unsaleQ = "update products set discounttype=0,special_price=0,start_date=0,end_date=0
                        from products as p, batches as b, upcLike as u
                        where u.likecode=? and u.upc=p.upc and b.startDate=p.start_date and b.endDate=p.end_date
                        and b.batchID=?";
                }
                $unsaleP = $dbc->prepare_statement($unsaleQ);
                $unsaleR = $dbc->exec_statement($unsaleP,array($lc,$id));

                //syncProductsAllLanes();
            }

            $delQ = $dbc->prepare_statement("delete from batchList where batchID=? and upc=?");
            $delR = $dbc->exec_statement($delQ,array($id,$upc));
            
            $delQ = $dbc->prepare_statement("delete from batchBarcodes where upc=? and batchID=?");
            $delR = $dbc->exec_statement($delQ,array($upc,$id));

            if (FormLib::get_form_value('audited') == '1') {
                AuditLib::batchNotification($id, $upc, AuditLib::BATCH_DELETE, (substr($upc,0,2)=='LC' ? true : false));
            }
            
            $out = $this->showBatchDisplay($id);
            break;
        case 'refilter':
            $owner = FormLib::get_form_value('owner','');
            
            $out = $this->batchListDisplay($owner);
            break;
        case 'savePrice':
            $id = FormLib::get_form_value('id',0);
            $upc = FormLib::get_form_value('upc','');
            $saleprice = FormLib::get_form_value('saleprice',0);
            $saleqty = FormLib::get_form_value('saleqty',1);
            $pm = ($saleqty >= 2)?2:0;    

            $model = new BatchListModel($dbc);
            $model->upc($upc);
            $model->batchID($id);
            $model->salePrice($saleprice);
            $model->quantity($saleqty);
            $model->pricemethod($pm);    
            $model->save();
            
            $upQ = $dbc->prepare_statement("update batchBarcodes set normal_price=? where upc=? and batchID=?");
            $upR = $dbc->exec_statement($upQ,array($saleprice,$upc,$id));

            if (FormLib::get_form_value('audited') == '1') {
                AuditLib::batchNotification($id, $upc, AuditLib::BATCH_EDIT, (substr($upc,0,2)=='LC' ? true : false));
            }
                
            break;
        case 'newTag':
            $id = FormLib::get_form_value('id',0);
            $upc = FormLib::get_form_value('upc','');
            $price = FormLib::get_form_value('price',0);
            
            $json = array();
            $json['input'] = $this->newTagInput($upc,$price,$id);
            $out = json_encode($json);
            
            break;
        case 'addTag':
            $id = FormLib::get_form_value('id',0);
            $upc = FormLib::get_form_value('upc','');
            $price = FormLib::get_form_value('price',0);
            $desc = FormLib::get_form_value('desc','');
            $brand = FormLib::get_form_value('brand','');
            $units = FormLib::get_form_value('units',1);
            $size = FormLib::get_form_value('size','');
            $sku = FormLib::get_form_value('sku','');
            $vendor = FormLib::get_form_value('vendor','');
            
            $checkQ = $dbc->prepare_statement("select upc from batchBarcodes where upc=? and batchID = ?");
            $checkR = $dbc->exec_statement($checkQ,array($upc,$id));
            if ($dbc->num_rows($checkR) == 0){
                $insQ = $dbc->prepare_statement("insert into batchBarcodes 
                    (upc,description,normal_price,brand,sku,size,units,vendor,batchID)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $args = array($upc,$desc,$price,$brand,$sku,$size,$units,$vendor,$id);
                $insR = $dbc->exec_statement($insQ,$args);
            }
            else {
                $upQ = $dbc->prepare_statement("update batchBarcodes set normal_price=? where upc=? AND batchID=?");
                $upR = $dbc->exec_statement($upQ,array($price,$upc,$id));
            }

            $model = new BatchListModel($dbc);
            $model->upc($upc);
            $model->batchID($id);
            $model->salePrice($price);
            $model->quantity(0);
            $model->pricemethod(0);
            $model->save();
            
            $out .= $this->addItemUPCInput(True);
            $out .= '`';
            $out .= $this->showBatchDisplay($id);
            break;
        case 'redisplay':
            $mode = FormLib::get_form_value('mode');
            $owner = FormLib::get('owner', '');
            $out = $this->batchListDisplay($owner, $mode);
            break;
        case 'batchListPage':
            $filter = FormLib::get_form_value('filter');
            $mode = FormLib::get_form_value('mode');
            $max = FormLib::get_form_value('maxBatchID');
            $out = $this->batchListDisplay($filter,$mode,$max);
            break;
        case 'forceBatch':
            $id = FormLib::get_form_value('id',0);
            forceBatch($id);    
            break;
        case 'switchToLC':
            $out .= $this->addItemLCInput();
            break;
        case 'switchFromLC':
            $out .= $this->addItemUPCInput();
            break;
        case 'redisplayWithOrder':
            $id = $_GET['id'];
            $id = FormLib::get_form_value('id',0);
            $order = FormLib::get_form_value('order');
            $out .= $this->showBatchDisplay($id,$order);
            break;
        case 'expand':
            $likecode = FormLib::get_form_value('likecode');
            $saleprice = FormLib::get_form_value('saleprice');
            $out .= $likecode."`";
            $out .= $saleprice."`";
            for ($i = 0; $i < 6; $i++) $out .= "<td>&nbsp;</td>";
            $out .= "`";
            
            $likeQ = $dbc->prepare_statement("select p.upc,p.description,p.normal_price
                from products as p left join upcLike as u on p.upc=u.upc
                where u.likecode = ? order by p.upc desc");
            $likeR = $dbc->exec_statement($likeQ,array($likecode));
            while ($likeW = $dbc->fetch_row($likeR)){
                $out .= "<td><a href={$FANNIE_URL}item/ItemEditorPage.php?searchupc=$likeW[0] target=_new$likeW[0]>$likeW[0]</a></td>";
                $out .= "<td>$likeW[1]</td>";
                $out .= "<td>$likeW[2]</td>";
                $out .= "<td>$saleprice</td>";
                $out .= "<td>&nbsp;</td>";
                $out .= "<td>&nbsp;</td>";
                $out .= "`";
            }
            $out = substr($out,0,strlen($out)-1);
            break;

        case 'doCut':
            $upc = FormLib::get_form_value('upc','');
            $bid = FormLib::get_form_value('batchID','');
            $uid = FormLib::get_form_value('uid','');
            $q = $dbc->prepare_statement("INSERT INTO batchCutPaste (batchID, upc, uid, tdate)
                                          VALUES (?,?,?," . $dbc->now() . ")");
            $dbc->exec_statement($q,array($bid,$upc,$uid));
            break;

        case 'unCut':
            $upc = FormLib::get_form_value('upc','');
            $bid = FormLib::get_form_value('batchID','');
            $uid = FormLib::get_form_value('uid','');
            $q = $dbc->prepare_statement("DELETE FROM batchCutPaste WHERE upc=?
                    AND batchID=? AND uid=?");
            $dbc->exec_statement($q,array($upc,$bid,$uid));
            break;

        case 'doPaste':
            $bid = FormLib::get_form_value('batchID','');
            $uid = FormLib::get_form_value('uid','');
            $q = $dbc->prepare_statement("SELECT listID FROM batchList as l INNER JOIN 
                batchCutPaste as b ON b.upc=l.upc AND b.batchID=l.batchID
                WHERE b.uid=?");
            $r = $dbc->exec_statement($q,array($uid));
            $upP = $dbc->prepare_statement('UPDATE batchList SET batchID=? WHERE listID=?');
            while($w = $dbc->fetch_row($r)){
                $dbc->exec_statement($upP,array($bid,$w['listID']));
            }
            $delP = $dbc->prepare_statement("DELETE FROM batchCutPaste WHERE uid=?");
            $dbc->exec_statement($delP,$uid);
            $out .= $this->showBatchDisplay($bid);
            break;
        case 'moveQual':
        case 'moveDisc':
            $batchID = FormLib::get_form_value('batchID','');
            $upc = FormLib::get_form_value('upc','');
            $q = $dbc->prepare_statement("UPDATE batchList SET salePrice = -1*salePrice
                WHERE batchID=? AND upc=?");
            $r = $dbc->exec_statement($q,array($batchID,$upc));
            $out .= $this->showBatchDisplay($batchID);
            break;
        case 'PS_toggleDiscSplit':
            $bid = FormLib::get_form_value('batchID','');
            $q = $dbc->prepare_statement("SELECT pricemethod FROM batchList WHERE
                batchID=?");
            $r = $dbc->exec_statement($q,array($bid));
            $currMethod = 4;
            if ($dbc->num_rows($r) > 0){
                $currMethod = array_pop($dbc->fetch_row($r));
                if (empty($currMethod)) $currMethod = 4;
            }
            $newMethod = ($currMethod==4) ? 3 : 4;
            
            $q = $dbc->prepare_statement("UPDATE batchList SET pricemethod=?
                WHERE batchID=?");
            $r = $dbc->exec_statement($q,array($newMethod,$bid));
            break;
        case 'PS_toggleMemberOnly':
            $bid = FormLib::get_form_value('batchID','');
            $model = new BatchesModel($dbc);
            $model->batchID($bid);
            $model->load();
            $cur = $model->discounttype();
            $new = ($cur==1) ? 2 : 1;
            $model->discounttype($new);
            $model->save();
            break;
        case 'PS_pricing':
            $qty = $_REQUEST['quantity'];
            $qty = FormLib::get_form_value('quantity');
            $disc = FormLib::get_form_value('discount');
            if ($disc < 0) $disc = abs($disc);
            $dtype = FormLib::get_form_value('discounttype');
            $pmethod = FormLib::get_form_value('pricemethod');
            $bid = FormLib::get_form_value('batchID',0);

            $model = new BatchesModel($dbc);
            $model->batchID($bid);
            $model->discounttype($dtype);
            $model->save();

            $upQ2 = $dbc->prepare_statement("UPDATE batchList SET
                    quantity=?,pricemethod=?,
                    salePrice=? WHERE batchID=?
                    AND salePrice >= 0");
            $upQ3 = $dbc->prepare_statement("UPDATE batchList SET
                    quantity=?,pricemethod=?,
                    salePrice=? WHERE batchID=?
                    AND salePrice < 0");
            $dbc->exec_statement($upQ2,array($qty+1,$pmethod,$disc,$bid));
            $dbc->exec_statement($upQ3,array($qty+1,$pmethod,-1*$disc,$bid));
            break;
        case 'saveLimit':
            $limit = FormLib::get_form_value('limit');
            $bid = FormLib::get_form_value('batchID',0);
            $limitQ = $dbc->prepare_statement("UPDATE batchList SET quantity=? WHERE batchID=?");
            $dbc->exec_statement($limitQ,array($limit,$bid));
            break;
        case 'autoTag':
            $bid = FormLib::get_form_value('batchID',0);
            $delQ = $dbc->prepare_statement("DELETE FROM batchBarcodes where batchID=?");
            $dbc->exec_statement($delQ,array($bid));
            
            $selQ = $dbc->prepare_statement("
                select l.upc,p.description,l.salePrice, 
                case when x.manufacturer is null then v.brand
                else x.manufacturer end as brand,
                case when v.sku is null then '' else v.sku end as sku,
                case when v.size is null then '' else v.size end as size,
                case when v.units is null then 1 else v.units end as units,
                case when x.distributor is null then z.vendorName
                else x.distributor end as vendor,
                l.batchID
                from batchList as l
                inner join products as p on
                l.upc=p.upc
                left join prodExtra as x on
                l.upc=x.upc
                left join vendorItems as v on
                l.upc=v.upc
                left join vendors as z on
                v.vendorID=z.vendorID
                where batchID=? ORDER BY l.upc");
            $selR = $dbc->exec_statement($selQ,array($bid));
            $upc = "";
            $insP = $dbc->prepare_statement("INSERT INTO batchBarcodes
                (upc,description,normal_price,brand,sku,size,units,vendor,batchID)
                VALUES (?,?,?,?,?,?,?,?,?)");
            while($selW = $dbc->fetch_row($selR)){
                if ($upc != $selW['upc']){
                    $dbc->exec_statement($insP,array(
                        $selW['upc'], $selW['description'],
                        $selW['salePrice'], $selW['brand'],
                        $selW['sku'], $selW['size'],
                        $selW['units'], $selW['vendor'],
                        $selW['batchID']
                    ));
                }
                $upc = $selW['upc'];
            }
            break;
        case 'UnsaleBatch':
            $id = FormLib::get_form_value('batchID',0);

            // unsale regular items
            $unsaleQ = "UPDATE products AS p LEFT JOIN batchList as b
                ON p.upc=b.upc
                SET special_price=0,
                specialpricemethod=0,specialquantity=0,
                specialgroupprice=0,discounttype=0,
                start_date='1900-01-01',end_date='1900-01-01'
                WHERE b.upc NOT LIKE '%LC%'
                AND b.batchID=?";
            if ($FANNIE_SERVER_DBMS=="MSSQL") {
                $unsaleQ = "UPDATE products SET special_price=0,
                    specialpricemethod=0,specialquantity=0,
                    specialgroupprice=0,discounttype=0,
                    start_date='1900-01-01',end_date='1900-01-01'
                    FROM products AS p, batchList as b
                    WHERE p.upc=b.upc AND b.upc NOT LIKE '%LC%'
                    AND b.batchID=?";
            }
            $prep = $dbc->prepare_statement($unsaleQ);
            $unsaleR = $dbc->exec_statement($prep,array($id));

            // unsale likecode items items
            $unsaleLCQ = "UPDATE products AS p LEFT JOIN
                upcLike AS v ON v.upc=p.upc LEFT JOIN
                batchList AS l ON l.upc=concat('LC',convert(v.likeCode,char))
                SET special_price=0,
                specialpricemethod=0,specialquantity=0,
                specialgroupprice=0,p.discounttype=0,
                start_date='1900-01-01',end_date='1900-01-01'
                WHERE l.upc LIKE '%LC%'
                AND l.batchID=?";
            if ($FANNIE_SERVER_DBMS=="MSSQL") {
                $unsaleLCQ = "UPDATE products
                    SET special_price=0,
                    specialpricemethod=0,specialquantity=0,
                    specialgroupprice=0,discounttype=0,
                    start_date='1900-01-01',end_date='1900-01-01'
                    FROM products AS p LEFT JOIN
                    upcLike AS v ON v.upc=p.upc LEFT JOIN
                    batchList AS l ON l.upc=concat('LC',convert(v.likeCode,char))
                    WHERE l.upc LIKE '%LC%'
                    AND l.batchID=?";
            }
            $prep = $dbc->prepare_statement($unsaleLCQ);
            $unsaleLCR = $dbc->exec_statement($prep,array($id));

            // find all affected UPCs
            $itemQ = 'SELECT l.upc
                      FROM batchList AS l 
                      WHERE l.batchID=?';
            $itemP = $dbc->prepare($itemQ);
            $likeQ = 'SELECT u.upc
                      FROM upcLike AS u
                        INNER JOIN products AS p ON u.upc=p.upc
                      WHERE u.likeCode=?';
            $likeP = $dbc->prepare($likeQ);
            $items = array();
            $itemR = $dbc->execute($itemP, array($id));
            while ($itemW = $dbc->fetch_row($itemR)) {
                if (substr($itemW['upc'], 0, 2) == 'LC') {
                    $likeCode = substr($itemW['upc'], 2);
                    $likeR = $dbc->execute($likeP, array($likeCode));
                    while ($likeW = $dbc->fetch_row($likeR)) {
                        $items[] = $likeW['upc'];
                    }
                } else {
                    $items[] = $itemW['upc'];
                }
            }

            // push changed items to lanes
            foreach ($items as $item) {
                updateProductAllLanes($item);
            }
            break;
        default:
            $out .= 'bad request';
            break;
        }
        
        print $out;
        return;
    }

    /* input functions
     * functions for generating content that goes in the
     * inputarea div
     */
    function newBatchInput()
    {
        global $FANNIE_URL;

        $ret = "<form id=\"newBatchForm\" onsubmit=\"newBatch(); return false;\">";
        $ret .= "<table>";
        $ret .= "<tr><th colspan=99 style='line-height:1.0em;'>Create a Batch:</th></tr>";
        $ret .= "<tr><th>Batch/Sale Type</th><th>Name</th><th>Start date</th><th>End date</th><th>Owner/Super Dept.</th><th>Priority</tr>";
        $ret .= "<tr>";
        $ret .= '<td><select id=newBatchType name="type">';
        foreach ($this->batchtypes as $id=>$desc) {
            $ret .= "<option value=$id>$desc</option>";
        }
        $ret .= "</select></td>";
        $ret .= '<td><input type=text id=newBatchName name="name" /></td>';
        $ret .= '<td><input type=text size=10 id=newBatchStartDate name="startdate" /></td>';
        $ret .= '<td><input type=text size=10 id=newBatchEndDate name="enddate" /></td>';
        $ret .= '<td><select id=newBatchOwner name="owner">';
        foreach ($this->owners as $o) {
            $ret .= "<option>$o</option>";
        }
        $ret .= "</select></td>";
        $ret .= '<td><select id="newBatchPriority" name="priority">';
        $ret .= sprintf('<option value="%d">Default</option>', 0);
        $ret .= sprintf('<option value="%d">Override</option>', 5);
        $ret .= "</select></td>";
        $ret .= "<td><input type=submit value=Add /></td>";
        $ret .= "</tr></table></form><br />";
        
        $ret .= "<span class=\"newBatchBlack\">";
        $ret .= "<b>Filter</b>: show batches owned by (of Super Dept.): ";
        $ret .= "</span>";
        $ret .= "<select id=filterOwner onchange=\"refilter(this.value);\">";
        foreach ($this->owners as $o) {
            $ret .= "<option>$o</option>";
        }
        $ret .= "</select>";
        
        $ret .= " <a href=\"{$FANNIE_URL}admin/labels/BatchShelfTags.php\">Print shelf tags</a>";
        
        return $ret;
    }

    function addItemUPCInput($newtags=false)
    {
        $ret = "<form class=\"addItemForm\" onsubmit=\"addItem(); return false;\">";
        $ret .= "<b style=\"color:#000;\">UPC</b>: <input type=text maxlength=13 name=\"upc\" id=addItemUPC /> ";
        $ret .= "<input type=submit value=Add />";
        $ret .= "<input type=checkbox id=addItemTag name=\"tag\" ";
         if ($newtags) {
            $ret .= " checked";
        }
        $ret .= " /> <label for=\"addItemTag\" class=\"newBatchBlack\">New shelf tag</label>";
        $ret .= " <input type=checkbox id=addItemLikeCode onclick=\"switchToLC();\" /> 
            <label for=\"addItemLikeCode\" class=\"newBatchBlack\">Likecode</label>";
        $ret .= "</form>";
        
        return $ret;
    }

    function addItemLCInput($newtags=false)
    {
        $dbc = $this->con;
        $ret = "<form class=\"addItemForm\" onsubmit=\"addItem(); return false;\">";
        $ret .= "<span class=\"newBatchBlack\">";
        $ret .= "<b>Like code</b>: <input type=text id=addItemUPC name=\"lc\" size=4 value=1 /> ";
        $ret .= "<select id=lcselect onchange=lcselect_util();>";
        $lcQ = $dbc->prepare_statement("select likecode,likecodeDesc from likeCodes order by likecode");
        $lcR = $dbc->exec_statement($lcQ);
        while ($lcW = $dbc->fetch_array($lcR)) {
            $ret .= "<option value=$lcW[0]>$lcW[0] $lcW[1]</option>";
        }
        $ret .= "</select>";
        $ret .= "<input type=submit value=Add />";
        $ret .= "<input type=checkbox id=addItemTag name=\"tag\" ";
        if ($newtags) {
            $ret .= " checked";
        }
        $ret .= " /> <label for=\"addItemTag\">New shelf tag</label>";
        $ret .= " <input type=checkbox id=addItemLikeCode checked onclick=\"switchFromLC();\" /> 
                  <label for=\"addItemLikeCode\">Likecode</label>";
        $ret .= "</span>";
        $ret .= "</form>";
        
        return $ret;
    }

    function addItemPriceInput($upc, $newtags=false)
    {
        $dbc = $this->con;

        $fetchQ = $dbc->prepare_statement("select description,normal_price from products where upc=?");
        $fetchR = $dbc->exec_statement($fetchQ,array($upc));
        $fetchW = $dbc->fetch_array($fetchR);
        
        $ret = "<form onsubmit=\"addItemFinish('$upc'); return false;\">";
        $ret .= "<span class=\"newBatchBlack\">";
        $ret .= "<b>UPC</b>: $upc <b>Description</b>: $fetchW[0] <b>Normal price</b>: $fetchW[1] ";
        $ret .= "<b>Sale price</b>: <input type=text id=addItemPrice size=5 /> ";
        $ret .= "<input type=submit value=Add />";
        $ret .= "<input type=checkbox id=addItemTag";
        if ($newtags) {
            $ret .= " checked";
        }
        $ret .= " /> New shelf tag";
        $ret .= "</span>";
        $ret .= "</form>";
        
        return $ret;
    }

    function addItemPriceLCInput($lc)
    {
        $dbc = $this->con;

        $fetchQ = $dbc->prepare_statement("select likecodedesc from likeCodes where likecode=?");
        $fetchR = $dbc->exec_statement($fetchQ,array($lc));
        $desc = array_pop($dbc->fetch_array($fetchR));
        
        /* get the most common price for items in a given
         * like code
         */
        $fetchQ = $dbc->prepare_statement("select p.normal_price from products as p
                left join upcLike as u on p.upc=u.upc and u.likecode=?
                where u.upc is not null
                group by p.normal_price
                order by count(*) desc");
        $fetchR = $dbc->exec_statement($fetchQ,array($lc));
        $normal_price = array_pop($dbc->fetch_array($fetchR));
        
        $ret = "<form onsubmit=\"addItemLCFinish('$lc'); return false;\">";
        $ret .= "<span class=\"newBatchBlack\">";
        $ret .= "<b>Like code</b>: $lc <b>Description</b>: $desc <b>Normal price</b>: $normal_price ";
        $ret .= "<b>Sale price</b>: <input type=text id=addItemPrice size=5 /> ";
        $ret .= "</span>";
        $ret .= "<input type=submit value=Add />";
        $ret .= "</form>";
    
        return $ret;
    }

    function newTagInput($upc, $price, $id)
    {
        $dbc = $this->con;

        $unfiQ = $dbc->prepare_statement("select brand,sku,size,upc,units,
                cost,description,vendorName from vendorItems as i 
                LEFT JOIN vendors AS v ON i.vendorID=v.vendorID
                where upc = ?");
        $unfiR = $dbc->exec_statement($unfiQ,array($upc));
        $unfiN = $dbc->num_rows($unfiR);
        
        $size = '';
        $brand = '';
        $units = '';
        $sku = '';
        $desc = '';
        $vendor = '';
        // grab info from the UNFI table if possible.
        if ($unfiN == 1) {
            $unfiW = $dbc->fetch_array($unfiR);
            $size = $unfiW['size'];
            $brand = strtoupper($unfiW['brand']);
            $brand = preg_replace("/\'/","",$brand);
            $units = $unfiW['units'];
            $sku = $unfiW['sku'];
            $desc = strtoupper($unfiW['description']);
            $desc = preg_replace("/\'/","",$desc);
            $vendor = $unfiW['vendorName'];
        } else {
            $descQ = $dbc->prepare_statement("select description from products where upc=?");
            $descR = $dbc->exec_statement($descQ,array($upc));
            $desc = strtoupper(array_pop($dbc->fetch_array($descR)));
        }
        
        $ret = "<form onsubmit=\"newTag(); return false;\">";
        $ret .= "<table>";
        $ret .= "<tr><th>UPC</th><td>$upc <input type=hidden id=newTagUPC value=$upc /></td></tr>";
        $ret .= "<tr><th>Description</th><td><input type=text id=newTagDesc value=\"$desc\" /></td></tr>";
        $ret .= "<tr><th>Brand</th><td><input type=text id=newTagBrand value=\"$brand\" /></td></tr>";
        $ret .= "<tr><th>Units</th><td><input type=text size=8 id=newTagUnits value=\"$units\" /></td></tr>";
        $ret .= "<tr><th>Size</th><td><input type=text size=7 id=newTagSize value=\"$size\" /></td></tr>";
        $ret .= "<tr><th>Vendor</th><td><input type=text id=newTagVendor value=\"$vendor\" /></td></tr>";
        $ret .= "<tr><th>SKU</th><td><input type=text id=newTagSKU value=\"$sku\" /></td></tr>";
        $ret .= "<tr><th>Price</th><td><span style=\"{color: #00bb00;}\">$price</span>";
        $ret .= "<input type=hidden id=newTagPrice value=\"$price\" /></td></tr>";
        $ret .= "<tr><td><input type=submit value=Add /></td>";
        $ret .= "<td><a href=\"\" onclick=\"showBatch($id,'true'); return false;\">Cancel</a></td></tr>";
        $ret .= "<input type=hidden id=newTagID value=$id />";
        $ret .= "</table></form>";
        
        return $ret;
    }

    /* display functions
     * functions for generating content that goes in the
     * displayarea div
     */
    function batchListDisplay($filter='', $mode='all', $maxBatchID='')
    {
        global $FANNIE_URL;
        $dbc = $this->con;
        
        $colors = array('#ffffff','#ffffcc');
        $c = 0;
        $ret = "<span class=\"newBatchBlack\">";
        $ret .= "<b>Display</b>: ";
        if ($mode != 'pending') {
            $ret .= "<a href=\"\" onclick=\"redisplay('pending'); return false;\">Pending</a> | ";
        } else {
            $ret .= "Pending | ";
        }
        if ($mode != 'current') {
            $ret .= "<a href=\"\" onclick=\"redisplay('current'); return false;\">Current</a> | ";
        } else {
            $ret .= "Current | ";
        }
        if ($mode != 'historical') {
            $ret .= "<a href=\"\" onclick=\"redisplay('historical'); return false;\">Historical</a> | ";
        } else {
            $ret .= "Historical | ";
        }
        if ($mode != 'all') {
            $ret .= "<a href=\"\" onclick=\"redisplay('all'); return false;\">All</a>";
        } else {
            $ret .= "All<br />";
        }
        $ret .= "</span>";
        $ret .= "<table border=1 cellspacing=0 cellpadding=3>";
        $ret .= "<tr><th bgcolor=$colors[$c]>Batch Name</th>";
        $ret .= "<th bgcolor=$colors[$c]>Type</th>";
        $ret .= "<th bgcolor=$colors[$c]>Start date</th>";
        $ret .= "<th bgcolor=$colors[$c]>End date</th>";
        $ret .= "<th bgcolor=$colors[$c]>Owner/Super Dept.</th>";
        $ret .= "<th colspan=\"3\">&nbsp;</th></tr>";
        
        // owner column might be in different places
        // depending if schema is up to date
        $ownerclause = "'' as owner FROM batches AS b";
        $batchesTable = $dbc->tableDefinition('batches');
        $owneralias = '';
        if (isset($batchesTable['owner'])) {
            $ownerclause = 'b.owner FROM batches AS b';
            $owneralias = 'b';
        } else if ($dbc->tableExists('batchowner')) {
            $ownerclause = 'o.owner FROM batches AS b LEFT JOIN
                            batchowner AS o ON b.batchID=o.batchID';
            $owneralias = 'o';
        }

        // the 'all' query
        // where clause is for str_ireplace below
        $fetchQ = "select b.batchName,b.batchType,b.startDate,b.endDate,b.batchID,
                   $ownerclause
                   WHERE 1=1 ";
        $args = array();
        switch($mode) {
            case 'pending':
                $fetchQ .= ' AND '. $dbc->datediff("b.startDate",$dbc->now()) . ' > 0 ';
                break;
            case 'current':
                $fetchQ .= '
                    AND ' . $dbc->datediff("b.startDate",$dbc->now()) . ' <= 0
                    AND ' . $dbc->datediff("b.endDate",$dbc->now()) . ' >= 0 ';
                break;
            case 'historical':
                $fetchQ .= ' AND '. $dbc->datediff("b.startDate",$dbc->now()) . ' <= 0 ';
                break;    
        }
        // use a filter - only works in 'all' mode
        if ($filter != '') {
            $fetchQ .= ' AND ' . $owneralias . '.owner = ? ';
            $args[] = $filter;
        }
        $fetchQ .= ' ORDER BY b.batchID DESC';
        $fetchQ = $dbc->add_select_limit($fetchQ,50);
        if (is_numeric($maxBatchID)) {
            $fetchQ = str_replace("WHERE ","WHERE b.batchID < ? AND ",$fetchQ);
            array_unshift($args,$maxBatchID);
        }
        $fetchR = $dbc->exec_statement($fetchQ,$args);
        
        $count = 0;
        $lastBatchID = 0;
        while($fetchW = $dbc->fetch_array($fetchR)) {
            $c = ($c + 1) % 2;
            $ret .= '<tr id="batchRow' . $fetchW['batchID'] . '">';
            $ret .= "<td bgcolor=$colors[$c] id=name$fetchW[4]><a id=namelink$fetchW[4] href=\"\" onclick=\"showBatch($fetchW[4]";
            if ($fetchW[1] == 4) {// batchtype 4
                $ret .= ",'true'";
            } else {
                $ret .= ",'false'";
            }
            $ret .= "); return false;\">$fetchW[0]</a></td>";
            $ret .= "<td bgcolor=$colors[$c] id=type$fetchW[4]>".$this->batchtypes[$fetchW[1]]."</td>";
            if (strpos($fetchW[2], ' ') > 0) {
                list($fetchW[2], $time) = explode(' ', $fetchW[2], 2);
            }
            $ret .= "<td bgcolor=$colors[$c] id=startdate$fetchW[4]>$fetchW[2]</td>";
            if (strpos($fetchW[3], ' ') > 0) {
                list($fetchW[3], $time) = explode(' ', $fetchW[3], 2);
            }
            $ret .= "<td bgcolor=$colors[$c] id=enddate$fetchW[4]>$fetchW[3]</td>";
            $ret .= "<td bgcolor=$colors[$c] id=owner$fetchW[4]>$fetchW[5]</td>";
            $ret .= "<td bgcolor=$colors[$c] id=edit$fetchW[4]>
                <a href=\"\" onclick=\"editBatch($fetchW[4]); return false;\" class=\"batchEditLink\">
                    <img src=\"{$FANNIE_URL}src/img/buttons/b_edit.png\" alt=\"Edit\" />
                </a>
                <a href=\"\" onclick=\"saveBatch($fetchW[4]); return false;\" class=\"batchSaveLink\" style=\"display:none;\">
                    <img src=\"{$FANNIE_URL}src/img/buttons/b_save.png\" alt=\"Save\" />
                </a>
                </td>";
            $ret .= "<td bgcolor=$colors[$c]><a href=\"\" onclick=\"deleteBatch($fetchW[4],'$fetchW[0]'); return false;\"><img src=\"{$FANNIE_URL}src/img/buttons/b_drop.png\" alt=\"Delete\" /></a></td>";
            $ret .= "<td bgcolor=$colors[$c]><a href=\"batchReport.php?batchID=$fetchW[4]\">Report</a></td>";
            $ret .= "</tr>";
            $count++;
            $lastBatchID = $fetchW[4];
        }
        
        $ret .= "</table>";

        if (is_numeric($maxBatchID)) {
            $ret .= sprintf("<a href=\"\" 
                    onclick=\"scroll(0,0); batchListPage('%s','%s',''); return false;\">First Page</a>
                     | ",
                    $filter,$mode);
        }
        if ($count >= 50) {
            $ret .= sprintf("<a href=\"\" 
                    onclick=\"scroll(0,0); batchListPage('%s','%s',%d); return false;\">Next page</a>",
                    $filter,$mode,$lastBatchID);                
        } else {
            $ret .= "<span class=\"newBatchBlack\">Next page</span>";
        }

        return $ret;
    }

    function showBatchDisplay($id, $order='natural')
    {
        global $FANNIE_SERVER_DBMS,$FANNIE_URL;
        $dbc = $this->con;
        $uid = getUID($this->current_user);
        $uid = ltrim($uid,'0');
    
        $orderby = '';
        switch($order){
        case 'natural':
        default:
            $orderby = 'ORDER BY b.listID DESC';
            break;
        case 'upc_a':
            $orderby = 'ORDER BY b.upc ASC';
            break;
        case 'upc_d':
            $orderby = 'ORDER BY b.upc DESC';
            break;
        case 'desc_a':
            $orderby = 'ORDER BY description ASC';
            break;
        case 'desc_b':
            $orderby = 'ORDER BY description DESC';
            break;
        case 'price_a':
            $orderby = 'ORDER BY p.normal_price ASC';
            break;
        case 'price_d':
            $orderby = 'ORDER BY p.normal_price DESC';
            break;
        case 'sale_a':
            $orderby = 'ORDER BY b.salePrice ASC';
            break;
        case 'sale_d':
            $orderby = 'ORDER BY b.salePrice DESC';
            break;
        case 'loc_a':
            $orderby = 'ORDER BY m.super_name,y.subsection,y.shelf_set,y.shelf';
            break;
        case 'loc_d':
            $orderby = 'ORDER BY m.super_name DESC,y.subsection DESC,y.shelf_set DESC,y.shelf DESC';
            break;
        }

        $model = new BatchesModel($dbc);
        $model->batchID($id);
        $model->load();
        $name = $model->batchName();
        $type = $model->batchType();
        $dtype = $model->discounttype();

        if ($type == 10){
            return $this->showPairedBatchDisplay($id,$name);
        }

        $limitQ = $dbc->prepare_statement("select max(quantity),max(pricemethod) from batchList WHERE batchID=?");
        $limitR = $dbc->exec_statement($limitQ,array($id));
        $hasLimit = False;
        $canHaveLimit = False;
        $limit = 0;
        if ($dbc->num_rows($limitR) > 0){
            $limitW = $dbc->fetch_row($limitR);
            $limit = $limitW[0];
            $pm = $limitW[1];
            if ($pm > 0){
                // no limits with grouped sales
                $canHaveLimit = False;
                $p = $dbc->prepare_statement("UPDATE batchList SET quantity=0 WHERE pricemethod=0
                    AND batchID=?");
                $dbc->exec_statement($p,array($id));
            }
            else {
                $canHaveLimit = True;
                if ($limit > 0){
                    $hasLimit = True;
                }
            }
        }

        $saleHeader = "Sale Price";
        if ($dtype == 3){
            $saleHeader = "$ Discount";
        }
        elseif ($dtype == 4){
            $saleHeader = "% Discount";
        }
        elseif ($dtype == 0){
            $saleHeader = "New price";
        }
        
        $fetchQ = "select b.upc,
                case when l.likeCode is null then p.description
                else l.likeCodeDesc end as description,
                p.normal_price,b.salePrice,
                CASE WHEN c.upc IS NULL then 0 ELSE 1 END as isCut,
                b.quantity,b.pricemethod,
                m.super_name, y.subsection, y.shelf_set, y.shelf
                from batchList as b left join products as p on
                b.upc = p.upc left join likeCodes as l on
                b.upc = concat('LC',convert(l.likeCode,char))
                left join batchCutPaste as c ON
                b.upc=c.upc AND b.batchID=c.batchID
                left join prodPhysicalLocation as y on b.upc=y.upc
                left join superDeptNames as m on y.section=m.superID
                where b.batchID = ? $orderby";
        if ($FANNIE_SERVER_DBMS == "MSSQL"){
            $fetchQ = "select b.upc,
                    case when l.likecode is null then p.description
                    else l.likecodedesc end as description,
                    p.normal_price,b.salePrice,
                    CASE WHEN c.upc IS NULL then 0 ELSE 1 END as isCut,
                    b.quantity,b.pricemethod
                    from batchList as b left join products as p on
                    b.upc = p.upc left join likeCodes as l on
                    b.upc = 'LC'+convert(varchar,l.likecode)
                    left join batchCutPaste as c ON
                    b.upc=c.upc AND b.batchID=c.batchID
                    where b.batchID = ? $orderby";
        }
        $fetchP = $dbc->prepare_statement($fetchQ);
        $fetchR = $dbc->exec_statement($fetchP,array($id));

        $cpCount = $dbc->prepare_statement("SELECT count(*) FROM batchCutPaste WHERE uid=?");
        $res = $dbc->exec_statement($cpCount,array($uid));
        $row = $dbc->fetch_row($res);
        $cp = $row[0];
        
        $ret = "<span class=\"newBatchBlack\"><b>Batch name</b>: $name</span><br />";
        $ret .= "<a href=\"\" onclick=\"backToList(); return false;\">Back to batch list</a> | ";
        $ret .= "<a href=\"{$FANNIE_URL}admin/labels/BatchShelfTags.php?batchID%5B%5D=$id\">Print shelf tags</a> | ";
        $ret .= "<a href=\"\" onclick=\"autoTag($id); return false;\">Auto-tag</a> | ";
        if ($cp > 0)
            $ret .= "<a href=\"\" onclick=\"doPaste($uid,$id); return false;\">Paste Items ($cp)</a> | ";
        $ret .= "<a href=\"\" onclick=\"forceBatch($id); return false;\">Force batch</a> | ";
        if ($dtype != 0) {
            $ret .= "<a href=\"\" onclick=\"unsaleBatch($id); return false;\">Stop Sale</a> | ";
        }
        if (!$canHaveLimit){
            $ret .= "No limit";
            $ret .= " <span id=\"currentLimit\" style=\"color:#000;\"></span>";
        }
        else if (!$hasLimit){
            $ret .= "<span id=\"limitLink\"><a href=\"\" onclick=\"editLimit($id,0); return false;\">Add Limit</a></span>";
            $ret .= " <span id=\"currentLimit\" style=\"color:#000;\"></span>";
        }
        else if ($hasLimit){
            $ret .= "<span id=\"limitLink\"><a href=\"\" onclick=\"editLimit($id,$limit); return false;\">Limit:</a></span>";
            $ret .= " <span id=\"currentLimit\" style=\"color:#000;\">$limit</span>";
        }
        $ret .= "<br />";
        $ret .= "<table id=yeoldetable cellspacing=0 cellpadding=3 border=1>";
        $ret .= "<tr>";
        if ($orderby != "ORDER BY b.upc ASC")
            $ret .= "<th><a href=\"\" onclick=\"redisplayWithOrder($id,'upc_a'); return false;\">UPC</a></th>";
        else
            $ret .= "<th><a href=\"\" onclick=\"redisplayWithOrder($id,'upc_d'); return false;\">UPC</a></th>";
        if ($orderby != "ORDER BY description ASC")
            $ret .= "<th><a href=\"\" onclick=\"redisplayWithOrder($id,'desc_a'); return false;\">Description</a></th>";
        else
            $ret .= "<th><a href=\"\" onclick=\"redisplayWithOrder($id,'desc_b'); return false;\">Description</a></th>";
        if ($orderby != "ORDER BY p.normal_price DESC")
            $ret .= "<th><a href=\"\" onclick=\"redisplayWithOrder($id,'price_d'); return false;\">Normal price</a></th>";
        else
            $ret .= "<th><a href=\"\" onclick=\"redisplayWithOrder($id,'price_a'); return false;\">Normal price</a></th>";
        if ($orderby != "ORDER BY b.salePrice DESC")
            $ret .= "<th><a href=\"\" onclick=\"redisplayWithOrder($id,'sale_d'); return false;\">$saleHeader</a></th>";
        else
            $ret .= "<th><a href=\"\" onclick=\"redisplayWithOrder($id,'sale_a'); return false;\">$saleHeader</a></th>";
        $ret .= "<th colspan=\"3\">&nbsp;</th>";
        if ($orderby != 'ORDER BY m.super_name,y.subsection,y.shelf_set,y.shelf')
            $ret .= "<th><a href=\"\" onclick=\"redisplayWithOrder($id,'loc_a'); return false;\">Location</a></th>";
        else
            $ret .= "<th><a href=\"\" onclick=\"redisplayWithOrder($id,'loc_d'); return false;\">Location</a></th>";
        $ret .= "</tr>";
        
        $colors = array('#ffffff','#ffffcc');
        $c = 0;
        $row = 1;
        while($fetchW = $dbc->fetch_array($fetchR)){
            $c = ($c + 1) % 2;
            $ret .= "<tr>";
            $fetchW[0] = rtrim($fetchW[0]);
            if (substr($fetchW[0],0,2) == "LC"){
                $likecode = rtrim(substr($fetchW[0],2));
                $ret .= "<td bgcolor=$colors[$c]>$fetchW[0]";
                $ret .= "<span id=LCToggle$likecode>";
                $ret .= " <a href=\"\" onclick=\"expand($likecode,$fetchW[3]); return false;\">[+]</a>";
                $ret .= "</span></td>";
                $ret .= "<input type=hidden value=$row id=expandId$likecode name=expandId />";
            }
            else {
                $ret .= "<td bgcolor=$colors[$c]><a href={$FANNIE_URL}item/ItemEditorPage.php?searchupc=$fetchW[0] target=_new$fetchW[0]>$fetchW[0]</a></td>";
            }
            $ret .= "<td bgcolor=$colors[$c]>$fetchW[1]</td>";
            $ret .= "<td bgcolor=$colors[$c]>$fetchW[2]</td>";
            $qtystr = ($fetchW['pricemethod']>0 && is_numeric($fetchW['quantity']) && $fetchW['quantity'] > 0)?$fetchW['quantity']." for ":"";
            $ret .= "<td bgcolor=$colors[$c]><span id=saleQty$fetchW[0]>$qtystr</span><span id=salePrice$fetchW[0]>";
            $ret .= sprintf("%.2f</span></td>",$fetchW[3]);
            $ret .= "<td bgcolor=$colors[$c] id=editLink$fetchW[0]><a href=\"\" onclick=\"editPrice('$fetchW[0]'); return false;\"><img src=\"{$FANNIE_URL}src/img/buttons/b_edit.png\" alt=\"Edit\" /></a></td>";
            $ret .= "<td bgcolor=$colors[$c]><a href=\"\" onclick=\"deleteItem('$fetchW[0]'); return false;\"><img src=\"{$FANNIE_URL}src/img/buttons/b_drop.png\" alt=\"Delete\" /></a></td>";
            if ($fetchW[4] == 1)
                $ret .= "<td bgcolor=$colors[$c] id=cpLink$fetchW[0]><a href=\"\" onclick=\"unCut('$fetchW[0]',$id,$uid); return false;\">Undo</a></td>";
            else
                $ret .= "<td bgcolor=$colors[$c] id=cpLink$fetchW[0]><a href=\"\" onclick=\"doCut('$fetchW[0]',$id,$uid); return false;\">Cut</a></td>";

            $loc = 'n/a';
            if (!empty($fetchW['subsection'])) {
                $loc = substr($fetchW['super_name'],0,4);
                $loc .= $fetchW['subsection'].', ';
                $loc .= 'Unit '.$fetchW['shelf_set'].', ';
                $loc .= 'Shelf '.$fetchW['shelf'];
            }
            $ret .= "<td bgcolor=$colors[$c]>".$loc.'</td>';

            $ret .= "</tr>";
            $row++;
        }
        $ret .= "</table>";
        $ret .= "<input type=hidden id=currentBatchID value=$id />";
        
        return $ret;
    }

    function showPairedBatchDisplay($id, $name)
    {
        global $FANNIE_SERVER_DBMS,$FANNIE_URL;
        $dbc = $this->con;
        $uid = getUID($this->current_user);
        $uid = ltrim($uid,'0');

        $ret = "";
        $ret .= sprintf('<input type="hidden" id="currentBatchID" value="%d" />',$id);
        $ret .= "<b>Batch name</b>: $name<br />";
        $ret .= "<a href=\"\" onclick=\"backToList(); return false;\">Back to batch list</a> | ";
        $ret .= "<a href=\"\" onclick=\"forceBatch($id); return false;\">Force batch</a>";
        $ret .= "No limit";
        $ret .= " <span id=\"currentLimit\" style=\"color:#000;\"></span>";

        $q = $dbc->prepare_statement("SELECT b.discounttype,salePrice,
            CASE WHEN l.pricemethod IS NULL THEN 4 ELSE l.pricemethod END as pricemethod,
            CASE WHEN l.quantity IS NULL THEN 1 ELSE l.quantity END as quantity
            FROM batches AS b LEFT JOIN batchList AS l 
            ON b.batchID=l.batchID WHERE b.batchID=? ORDER BY l.pricemethod");
        $r = $dbc->exec_statement($q,array($id));
        $w = $dbc->fetch_row($r);

        if (!empty($w['salePrice'])){
            $ret .= "<i>Add all items before fiddling with these settings
                or they'll tend to go haywire</i>";
            $ret .= '<table cellspacing=0 cellpadding=4 border=1>';
            $ret .= '<tr><th>Member only sale</th><td colspan="3"><input type="checkbox"
                onclick="PS_toggleMemberOnly('.$id.');" id="PS_memCBX" '
                .($w['discounttype']==2?'checked':'').' /></td>';    
            $ret .= '<th>Split discount</th><td colspan="1"><input type="checkbox"
                onclick="PS_toggleDiscSplit('.$id.');" id="PS_splitCBX" '
                .($w['pricemethod']==4?'':'checked').' /></td></tr>';
            $ret .= '<tr><th>Qualifiers Required</th>';
            $ret .= sprintf('<td><input type="text" size="4" value="%d"
                    id="PS_qualCount" /></td>',
                    $w['quantity']-1);
            $ret .= '<th>Discount</th>';
            $ret .= sprintf('<td><input type="text" size="5" value="%.2f"
                    id="PS_discount" /></td>',
                    (empty($w['salePrice'])?'':abs($w['salePrice'])));
            $ret .= sprintf('<td colspan="2"><input type="submit" value="Update Pricing"
                    onclick="PS_pricing(%d); return false;" /></td></tr>',$id);
            $ret .= '</table>';
        }
        else {
            $ret .= "<i>Add items first</i>";
        }

        $fetchQ = $dbc->prepare_statement("select b.upc,
                case when l.likeCode is null then p.description
                else l.likeCodeDesc end as description,
                p.normal_price,b.salePrice
                from batchList as b left join products as p on
                b.upc = p.upc left join likeCodes as l on
                b.upc = concat('LC'+convert(l.likeCode,char))
                where b.batchID = ? AND b.salePrice >= 0");
        if ($FANNIE_SERVER_DBMS == "MSSQL"){
            $fetchQ = $dbc->prepare_statement("select b.upc,
                    case when l.likecode is null then p.description
                    else l.likecodedesc end as description,
                    p.normal_price,b.salePrice
                    from batchList as b left join products as p on
                    b.upc = p.upc left join likeCodes as l on
                    b.upc = 'LC'+convert(varchar,l.likecode)
                    where b.batchID = ? AND b.salePrice >= 0");
        }
        $fetchR = $dbc->exec_statement($fetchQ,array($id));

        $colors = array('#ffffff','#ffffcc');
        $c = 0;
        $row = 1;
        $ret .= '<p /><table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr><th colspan="4">Qualifying Item(s)</th></tr>';
        while($fetchW = $dbc->fetch_array($fetchR)){
            $c = ($c + 1) % 2;
            $ret .= "<tr>";
            $fetchW[0] = rtrim($fetchW[0]);
            if (substr($fetchW[0],0,2) == "LC"){
                $likecode = rtrim(substr($fetchW[0],2));
                $ret .= "<td bgcolor=$colors[$c]>$fetchW[0]";
                $ret .= "</td>";
                $ret .= "<input type=hidden value=$row id=expandId$likecode name=expandId />";
            }
            else {
                $ret .= "<td bgcolor=$colors[$c]><a href={$FANNIE_URL}item/ItemEditorPage.php?searchupc=$fetchW[0] target=_new$fetchW[0]>$fetchW[0]</a></td>";
            }
            $ret .= "<td bgcolor=$colors[$c]>$fetchW[1]</td>";
            $ret .= "<td bgcolor=$colors[$c]><a href=\"\" onclick=\"moveDisc('$fetchW[0]'); return false;\"><img src=\"{$FANNIE_URL}src/img/buttons/arrow_down.gif\" alt=\"Make Discount Item\" /></a></td>";
            $ret .= "<td bgcolor=$colors[$c]><a href=\"\" onclick=\"deleteItem('$fetchW[0]'); return false;\"><img src=\"{$FANNIE_URL}src/img/buttons/b_drop.png\" alt=\"Delete\" /></a></td>";
            $ret .= "</tr>";
            $row++;
        }
        $ret .= "</table>";

        $fetchQ = $dbc->prepare_statement("select b.upc,
                case when l.likecode is null then p.description
                else l.likecodedesc end as description,
                p.normal_price,b.salePrice
                from batchList as b left join products as p on
                b.upc = p.upc left join likeCodes as l on
                b.upc = concat('LC',convert(l.likecode,char))
                where b.batchID = ? AND b.salePrice < 0");
        if ($FANNIE_SERVER_DBMS == "MSSQL"){
            $fetchQ = $dbc->prepare_statement("select b.upc,
                    case when l.likecode is null then p.description
                    else l.likecodedesc end as description,
                    p.normal_price,b.salePrice
                    from batchList as b left join products as p on
                    b.upc = p.upc left join likeCodes as l on
                    b.upc = 'LC'+convert(varchar,l.likecode)
                    where b.batchID = ? AND b.salePrice < 0");
        }
        $fetchR = $dbc->exec_statement($fetchQ,array($id));

        $colors = array('#ffffff','#ffffcc');
        $c = 0;
        $row = 1;
        $ret .= '<p /><table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr><th colspan="4">Discount Item(s)</th></tr>';
        while($fetchW = $dbc->fetch_array($fetchR)){
            $c = ($c + 1) % 2;
            $ret .= "<tr>";
            $fetchW[0] = rtrim($fetchW[0]);
            if (substr($fetchW[0],0,2) == "LC"){
                $likecode = rtrim(substr($fetchW[0],2));
                $ret .= "<td bgcolor=$colors[$c]>$fetchW[0]";
                $ret .= "</td>";
                $ret .= "<input type=hidden value=$row id=expandId$likecode name=expandId />";
            }
            else {
                $ret .= "<td bgcolor=$colors[$c]><a href={$FANNIE_URL}item/ItemEditorPage.php?searchupc=$fetchW[0] target=_new$fetchW[0]>$fetchW[0]</a></td>";
            }
            $ret .= "<td bgcolor=$colors[$c]>$fetchW[1]</td>";
            $ret .= "<td bgcolor=$colors[$c]><a href=\"\" onclick=\"moveQual('$fetchW[0]'); return false;\"><img src=\"{$FANNIE_URL}src/img/buttons/arrow_up.gif\" alt=\"Make Qualifying Item\" /></a></td>";
            $ret .= "<td bgcolor=$colors[$c]><a href=\"\" onclick=\"deleteItem('$fetchW[0]'); return false;\"><img src=\"{$FANNIE_URL}src/img/buttons/b_drop.png\" alt=\"Delete\" /></a></td>";
            $ret .= "</tr>";
            $row++;
        }
        $ret .= "</table>";

        return $ret;
    }

    function body_content()
    {
        global $FANNIE_URL;
        $this->add_script('index.js');
        $this->add_script($FANNIE_URL.'src/javascript/jquery.js');
        $this->add_script($FANNIE_URL.'src/javascript/jquery-ui.js');
        $this->add_css_file('index.css');
        $this->add_css_file($FANNIE_URL.'src/style.css');
        $this->add_css_file($FANNIE_URL.'src/javascript/jquery-ui.css');
        ob_start();
        ?>
        <html>
        <head><title>Batch Management</title>
        </head>
        <body>

        <div id="inputarea">
        <?php echo $this->newBatchInput(); ?>
        </div>
        <div id="displayarea">
        <?php echo $this->batchListDisplay(); ?>
        </div>
        <input type=hidden id=uid value="<?php echo $this->current_user; ?>" />
        <input type=hidden id=isAudited value="<?php echo $this->audited; ?>" />
        <?php
        $ret = ob_get_clean();
    
        $typestr = "";
        foreach($this->batchtypes as $b) {
            $typestr .= $b."`";
        }
        $typestr = substr($typestr,0,strlen($typestr)-1);

        $tidstr = "";
        foreach($this->batchtypes as $tid=>$b) {
            $tidstr .= $tid."`";
        }
        $tidstr = substr($tidstr,0,strlen($tidstr)-1);

        $ownerstr = "";
        foreach($this->owners as $o) {
            $ownerstr .= $o."`";
        }
        $ownerstr = substr($ownerstr,0,strlen($ownerstr)-1);    

        $ret .= "<input type=hidden id=passtojstypes value=\"$typestr\" />";
        $ret .= "<input type=hidden id=passtojstypeids value=\"$tidstr\" />";
        $ret .= "<input type=hidden id=passtojsowners value=\"$ownerstr\" />";
        $ret .= "<input type=hidden id=buttonimgpath value=\"{$FANNIE_URL}src/img/buttons/\" />";
        $ret .= '</body></html>';

        if (FormLib::get('startAt', 0 ) != 0) {
            $showID = FormLib::get('startAt');
            $this->add_onload_command("showBatch($showID, false);\n");
        } else {
            $this->add_onload_command("\$('#newBatchStartDate').datepicker();\n");
            $this->add_onload_command("\$('#newBatchEndDate').datepicker();\n");
        }

        return $ret;
    }
}

FannieDispatch::conditionalExec(false);


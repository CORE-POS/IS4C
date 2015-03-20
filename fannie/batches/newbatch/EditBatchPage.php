<?php
/*******************************************************************************

    Copyright 2009,2010 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!function_exists('checkLogin')) {
    include_once($FANNIE_ROOT . 'auth/login.php');
}
if (!function_exists("updateProductAllLanes")) include($FANNIE_ROOT.'item/laneUpdates.php');

class EditBatchPage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $auth_classes = array('batches','batches_audited');
    protected $title = 'Sales Batches Tool';
    protected $header = 'Sales Batches Tool';

    public $description = '[Sales Batches] is the primary tool for creating, editing, and managing 
    sale and price change batches.';
    public $themed = true;

    private $audited = 1;
    private $con = null;

    function preprocess()
    {
        global $FANNIE_OP_DB;
        // maintain user logins longer
        refreshSession();
        if (validateUserQuiet('batches')) {
            $this->audited = 0;
        }

        $this->con = FannieDB::get($FANNIE_OP_DB);

        // autoclear old data from clipboard table on intial page load
        $clipboard = $this->con->tableDefinition('batchCutPaste');
        if (isset($clipboard['tdate'])) {
            $this->con->query('DELETE FROM batchCutPaste WHERE tdate < ' . $this->con->curdate());
        }

        $this->__routes[] = 'get<id><paste>';
        $this->__routes[] = 'post<id><addUPC>';
        $this->__routes[] = 'post<id><addLC>';
        $this->__routes[] = 'post<id><upc><price>';
        $this->__routes[] = 'post<id><autotag>';
        $this->__routes[] = 'post<id><force>';
        $this->__routes[] = 'post<id><unsale>';
        $this->__routes[] = 'post<id><limit>';
        $this->__routes[] = 'post<id><upc><uid><cut>';
        $this->__routes[] = 'post<id><upc><price><qty>';
        $this->__routes[] = 'delete<id><upc>';
        $this->__routes[] = 'post<id><upc><swap>';
        $this->__routes[] = 'post<id><qualifiers><discount>';

        return parent::preprocess();
    }

    protected function get_id_paste_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $uid = getUID($this->current_user);
        $uid = ltrim($uid,'0');
        $bid = $this->id;

        $q = $dbc->prepare_statement("
            SELECT listID 
            FROM batchList AS l 
                INNER JOIN batchCutPaste as b ON b.upc=l.upc AND b.batchID=l.batchID
            WHERE b.uid=?"
        );
        $r = $dbc->exec_statement($q,array($uid));
        $upP = $dbc->prepare_statement('UPDATE batchList SET batchID=? WHERE listID=?');
        $count = 0;
        while ($w = $dbc->fetch_row($r)) {
            $dbc->exec_statement($upP,array($bid,$w['listID']));
            $count++;
        }
        $delP = $dbc->prepare_statement("DELETE FROM batchCutPaste WHERE uid=?");
        $dbc->exec_statement($delP,$uid);

        $this->add_onload_command("showBootstrapAlert('#inputarea', 'success', 'Pasted $count items');\n");

        return true;
    }

    protected function post_id_addUPC_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $id = $this->id;
        $upc = trim($this->addUPC);
        $upc = BarcodeLib::padUPC($upc);

        $json = array(
            'error' => 0,
            'msg' => '',
            'content' => '',
            'field' => '#addItemUPC',
        );

        /**
          Nothing submitted; don't do anything
        */
        if ($this->addUPC === '') {
            echo json_encode($json);
            return false;
        }

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
                . '<a style="color:blue;" href="EditBatchPage.php?id=' . $row['batchID'] . '">'
                . $row['batchName'] . '</a> ('
                . date('Y-m-d', strtotime($row['startDate'])) . ' - '
                . date('Y-m-d', strtotime($row['endDate'])) . ')'
                . '<br />'
                . 'Either remove item from conflicting batch or change
                   dates so the batches do not overlap.';
            $json['error'] = 1;
            $json['msg'] = $error;
        } else {
            $product = new ProductsModel($dbc);
            $product->upc($upc);
            if (!$product->load()) {
                $json['error'] = 1;
                $json['msg'] = 'Item not found: ' . $upc;
            } else {
                $tag = FormLib::get('tag') !== '' ? true : false;
                $json['content'] = $this->addItemPriceInput($upc, $tag, $product->description(), $product->normal_price());
                $json['field'] = '#add-item-price';
            }
        }
        echo json_encode($json);

        return false;
    }

    protected function post_id_addLC_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $json = array(
            'error' => 0,
            'msg' => '',
            'content' => '',
            'field' => '#addItemLC',
        );

        /**
          Nothing submitted; don't do anything
        */
        if ($this->addLC === '') {
            echo json_encode($json);
            return false;
        }

        $infoP = $dbc->prepare('
            SELECT l.likeCodeDesc,
                p.normal_price
            FROM likeCodes AS l
                INNER JOIN upcLike AS u ON l.likeCode=u.likeCode
                INNER JOIN products AS p ON u.upc=p.upc
            WHERE l.likeCode=?
            ORDER BY p.normal_price
        ');
        $infoR = $dbc->execute($infoP, array($this->addLC));
        if ($dbc->num_rows($infoR) == 0) {
            $json['error'] = 1;
            $json['msg'] = 'Like code #' . $this->addLC . ' not found';
        } else {
            $tag = FormLib::get('tag') !== '' ? true : false;
            $infoW = $dbc->fetch_row($infoR);
            $json['content'] = $this->addItemPriceInput('LC' . $this->addLC, $tag, $infoW['likeCodeDesc'], $infoW['normal_price']);
            $json['field'] = '#add-item-price';
        }

        echo json_encode($json);

        return false;
    }

    protected function post_id_upc_price_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $id = $this->id;
        $upc = $this->upc;
        $price = trim($this->price);
        $qty = 0;

        $json = array();
        $json['input'] = $this->addItemUPCInput();
        $json['added'] = 0;
        
        if ($price != "") {
            $model = new BatchListModel($dbc);
            $model->upc($upc);
            $model->batchID($id);
            $model->salePrice($price);
            $model->quantity($qty);
            $model->pricemethod(0);
            $model->save();

            if (FormLib::get_form_value('audited') == '1') {
                \COREPOS\Fannie\API\lib\AuditLib::batchNotification(
                    $id, 
                    $upc, 
                    \COREPOS\Fannie\API\lib\AuditLib::BATCH_ADD);
            }
            $json['added'] = 1;
            $json['display'] = $this->showBatchDisplay($id);
        }
        
        echo json_encode($json);

        return false;
    }

    protected function post_id_autotag_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $bid = $this->id;
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
        $tag_count = 0;
        while ($selW = $dbc->fetch_row($selR)) {
            if ($upc != $selW['upc']){
                $dbc->exec_statement($insP,array(
                    $selW['upc'], $selW['description'],
                    $selW['salePrice'], $selW['brand'],
                    $selW['sku'], $selW['size'],
                    $selW['units'], $selW['vendor'],
                    $selW['batchID']
                ));
                $tag_count++;
            }
            $upc = $selW['upc'];
        }

        $json = array('tags' => $tag_count);
        echo json_encode($json);

        return false;
    }

    protected function post_id_force_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $model = new BatchesModel($dbc);
        $model->forceStartBatch($this->id);
        $json = array('error'=>0, 'msg'=>'Batch #' . $this->id . ' has been applied');
        echo json_encode($json);

        return false;
    }

    protected function post_id_unsale_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $model = new BatchesModel($dbc);
        $model->forceStopBatch($this->id);

        $json = array('error'=>0, 'msg'=> 'Batch items taken off sale');
        echo json_encode($json);

        return false;
    }

    protected function post_id_limit_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $batches = new BatchesModel($dbc);
        $batches->batchID($this->id);
        $batches->transLimit($this->limit);
        $batches->save();

        return false;
    }

    protected function post_id_upc_uid_cut_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $delP = $dbc->prepare('
            DELETE
            FROM batchCutPaste
            WHERE upc=?
                AND batchID=?
                AND uid=?
        ');
        $args = array($this->upc, $this->id, $this->uid);
        $dbc->execute($delP, $args);

        if ($this->cut) {
            $insP = $dbc->prepare('
                INSERT INTO batchCutPaste
                    (upc, batchID, uid, tdate)
                VALUES
                    (?, ?, ?, ' . $dbc->now() . ')
            ');
            $dbc->execute($insP, $args);
        }

        return false;
    }

    protected function post_id_upc_price_qty_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $json = array('error'=>0, 'msg'=>'');

        if (!is_numeric($this->qty) || $this->qty < 1 || $this->qty != round($this->qty)) {
            $json['error'] = 1;
            $json['msg'] = 'Invalid quantity "' . $this->qty . '"; using quantity one';
            $this->qty = 1;
        }

        $pm = ($this->qty >= 2)?2:0;    

        $model = new BatchListModel($dbc);
        $model->upc($this->upc);
        $model->batchID($this->id);
        $model->salePrice($this->price);
        $model->quantity($this->qty);
        // quantity functions as a per-transaction limit
        // when pricemethod=0
        if ($this->qty <= 1) {
            $this->qty = 1;
            $model->quantity(0);
        }
        $model->pricemethod($pm);    
        $model->save();

        $json['price'] = sprintf('%.2f', $this->price);
        $json['qty'] = (int)$this->qty;

        $upQ = $dbc->prepare_statement("update batchBarcodes set normal_price=? where upc=? and batchID=?");
        $upR = $dbc->exec_statement($upQ,array($this->price,$this->upc,$this->id));

        if (FormLib::get_form_value('audited') == '1') {
            \COREPOS\Fannie\API\lib\AuditLib::batchNotification(
                $this->id, 
                $this->upc, 
                \COREPOS\Fannie\API\lib\AuditLib::BATCH_EDIT, 
                (substr($this->upc,0,2)=='LC' ? true : false));
        }

        echo json_encode($json);

        return false;
    }

    protected function delete_id_upc_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $id = $this->id;
        $upc = $this->upc;

        $json = array('error'=>0, 'msg'=>'Item ' . $upc . ' removed from batch');
        
        if (substr($upc,0,2) != 'LC') {
            // take the item off sale if this batch is currently on sale
            $product = new ProductsModel($dbc);
            $product->upc($upc);
            $product->discounttype(0);
            $product->special_price(0);
            $product->start_date(0);
            $product->end_date(0);
            $unsaleR = $product->save();

            if ($unsaleR === false) {
                $json['error'] = 1;
                $json['msg'] = 'Error taking item ' . $upc . ' off sale';
            }
            
            updateProductAllLanes($upc);
        } else {
            $lc = substr($upc,2);
            $upcLike = new UpcLikeModel($dbc);
            $upcLike->likeCode($lc);
            $unsaleR = true;
            foreach ($upcLike->find() as $u) {
                $product = new ProductsModel($dbc);
                $product->upc($u->upc());
                $product->discounttype(0);
                $product->special_price(0);
                $product->start_date(0);
                $product->end_date(0);
                $unsaleR = $product->save();
            }

            if ($unsaleR === false) {
                $json['error'] = 1;
                $json['msg'] = 'Error taking like code ' . $lc . ' off sale';
            }
        }

        $delQ = $dbc->prepare_statement("delete from batchList where batchID=? and upc=?");
        $delR = $dbc->exec_statement($delQ,array($id,$upc));
        if ($delR === false) {
            if ($json['error']) {
                $json['msg'] .= '<br />Error deleting item ' . $upc . ' from batch';
            } else {
                $json['error'] = 1;
                $json['msg'] = 'Error deleting item ' . $upc . ' from batch';
            }
        }
        
        $delQ = $dbc->prepare_statement("delete from batchBarcodes where upc=? and batchID=?");
        $delR = $dbc->exec_statement($delQ,array($upc,$id));

        if (FormLib::get_form_value('audited') == '1') {
            \COREPOS\Fannie\API\lib\AuditLib::batchNotification(
                $id, 
                $upc, 
                \COREPOS\Fannie\API\lib\AuditLib::BATCH_DELETE, 
                (substr($upc,0,2)=='LC' ? true : false));
        }
        
        echo json_encode($json);

        return false;
    }

    protected function post_id_upc_swap_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $q = $dbc->prepare_statement("UPDATE batchList SET salePrice = -1*salePrice
            WHERE batchID=? AND upc=?");
        $r = $dbc->exec_statement($q,array($this->id,$this->upc));

        $json = array('error' => 0);
        if ($r === false) {
            $json['error'] = 'Error moving item';
        }
        echo json_encode($json);

        return false;
    }

    protected function post_id_qualifiers_discount_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $batch = new BatchesModel($dbc);
        $batch->batchID($this->id);
        if (FormLib::get('member') == '1') {
            $batch->discounttype(2);
        } else {
            $batch->discounttype(1);
        }
        $save1 = $batch->save();

        $pmethod = 4;
        if (FormLib::get('split') == '1') {
            $pmethod = 3;
        }

        $upQ2 = $dbc->prepare_statement("UPDATE batchList SET
                quantity=?,pricemethod=?,
                salePrice=? WHERE batchID=?
                AND salePrice >= 0");
        $upQ3 = $dbc->prepare_statement("UPDATE batchList SET
                quantity=?,pricemethod=?,
                    salePrice=? WHERE batchID=?
                    AND salePrice < 0");
        $save2 = $dbc->exec_statement($upQ2, array($this->qualifiers+1,$pmethod,$this->discount,$this->id));
        $save3 = $dbc->exec_statement($upQ3,array($this->qualifiers+1,$pmethod,-1*$this->discount,$this->id));

        $json['error'] = 0;
        if (!$save1 || !$save2 || !$save3) {
            $json['error'] = 'Error saving paired sale settings';
        }
        echo json_encode($json);

        return false;
    }

    private function addItemUPCInput($newtags=false)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $ret = "<form class=\"form-inline\" onsubmit=\"advanceToPrice(); return false;\" id=\"add-item-form\">";

        $ret .= '<span class="add-by-upc-fields">';
        $ret .= "<label class=\"control-label\">UPC</label>
            <input type=text maxlength=13 name=\"addUPC\" id=addItemUPC 
            class=\"form-control\" /> ";
        $ret .= '</span>';

        $ret .= '<span class="add-by-lc-fields collapse">';
        $ret .= "<label class=\"control-label\">Like code</label><input type=text id=addItemLC 
            name=\"addLC\" size=4 value=1 class=\"form-control\" disabled /> ";
        $ret .= "<select id=lcselect onchange=\"\$('#addItemLC').val(this.value);\" class=\"form-control\" disabled>";
        $lcQ = $dbc->prepare_statement("select likecode,likecodeDesc from likeCodes order by likecode");
        $lcR = $dbc->exec_statement($lcQ);
        while ($lcW = $dbc->fetch_array($lcR)) {
            $ret .= "<option value=$lcW[0]>$lcW[0] $lcW[1]</option>";
        }
        $ret .= "</select>";
        $ret .= '</span>';

        $ret .= "<button type=submit value=Add class=\"btn btn-default\">Add</button>";
        /* No shelf tag creation
        $ret .= "<input type=checkbox id=addItemTag name=\"tag\" ";
         if ($newtags) {
            $ret .= " checked";
        }
        $ret .= " /> <label for=\"addItemTag\">New shelf tag</label>";
        */
        $ret .= " <input type=checkbox id=addItemLikeCode onchange=\"toggleUpcLcInput();\" /> 
            <label for=\"addItemLikeCode\" class=\"control-label\">Likecode</label>";
        $ret .= "</form>";
        
        return $ret;
    }

    private function addItemPriceInput($upc, $newtags=false, $description, $price)
    {
        $ret = "<form onsubmit=\"addItemPrice('$upc'); return false;\" id=\"add-price-form\" class=\"form-inline\">";
        $ret .= "<label>ID</label>: $upc <label>Description</label>: $description <label>Normal price</label>: $price ";
        $ret .= "<label>Sale price</label><input class=\"form-control\" type=text id=add-item-price name=price size=5 /> ";
        $ret .= "<button type=submit value=Add class=\"btn btn-default\">Add</button>";
        /* No shelf tag creation
        $ret .= "<label><input type=checkbox id=addItemTag name=\"tag\" ";
        if ($newtags) {
            $ret .= " checked";
        }
        $ret .= " /> New shelf tag</label>";
        */
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

    function showBatchDisplay($id, $order='natural')
    {
        global $FANNIE_SERVER_DBMS,$FANNIE_URL;
        $dbc = $this->con;
        $uid = getUID($this->current_user);
        $uid = ltrim($uid,'0');
    
        $orderby = '';
        switch($order) {
            case 'upc_a':
                $orderby = 'ORDER BY b.upc ASC';
                break;
            case 'upc_d':
                $orderby = 'ORDER BY b.upc DESC';
                break;
            case 'desc_a':
                $orderby = 'ORDER BY description ASC';
                break;
            case 'desc_d':
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
            case 'natural':
            default:
                $orderby = 'ORDER BY b.listID DESC';
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

        $limit = $model->transLimit();
        $hasLimit = $limit > 0 ? true : false;

        $saleHeader = "Sale Price";
        if ($dtype == 3) {
            $saleHeader = "$ Discount";
        } elseif ($dtype == 4) {
            $saleHeader = "% Discount";
        } elseif ($dtype == 0) {
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
        if ($dbc->dbms_name() == "mssql") {
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
        $ret .= "<a href=\"BatchListPage.php\">Back to batch list</a> | ";
        $ret .= sprintf('<input type="hidden" id="batch-discount-type" value="%d" />', $model->discounttype());
        /**
          Price change batches probably want the upcoming retail
          rather than the current retail. Current sales will want
          the current sale price; future sales will want the future
          sale price. Past sales probably won't print signs under
          normal circumstances.
        */
        $future_mode = false;
        if ($model->discounttype() == 0) {
            $future_mode = true;
        } elseif (strtotime($model->startDate()) >= strtotime(mktime(0,0,0,date('n'),date('j'),date('Y')))) {
            $future_mode = true;
        }
        $ret .= sprintf('<input type="hidden" id="batch-future-mode" value="%d" />', $future_mode ? 1 : 0);
        $ret .= "<a href=\"\" onclick=\"printSigns();return false;\">Print Sale Signs</a> | ";
        $ret .= "<a href=\"{$FANNIE_URL}admin/labels/BatchShelfTags.php?batchID%5B%5D=$id\">Print Shelf Tags</a> | ";
        $ret .= "<a href=\"\" onclick=\"generateTags($id); return false;\">Auto-tag</a> | ";
        if ($cp > 0) {
            $ret .= "<a href=\"EditBatchPage.php?id=$id&paste=1\">Paste Items ($cp)</a> | ";
        }
        $ret .= "<a href=\"\" onclick=\"forceNow($id); return false;\">Force batch</a> | ";
        if ($dtype != 0) {
            $ret .= "<a href=\"\" onclick=\"unsaleNow($id); return false;\">Stop Sale</a> | ";
        }
        $ret .= "<span id=\"edit-limit-link\"><a href=\"\" 
            onclick=\"editTransLimit(); return false;\">" . ($hasLimit ? 'Edit' : 'Add' ) . " Limit</a></span>";
        $ret .= "<span id=\"save-limit-link\" class=\"collapse\"><a href=\"\" onclick=\"saveTransLimit($id); return false;\">Save Limit</a></span>";
        $ret .= " <span class=\"form-group form-inline\" id=\"currentLimit\" style=\"color:#000;\">{$limit}</span>";
        $ret .= "<br />";
        $ret .= "<table id=yeoldetable class=\"table\">";
        $ret .= "<tr>";
        if ($orderby != "ORDER BY b.upc ASC") {
            $ret .= "<th><a href=\"EditBatchPage.php?id=$id&sort=upc_a\">UPC</a></th>";
        } else {
            $ret .= "<th><a href=\"EditBatchPage.php?id=$id&sort=upc_d\">UPC</a></th>";
        }
        if ($orderby != "ORDER BY description ASC") {
            $ret .= "<th><a href=\"EditBatchPage.php?id=$id&sort=desc_a\">Description</a></th>";
        } else {
            $ret .= "<th><a href=\"EditBatchPage.php?id=$id&sort=desc_d\">Description</a></th>";
        }
        if ($orderby != "ORDER BY p.normal_price DESC") {
            $ret .= "<th><a href=\"EditBatchPage.php?id=$id&sort=price_d\">Normal Price</a></th>";
        } else {
            $ret .= "<th><a href=\"EditBatchPage.php?id=$id&sort=price_a\">Normal Price</a></th>";
        }
        if ($orderby != "ORDER BY b.salePrice DESC") {
            $ret .= "<th><a href=\"EditBatchPage.php?id=$id&sort=sale_d\">$saleHeader</a></th>";
        } else {
            $ret .= "<th><a href=\"EditBatchPage.php?id=$id&sort=sale_a\">$saleHeader</a></th>";
        }
        $ret .= "<th colspan=\"3\">&nbsp;</th>";
        if ($orderby != 'ORDER BY m.super_name,y.subsection,y.shelf_set,y.shelf') {
            $ret .= "<th><a href=\"EditBatchPage.php?id=$id&sort=loc_a\">Location</a></th>";
        } else {
            $ret .= "<th><a href=\"EditBatchPage.php?id=$id&sort=loc_d\">Location</a></th>";
        }
        $ret .= "</tr>";

        $likeP = $dbc->prepare("
            SELECT p.upc,
                p.description,
                p.normal_price
            FROM products AS p 
                INNER JOIN upcLike AS u ON p.upc=u.upc
            WHERE u.likeCode = ? 
            ORDER BY p.upc DESC");
        
        $colors = array('#ffffff','#ffffcc');
        $c = 0;
        while ($fetchW = $dbc->fetch_array($fetchR)) {
            $c = ($c + 1) % 2;
            $ret .= "<tr>";
            $fetchW['upc'] = rtrim($fetchW['upc']);
            if (substr($fetchW['upc'],0,2) == "LC") {
                $likecode = rtrim(substr($fetchW['upc'],2));
                $ret .= "<td bgcolor=$colors[$c]>" . $fetchW['upc'];
                $ret .= " <a href=\"\" onclick=\"\$('.lc-item-{$likecode}').toggle(); return false;\">[+]</a>";
                $ret .= "</td>";
            } else {
                $ret .= "<td bgcolor=$colors[$c]><a href=\"{$FANNIE_URL}item/ItemEditorPage.php?searchupc={$fetchW['upc']}\" 
                    target=\"_new{$fetchW['upc']}\">{$fetchW['upc']}</a></td>";
            }
            $ret .= "<td bgcolor=$colors[$c]>{$fetchW['description']}</td>";
            $ret .= "<td bgcolor=$colors[$c]>{$fetchW['normal_price']}</td>";
            $qtystr = ($fetchW['pricemethod']>0 && is_numeric($fetchW['quantity']) && $fetchW['quantity'] > 0) ? $fetchW['quantity'] . " for " : "";
            $qty = is_numeric($fetchW['quantity']) && $fetchW['quantity'] > 0 ? $fetchW['quantity'] : 1;
            $ret .= "<td bgcolor=$colors[$c] class=\"\">";
            $ret .= '<span id="editable-text-' . $fetchW['upc'] . '">';
            $ret .= '<span class="editable-' . $fetchW['upc'] . ($qty == 1 ? ' collapse ' : '') . '"'
                    . ' id="item-qty-' . $fetchW['upc'] . '" data-name="qty">'
                    . $qty . ' for </span>';
            $ret .= "<span class=\"editable-{$fetchW['upc']}\" 
                    id=\"sale-price-{$fetchW['upc']}\" data-name=\"price\">"
                    . sprintf("%.2f</span>",$fetchW['salePrice']);
            $ret .= '</span>';
            $ret .= '<div class="form-group form-inline collapse" id="editable-fields-' . $fetchW['upc'] . '">';
            $ret .= '<div class="input-group">';
            $ret .= sprintf('<input type="text" class="form-control" name="qty" value="%d" />', $qty);
            $ret .= '<span class="input-group-addon">for</span>';
            $ret .= '<span class="input-group-addon">$</span>';
            $ret .= sprintf('<input text="text" class="form-control" name="price" value="%.2f" />', $fetchW['salePrice']);
            $ret .= '</div></div></td>';
            $ret .= "<td bgcolor=$colors[$c] id=editLink{$fetchW['upc']}>
                <a href=\"\" class=\"edit\" onclick=\"editUpcPrice('{$fetchW['upc']}'); return false;\">
                    " . \COREPOS\Fannie\API\lib\FannieUI::editIcon() . "</a>
                <a href=\"\" class=\"save collapse\" onclick=\"saveUpcPrice('{$fetchW['upc']}'); return false;\">
                    " . \COREPOS\Fannie\API\lib\FannieUI::saveIcon() . "</a>
                </td>";
            $ret .= "<td bgcolor=$colors[$c]><a href=\"\" 
                onclick=\"deleteUPC.call(this, $id, '{$fetchW['upc']}'); return false;\">"
                . \COREPOS\Fannie\API\lib\FannieUI::deleteIcon() . "</a>
                </td>";
            if ($fetchW['isCut'] == 1) {
                $ret .= "<td bgcolor=$colors[$c] id=cpLink{$fetchW['upc']}>
                    <a href=\"\" id=\"unCut{$fetchW['upc']}\" onclick=\"cutItem('{$fetchW['upc']}',$id,$uid, 0); return false;\">Undo</a>
                    <a href=\"\" class=\"collapse\" id=\"doCut{$fetchW['upc']}\" onclick=\"cutItem('{$fetchW['upc']}',$id,$uid, 1); return false;\">Cut</a>
                    </td>";
            } else {
                $ret .= "<td bgcolor=$colors[$c] id=cpLink{$fetchW['upc']}>
                    <a href=\"\" class=\"collapse\" id=\"unCut{$fetchW['upc']}\" onclick=\"cutItem('{$fetchW['upc']}',$id,$uid,0); return false;\">Undo</a>
                    <a href=\"\" id=\"doCut{$fetchW['upc']}\" onclick=\"cutItem('{$fetchW['upc']}',$id,$uid,1); return false;\">Cut</a>
                    </td>";
            }

            $loc = 'n/a';
            if (!empty($fetchW['subsection'])) {
                $loc = substr($fetchW['super_name'],0,4);
                $loc .= $fetchW['subsection'].', ';
                $loc .= 'Unit '.$fetchW['shelf_set'].', ';
                $loc .= 'Shelf '.$fetchW['shelf'];
            }
            $ret .= "<td bgcolor=$colors[$c]>".$loc.'</td>';
            $ret .= '<input type="hidden" class="batch-hidden-upc" value="' . $fetchW['upc'] . '" />';

            $ret .= "</tr>";
            if (substr($fetchW['upc'], 0, 2) == "LC") {
                $likecode = rtrim(substr($fetchW['upc'],2));
                $likeR = $dbc->execute($likeP, array($likecode));
                while ($likeW = $dbc->fetch_row($likeR)) {
                    $ret .= '<tr class="collapse lc-item-' . $likecode . '">';
                    $ret .= "<td><a href=\"{$FANNIE_URL}item/ItemEditorPage.php?searchupc={$likeW['upc']}\" 
                        target=_new{$likeW['upc']}>{$likeW['upc']}</a></td>";
                    $ret .= "<td>{$likeW['description']}</td>";
                    $ret .= "<td>{$likeW['normal_price']}</td>";
                    $ret .= "<td>{$fetchW['salePrice']}</td>";
                    $ret .= "<td>&nbsp;</td>";
                    $ret .= "<td>&nbsp;</td>";
                    $ret .= '</tr>';
                }
            }
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
        $ret .= "<a href=\"BatchListPage.php\">Back to batch list</a> | ";
        $ret .= "<a href=\"\" onclick=\"forceNow($id); return false;\">Force batch</a>";
        $ret .= " | No limit";
        $ret .= " <span id=\"currentLimit\" style=\"color:#000;\"></span>";

        $q = $dbc->prepare_statement("SELECT b.discounttype,salePrice,
            CASE WHEN l.pricemethod IS NULL THEN 4 ELSE l.pricemethod END as pricemethod,
            CASE WHEN l.quantity IS NULL THEN 1 ELSE l.quantity END as quantity
            FROM batches AS b LEFT JOIN batchList AS l 
            ON b.batchID=l.batchID WHERE b.batchID=? ORDER BY l.pricemethod");
        $r = $dbc->exec_statement($q,array($id));
        $w = $dbc->fetch_row($r);

        if (!empty($w['salePrice'])){
            $ret .= "<div class=\"well\">Add all items before fiddling with these settings
                or they'll tend to go haywire</div>";
            $ret .= '<div id="paired-fields">
                <div class="form-group form-inline">';
            $ret .= '<label>Member only sale
                <input type="checkbox" name="member" value="1" '
                .($w['discounttype']==2?'checked':'').' />
                </label>';    
            $ret .= ' | ';
            $ret .= '<label>Split discount
                <input type="checkbox" name="split" value="1" '
                .($w['pricemethod']==4?'':'checked').' />
                </label>';
            $ret .= '</div>';
            $ret .= '<div class="form-group form-inline">';
            $ret .= '<label>Qualifiers Required</label> ';
            $ret .= sprintf('<input type="number" class="form-control" value="%d"
                    name="qualifiers" />',
                    $w['quantity']-1);
            $ret .= ' <label>Discount</label> ';
            $ret .= sprintf('<div class="input-group">
                    <span class="input-group-addon">$</span>
                    <input type="text" class="form-control" value="%.2f"
                    name="discount" /></div>',
                    (empty($w['salePrice'])?'':abs($w['salePrice'])));
            $ret .= sprintf(' <button type="submit" class="btn btn-default"
                    onclick="savePairedPricing(%d); return false;">Update Pricing</button>',$id);
            $ret .= '</div>';
            $ret .= '</div>'; // end #paired-fields
        } else {
            $ret .= "<div class=\"alert alert-warning\">Add items first</div>";
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

        $likeP = $dbc->prepare("
            SELECT p.upc,
                p.description,
                p.normal_price
            FROM products AS p 
                INNER JOIN upcLike AS u ON p.upc=u.upc
            WHERE u.likeCode = ? 
            ORDER BY p.upc DESC");
        
        $colors = array('#ffffff','#ffffcc');
        $c = 0;
        $ret .= '<table class="table" id="qualifier-table">';
        $ret .= '<tr><th colspan="4">Qualifying Item(s)</th></tr>';
        while ($fetchW = $dbc->fetch_array($fetchR)) {
            $c = ($c + 1) % 2;
            $ret .= "<tr>";
            $fetchW[0] = rtrim($fetchW[0]);
            if (substr($fetchW[0],0,2) == "LC") {
                $likecode = rtrim(substr($fetchW[0],2));
                $ret .= "<td bgcolor=$colors[$c]>" . $fetchW['upc'];
                $ret .= " <a href=\"\" onclick=\"\$('.lc-item-{$likecode}').toggle(); return false;\">[+]</a>";
                $ret .= "</td>";
            } else {
                $ret .= "<td bgcolor=$colors[$c]><a href={$FANNIE_URL}item/ItemEditorPage.php?searchupc=$fetchW[0] target=_new$fetchW[0]>$fetchW[0]</a></td>";
            }
            $ret .= "<td bgcolor=$colors[$c]>$fetchW[1]</td>";
            $ret .= "<td bgcolor=$colors[$c]>
                <a href=\"\" class=\"down-arrow\" onclick=\"swapQualifierToDiscount(this, '$fetchW[0]'); return false;\">
                    <img src=\"{$FANNIE_URL}src/img/buttons/arrow_down.gif\" alt=\"Make Discount Item\" /></a>
                <a href=\"\" class=\"up-arrow collapse\" onclick=\"swapDiscountToQualifier(this, '$fetchW[0]'); return false;\">
                    <img src=\"{$FANNIE_URL}src/img/buttons/arrow_up.gif\" alt=\"Make Qualifying Item\" />
                    </a>
                </td>";
            $ret .= "<td bgcolor=$colors[$c]><a href=\"\" onclick=\"deleteUPC.call(this, $id, '$fetchW[0]'); return false;\">"
                . \COREPOS\Fannie\API\lib\FannieUI::deleteIcon() . '</a></td>';
            $ret .= "</tr>";

            if (substr($fetchW['upc'], 0, 2) == "LC") {
                $likecode = rtrim(substr($fetchW['upc'],2));
                $likeR = $dbc->execute($likeP, array($likecode));
                while ($likeW = $dbc->fetch_row($likeR)) {
                    $ret .= '<tr class="collapse lc-item-' . $likecode . '">';
                    $ret .= "<td><a href=\"{$FANNIE_URL}item/ItemEditorPage.php?searchupc={$likeW['upc']}\" 
                        target=_new{$likeW['upc']}>{$likeW['upc']}</a></td>";
                    $ret .= "<td>{$likeW['description']}</td>";
                    $ret .= "<td>{$likeW['normal_price']}</td>";
                    $ret .= "<td>{$fetchW['salePrice']}</td>";
                    $ret .= "<td>&nbsp;</td>";
                    $ret .= "<td>&nbsp;</td>";
                    $ret .= '</tr>';
                }
            }
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
        $ret .= '<table class="table" id="discount-table">';
        $ret .= '<tr><th colspan="4">Discount Item(s)</th></tr>';
        while($fetchW = $dbc->fetch_array($fetchR)){
            $c = ($c + 1) % 2;
            $ret .= "<tr>";
            $fetchW[0] = rtrim($fetchW[0]);
            if (substr($fetchW[0],0,2) == "LC") {
                $likecode = rtrim(substr($fetchW[0],2));
                $ret .= "<td bgcolor=$colors[$c]>" . $fetchW['upc'];
                $ret .= " <a href=\"\" onclick=\"\$('.lc-item-{$likecode}').toggle(); return false;\">[+]</a>";
                $ret .= "</td>";
            } else {
                $ret .= "<td bgcolor=$colors[$c]><a href={$FANNIE_URL}item/ItemEditorPage.php?searchupc=$fetchW[0] target=_new$fetchW[0]>$fetchW[0]</a></td>";
            }
            $ret .= "<td bgcolor=$colors[$c]>$fetchW[1]</td>";
            $ret .= "<td bgcolor=$colors[$c]>
                <a href=\"\" class=\"down-arrow collapse\" onclick=\"swapQualifierToDiscount(this, '$fetchW[0]'); return false;\">
                    <img src=\"{$FANNIE_URL}src/img/buttons/arrow_down.gif\" alt=\"Make Discount Item\" /></a>
                <a href=\"\" class=\"up-arrow\" onclick=\"swapDiscountToQualifier(this, '$fetchW[0]'); return false;\">
                    <img src=\"{$FANNIE_URL}src/img/buttons/arrow_up.gif\" alt=\"Make Qualifying Item\" />
                    </a>
                </td>";
            $ret .= "<td bgcolor=$colors[$c]><a href=\"\" onclick=\"deleteUPC.call(this, $id, '$fetchW[0]'); return false;\">"
                . \COREPOS\Fannie\API\lib\FannieUI::deleteIcon() . '</a></td>';
            $ret .= "</tr>";

            if (substr($fetchW['upc'], 0, 2) == "LC") {
                $likecode = rtrim(substr($fetchW['upc'],2));
                $likeR = $dbc->execute($likeP, array($likecode));
                while ($likeW = $dbc->fetch_row($likeR)) {
                    $ret .= '<tr class="collapse lc-item-' . $likecode . '">';
                    $ret .= "<td><a href=\"{$FANNIE_URL}item/ItemEditorPage.php?searchupc={$likeW['upc']}\" 
                        target=_new{$likeW['upc']}>{$likeW['upc']}</a></td>";
                    $ret .= "<td>{$likeW['description']}</td>";
                    $ret .= "<td>{$likeW['normal_price']}</td>";
                    $ret .= "<td>{$fetchW['salePrice']}</td>";
                    $ret .= "<td>&nbsp;</td>";
                    $ret .= "<td>&nbsp;</td>";
                    $ret .= '</tr>';
                }
            }

        }
        $ret .= "</table>";

        return $ret;
    }

    public function get_id_paste_view()
    {
        return $this->get_id_view();
    }

    public function get_id_view()
    {
        global $FANNIE_URL;
        $this->add_script('edit.js');
        $this->add_css_file('index.css');
        $sort = FormLib::get('sort', 'natural');
        ob_start();
        ?>
        <div id="inputarea">
        <?php echo $this->addItemUPCInput(); ?>
        </div>
        <div class="progress collapse" id="progress-bar">
            <div class="progress-bar progress-bar-striped active" 
                role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" 
                style="width: 100%" title="Working">
                <span class="sr-only">Working</span>
            </div>
        </div>
        <div id="displayarea">
        <?php echo $this->showBatchDisplay($this->id, $sort); ?>
        </div>
        <input type=hidden id=uid value="<?php echo $this->current_user; ?>" />
        <input type=hidden id=isAudited value="<?php echo $this->audited; ?>" />
        <input type="hidden" id="batchID" value="<?php echo $this->id; ?>" />
        <?php
        $ret = ob_get_clean();
    
        $ret .= "<input type=hidden id=buttonimgpath value=\"{$FANNIE_URL}src/img/buttons/\" />";
        $this->add_onload_command('$(\'#addItemUPC\').focus()');

        return $ret;
    }

    public function helpContent()
    {
        return '<p>Add one or more items to the batch. Enter a UPC by default or check
            the likecode option to add items by likecode. Next enter a price. Note that if
            you enter an incorrect UPC (or likecode) you can simply press enter with the price
            field blank to skip that item and enter another UPC (or likecode)</p>
            <p><em>Force Batch</em> will apply the batch prices immediately and push those
            changes to the lanes. Forcing a batch will ignore start and end dates.</p>
            <p><em>Stop Sale</em> will take items off sale immediately. However, 
            depending on start and end dates the batch may be reapplied on the next automated
            batch update. Change the dates or delete the batch after stopping the sale
            if needed.</p>
            <p><em>Auto-tag</em> creates shelf tags for the batch using the batch price
            and vendor catalog data. This is primarily used with price change batches.
            <em>Print shelf tags</em> of course shows the actual tags.</p>
            <p><em>Add Limit</em> creates a per-transaction limit on each item in the
            batch. Setting a limit of one for example means the sale price only applies
            once per transaction. Additional identical items ring up at regular price. This limit
            applies to each item individually rather than all items in the batch 
            collectively. These limits cannot be used for volume sale price (i.e., 2-for-$1).</p>
            <p><em>Cut</em> and <em>Paste</em> can move items items from one batch to
            another. This feature requires user authentication so that each user has their
            own clipboard and don\'t interfere with each oter.</p>
            ';
    }
}

FannieDispatch::conditionalExec(false);


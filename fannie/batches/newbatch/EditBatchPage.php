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

use COREPOS\Fannie\API\lib\PriceLib;

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}
if (!function_exists('checkLogin')) {
    include_once(__DIR__ . '/../../auth/login.php');
}

class EditBatchPage extends FannieRESTfulPage
{
    protected $must_authenticate = true;
    protected $auth_classes = array('batches','batches_audited');
    protected $title = 'Sales Batches Tool';
    protected $header = 'Sales Batches Tool';
    protected $enable_linea = true;
    protected $debug_routing = false;

    public $description = '[Sales Batches] is the primary tool for creating, editing, and managing
    sale and price change batches.';

    private $audited = 1;
    private $con = null;

    public function preprocess()
    {
        // maintain user logins longer
        refreshSession();
        if (validateUserQuiet('batches')) {
            $this->audited = 0;
        }

        // autoclear old data from clipboard table on intial page load
        $clipboard = $this->connection->tableDefinition('batchCutPaste');
        if (isset($clipboard['tdate'])) {
            $this->connection->query('DELETE FROM batchCutPaste WHERE tdate < ' . $this->connection->curdate());
        }

        $this->addRoute(
            'get<id><paste>',
            'post<id><addUPC>',
            'post<id><addLC>',
            'post<id><upc><price>',
            'post<id><autotag>',
            'post<id><force>',
            'post<id><unsale>',
            'post<id><limit>',
            'post<id><upc><uid><cut>',
            'post<id><upc><price><qty>',
            'delete<id><upc>',
            'post<id><upc><swap>',
            'post<id><qualifiers><discount>',
            'post<id><trim>',
            'post<id><storeID>',
            'post<noteID><batchNotes>',
            'post<partialID>',
            'post<editBatch>',
            'post<editDate>'
        );

        return parent::preprocess();
    }

    protected function post_editDate_handler()
    {
        $id = FormLib::get('id');
        $start = FormLib::get('startDate');
        $end = FormLib::get('endDate');
        $action = FormLib::get('action');
        $model = new BatchesModel($this->connection);
        $model->batchID($id);
        switch ($action) {
            case 'start':
                $model->startDate($start);
                break;
            case 'end':
                $model->endDate($end);
                break;
        }
        $model->save();
        echo 'Saved';

        $this->runCallbacks($id);

        return false;
    }


    protected function post_editBatch_handler()
    {
        $id = FormLib::get('id');
        $name = FormLib::get('name');
        $model = new BatchesModel($this->connection);
        $model->batchID($id);
        $model->batchName($name);
        $model->save();
        echo 'Saved';

        $this->runCallbacks($id);

        return false;
    }

    protected function post_noteID_batchNotes_handler()
    {
        $model = new BatchesModel($this->connection);
        $model->batchID($this->noteID);
        $model->notes($this->batchNotes);
        $model->save();
        echo 'Saved';

        return false;
    }

    protected function post_partialID_handler()
    {
        $partial = new PartialBatchesModel($this->connection);
        $partial->batchID($this->partialID);
        foreach ($partial->find() as $obj) {
            $partial = $obj;
            break;
        }
        $partial->startTime(FormLib::get('pStart', null));
        $partial->endTime(FormLib::get('pEnd', null));
        $partial->overwriteSales(FormLib::get('pOver', 0));
        $partial->repetition(FormLib::get('pRepeat'));
        $partial->save();
        echo 'Saved';

        return false;
    }

    protected function get_id_paste_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $uid = getUID($this->current_user);
        $uid = ltrim($uid,'0');
        $bu = new BatchUpdateModel($dbc);

        $prep = $dbc->prepare("
            SELECT listID,b.upc
            FROM batchList AS l
                INNER JOIN batchCutPaste as b ON b.upc=l.upc AND b.batchID=l.batchID
            WHERE b.uid=?"
        );
        $res = $dbc->execute($prep,array($uid));
        $upP = $dbc->prepare('UPDATE batchList SET batchID=? WHERE listID=?');
        $count = 0;
        while ($row = $dbc->fetchRow($res)) {
            $dbc->execute($upP,array($this->id,$row['listID']));
            $count++;
            $bu->reset();
            $bu->batchID($this->id);
            $bu->upc($row['upc']);
            $bu->logUpdate($bu::UPDATE_ADDED);
        }
        $delP = $dbc->prepare("DELETE FROM batchCutPaste WHERE uid=?");
        $dbc->execute($delP,$uid);

        $this->addOnloadCommand("showBootstrapAlert('#inputarea', 'success', 'Pasted $count items');\n");

        $this->runCallbacks($this->id);

        return true;
    }

    private function checkAllOverlap($id)
    {
        $dbc = $this->connection;
        $batch = new BatchesModel($dbc);
        $batch->batchID($id);
        $batch->load();
        if ($batch->discountType() <= 0) {
            return false;
        }
        $batchP = $dbc->prepare("SELECT batchID FROM batches
            WHERE batchID <> ?
                AND discounttype > 0
                AND endDate >= ?");
        $batchIDs = $dbc->getAllValues($batchP, array($id, date('Y-m-d')));
        list($inStr, $args) = $dbc->safeInClause($batchIDs);
        $overlapP = $dbc->prepare('
            SELECT b.batchName,
                b.startDate,
                b.endDate,
                b.batchID,
                l.upc
            FROM batchList AS l
                INNER JOIN batches AS b ON l.batchID=b.batchID
            WHERE l.batchID IN (' . $inStr . ')
                AND b.endDate >= ?
                AND b.startDate <= ?
                AND b.discounttype > 0
                AND b.endDate >= ' . $dbc->curdate()
        );
        $stamp = strtotime($batch->startDate());
        if ($stamp === false) {
            return false;
        }
        $args[] = $stamp ? date('Y-m-d', $stamp) : '1900-01-01';
        $stamp = strtotime($batch->endDate());
        $args[] = $stamp ? date('Y-m-d', $stamp) : '1900-01-01';
        $overlapR = $dbc->execute($overlapP, $args);
        $ret = array();
        while ($row = $dbc->fetchRow($overlapR)) {
            $ret[$row['upc']] = $row;
        }

        return $ret;
    }

    private function checkOverlap($id, $upc)
    {
        $dbc = $this->connection;
        $batch = new BatchesModel($dbc);
        $batch->batchID($id);
        $batch->load();
        if ($batch->discountType() <= 0) {
            return false;
        }
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
                AND b.discounttype > 0
                AND b.endDate >= ' . $dbc->curdate()
        );
        $args = array(
            $id,
            $upc,
        );
        $stamp = strtotime($batch->startDate());
        if ($stamp === false) {
            return false;
        }
        $args[] = $stamp ? date('Y-m-d', $stamp) : '1900-01-01';
        $stamp = strtotime($batch->endDate());
        $args[] = $stamp ? date('Y-m-d', $stamp) : '1900-01-01';
        $overlapR = $dbc->execute($overlapP, $args);
        if ($dbc->numRows($overlapR) > 0) {
            return $dbc->fetchRow($overlapR);
        }

        return false;
    }

    protected function post_id_addUPC_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
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
        if ($upc === '') {
            echo $this->debugJSON($json);
            return false;
        }

        $overlap = $this->checkOverlap($this->id, $upc);
        if ($this->config->get('STORE_MODE') != 'HQ' && $overlap !== false) {
            $error = 'Item already in concurrent batch: '
                . '<a style="color:blue;" href="EditBatchPage.php?id=' . $overlap['batchID'] . '">'
                . $overlap['batchName'] . '</a> ('
                . date('Y-m-d', strtotime($overlap['startDate'])) . ' - '
                . date('Y-m-d', strtotime($overlap['endDate'])) . ')'
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
                $json['content'] = $this->addItemPriceInput($upc, $product->description(), $product->normal_price());
                $json['field'] = '#add-item-price';
            }
        }
        echo $this->debugJSON($json);

        return false;
    }

    protected function post_id_addLC_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

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
            echo $this->debugJSON($json);
            return false;
        }

        $infoP = $dbc->prepare('
            SELECT l.likeCodeDesc,
                p.normal_price
            FROM likeCodes AS l
                INNER JOIN upcLike AS u ON l.likeCode=u.likeCode
                ' . DTrans::joinProducts('u', 'p', 'INNER') . '
            WHERE l.likeCode=?
            ORDER BY p.normal_price
        ');
        $infoW = $dbc->getRow($infoP, array($this->addLC));
        if ($infoW === false) {
            $json['error'] = 1;
            $json['msg'] = 'Like code #' . $this->addLC . ' not found';
        } else {
            $json['content'] = $this->addItemPriceInput('LC' . $this->addLC, $infoW['likeCodeDesc'], $infoW['normal_price']);
            $json['field'] = '#add-item-price';
        }

        echo $this->debugJSON($json);

        return false;
    }

    protected function post_id_upc_price_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $price = trim($this->price);

        $json = array(
            'input' => $this->addItemUPCInput(),
            'added' => 0,
        );

        if ($price != '') {
            $model = new BatchListModel($dbc);
            $model->upc($this->upc);
            $model->batchID($this->id);
            $model->salePrice($price);
            $model->groupSalePrice($price);
            $model->quantity(0);
            $model->pricemethod(0);
            $saved = $model->save();

            if ($saved == true) {
                $bu = new BatchUpdateModel($dbc);
                $bu->batchID($this->id);
                $bu->upc($this->upc);
                $bu->logUpdate($bu::UPDATE_ADDED);
            }

            if (FormLib::get('audited') == '1') {
                \COREPOS\Fannie\API\lib\AuditLib::batchNotification(
                    $this->id,
                    $this->upc,
                    \COREPOS\Fannie\API\lib\AuditLib::BATCH_ADD);
            }
            $json['added'] = 1;
            $json['display'] = $this->showBatchDisplay($this->id);

            $this->runCallbacks($this->id);
        }

        echo $this->debugJSON($json);

        return false;
    }

    protected function post_id_autotag_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $bid = $this->id;
        $delQ = $dbc->prepare("DELETE FROM batchBarcodes where batchID=?");
        $dbc->execute($delQ,array($bid));

        $selQ = "
            SELECT l.upc,
                l.salePrice,
                l.batchID
            from batchList as l
                " . DTrans::joinProducts('l', 'p', 'INNER') . "
            WHERE l.batchID=? ";
        $args = array($bid);
        if ($this->config->get('STORE_MODE') == 'HQ') {
            $selQ .= " AND p.store_id=? ";
            $args[] = $this->config->get('STORE_ID');
        }
        $selQ .= " ORDER BY l.upc";
        $selP = $dbc->prepare($selQ);
        $selR = $dbc->execute($selP, $args);
        $upc = "";
        $insP = $dbc->prepare("INSERT INTO batchBarcodes
            (upc,description,normal_price,brand,sku,size,units,vendor,batchID)
            VALUES (?,?,?,?,?,?,?,?,?)");
        $tag_count = 0;
        $source = $this->config->get('TAG_DATA_SOURCE');
        if (empty($source) || !class_exists($source)) {
            $source = 'COREPOS\\Fannie\\API\\item\\TagDataSource';
        }
        $tagSource = new $source();
        while ($selW = $dbc->fetchRow($selR)) {
            if ($upc != $selW['upc']){
                $tag = $tagSource->getTagData($dbc, $selW['upc'], $selW['salePrice']);
                $dbc->execute($insP,array(
                    $tag['upc'], $tag['description'],
                    $tag['normal_price'], $tag['brand'],
                    $tag['sku'], $tag['size'],
                    $tag['units'], $tag['vendor'],
                    $selW['batchID']
                ));
                $tag_count++;
            }
            $upc = $selW['upc'];
        }

        $json = array('tags' => $tag_count);
        echo $this->debugJSON($json);

        return false;
    }

    protected function post_id_force_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $model = new BatchesModel($dbc);
        $model->forceStartBatch($this->id);
        $json = array('error'=>0, 'msg'=>'Batch #' . $this->id . ' has been applied');
        echo $this->debugJSON($json);

        return false;
    }

    protected function post_id_unsale_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $model = new BatchesModel($dbc);
        $model->forceStopBatch($this->id);

        $json = array('error'=>0, 'msg'=> 'Batch items taken off sale');
        echo $this->debugJSON($json);

        return false;
    }

    protected function post_id_limit_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $batches = new BatchesModel($dbc);
        $batches->batchID($this->id);
        $batches->transLimit($this->limit);
        $batches->save();

        return false;
    }

    protected function post_id_upc_uid_cut_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

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

        $bu = new BatchUpdateModel($dbc);
        $bu->upc($this->upc);
        $bu->batchID($this->id);
        $bu->logUpdate($bu::UPDATE_REMOVED);

        return false;
    }

    protected function post_id_upc_price_qty_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $json = array('error'=>0, 'msg'=>'');

        if (!is_numeric($this->qty) || $this->qty < 1 || $this->qty != round($this->qty)) {
            $json['error'] = 1;
            $json['msg'] = 'Invalid quantity "' . $this->qty . '"; using quantity one';
            $this->qty = 1;
        }

        $pmethod = ($this->qty >= 2) ? 2 : 0;
        if ($this->qty == 2) {
            $pmethod = $this->config->get('BOGO_MODE', 2);
        }

        $model = new BatchListModel($dbc);
        $model->upc($this->upc);
        $model->batchID($this->id);
        $model->salePrice($this->price);
        $model->groupSalePrice($this->price);
        $model->quantity($this->qty);
        // quantity functions as a per-transaction limit
        // when pricemethod=0
        if ($this->qty <= 1) {
            $this->qty = 1;
            $model->quantity(0);
        }
        $model->pricemethod($pmethod);
        $saved = $model->save();

        $bu = new BatchUpdateModel($dbc);
        $bu->upc($this->upc);
        $bu->batchID($this->id);
        $bu->specialPrice($this->price);
        $bu->quantity($this->qty);
        if ($this->qty <= 1) {
            $this->qty = 1;
            $bu->quantity(0);
        }
        $bu->logUpdate($bu::UPDATE_PRICE_EDIT);

        $json['price'] = sprintf('%.2f', $this->price);
        $json['qty'] = (int)$this->qty;

        $upQ = $dbc->prepare("update batchBarcodes set normal_price=? where upc=? and batchID=?");
        $upR = $dbc->execute($upQ,array($this->price,$this->upc,$this->id));

        if (FormLib::get('audited') == '1') {
            \COREPOS\Fannie\API\lib\AuditLib::batchNotification(
                $this->id,
                $this->upc,
                \COREPOS\Fannie\API\lib\AuditLib::BATCH_EDIT,
                (substr($this->upc,0,2)=='LC' ? true : false));
        }

        $this->runCallbacks($this->id);

        echo $this->debugJSON($json);

        return false;
    }

    private function unsaleItem($upc, $json)
    {
        if (substr($upc,0,2) != 'LC') {
            // take the item off sale if this batch is currently on sale
            if ($this->unsaleUPC($upc) === false) {
                $json['error'] = 1;
                $json['msg'] = 'Error taking item ' . $upc . ' off sale';
            }

            COREPOS\Fannie\API\data\ItemSync::sync($upc);
        } else {
            $likecode = substr($upc,2);
            if ($this->unsaleLikeCode($likecode) === false) {
                $json['error'] = 1;
                $json['msg'] = 'Error taking like code ' . $likecode . ' off sale';
            }
        }

        return $json;
    }

    private function unsaleUPC($upc)
    {
        // take the item off sale if this batch is currently on sale
        $product = new ProductsModel($this->connection);
        $product->upc($upc);
        $ret = true;
        foreach ($product->find('store_id') as $obj) {
            $obj->discountType(0);
            $obj->special_price(0);
            $obj->start_date('1900-01-01');
            $obj->end_date('1900-01-01');
            $ret = $obj->save();
        }

        return $ret ? true : false;
    }

    private function unsaleLikeCode($likecode)
    {
        $upcLike = new UpcLikeModel($this->connection);
        $upcLike->likeCode($likecode);
        $ret = true;
        foreach ($upcLike->find() as $u) {
            $ret = $this->unsaleUPC($u->upc());
        }

        return $ret ? true : false;
    }

    private function repriceItem($upc, $data, $json, $useStores=false)
    {
        if (substr($upc,0,2) != 'LC') {
            // take the item off sale if this batch is currently on sale
            if ($this->repriceUPC($upc,$data,$useStores) === false) {
                $json['error'] = 1;
                $json['msg'] = 'Error repricing item ' . $upc;
            }

            COREPOS\Fannie\API\data\ItemSync::sync($upc);
        } else {
            $likecode = substr($upc,2);
            if ($this->repriceLikeCode($likecode,$data,$useStores) === false) {
                $json['error'] = 1;
                $json['msg'] = 'Error taking like code ' . $likecode . ' repricing like code';
            }
        }

        return $json;
    }

    private function repriceLikeCode($likecode, $data, $useStores=false)
    {
        $upcLike = new UpcLikeModel($this->connection);
        $upcLike->likeCode($likecode);
        $ret = true;
        foreach ($upcLike->find() as $u) {
            $ret = $this->repriceUPC($u->upc(),$data,$useStores);
        }

        return $ret ? true : false;
    }

    private function repriceUPC($upc, $data, $useStores=false)
    {
        // set price of item to match current sale batch
        $product = new ProductsModel($this->connection);
        $product->upc($upc);
        $ret = true;
        foreach ($product->find('store_id') as $obj) {
            if ($useStores && !isset($data[$obj->store_id()])) {
                $data[$obj->store_id()] = array(
                    'discountType'=>0,
                    'salePrice'=>0,
                    'startDate'=>'1900-01-01',
                    'endDate'=>'1900-01-01',
                );
            }
            $cur = $useStores ? $data[$obj->store_id()] : $data;
            $obj->discountType(isset($cur['discountType']) ? $cur['discountType'] : 0);
            $obj->special_price($cur['salePrice']);
            $obj->start_date($cur['startDate']);
            $obj->end_date($cur['endDate']);
            $ret = $obj->save();
        }

        return $ret ? true : false;
    }

    protected function delete_id_upc_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $id = $this->id;
        $upc = $this->upc;

        $bu = new BatchUpdateModel($dbc);
        $bu->upc($upc);
        $bu->batchID($id);
        $bu->logUpdate($bu::UPDATE_REMOVED);

        $delQ = $dbc->prepare("delete from batchList where batchID=? and upc=?");
        $delR = $dbc->execute($delQ,array($id,$upc));
        if ($delR === false) {
            if ($json['error']) {
                $json['msg'] .= '<br />Error deleting item ' . $upc . ' from batch';
            } else {
                $json['error'] = 1;
                $json['msg'] = 'Error deleting item ' . $upc . ' from batch';
             }
        }

        $delQ = $dbc->prepare("delete from batchBarcodes where upc=? and batchID=?");
        $delR = $dbc->execute($delQ,array($upc,$id));

        if (FormLib::get_form_value('audited') == '1') {
            \COREPOS\Fannie\API\lib\AuditLib::batchNotification(
                $id,
                $upc,
                \COREPOS\Fannie\API\lib\AuditLib::BATCH_DELETE,
                (substr($upc,0,2)=='LC' ? true : false));
        }


        $json = array('error'=>0, 'msg'=>'Item ' . $upc . ' removed from batch');
        $currentP = $dbc->prepare('SELECT batchID FROM batches WHERE ? BETWEEN startDate AND endDate AND batchID=?');
        $current = $dbc->getValue($currentP, array(date('Y-m-d 00:00:00'), $this->id));
        if ($current) {

            $effective = PriceLib::effectiveSalePrice($dbc, $this->config, $upc);
            if (!isset($effective[$upc])) { // Item is not on sale
                $this->unsaleItem($upc, $json);
            } else { // Item is on sale [at some stores, possibly]
                $useStores = $this->config->get('STORE_MODE') == 'HQ' ? true : false;
                $json = $this->repriceItem($upc, $effective[$upc], $json, $useStores);
            }

            /*
            $currentSalesA = array($upc, date('Y-m-d 00:00:00'));
            $currentSalesP = $dbc->prepare("
                SELECT b.batchID, bl.upc, bl.salePrice, b.discountType, b.startDate, b.endDate
                FROM batches AS b
                    LEFT JOIN batchList AS bl ON b.batchID=bl.batchID
                WHERE b.discountType > 0
                    AND bl.upc = ?
                    AND ? BETWEEN startDate AND endDate;
            ");
            $currentSalesR = $dbc->execute($currentSalesP,$currentSalesA);
            $curSale = array(
                'batchID' => $row['batchID'],
                'salePrice' => 0,
                'discountType' => 1,
                'startDate' => '',
                'endDate' => '',
            );
            while ($row = $dbc->fetchRow($currentSalesR)) {
                if ($row['batchID'] != $id) {
                    if ($row['salePrice'] < $curSale['salePrice'] || $curSale['salePrice'] == 0) {
                        $curSale['salePrice'] = $row['salePrice'];
                        $curSale['batchID'] = $row['batchID'];
                        $cursale['discountType'] = $row['discountType'];
                        $cursale['startDate'] = $row['startDate'];
                        $cursale['endDate'] = $row['endDate'];
                    }
                }
            }
            if ($curSale['batchID'] == $id) {
                $json = $this->unsaleItem($upc, $json);
            } elseif ($curSale['batchID'] != $id && $curSale['salePrice'] != 0) {
                $json = $this->repriceItem($upc, $curSale, $json);
            }
             */
        }

        $this->runCallbacks($this->id);

        echo $this->debugJSON($json);

        return false;
    }

    protected function post_id_upc_swap_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $q = $dbc->prepare("UPDATE batchList SET salePrice = -1*salePrice
            WHERE batchID=? AND upc=?");
        $r = $dbc->execute($q,array($this->id,$this->upc));

        $json = array('error' => 0);
        if ($r === false) {
            $json['error'] = 'Error moving item';
        }
        echo $this->debugJSON($json);

        return false;
    }

    protected function post_id_qualifiers_discount_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $batch = new BatchesModel($dbc);
        $batch->batchID($this->id);
        if (FormLib::get('member') == '1') {
            $batch->discountType(2);
        } else {
            $batch->discountType(1);
        }
        $save1 = $batch->save();

        $pmethod = 4;
        if (FormLib::get('split') == '1') {
            $pmethod = 3;
        }

        $upQ2 = $dbc->prepare("UPDATE batchList SET
                quantity=?,pricemethod=?,
                salePrice=?, groupSalePrice=? WHERE batchID=?
                AND salePrice >= 0");
        $upQ3 = $dbc->prepare("UPDATE batchList SET
                quantity=?,pricemethod=?,
                    salePrice=?, groupSalePrice=? WHERE batchID=?
                    AND salePrice < 0");
        $save2 = $dbc->execute($upQ2, array($this->qualifiers+1,$pmethod,$this->discount,$this->discount,$this->id));
        $save3 = $dbc->execute($upQ3,array($this->qualifiers+1,$pmethod,-1*$this->discount,$this->discount,$this->id));

        $json['error'] = 0;
        if (!$save1 || !$save2 || !$save3) {
            $json['error'] = 'Error saving paired sale settings';
        }
        echo $this->debugJSON($json);

        return false;
    }

    protected function post_id_trim_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->OP_DB);
        $ret = array('error'=>0, 'display'=>'');

        $query = '
            SELECT b.upc
            FROM batchList AS b
                ' . DTrans::joinProducts('b', 'p', 'INNER') . '
            WHERE b.batchID=?
                AND b.salePrice=p.normal_price
            GROUP BY b.upc';
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($this->id));

        $delP = $dbc->prepare('
            DELETE FROM batchList
            WHERE batchID=?
                AND upc=?');
        $dbc->startTransaction();
        $bu = new BatchUpdateModel($dbc);
        while ($w = $dbc->fetchRow($res)) {
            $dbc->execute($delP, array($this->id, $w['upc']));
            $bu->reset();
            $bu->batchID($this->id);
            $bu->upc($w['upc']);
            $bu->logUpdate($bu::UPDATE_REMOVED);
        }
        $dbc->commitTransaction();

        $query = "SELECT upc, salePrice FROM batchList WHERE batchID=? AND upc like 'LC%'";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($this->id));
        $priceP = $dbc->prepare("SELECT MIN(normal_price), MAX(normal_price)
            FROM upcLike AS u INNER JOIN products AS p ON p.upc=u.upc
            WHERE u.likeCode=?");
        $dbc->startTransaction();
        while ($row = $dbc->fetchRow($res)) {
            $price = $row['salePrice'];
            $minMax = $dbc->getRow($priceP, array(substr($row['upc'], 2)));
            if (abs($price - $minMax[0]) < 0.005 && abs($price - $minMax[1]) < 0.005) {
                $dbc->execute($delP, array($this->id, $row['upc']));
            }
        }
        $dbc->commitTransaction();

        $ret['display'] = $this->showBatchDisplay($this->id);
        echo $this->debugJSON($ret);

        $this->runCallbacks($this->id);

        return false;
    }

    public function post_id_storeID_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->OP_DB);
        $ret = array('error'=>0, 'display'=>'');

        $map = new StoreBatchMapModel($dbc);
        $map->storeID($this->storeID);
        $map->batchID($this->id);
        if ($map->load()) {
            $deleted = $map->delete();
            if (!$deleted) {
                $ret['error'] = 'Error removing store mapping';
            }
        } else {
            $saved = $map->save();
            if (!$saved) {
                $ret['error'] = 'Error saving store mapping';
            }
        }
        echo $this->debugJSON($ret);

        $this->runCallbacks($this->id);

        return false;
    }

    private function runCallbacks($batchID)
    {
        $cbs = $this->config->get('BATCH_CALLBACKS');
        $this->logger->debug("Attempting batch callbacks");
        foreach ($cbs as $cb) {
            $obj = new $cb();
            $obj->run($batchID);
            $this->logger->debug("Running $cb for batch $batchID");
        }
    }

    private function addItemUPCInput($newtags=false)
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->OP_DB);
        $model = new LikeCodesModel($dbc);
        $lcOpts = $model->toOptions(-1);

        return <<<HTML
<form class="form-inline" onsubmit="batchEdit.advanceToPrice(); return false;" id="add-item-form">
    <span class="add-by-upc-fields">
        <label class=\"control-label\">UPC</label>
        <input type=text maxlength=13 name="addUPC" id=addItemUPC
            class="form-control" />
    </span>
    <span class="add-by-lc-fields collapse">
        <label class="control-label">Like code</label>
        <input type=text id=addItemLC name="addLC" size=4 value=1 class="form-control" disabled />
        <select id=lcselect onchange="\$('#addItemLC').val(this.value);" class="form-control chosen-select" disabled>
        {$lcOpts}
        </select>
    </span>
    <button type=submit value=Add class="btn btn-default">Add</button>
    <input type=checkbox id=addItemLikeCode onchange="batchEdit.toggleUpcLcInput();" />
    <label for="addItemLikeCode" class="control-label">Likecode</label>
</form>
HTML;
    }

    private function addItemPriceInput($upc, $description, $price)
    {
        return <<<HTML
<form onsubmit="batchEdit.addItemPrice('{$upc}'); return false;" id="add-price-form" class="form-inline">
    <label>ID</label>: {$upc}
    <label>Description</label>: {$description}
    <label>Normal price</label>: {$price}
    <label>Sale price</label>
    <input class="form-control" type=text id=add-item-price name=price size=5 />
    <button type=submit value=Add class="btn btn-default">Add</button>
</form>
HTML;
    }

    protected function showBatchDisplay($id, $order='natural')
    {
        global $FANNIE_SERVER_DBMS,$FANNIE_URL;
        $dbc = $this->connection;
        $uid = getUID($this->current_user);
        $uid = ltrim($uid,'0');
        $authorized = false;
        if (FannieAuth::validateUserQuiet('admin')) {
            $authorized = true;
        }

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
                $orderby = 'ORDER BY locationName';
                break;
            case 'loc_d':
                $orderby = 'ORDER BY locationName';
                break;
            case 'brand_a':
                $orderby = 'ORDER BY p.brand ASC';
                break;
            case 'brand_d':
                $orderby = 'ORDER BY p.brand DESC';
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
        $dtype = $model->discountType();
        $start = strtotime($model->startDate());
        $end = strtotime($model->endDate()) + (60*60*24);
        $typeModel = new BatchTypeModel($dbc);
        $typeModel->batchTypeID($type);
        $typeModel->load();

        if ($typeModel->editorUI() == 2) {
            return $this->showPairedBatchDisplay($id,$name);
        }
        $noprices = $typeModel->editorUI() == 4 ? 'collapse' : '';

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

        $fetchArgs = array();
        $store_location = COREPOS\Fannie\API\lib\Store::getIdByIp();

        // logically string "LC" followed by like code number
        $joinColumn = $dbc->concat("'LC'", $dbc->convert('l.likeCode', 'CHAR'), '');

        $fetchQ = "
            SELECT b.upc,
                CASE
                    WHEN l.likeCode IS NULL THEN p.description
                    ELSE l.likeCodeDesc
                END AS description,
                p.normal_price,
                b.salePrice,
                CASE WHEN c.upc IS NULL then 0 ELSE 1 END as isCut,
                b.quantity,
                b.pricemethod,
                p.brand,
                NULL AS locationName,
                r.maxPrice,
                r.priceRuleID,
                r.priceRuleTypeID
            FROM batchList AS b
                " . DTrans::joinProducts('b') . "
                LEFT JOIN likeCodes AS l ON b.upc = {$joinColumn}
                LEFT JOIN batchCutPaste AS c ON b.upc=c.upc AND b.batchID=c.batchID
                LEFT JOIN FloorSectionsListView as f on b.upc=f.upc and f.storeID=?
                LEFT JOIN PriceRules AS r ON p.price_rule_id=r.priceRuleID
            WHERE b.batchID = ?
            $orderby";
        $fetchArgs[] = $store_location;
        $fetchArgs[] = $id;
        if ($dbc->tableExists('FloorSectionsListView')) {
            $fetchQ = str_replace('NULL AS locationName', 'f.sections AS locationName', $fetchQ);
        }
        /*elseif ($dbc->tableExists('FloorSections')) {
            $fetchQ = str_replace('NULL AS locationName', 's.name AS locationName', $fetchQ);
        }*/

        $fetchP = $dbc->prepare($fetchQ);
        $fetchR = $dbc->execute($fetchP, $fetchArgs);

        $overlapP = $dbc->prepare('
            SELECT b.batchID,
                b.batchName
            FROM batchList as l
                INNER JOIN batches AS b ON b.batchID=l.batchID
            WHERE l.upc=?
                AND l.batchID <> ?
                AND b.discounttype > 0
                AND (
                    (b.startDate BETWEEN ? AND ?)
                    OR
                    (b.endDate BETWEEN ? AND ?)
                )
                AND b.endDate <= ' . $dbc->curdate()
        );
        $overlap_args = array($model->startDate(), $model->endDate(), $model->startDate(), $model->endDate());
        $allOverlap = $this->checkAllOverlap($id);

        $cpCount = $dbc->prepare("SELECT count(*) FROM batchCutPaste WHERE uid=?");
        $res = $dbc->execute($cpCount,array($uid));
        $row = $dbc->fetch_row($res);
        $cpCount = $row[0];

        $this->addOnloadCommand("$('.be-editable-date').datepicker();");
        //$this->addOnloadCommand("$('#batchName').removeAttribute('tabIndex');");

        $ret = "<span class=\"newBatchBlack\"><b>Batch name</b>: <input type=\"text\" class=\"be-editable form-control wide\"
            value=\"$name\" name=\"batchName\" id=\"batchName\" onchange=\"batchEdit.renameBatch('$name'); return false;\" /></span> | ";

        $startYMD = date('Y-m-d', strtotime($model->startDate()));
        $endYMD = date('Y-m-d', strtotime($model->endDate()));
        $ret .= "<input type=\"hidden\" id=\"batchStartDate\" value=\"$startYMD\"/>";
        $ret .= "<input type=\"hidden\" id=\"batchEndDate\" value=\"$endYMD\"/>";
        $ret .= "<input type=\"hidden\" id=\"batchType\" value=\"$type\"/>";
        $ret .= '<b>Sale Dates</b>: <input type="text" class="be-editable be-editable-date form-control"
            onchange="batchEdit.editBatchDate(\''.$startYMD.'\', \'start\'); return false;"
            name="startDate" id="startDate" value="'
            . date('Y-m-d', strtotime($model->startDate()))
            . '"/> - <input type="text" class="be-editable be-editable-date form-control"
            onchange="batchEdit.editBatchDate(\''.$endYMD.'\', \'end\'); return false;"
            name="startDate" id="endDate" value="'
            . date('Y-m-d', strtotime($model->endDate()))
            . '"/> | ' . '<a href="batchReport.php?batchID=' . $id . '">Report</a><br />';
        if ($this->config->get('STORE_MODE') === 'HQ') {
            $stores = new StoresModel($dbc);
            $stores->hasOwnItems(1);
            $mapP = $dbc->prepare('SELECT storeID FROM StoreBatchMap WHERE storeID=? AND batchID=?');
            foreach ($stores->find('storeID') as $s) {
                $mapR = $dbc->execute($mapP, array($s->storeID(), $id));
                $checked = ($mapR && $dbc->numRows($mapR)) ? 'checked' : '';
                $disabled = $typeModel->allowSingleStore() ? '' : 'disabled';
                $ret .= sprintf('<label>
                    <input type="checkbox" onchange="batchEdit.toggleStore(%d, %d);" %s %s />
                    %s
                    </label> | ',
                    $s->storeID(), $id,
                    $disabled, $checked, $s->description());
            }
            $ret .= '<br />';
        }
        if ($model->discountType() == 0) {
            $ret .= '<div class="alert alert-danger">This is a price change batch</div>';
        }
        $ret .= '<span class="hidden-print">';
        $ret .= "<a href=\"BatchListPage.php\">Back to batch list</a> | ";
        $ret .= sprintf('<input type="hidden" id="batch-discount-type" value="%d" />', $model->discountType());
        /**
          Price change batches probably want the upcoming retail
          rather than the current retail. Current sales will want
          the current sale price; future sales will want the future
          sale price. Past sales probably won't print signs under
          normal circumstances.
        */
        $future_mode = false;
        if ($model->discountType() == 0) {
            $future_mode = true;
        } elseif (strtotime($model->startDate()) >= strtotime(mktime(0,0,0,date('n'),date('j'),date('Y')))) {
            $future_mode = true;
        }
        $ret .= sprintf('<input type="hidden" id="batch-future-mode" value="%d" />', $future_mode ? 1 : 0);
        $ret .= "<a href=\"../../admin/labels/SignFromSearch.php?batch=$id\">Print Sale Signs</a> | ";
        $ret .= "<a href=\"BatchSignStylesPage.php?id=$id\">Sign Pricing</a> | ";
        $ret .= "<a href=\"{$FANNIE_URL}admin/labels/BatchShelfTags.php?batchID%5B%5D=$id\">Print Shelf Tags</a> | ";
        $ret .= "<a href=\"\" onclick=\"batchEdit.generateTags($id); return false;\">Auto-tag</a> | ";
        if ($cpCount > 0) {
            $ret .= "<a href=\"EditBatchPage.php?id=$id&paste=1\">Paste Items ($cpCount)</a> | ";
        }

        if ($dtype == 0 || (time() >= $start && time() <= $end)) {
            $ret .= "<a href=\"\" class=\"{$noprices}\" onclick=\"batchEdit.forceNow($id); return false;\">Force batch</a> | ";
        }
        if ($dtype != 0) {
            $ret .= "<a href=\"\" class=\"{$noprices}\" onclick=\"batchEdit.unsaleNow($id); return false;\">Stop Sale</a> | ";
        }

        $ret .= "<a href=\"\" onclick=\"batchEdit.cutAll($id,$uid); return false;\">Cut All</a> ";

        if ($dtype <= 0) {
            $ret .= " | <a href=\"\" class=\"{$noprices}\" onclick=\"batchEdit.trimPcBatch($id); return false;\">Trim Unchanged</a> ";
        } else {
            $ret .= " | <span id=\"edit-limit-link\"><a href=\"\"
                onclick=\"batchEdit.editTransLimit(); return false;\">" . ($hasLimit ? 'Edit' : 'Add' ) . " Limit</a></span>";
            $ret .= "<span id=\"save-limit-link\" class=\"collapse\"><a href=\"\" onclick=\"batchEdit.saveTransLimit($id); return false;\">Save Limit</a></span>";
            $ret .= " <span class=\"form-group form-inline\" id=\"currentLimit\" style=\"color:#000;\">{$limit}</span>";
        }
        $ret .= " | <a data-toggle='modal' data-target='#myModal'>Batch History</a>";
        $ret .= '</span>';
        if ($authorized === true && $type == 4) {
            $ret .= " | <a href='' onclick='batchEdit.logBatch($id); return false;'>
                <span class='btn-info'>Admin</span>: Stage Price Change</a>";
        }

        /**
          Insert extra fields to manage partial day batch
        */
        if ($typeModel->editorUI() == 3) {
            $partialP = $dbc->prepare('SELECT * FROM PartialBatches WHERE batchID=?');
            $partial = $dbc->getRow($partialP, array($id));
            $ret .= '<table class="table small table-bordered">';
            $ret .= '<tr><th>Start Time</th><th>End Time</th><th>Override</th><th>Frequency</th></tr>';
            $ret .= sprintf('<tr><td><input type="text" class="form-control small partialBatch"
                        onchange="batchEdit.updatePartial(%d);"
                        name="pStart" placeholder="HH:MM" value="%s" /></td>', $id, $partial['startTime']);
            $ret .= sprintf('<td><input type="text" class="form-control small partialBatch"
                        onchange="batchEdit.updatePartial(%d);"
                        name="pEnd" placeholder="HH:MM" value="%s" /></td>', $id, $partial['endTime']);
            $ret .= sprintf('<td><input type="checkbox" class="partialBatch" name="pOver" %s value="1"
                onchange="batchEdit.updatePartial(%d);" /></td>',
                ($partial['overwriteSales'] ? 'checked' : ''), $id);
            $ret .= '<td><select name="pRepeat" class="form-control small partialBatch"
                        onchange="batchEdit.updatePartial(' . $id . ');">';
            foreach (array('daily', 'weekdays', 'short weekends', 'long weekends', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') as $r) {
                $ret .= sprintf('<option %s value="%s">%s</option>',
                    ($r == $partial['repetition'] ? 'selected' : ''), $r, ucwords($r));
            }
            $ret .= '</select></td></tr></table>';
        }

        $ret .= "<br />";
        $ret .= "<table id=yeoldetable class=\"table\">";
        $ret .= "<tr>";
        if ($orderby != "ORDER BY b.upc ASC") {
            $ret .= "<th><a href=\"EditBatchPage.php?id=$id&sort=upc_a\">UPC</a></th>";
        } else {
            $ret .= "<th><a href=\"EditBatchPage.php?id=$id&sort=upc_d\">UPC</a></th>";
        }
        if ($orderby != "ORDER BY p.brand ASC") {
            $ret .= "<th><a href=\"EditBatchPage.php?id=$id&sort=brand_a\">Brand</a></th>";
        } else {
            $ret .= "<th><a href=\"EditBatchPage.php?id=$id&sort=brand_d\">Brand</a></th>";
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
            $ret .= "<th class=\"{$noprices}\"><a href=\"EditBatchPage.php?id=$id&sort=sale_d\">$saleHeader</a></th>";
        } else {
            $ret .= "<th class=\"{$noprices}\"><a href=\"EditBatchPage.php?id=$id&sort=sale_a\">$saleHeader</a></th>";
        }
        $ret .= "<th class=\"hidden-print\" colspan=\"3\">&nbsp;</th>";
        if ($orderby != 'ORDER BY locationName') {
            $ret .= "<th><a href=\"EditBatchPage.php?id=$id&sort=loc_a\">Location</a></th>";
        } else {
            $ret .= "<th><a href=\"EditBatchPage.php?id=$id&sort=loc_d\">Location</a></th>";
        }
        $ret .= "</tr>";

        $colors = array('#ffffff','#ffffcc');
        $cur = 0;
        $upcFields = '';
        $upcs = '';
        $allLCs = true;
        $products = new ProductsModel($dbc);
        while ($fetchW = $dbc->fetchRow($fetchR)) {
            $products->reset();
            $products->upc($fetchW['upc']);
            if ($this->config->get('STORE_MODE') == 'HQ') {
                $storeLocation = COREPOS\Fannie\API\lib\Store::getIdByIp();
                $products->store_id($storeLocation);
            }
            $products->load();
            $tr_style = ($products->inUse() === '0') ? "style=\"color: black; background-color: lightgrey;\"" : "";
            $cur = ($cur + 1) % 2;
            $ret .= "<tr>";
            $fetchW['upc'] = rtrim($fetchW['upc']);
            if (substr($fetchW['upc'],0,2) == "LC") {
                $likecode = rtrim(substr($fetchW['upc'],2));
                $ret .= "<td bgcolor=$colors[$cur]>" . $fetchW['upc'];
                $ret .= " <a href=\"\" onclick=\"\$('.lc-item-{$likecode}').toggle(); return false;\">[+]</a>";
                $ret .= "</td>";
            } else {
                $allLCs = false;
                $upcFields .= sprintf('<input type="hidden" name="u[]" value="%s" />', $fetchW['upc']);
                $upcs .= $fetchW['upc'] . "\n";
                $conflict = '';
                if ($dtype > 0) {
                    //$overlapR = $dbc->execute($overlapP, array_merge(array($fetchW['upc'], $id), $overlap_args));
                    //if ($overlapR && $dbc->numRows($overlapR)) {
                    if (isset($allOverlap[$fetchW['upc']])) {
                        //$overlap = $dbc->fetchRow($overlapR);
                        $overlap = $allOverlap[$fetchW['upc']];
                        $conflict = sprintf('<a href="EditBatchPage.php?id=%d" target="_batch%d"
                                                title="!!Conflicts with batch %s" class="btn btn-xs btn-danger">
                                                <span class="fas fa-exclamation-circle">
                                                </span></a>',
                                                $overlap['batchID'], $overlap['batchID'],
                                                $overlap['batchName']);
                    }
                }
                if ($fetchW['priceRuleID'] != NULL && $fetchW['maxPrice'] > 0) {
                    $mp = $fetchW['maxPrice'];
                    $sp = $fetchW['salePrice'];
                    if ($sp < $mp && $fetchW['priceRuleTypeID'] == 10) {
                        $conflict .= '<a href="#" class="btn btn-warning btn-xs"
                            title="Sale price falls below MAP restriction. Minimum Price: $'.$mp.'">
                            <span class="fas fa-exclamation-circle"></span></span>';
                    }
                }
                $ret .= "<td bgcolor=$colors[$cur]><a href=\"{$FANNIE_URL}item/ItemEditorPage.php?searchupc={$fetchW['upc']}\"
                    target=\"_new{$fetchW['upc']}\">{$fetchW['upc']}</a>{$conflict}</td>";
            }
            $ret .= "<td bgcolor=$colors[$cur]><span $tr_style>{$fetchW['brand']}</span></td>";
            $ret .= "<td bgcolor=$colors[$cur]><span $tr_style>{$fetchW['description']}</span></td>";
            $ret .= "<td bgcolor=$colors[$cur] class=\"price\">{$fetchW['normal_price']}</td>";
            $qtystr = ($fetchW['pricemethod']>0 && is_numeric($fetchW['quantity']) && $fetchW['quantity'] > 0) ? $fetchW['quantity'] . " for " : "";
            $qty = is_numeric($fetchW['quantity']) && $fetchW['quantity'] > 0 ? $fetchW['quantity'] : 1;
            $ret .= "<td bgcolor=$colors[$cur] class=\"{$noprices} saleprice\">";
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
            $ret .= "<td class=\"hidden-print\" bgcolor=$colors[$cur] id=editLink{$fetchW['upc']}>
                <a href=\"\" class=\"edit {$noprices}\" onclick=\"batchEdit.editUpcPrice('{$fetchW['upc']}'); return false;\">
                    " . \COREPOS\Fannie\API\lib\FannieUI::editIcon() . "</a>
                <a href=\"\" class=\"save collapse\" onclick=\"batchEdit.saveUpcPrice('{$fetchW['upc']}'); return false;\">
                    " . \COREPOS\Fannie\API\lib\FannieUI::saveIcon() . "</a>
                </td>";
            $ret .= "<td class=\"hidden-print\" bgcolor=$colors[$cur]><a href=\"\"
                onclick=\"batchEdit.deleteUPC.call(this, $id, '{$fetchW['upc']}'); return false;\">"
                . \COREPOS\Fannie\API\lib\FannieUI::deleteIcon() . "</a>
                </td>";
            if ($fetchW['isCut'] == 1) {
                $ret .= "<td class=\"hidden-print\" bgcolor=$colors[$cur] id=cpLink{$fetchW['upc']}>
                    <a href=\"\" class=\"unCutLink\" id=\"unCut{$fetchW['upc']}\" onclick=\"batchEdit.cutItem('{$fetchW['upc']}',$id,$uid, 0); return false;\">Undo</a>
                    <a href=\"\" class=\"cutLink collapse\" id=\"doCut{$fetchW['upc']}\" onclick=\"batchEdit.cutItem('{$fetchW['upc']}',$id,$uid, 1); return false;\">Cut</a>
                    </td>";
            } else {
                $ret .= "<td class=\"hidden-print\" bgcolor=$colors[$cur] id=cpLink{$fetchW['upc']}>
                    <a href=\"\" class=\"unCutLink collapse\" id=\"unCut{$fetchW['upc']}\" onclick=\"batchEdit.cutItem('{$fetchW['upc']}',$id,$uid,0); return false;\">Undo</a>
                    <a href=\"\" class=\"cutLink\" id=\"doCut{$fetchW['upc']}\" onclick=\"batchEdit.cutItem('{$fetchW['upc']}',$id,$uid,1); return false;\">Cut</a>
                    </td>";
            }

            $loc = 'n/a';
            if (!empty($fetchW['locationName'])) {
                $loc = $fetchW['locationName'];
            }
            $ret .= "<td bgcolor=$colors[$cur]>".$loc.'</td>';
            $ret .= '<input type="hidden" class="batch-hidden-upc" value="' . $fetchW['upc'] . '" />';

            $ret .= "</tr>";
            if (substr($fetchW['upc'], 0, 2) == "LC") {
                $likecode = rtrim(substr($fetchW['upc'],2));
                $ret .= self::likeToTable($dbc, $likecode, $fetchW['salePrice']);
            }
        }
        $ret .= "</table>";
        $ret .= "<input type=hidden id=currentBatchID value=$id />";
        $ret .= '<label>Notes</label><textarea name="batchNotes" id="batchNotes" class="form-control" rows="4"
            onchange="batchEdit.saveNotes(' . $id . ');" onkeyup="batchEdit.noteTyped(' . $id . ');">'
            . $model->notes() . '</textarea>';
        if ($dbc->numRows($fetchR) > 0) {
            $ret .= '<p>
                <a href="BatchImportExportPage.php?id=' . $id . '">Export as JSON</a>
                | <a href="BatchExportExcel.php?id=' . $id . '">Export as Excel</a>
                | <a href="" onclick="$(\'#previousPromos\').submit(); return false;">Previous Promos</a>
                | <a href="" onclick="$(\'#searchForm\').submit(); return false;">Search These</a>
                <form method="post" id="previousPromos" action="../../reports/from-search/PreviousPromos/PreviousPromosReport.php">
                ' . $upcFields . '</form>
                <form method="post" id="searchForm" action="../../item/AdvancedItemSearch.php">
                <input type="hidden" name="extern" value="1" />
                <input type="hidden" name="upcs" value="' . $upcs . '" />
                </p>';
        }

        if ($allLCs && $model->discountType() == 0) {
            $ret = str_replace('SignFromSearch.php?batch', 'LikeCodeBatchSigns.php?id', $ret);
        }

        return $ret;
    }

    private static $like_stmt = null;
    private static $like_args = null;
    private static function likeToTable($dbc, $likecode, $salePrice)
    {
        // singleton prepared statement
        if (self::$like_stmt === null) {
            $likeQ = "
                SELECT p.upc,
                    p.description,
                    p.normal_price
                FROM products AS p
                    INNER JOIN upcLike AS u ON p.upc=u.upc
                WHERE 1=1 ";
            self::$like_args = array();
            if (FannieConfig::config('STORE_MODE') == 'HQ') {
                $likeQ .= " AND p.store_id=? ";
                self::$like_args[] = FannieConfig::config('STORE_ID');
            }
            $likeQ .= "
                    AND u.likeCode=?
                ORDER BY p.upc DESC";
            self::$like_stmt = $dbc->prepare($likeQ);
        }

        $likeR = $dbc->execute(self::$like_stmt, array_merge(self::$like_args, array($likecode)));
        $ret = '';
        $FANNIE_URL = FannieConfig::config('URL');
        while ($likeW = $dbc->fetch_row($likeR)) {
            $ret .= '<tr class="collapse lc-item-' . $likecode . '">';
            $ret .= "<td><a href=\"{$FANNIE_URL}item/ItemEditorPage.php?searchupc={$likeW['upc']}\"
                target=_new{$likeW['upc']}>{$likeW['upc']}</a></td>";
            $ret .= "<td>{$likeW['description']}</td>";
            $ret .= "<td>{$likeW['normal_price']}</td>";
            $ret .= "<td>{$salePrice}</td>";
            $ret .= "<td>&nbsp;</td>";
            $ret .= "<td>&nbsp;</td>";
            $ret .= '</tr>';
        }

        return $ret;
    }

    private function pairedTableBody($dbc, $result, $down=true)
    {
        $colors = array('#ffffff','#ffffcc');
        $cur = 0;
        $FANNIE_URL = $this->config->get('URL');
        $ret = '';
        while ($fetchW = $dbc->fetchRow($result)) {
            $cur = ($cur + 1) % 2;
            $ret .= "<tr>";
            $fetchW[0] = rtrim($fetchW[0]);
            if (substr($fetchW[0],0,2) == "LC") {
                $likecode = rtrim(substr($fetchW[0],2));
                $ret .= "<td bgcolor=$colors[$cur]>" . $fetchW['upc'];
                $ret .= " <a href=\"\" onclick=\"\$('.lc-item-{$likecode}').toggle(); return false;\">[+]</a>";
                $ret .= "</td>";
            } else {
                $ret .= "<td bgcolor=$colors[$cur]><a href={$FANNIE_URL}item/ItemEditorPage.php?searchupc=$fetchW[0] target=_new$fetchW[0]>$fetchW[0]</a></td>";
            }
            $ret .= "<td bgcolor=$colors[$cur]>$fetchW[1]</td>";
            $showDown = $down ? '' : 'collapse';
            $showUp = $down ? 'collapse' : '';
            $ret .= "<td bgcolor=$colors[$cur]>
                <a href=\"\" class=\"down-arrow {$showDown}\" onclick=\"batchEdit.swapQualifierToDiscount(this, '$fetchW[0]'); return false;\">
                    <img src=\"{$FANNIE_URL}src/img/buttons/arrow_down.gif\" alt=\"Make Discount Item\" /></a>
                <a href=\"\" class=\"up-arrow {$showUp}\" onclick=\"batchEdit.swapDiscountToQualifier(this, '$fetchW[0]'); return false;\">
                    <img src=\"{$FANNIE_URL}src/img/buttons/arrow_up.gif\" alt=\"Make Qualifying Item\" />
                    </a>
                </td>";
            $ret .= "<td bgcolor=$colors[$cur]><a href=\"\" onclick=\"batchEdit.deleteUPC.call(this, {$fetchW['batchID']}, '$fetchW[0]'); return false;\">"
                . \COREPOS\Fannie\API\lib\FannieUI::deleteIcon() . '</a></td>';
            $ret .= "</tr>";

            if (substr($fetchW['upc'], 0, 2) == "LC") {
                $likecode = rtrim(substr($fetchW['upc'],2));
                $ret .= self::likeToTable($dbc, $likecode, $fetchW['salePrice']);
            }
        }

        return $ret;
    }

    protected function showPairedBatchDisplay($id, $name)
    {
        global $FANNIE_SERVER_DBMS;
        $dbc = $this->connection;
        $uid = getUID($this->current_user);
        $uid = ltrim($uid,'0');

        $ret = "";
        $ret .= sprintf('<input type="hidden" id="currentBatchID" value="%d" />',$id);
        $ret .= "<b>Batch name</b>: <input type=\"text\" class=\"editable\" value=\"$name\"
            name=\"batchName\" /><br />";
        $ret .= "<a href=\"BatchListPage.php\">Back to batch list</a> | ";
        $ret .= "<a href=\"\" onclick=\"batchEdit.forceNow($id); return false;\">Force batch</a>";
        $ret .= " | No limit";
        $ret .= " <span id=\"currentLimit\" style=\"color:#000;\"></span>";

        $q = $dbc->prepare("SELECT b.discounttype,salePrice,
            CASE WHEN l.pricemethod IS NULL THEN 4 ELSE l.pricemethod END as pricemethod,
            CASE WHEN l.quantity IS NULL THEN 1 ELSE l.quantity END as quantity
            FROM batches AS b LEFT JOIN batchList AS l
            ON b.batchID=l.batchID WHERE b.batchID=? ORDER BY l.pricemethod");
        $r = $dbc->execute($q,array($id));
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
                    onclick="batchEdit.savePairedPricing(%d); return false;">Update Pricing</button>',$id);
            $ret .= '</div>';
            $ret .= '</div>'; // end #paired-fields
        } else {
            $ret .= "<div class=\"alert alert-warning\">Add items first</div>";
        }

        // logically string "LC" followed by like code number
        $joinColumn = $dbc->concat("'LC'", $dbc->convert('l.likeCode', 'CHAR'), '');

        $fetchQ = $dbc->prepare("
            SELECT b.upc,
                case when l.likeCode is null then p.description else l.likeCodeDesc end as description,
                p.normal_price,
                b.salePrice,
                b.batchID
            FROM batchList AS b
                " . DTrans::joinProducts('b') . "
                LEFT JOIN likeCodes as l on b.upc = {$joinColumn}
            WHERE b.batchID = ?
                AND b.salePrice >= 0");
        $fetchR = $dbc->execute($fetchQ,array($id));

        $ret .= '<table class="table" id="qualifier-table">';
        $ret .= '<tr><th colspan="4">Qualifying Item(s)</th></tr>';
        $ret .= $this->pairedTableBody($dbc, $fetchR);
        $ret .= "</table>";

        $fetchQ = $dbc->prepare("
            SELECT b.upc,
                case when l.likeCode is null then p.description else l.likeCodeDesc end as description,
                p.normal_price,
                b.salePrice,
                b.batchID
            FROM batchList AS b
                " . DTrans::joinProducts('b') . "
                LEFT JOIN likeCodes as l on b.upc = {$joinColumn}
            WHERE b.batchID = ?
                AND b.salePrice < 0");
        $fetchR = $dbc->execute($fetchQ,array($id));

        $ret .= '<table class="table" id="discount-table">';
        $ret .= '<tr><th colspan="4">Discount Item(s)</th></tr>';
        $ret .= $this->pairedTableBody($dbc, $fetchR, false);
        $ret .= "</table>";

        return $ret;
    }

    public function get_id_paste_view()
    {
        return $this->get_id_view();
    }

    public function batch_history($bid)
    {
        include('../batchhistory/BatchHistoryPage.php');
        $modal = '';
        $modal .= '
            <style>
            .vertical-alignment-helper {
                display:table;
                height: 100%;
                width: 100%;
                pointer-events:none; /* This makes sure that we can still click outside of the modal to close it */
            }
            .vertical-align-center {
                /* To center vertically */
                display: table-cell;
                vertical-align: middle;
                pointer-events:none;
            }
            .modal-content {
                /* Bootstrap sets the size of the modal in the modal-dialog class, we need to inherit it */
                width:inherit;
                height:inherit;
                /* To center horizontally */
                margin: 0 auto;
                pointer-events: all;
            }
            </style>
        ';
        $modal .= '
                <!-- Modal -->
                <div id="myModal" class="modal" role="dialog">
                <div class="vertical-alignment-helper">
                  <div class="modal-dialog vertical-align-center">
                    <!-- Modal content-->
                    <div class="modal-content" style="height: 85vh; width: 85vw;">
                        <div style="max-height: 85vh; overflow-y:auto;">
                            ';
        $bhp = new BatchHistoryPage;
        $modal .= $bhp->getBatchHistory($bid);
        $modal .='
                        </div>
                    </div>
                  </div>
                </div>
                </div>
        ';

        return $modal;
    }

    public function get_id_view()
    {
        $this->addScript($this->config->get('URL') . 'src/javascript/chosen/chosen.jquery.min.js');
        $this->addCssFile($this->config->get('URL') . 'src/javascript/chosen/bootstrap-chosen.css');
        $this->addScript('edit.js?20180523');
        $this->addCssFile('index.css');
        $this->addOnloadCommand('$(\'#addItemUPC\').focus()');
        $this->addOnloadCommand("enableLinea('#addItemUPC');\n");
        $cmd = <<<JAVASCRIPT
function resizeInput()
{
    $('.be-editable').each(function(){
        var elm = $(this);
        var newWidth = (parseInt(elm.val().length, 10) + 1) * 10;
        newWidth = newWidth.toString() + "px";
        elm.css('width', newWidth);
    });
    $('.be-editable').on('keyup', function(){
        var elm = $(this);
        var newWidth = (parseInt(elm.val().length, 10) + 1) * 10;
        newWidth = newWidth.toString() + "px";
        elm.css('width', newWidth);
    });

}
resizeInput();
JAVASCRIPT;
        $this->addOnloadCommand($cmd);

        $url = $this->config->get('URL');
        $sort = FormLib::get('sort', 'natural');
        $inputForm = $this->addItemUPCInput();
        $test = 'test';
        $batchList = $this->showBatchDisplay($this->id, $sort);
        $linea = $this->enable_linea ? '<script type="text/javascript">' . $this->lineaJS() . '</script>' : '';
        $history = $this->batch_history($this->id);
        return <<<HTML
<div id="inputarea" class="hidden-print">
{$inputForm}
</div>
<div class="progress collapse" id="progress-bar">
    <div class="progress-bar progress-bar-striped active"
        role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"
        style="width: 100%" title="Working">
        <span class="sr-only">Working</span>
    </div>
</div>
<div id="displayarea">
{$batchList}
</div>
<input type=hidden id=uid value="{$this->current_user}" />
<input type=hidden id=isAudited value="{$this->audited}" />
<input type="hidden" id="batchID" value="{$this->id}" />
<input type=hidden id=buttonimgpath value="{$url}src/img/buttons/" />
{$linea}
{$history}
HTML;
    }

    // intentionally blank so Linea device javascript
    // isn't appended to AJAX responses
    public function postFlight()
    {
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
            own clipboard and don\'t interfere with each other.</p>
            <p><em>Items highlighted</em> in grey are not in-use in POS for the store the 
            batch is being viewed from.</p>
            ';
    }

    public function unitTest($phpunit)
    {
        $this->id = 1;
        $this->paste = 1;
        $phpunit->assertEquals(true, $this->get_id_paste_handler());
        $phpunit->assertNotEquals(0, strlen($this->get_id_paste_view()));

        ob_start();
        $phpunit->assertEquals(false, $this->post_id_trim_handler());
        ob_get_clean();

        $this->upc = '0000000004011';
        ob_start();
        $phpunit->assertEquals(false, $this->post_id_upc_swap_handler());
        ob_get_clean();

        ob_start();
        $phpunit->assertEquals(false, $this->delete_id_upc_handler());
        ob_get_clean();

        $this->limit = 1;
        $phpunit->assertEquals(false, $this->post_id_limit_handler());

        ob_start();
        $phpunit->assertEquals(false, $this->post_id_unsale_handler());
        ob_get_clean();

        ob_start();
        $phpunit->assertEquals(false, $this->post_id_force_handler());
        ob_get_clean();

        ob_start();
        $phpunit->assertEquals(false, $this->post_id_autotag_handler());
        ob_get_clean();

        $this->addUPC = $this->upc;
        ob_start();
        $phpunit->assertEquals(false, $this->post_id_addUPC_handler());
        ob_get_clean();

        $this->addLC = 1;
        ob_start();
        $phpunit->assertEquals(false, $this->post_id_addLC_handler());
        ob_get_clean();

        $phpunit->assertEquals(true, $this->get_id_paste_handler());
    }
}

FannieDispatch::conditionalExec();


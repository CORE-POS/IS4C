<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class EdlpBatchPage extends FannieRESTfulPage
{
    public $title = "Fannie - NCG EDLP Batch";
    public $header = "Create NCG EDLP Batch";

    public $description = '[NCG EDLP Batch] creates a price change
    batch for all items that have a maximum pricing rule attached.';

    private $itemQ = '
        SELECT p.upc,
            r.maxPrice
        FROM products AS p
            INNER JOIN PriceRules AS r ON p.price_rule_id=r.priceRuleID
        WHERE r.priceRuleTypeID=?
            AND r.maxPrice <> 0
            AND r.maxPrice IS NOT NULL
            AND r.maxPrice <> p.normal_price';

    public function get_id_view()
    {
        $dbc = $this->connection;
        $date = FormLib::get('date', false);

        $args = array($this->id);
        if ($date) {
            $this->itemQ .= ' AND r.reviewDate BETWEEN ? AND ?';
            $args[] = $date . ' 00:00:00';
            $args[] = $date . ' 23:59:59';
        }
        $itemP = $dbc->prepare($this->itemQ);
        $itemR = $dbc->execute($itemP, $args);

        if ($dbc->numRows($itemR) == 0) {
            return '<div class="alert alert-warning">No applicable items</div>' . $this->get_view();
        }

        $typeID = $this->getBatchType();
        if ($typeID === false) {
            return '<div class="alert alert-danger">Cannot create a price change batch</div>' . $this->get_view();
        }

        $batch = new BatchesModel($dbc);
        $batch->batchName('EDLP Price Change');
        $yesterday = date('Y-m-d', strtotime('yesterday'));
        $batch->startDate($yesterday);
        $batch->endDate($yesterday);
        $batch->batchType($typeID);
        $batch->discountType(0);
        $batchID = $batch->save();
        if ($this->config->get('STORE_MODE') === 'HQ') {
            StoreBatchMapModel::initBatch($batchID);
        }

        $list = new BatchListModel($dbc);
        $list->batchID($batchID);
        while ($itemW = $dbc->fetchRow($itemR)) {
            $list->upc($itemW['upc']);
            $list->salePrice($itemW['maxPrice']);
            $list->save();
        }

        return sprintf('<div class="alert alert-success">Created Batch. 
            <a href="../newbatch/EditBatchPage.php?id=%d">View it</a>.</div>',
            $batchID) . $this->get_view();
    }

    private function getBatchType()
    {
        $type = new BatchTypeModel($this->connection);
        $type->discType(0);
        foreach ($type->find('batchTypeID') as $t) {
            return $t->batchTypeID();
        }

        return false;
    }

    public function get_view()
    {
        $model = new PriceRuleTypesModel($this->connection);
        $ret = '<form method="get">
            <div class="form-group">
                <label>Rule Type</label>
                <select class="form-control" name="id">
                ' . $model->toOptions() . '
                </select>
            </div>
            <div class="form-group">
                <label>Review Date</label>
                <input type="text" class="form-control date-field" name="date" 
                    placeholder="Optional; omit for all items with the given rule type" />
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default">Create Batch</button>
            </div>
            </form>';

        return $ret;
    }

    public function helpContent()
    {
        return '
            <p>
            This will look up all items with a pricing rule of the 
            designated type and compare the maximum price specified
            in the rule to the item\'s current retail price. Items
            with discrepancies are added to a price change batch.
            </p>
            <p>
            If a review date is specified, only items with that pricing
            rule review date will be examined. If the date is omitted
            all items with the specified price rule type will be
            examined.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
    }
}

FannieDispatch::conditionalExec();


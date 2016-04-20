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

class BatchImportExportPage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $auth_classes = array('batches','batches_audited');
    protected $title = 'Batch Import/Export';
    protected $header = 'Batch Import/Export';
    public $description = '[Batch Import/Export] can import or export sales batches as formatted text (specifically, JSON)';

    protected function post_id_handler()
    {
        $json = json_decode($this->id, true);
        if ($json === null) {
            return true;
        }
        $batch = new BatchesModel($this->connection);
        $batch->startDate($json['startDate']);
        $batch->endDate($json['endDate']);
        $batch->batchName($json['batchName']);
        $batch->batchType($json['batchType']);
        $batch->discountType($json['discountType']);
        $batch->priority($json['priority']);
        $batch->owner($json['owner']);
        $batch->transLimit($json['transLimit']);
        $batchID = $batch->save();
        if ($this->config->get('STORE_MODE') === 'HQ') {
            StoreBatchMapModel::initBatch($batchID);
        }

        $item = new BatchListModel($this->connection);
        $item->batchID($batchID);
        foreach ($json['items'] as $jitem) {
            $item->upc($jitem['upc']);
            $item->salePrice($jitem['salePrice']);
            $item->groupSalePrice($jitem['groupSalePrice']);
            $item->active($jitem['active']);
            $item->pricemethod($jitem['pricemethod']);
            $item->quantity($jitem['quantity']);
            $item->signMultiplier($jitem['signMultiplier']);
            $item->save();
        }

        return 'EditBatchPage.php?id=' . $batchID;
    }

    protected function post_id_view()
    {
        return '<div class="alert alert-danger">Invalid Import</div>'
            . $this->get_view();
    }

    protected function get_id_view()
    {
        $batch = new BatchesModel($this->connection);
        $batch->batchID($this->id);
        $batch->load();
        $items = new BatchListModel($this->connection);
        $items->batchID($this->id);

        $ret = array(
            'startDate' => $batch->startDate(),
            'endDate' => $batch->endDate(),
            'batchName' => $batch->batchName(),
            'batchType' => $batch->batchType(),
            'discountType' => $batch->discountType(),
            'priority' => $batch->priority(),
            'owner' => $batch->owner(),
            'transLimit' => $batch->transLimit(),
            'items' => array(),
        );
        foreach ($items->find() as $item) {
            $ret['items'][] = array(
                'upc' => $item->upc(),
                'salePrice' => $item->salePrice(),
                'groupSalePrice' => $item->groupSalePrice(),
                'active' => $item->active(),
                'pricemethod' => $item->pricemethod(),
                'quantity' => $item->quantity(),
                'signMultiplier' => $item->signMultiplier(),
            );
        }

        return '<label>Export</label>
            <p>
            <button type="button" class="btn btn-default" onclick="$(\'#export-json\').select();">Select All</button>
            <a href="BatchImportExportPage.php" class="btn btn-default btn-reset">Go to Import</a>
            </p>
            <div class="form-group">
            <textarea rows="50" class="form-control" id="export-json">'
            . \COREPOS\Fannie\API\lib\FannieUI::prettyJSON(json_encode($ret)) 
            . '</textarea>
            </div>';
    }

    protected function get_view()
    {
        return '<form method="post">
            <label>Import</label>
            <div class="form-group">
                <textarea rows="50" class="form-control" id="import-json" name="id"></textarea>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default btn-core">Import</button>
            </div>
            </form>';
    }

    public function helpContent()
    {
        return '<p>
            Batch import/export can import or export batches as
            plain text. This can be used to transmit a batch to someone
            else via email or load it into another store\'s server.
            </p><p>
            Export provides a copy of the batch information in JSON
            format. This is a flat text format that is human readable.
            Exporting a batch does <em>not</em> delete the batch from the 
            system. The exporting user is expected to copy/paste the batch
            data into another format for transmission (a text file, email, etc)
            </p><p>
            Import provides one larger input to enter batch information. The import
            format is the same as the export format. You can manipulte or build batches
            in JSON if desired, but the typical workflow is
            <ul>
                <li>Copy/paste from export into some kind of message</li>
                <li>Send message to another person/location</li>
                <li>Copy/paste into import</li>
            </ul>
            <p><em>
            Note: exporting & importing batches does not preserve numeric batch IDs.
            </em></p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->id = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
        $phpunit->assertNotEquals(0, strlen($this->post_id_view()));
    }
}

FannieDispatch::conditionalExec();


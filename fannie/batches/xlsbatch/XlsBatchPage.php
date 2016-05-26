<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

class XlsBatchPage extends \COREPOS\Fannie\API\FannieUploadPage {
    protected $title = "Fannie -  Sales Batch";
    protected $header = "Upload Batch file";

    protected $auth_classes = array('batches');
    protected $must_authenticate = true;

    public $description = '[Excel Batch] creates a sale or price change batch from a spreadsheet.';

    protected $preview_opts = array(
        'upc_lc' => array(
            'display_name' => 'UPC/LC',
            'default' => 0,
            'required' => true
        ),
        'price' => array(
            'display_name' => 'Price',
            'default' => 1,
            'required' => true
        )
    );

    private $results = '';

    private function get_batch_types(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $batchtypes = array();
        $typesQ = $dbc->prepare("select batchTypeID,typeDesc from batchType order by batchTypeID");
        $typesR = $dbc->execute($typesQ);
        while ($typesW = $dbc->fetchRow($typesR))
            $batchtypes[$typesW[0]] = $typesW[1];
        return $batchtypes;
    }

    private function createBatch($dbc)
    {
        $btype = FormLib::get('btype',0);
        $date1 = FormLib::get('date1',date('Y-m-d'));
        $date2 = FormLib::get('date2',date('Y-m-d'));
        $bname = FormLib::get('bname','');
        $owner = FormLib::get('bowner','');

        $dtQ = $dbc->prepare("SELECT discType FROM batchType WHERE batchTypeID=?");
        $discountType = $dbc->getValue($dtQ, array($btype));

        $insQ = $dbc->prepare("
            INSERT INTO batches 
            (startDate,endDate,batchName,batchType,discounttype,priority,owner)
            VALUES 
            (?,?,?,?,?,0,?)");
        $args = array($date1,$date2,$bname,$btype,$discountType,$owner);
        $insR = $dbc->execute($insQ,$args);
        $batchID = $dbc->insertID();

        return $batchID;
    }

    function process_file($linedata, $indexes)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $ftype = FormLib::get('ftype','UPCs');
        $has_checks = FormLib::get('has_checks') !== '' ? True : False;

        $batchID = $this->createBatch($dbc);
        if ($this->config->get('STORE_MODE') === 'HQ') {
            StoreBatchMapModel::initBatch($batchID);
        }

        $upcChk = $dbc->prepare("SELECT upc FROM products WHERE upc=?");

        $model = new BatchListModel($dbc);
        $model->batchID($batchID);
        $model->pricemethod(0);
        $model->quantity(0);
        $model->active(0);

        $ret = '';
        foreach ($linedata as $line) {
            if (!isset($line[$indexes['upc_lc']])) continue;
            if (!isset($line[$indexes['price']])) continue;
            $upc = $line[$indexes['upc_lc']];
            $price = $line[$indexes['price']];
            $upc = str_replace(" ","",$upc);    
            $upc = str_replace("-","",$upc);    
            $price = trim($price,' ');
            $price = trim($price,'$');
            if (!is_numeric($upc)) {
                $ret .= "<i>Omitting item. Identifier {$upc} isn't a number</i><br />";
                continue; 
            } elseif(!is_numeric($price)){
                $ret .= "<i>Omitting item. Price {$price} isn't a number</i><br />";
                continue;
            }

            $upc = ($ftype=='UPCs') ? BarcodeLib::padUPC($upc) : 'LC'.$upc;
            if ($has_checks && $ftype=='UPCs')
                $upc = '0'.substr($upc,0,12);

            if ($ftype == 'UPCs'){
                $chkR = $dbc->execute($upcChk, array($upc));
                if ($dbc->num_rows($chkR) ==  0) continue;
            }   

            $model->upc($upc);
            $model->salePrice($price);
            $model->groupSalePrice($price);
            $model->save();
        }

        $ret .= '
        <p>
            Batch created
            <a href="' . $this->config->URL . 'batches/newbatch/EditBatchPage.php?id=' . $batchID 
                . '" class="btn btn-default">View Batch</a>
        </p>';
        $this->results = $ret;

        return true;
    }

    function results_content()
    {
        return $this->results;
    }

    function preview_content()
    {
        $batchtypes = $this->get_batch_types();
        $type = FormLib::get('btype');
        $ret = sprintf("<b>Batch Type</b>: %s <input type=hidden value=%d name=btype /><br />",
            isset($batchtypes[$type]) ? $batchtypes[$type] : 1, $type);
        $ret .= sprintf("<b>Batch Name</b>: %s <input type=hidden value=\"%s\" name=bname /><br />",
            FormLib::get('bname'),FormLib::get('bname'));
        $ret .= sprintf("<b>Owner</b>: %s <input type=hidden value=\"%s\" name=bowner /><br />",
            FormLib::get('bowner'),FormLib::get('bowner'));
        $ret .= sprintf("<b>Start Date</b>: %s <input type=hidden value=\"%s\" name=date1 /><br />",
            FormLib::get('date1'),FormLib::get('date1'));
        $ret .= sprintf("<b>End Date</b>: %s <input type=hidden value=\"%s\" name=date2 /><br />",
            FormLib::get('date2'),FormLib::get('date2'));
        $ret .= sprintf("<b>Product Identifier</b>: %s <input type=hidden value=\"%s\" name=ftype /><br />",
            FormLib::get('ftype'),FormLib::get('ftype'));
        $ret .= sprintf("<b>Includes check digits</b>: <input type=checkbox name=has_checks /><br />");
        $ret .= "<i>&nbsp;&nbsp;&nbsp;&nbsp;UPCs have check digits</i><br />";
        $ret .= "<br />";
        return $ret;
    }

    function form_content()
    {
        ob_start();
        ?>
        <div class="well">
        Use this tool to create a sales batch from an Excel file (XLS or CSV). Uploaded
        files should have a column identifying the product, either by UPC
        or likecode, and a column with prices.
        </div>
        <?php
        return ob_get_clean();
    }

    /**
      overriding the basic form since I need several extra fields   
    */
    protected function basicForm()
    {
        $batchtypes = $this->get_batch_types();
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $owners = new MasterSuperDeptsModel($dbc);
        ob_start();
        ?>
        <form enctype="multipart/form-data" action="XlsBatchPage.php" id="FannieUploadForm" method="post">
        <div class="row form-group form-horizontal">
            <label class="col-sm-2 control-label">Type</label>
            <div class="col-sm-4">
                <select name="btype" class="form-control">
                <?php foreach($batchtypes as $k=>$v) printf("<option value=%d>%s</option>",$k,$v); ?>
                </select>
            </div>
            <label class="col-sm-2 control-label">Start Date</label>
            <div class="col-sm-4">
                <input type="text" name="date1" id="date1" class="form-control date-field" />
            </div>
        </div>
        <div class="row form-group form-horizontal">
            <label class="col-sm-2 control-label">Name</label>
            <div class="col-sm-4">
                <input type="text" name="bname" class="form-control" />
            </div>
            <label class="col-sm-2 control-label">End Date</label>
            <div class="col-sm-4">
                <input type="text" name="date2" id="date2" class="form-control date-field" />
            </div>
        </div>
        <div class="row form-group form-horizontal">
            <label class="col-sm-2 control-label">File</label>
            <div class="col-sm-4">
                <input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
                <input type="file" id="FannieUploadFile" name="FannieUploadFile" />
            </div>
            <label class="col-sm-2 control-label">Owner</label>
            <div class="col-sm-4">
                <select name="bowner" class="form-control">
                <option value="">Choose...</option>
                <?php 
                $prev = '';
                foreach ($owners->find('super_name') as $obj) { 
                    if ($obj->super_name() == $prev) {
                        continue;
                    }
                    echo '<option>' . $obj->super_name() . '</option>';
                    $prev = $obj->super_name();
                }
                ?>
                </select>
            </div>
        </div>
        <div class="row form-group form-horizontal">
            <label class="col-sm-2 control-label">Type</label>
            <div class="col-sm-4">
                <select name="ftype" class="form-control" required>
                    <option value="">Select one...</option>
                    <option>UPCs</option>
                    <option>Likecodes</option>
                </select>
            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-default">Upload File</button>
            </div>
        </div>
        </form>
        <?php

        return ob_get_clean();
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->basicForm()));
        $phpunit->assertNotEquals(0, strlen($this->preview_content()));
        $this->results = 'foo';
        $phpunit->assertNotEquals(0, strlen($this->results_content()));
        $data = array('4011', 0.99);
        $indexes = array('upc_lc' => 0, 'price' => 1);
        $phpunit->assertEquals(true, $this->process_file(array($data), $indexes));
    }
}

FannieDispatch::conditionalExec();


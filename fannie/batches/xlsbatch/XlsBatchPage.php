<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

class XlsBatchPage extends \COREPOS\Fannie\API\FannieUploadPage {
    protected $title = "Fannie -  Sales Batch";
    protected $header = "Upload Batch file";

    public $description = '[Excel Batch] creates a sale or price change batch from a spreadsheet.';
    public $themed = true;

    protected $preview_opts = array(
        'upc_lc' => array(
            'name' => 'upc_lc',
            'display_name' => 'UPC/LC',
            'default' => 0,
            'required' => True
        ),
        'price' => array(
            'name' => 'price',
            'display_name' => 'Price',
            'default' => 1,
            'required' => True
        )
    );

    private $results = '';

    private function get_batch_types(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $batchtypes = array();
        $typesQ = $dbc->prepare_statement("select batchTypeID,typeDesc from batchType order by batchTypeID");
        $typesR = $dbc->exec_statement($typesQ);
        while ($typesW = $dbc->fetch_array($typesR))
            $batchtypes[$typesW[0]] = $typesW[1];
        return $batchtypes;
    }

    function process_file($linedata){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $upcCol = $this->get_column_index('upc_lc');
        $priceCol = $this->get_column_index('price');

        $btype = FormLib::get_form_value('btype',0);
        $date1 = FormLib::get_form_value('date1',date('Y-m-d'));
        $date2 = FormLib::get_form_value('date2',date('Y-m-d'));
        $bname = FormLib::get_form_value('bname','');
        $owner = FormLib::get_form_value('bowner','');
        $ftype = FormLib::get_form_value('ftype','UPCs');
        $has_checks = FormLib::get_form_value('has_checks') !== '' ? True : False;

        $dtQ = $dbc->prepare_statement("SELECT discType FROM batchType WHERE batchTypeID=?");
        $dt = array_pop($dbc->fetch_row($dbc->exec_statement($dtQ,array($btype))));

        $insQ = $dbc->prepare_statement("
            INSERT INTO batches 
            (startDate,endDate,batchName,batchType,discounttype,priority,owner)
            VALUES 
            (?,?,?,?,?,0,?)");
        $args = array($date1,$date2,$bname,$btype,$dt,$owner);
        $insR = $dbc->exec_statement($insQ,$args);
        $id = $dbc->insert_id();

        $upcChk = $dbc->prepare_statement("SELECT upc FROM products WHERE upc=?");

        $model = new BatchListModel($dbc);
        $model->batchID($id);
        $model->pricemethod(0);
        $model->quantity(0);
        $model->active(0);

        $ret = '';
        foreach($linedata as $line){
            if (!isset($line[$upcCol])) continue;
            if (!isset($line[$priceCol])) continue;
            $upc = $line[$upcCol];
            $price = $line[$priceCol];
            $upc = str_replace(" ","",$upc);    
            $upc = str_replace("-","",$upc);    
            $price = trim($price,' ');
            $price = trim($price,'$');
            if(!is_numeric($upc)){
                $ret .= "<i>Omitting item. Identifier {$upc} isn't a number</i><br />";
                continue; 
            }
            elseif(!is_numeric($price)){
                $ret .= "<i>Omitting item. Price {$price} isn't a number</i><br />";
                continue;
            }

            $upc = ($ftype=='UPCs') ? BarcodeLib::padUPC($upc) : 'LC'.$upc;
            if ($has_checks && $ftype=='UPCs')
                $upc = '0'.substr($upc,0,12);

            if ($ftype == 'UPCs'){
                $chkR = $dbc->exec_statement($upcChk, array($upc));
                if ($dbc->num_rows($chkR) ==  0) continue;
            }   

            $model->upc($upc);
            $model->salePrice($price);
            $model->save();
        }

        $ret .= "Batch created";
        $this->results = $ret;
        return True;
    }

    function results_content()
    {
        return $this->results;
    }

    function preview_content()
    {
        $batchtypes = $this->get_batch_types();
        $ret = sprintf("<b>Batch Type: %s <input type=hidden value=%d name=btype /><br />",
            $batchtypes[FormLib::get_form_value('btype')],FormLib::get_form_value('btype'));
        $ret .= sprintf("<b>Batch Name: %s <input type=hidden value=\"%s\" name=bname /><br />",
            FormLib::get_form_value('bname'),FormLib::get_form_value('bname'));
        $ret .= sprintf("<b>Owner: %s <input type=hidden value=\"%s\" name=bowner /><br />",
            FormLib::get_form_value('bowner'),FormLib::get_form_value('bowner'));
        $ret .= sprintf("<b>Start Date: %s <input type=hidden value=\"%s\" name=date1 /><br />",
            FormLib::get_form_value('date1'),FormLib::get_form_value('date1'));
        $ret .= sprintf("<b>End Date: %s <input type=hidden value=\"%s\" name=date2 /><br />",
            FormLib::get_form_value('date2'),FormLib::get_form_value('date2'));
        $ret .= sprintf("<b>Product Identifier</b>: %s <input type=hidden value=\"%s\" name=ftype /><br />",
            FormLib::get_form_value('ftype'),FormLib::get_form_value('ftype'));
        $ret .= sprintf("<b>Includes check digits</b>: <input type=checkbox name=has_checks /><br />");
        $ret .= "<i>&nbsp;&nbsp;&nbsp;&nbsp;UPCs have check digits</i><br />";
        $ret .= "<br />";
        return $ret;
    }

    function form_content(){
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
        global $FANNIE_URL;
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
                <select name="ftype" class="form-control">
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
}

FannieDispatch::conditionalExec(false);

?>

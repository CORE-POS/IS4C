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

class CoopDealsUploadPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    public $title = "Fannie - Co+op Deals sales";
    public $header = "Upload Co+op Deals file";

    public $description = '[Co+op Deals Import] loads sales information from Co+op Deals pricing spreadsheets.
    This data can be used to create sales batches.';
    public $themed = true;

    protected $preview_opts = array(
        'upc' => array(
            'name' => 'upc',
            'display_name' => 'UPC',
            'default' => 7,
            'required' => True
        ),
        'price' => array(
            'name' => 'price',
            'display_name' => 'Sale Price',
            'default' => 24,
            'required' => True
        ),
        'abt' => array(
            'name' => 'abt',
            'display_name' => 'A/B/TPR',
            'default' => 5,
            'required' => True
        ),
        'sku' => array(
            'name' => 'sku',
            'display_name' => 'SKU',
            'default' => 8,
            'required' => False
        ),
        'sub' => array(
            'name' => 'sub',
            'display_name' => 'Sub',
            'default' => 6,
            'required' => False
        )
    );

    function process_file($linedata){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        if ($dbc->table_exists('tempCapPrices')){
            $drop = $dbc->prepare_statement("DROP TABLE tempCapPrices");
            $dbc->exec_statement($drop);
        }
        $create = $dbc->prepare_statement("CREATE TABLE tempCapPrices (upc varchar(13), price decimal(10,2), abtpr varchar(3))");
        $dbc->exec_statement($create);

        $SUB = $this->get_column_index('sub');
        $UPC = $this->get_column_index('upc');
        $SKU = $this->get_column_index('sku');
        $PRICE = $this->get_column_index('price');
        $ABT = $this->get_column_index('abt');

        $rm_checks = (FormLib::get_form_value('rm_cds') != '') ? True : False;
        $upcP = $dbc->prepare_statement('SELECT upc FROM products WHERE upc=?');
        $skuP = $dbc->prepare_statement('
            SELECT s.upc 
            FROM vendorSKUtoPLU AS s
                INNER JOIN vendors AS v ON s.vendorID=v.vendorID
            WHERE s.sku=?
                AND v.vendorName LIKE \'%UNFI%\'');
        $insP = $dbc->prepare_statement('INSERT INTO tempCapPrices VALUES (?,?,?)');
        foreach($linedata as $data){
            if (!is_array($data)) continue;
            if (count($data) < 14) continue;

            $upc = str_replace("-","",$data[$UPC]);
            $upc = str_replace(" ","",$upc);
            if ($rm_checks)
                $upc = substr($upc,0,strlen($upc)-1);
            $upc = BarcodeLib::padUPC($upc);

            $lookup = $dbc->exec_statement($upcP, array($upc));
            if ($dbc->num_rows($lookup) == 0){
                if ($SUB === False) continue;
                if ($SKU === False) continue;
                if ($data[$SUB] != "BULK") continue;
                if ($data[$SKU] == "direct") continue;
                $sku = $data[$SKU];
                $look2 = $dbc->exec_statement($skuP, array($sku));
                if ($dbc->num_rows($look2) == 0) continue;
                $w = $dbc->fetch_row($look2);
                $upc = $w['upc'];
            }

            $price = trim($data[$PRICE],"\$");
            $abt = array();
            if (strstr($data[$ABT],"A"))
                $abt[] = "A";
            if (strstr($data[$ABT],"B"))
                $abt[] = "B";
            if (strstr($data[$ABT],"TPR"))
                $abt[] = "TPR";
            foreach($abt as $type){
                $dbc->exec_statement($insP,array($upc,$price,$type));
            }
        }

        return True;
    }

    function form_content(){
        return '<div class="well">Upload a CSV or Excel (XLS, not XLSX) file containing Co+op Deals
            Sale information. The file needs to contain UPCs, sale prices,
            and a column indicating A, B, or TPR (or some combination of the
            three).</div>';
    }

    function preview_content(){
        return '<input type="checkbox" name="rm_cds" checked /> Remove check digits';
    }

    function results_content(){
        $ret = "<p>Sales data import complete</p>";
        $ret .= "<p><a href=\"CoopDealsReviewPage.php\">Review data &amp; set up sales</a></p>";
        return $ret;
    }

    public function helpContent()
    {
        return '<p>Default column selections correspond to the
            tab/worksheet that lists all A, B, and TPR items</p>'
            . parent::helpContent();
    }
}

FannieDispatch::conditionalExec(false);


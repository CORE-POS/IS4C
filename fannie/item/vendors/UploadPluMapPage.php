<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

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
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class UploadPluMapPage extends FannieUploadPage {

    public $title = "Fannie - Load Vendor SKU/PLU mapping";
    public $header = "Upload Vendor SKU/PLU file";

    public $description = '[Vendor PLU Map] loads a list of vendor SKUs and the corresponding
    POS UPC used to sell the item. Typically these are things like bulk PLUs but any UPC is
    permitted.';

    protected $preview_opts = array(
        'sku' => array(
            'name' => 'sku',
            'display_name' => 'SKU',
            'default' => 0,
            'required' => True
        ),
        'plu' => array(
            'name' => 'plu',
            'display_name' => 'PLU',
            'default' => 1,
            'required' => True
        )
    );

    protected $use_splits = False;

    function process_file($linedata){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        if (!isset($_SESSION['vid'])){
            $this->error_details = 'Missing vendor setting';
            return False;
        }
        $VENDOR_ID = $_SESSION['vid'];

        $p = $dbc->prepare_statement("SELECT vendorID FROM vendors WHERE vendorID=?");
        $idR = $dbc->exec_statement($p,array($VENDOR_ID));
        if ($dbc->num_rows($idR) == 0){
            $this->error_details = 'Cannot find vendor';
            return False;
        }

        $SKU = $this->get_column_index('sku');
        $PLU = $this->get_column_index('plu');
        $mode = FormLib::get_form_value('map_mode',0);
        $REPLACE = ($mode === '1') ? True : False;

        if ($REPLACE){
            $delP = $dbc->prepare_statement('DELETE FROM vendorSKUtoPLU WHERE vendorID=?');
            $dbc->exec_statement($delP, array($VENDOR_ID));
        }

        $insP = $dbc->prepare_statement('INSERT INTO vendorSKUtoPLU (vendorID, sku, upc) VALUES (?,?,?)');
        $upP  = $dbc->prepare_statement('UPDATE vendorSKUtoPLU SET upc=? WHERE sku=? AND vendorID=?');
        $chkP = $dbc->prepare_statement('SELECT upc FROM vendorSKUtoPLU WHERE sku=? AND upc=? AND vendorID=?');
        $pluP = $dbc->prepare_statement('SELECT upc FROM vendorSKUtoPLU WHERE sku=? AND vendorID=?');

        foreach($linedata as $data){
            if (!is_array($data)) continue;

            if (!isset($data[$PLU])) continue;
            if (!isset($data[$SKU])) continue;

            // grab data from appropriate columns
            $sku = $data[$SKU];
            $plu = substr($data[$PLU],0,13);
            $plu = BarcodeLib::padUPC($plu);
            if (!is_numeric($plu)) continue;
    
            $chkR = $dbc->exec_statement($chkP, array($sku,$plu,$VENDOR_ID));
            if ($dbc->num_rows($chkR) > 0) continue; // entry exists

            $pluR = $dbc->exec_statement($chkP, array($sku,$plu,$VENDOR_ID));
            if ($dbc->num_rows($pluR) == 0){
                $dbc->exec_statement($insP, array($VENDOR_ID, $sku, $plu));
            }
            else {
                $dbc->exec_statement($upP, array($plu, $sku, $VENDOR_ID));
            }
        }

        // update vendorItems to use the new PLU mapping
        $resetP = $dbc->prepare_statement('UPDATE vendorItems AS i
                INNER JOIN vendorSKUtoPLU as s
                ON s.sku=i.sku AND s.vendorID=i.vendorID
                SET i.upc=s.upc
                WHERE i.vendorID=?');
        $dbc->exec_statement($resetP, array($VENDOR_ID));

        return True;
    }

    function preview_content(){
        return 'Mode <select name="map_mode"><option value="0">Update</option>
                <option value="1">Replace</option></select>';
    }

    function results_content(){
        $ret = "Mapping updated<p />";
        $ret .= '<a href="'.$_SERVER['PHP_SELF'].'">Upload Another</a>';
        unset($_SESSION['vid']);
        return $ret;
    }

    function form_content(){
        global $FANNIE_OP_DB;
        $vid = FormLib::get_form_value('vid');
        if ($vid === ''){
            $this->add_onload_command("\$('#FannieUploadForm').remove();");
            return '<span style="color:red;">Error: No Vendor Selected</span>';
        }
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $vp = $dbc->prepare_statement('SELECT vendorName FROM vendors WHERE vendorID=?');
        $vr = $dbc->exec_statement($vp,array($vid));
        if ($dbc->num_rows($vr)==0){
            $this->add_onload_command("\$('#FannieUploadForm').remove();");
            return '<span style="color:red;">Error: No Vendor Found</span>';
        }
        $vrow = $dbc->fetch_row($vr);
        $_SESSION['vid'] = $vid;
        return '<fieldset><legend>Instructions</legend>
            Upload a PLU and SKU file for <i>'.$vrow['vendorName'].'</i> ('.$vid.'). File
            can be CSV, XLS, or XLSX.</fieldset><br />';
    }

    public function preprocess()
    {
        if (php_sapi_name() !== 'cli') {
            /* this page requires a session to pass some extra
               state information through multiple requests */
            @session_start();
        }

        return parent::preprocess();
    }
}

FannieDispatch::conditionalExec(false);


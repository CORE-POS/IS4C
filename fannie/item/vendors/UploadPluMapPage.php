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
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class UploadPluMapPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    public $title = "Fannie - Load Vendor SKU/PLU mapping";
    public $header = "Upload Vendor SKU/PLU file";

    protected $must_authenticate = true;
    protected $auth_classes = array('pricechange');

    public $description = '[Vendor PLU Map] loads a list of vendor SKUs and the corresponding
    POS UPC used to sell the item. Typically these are things like bulk PLUs but any UPC is
    permitted.';
    public $themed = true;

    protected $preview_opts = array(
        'sku' => array(
            'display_name' => 'SKU',
            'default' => 0,
            'required' => true
        ),
        'plu' => array(
            'display_name' => 'PLU',
            'default' => 1,
            'required' => true
        )
    );

    protected $use_splits = false;

    private function prepStatements($dbc)
    {
        $this->insP = $dbc->prepare('INSERT INTO vendorSKUtoPLU (vendorID, sku, upc) VALUES (?,?,?)');
        $this->upP  = $dbc->prepare('UPDATE vendorSKUtoPLU SET upc=? WHERE sku=? AND vendorID=?');
        $this->chkP = $dbc->prepare('SELECT upc FROM vendorSKUtoPLU WHERE sku=? AND upc=? AND vendorID=?');
        $this->pluP = $dbc->prepare('SELECT upc FROM vendorSKUtoPLU WHERE sku=? AND vendorID=?');
    }

    private function sessionVendorID($dbc)
    {
        if (!isset($this->session->vid)){
            $this->error_details = 'Missing vendor setting';
            return false;
        }
        $VENDOR_ID = $this->session->vid;

        $idP = $dbc->prepare("SELECT vendorID FROM vendors WHERE vendorID=?");
        $idR = $dbc->execute($idP,array($VENDOR_ID));
        if ($dbc->num_rows($idR) == 0){
            $this->error_details = 'Cannot find vendor';
            return false;
        }

        return $VENDOR_ID;
    }

    private function getSkuPlu($data, $SKU, $PLU)
    {
        $sku = false;
        $plu = false;
        if (is_array($data) && isset($data[$SKU]) && isset($data[$PLU])) {
            // grab data from appropriate columns
            $sku = str_pad($data[$SKU], 7, "0", STR_PAD_LEFT);
            $plu = substr($data[$PLU],0,13);
            $plu = BarcodeLib::padUPC($plu);
            if (!is_numeric($plu)) { 
                $plu = false;
            }
        }

        return array($sku, $plu);
    }

    public function process_file($linedata, $indexes)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $VENDOR_ID = $this->sessionVendorID($dbc);
        if ($VENDOR_ID === false) {
            return false;
        }

        $this->prepStatements($dbc);

        $mode = FormLib::get_form_value('map_mode',0);
        $REPLACE = ($mode === '1') ? True : False;

        if ($REPLACE){
            $delP = $dbc->prepare('DELETE FROM vendorSKUtoPLU WHERE vendorID=?');
            $dbc->execute($delP, array($VENDOR_ID));
        }

        $this->stats = array('done' => 0, 'error' => array());
        foreach ($linedata as $data) {
            list($sku, $plu) = $this->getSkuPlu($data, $indexes['sku'], $indexes['plu']);
            if ($sku !== false && $plu !== false) {
                $chkR = $dbc->execute($this->chkP, array($sku,$plu,$VENDOR_ID));
                if ($dbc->num_rows($chkR) > 0) continue; // entry exists

                $pluR = $dbc->execute($this->pluP, array($sku,$VENDOR_ID));
                $success = false;
                if ($dbc->num_rows($pluR) == 0){
                    $success = $dbc->execute($this->insP, array($VENDOR_ID, $sku, $plu));
                } else {
                    $success = $dbc->execute($this->upP, array($plu, $sku, $VENDOR_ID));
                }

                if ($success) {
                    $this->stats['done']++;
                } else {
                    $this->stats['error'][] = 'Error updating SKU #' . $sku;
                }
            }
        }

        // update vendorItems to use the new PLU mapping
        $resetP = $dbc->prepare('UPDATE vendorItems AS i
                INNER JOIN vendorSKUtoPLU as s
                ON s.sku=i.sku AND s.vendorID=i.vendorID
                SET i.upc=s.upc
                WHERE i.vendorID=?');
        $dbc->execute($resetP, array($VENDOR_ID));

        return true;
    }

    /**
      This option is rather unsafe. Removing the existing map
      is probably a bad idea in most cases.
    function preview_content(){
        return 'Mode <select name="map_mode"><option value="0">Update</option>
                <option value="1">Replace</option></select>';
    }
    */

    function results_content()
    {
        unset($this->session->vid);
        return $this->simpleStats($this->stats, 'done');
    }

    function form_content()
    {
        global $FANNIE_OP_DB;
        $vid = FormLib::get_form_value('vid');
        if ($vid === ''){
            $this->add_onload_command("\$('#FannieUploadForm').remove();");
            return '<div class="alert alert-danger">Error: No Vendor Selected</div>';
        }
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $vendP = $dbc->prepare('SELECT vendorName FROM vendors WHERE vendorID=?');
        $vname = $dbc->getValue($vendP,array($vid));
        if (!$vname) {
            $this->add_onload_command("\$('#FannieUploadForm').remove();");
            return '<div class="alert alert-danger">Error: No Vendor Found</div>';
        }
        $this->session->vid = $vid;
        return '<div class="well"><legend>Instructions</legend>
            Upload a PLU and SKU file for <i>'.$vname.'</i> ('.$vid.'). File
            can be CSV, XLS, or XLSX.</div><br />';
    }

    public function preprocess()
    {
        if (php_sapi_name() !== 'cli' && !headers_sent() && session_id() === '') {
            /* this page requires a session to pass some extra
               state information through multiple requests */
            session_start();
        }

        return parent::preprocess();
    }
}

FannieDispatch::conditionalExec();


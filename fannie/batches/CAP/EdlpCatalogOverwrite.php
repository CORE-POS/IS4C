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

class EdlpCatalogOverwrite extends \COREPOS\Fannie\API\FannieUploadPage 
{
    public $title = "Fannie - EDLP Fix Prices";
    public $header = "Upload EDLP price adjustments";
    public $themed = true;

    public $description = '[EDLP Catalog Overwrite] imports a set of corrected costs
    and SRPs to replace what\'s found in the regular UNFI catalog.';

    protected $preview_opts = array(
        'upc' => array(
            'display_name' => 'UPC *',
            'default' => 8,
            'required' => true
        ),
        'srp' => array(
            'display_name' => 'SRP *',
            'default' => 37,
            'required' => True
        ),
        'sku' => array(
            'display_name' => 'SKU *',
            'default' => 9,
            'required' => true
        ),
        'unitCost' => array(
            'display_name' => 'Unit Cost *',
            'default' => 36,
            'required' => true
        ),
    );

    private function buildSkuMap($dbc, $VENDOR_ID)
    {
        // PLU items have different internal UPCs
        // map vendor SKUs to the internal PLUs
        $SKU_TO_PLU_MAP = array();
        $skusP = $dbc->prepare('SELECT sku, upc FROM VendorAliases WHERE vendorID=?');
        $skusR = $dbc->execute($skusP, array($VENDOR_ID));
        while($skusW = $dbc->fetch_row($skusR)) {
            if (!isset($SKU_TO_PLU_MAP[$skusW['sku']])) {
                $SKU_TO_PLU_MAP[$skusW['sku']] = array();
            }
            $SKU_TO_PLU_MAP[$skusW['sku']][] = $skusW['upc'];
        }

        return $SKU_TO_PLU_MAP;
    }

    private function prepareStatements($dbc)
    {
        $this->extraP = $dbc->prepare("update prodExtra set cost=?,variable_pricing=1 where upc=?");
        $this->prodP = $dbc->prepare('
            UPDATE products
            SET cost=?,
                modified=' . $dbc->now() . '
            WHERE upc=?
                AND default_vendor_id=?');
        $this->itemP = $dbc->prepare('
            UPDATE vendorItems
            SET cost=?,
                srp=?,
                modified=?
            WHERE sku=?
                AND vendorID=?');
        $this->srpP = false;
        if ($dbc->tableExists('vendorSRPs')) {
            $this->srpP = $dbc->prepare("UPDATE vendorSRPs SET srp=? WHERE upc=? AND vendorID=?");
        }
    }

    function process_file($linedata, $indexes)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $idP = $dbc->prepare("SELECT vendorID FROM vendors WHERE vendorName='UNFI' ORDER BY vendorID");
        $VENDOR_ID = $dbc->getValue($idP);
        if ($VENDOR_ID === false) {
            $this->error_details = 'Cannot find vendor';
            return False;
        }
        $this->prepareStatements($dbc);

        $SKU_TO_PLU_MAP = $this->buildSkuMap($dbc, $VENDOR_ID);
        $rm_checks = (FormLib::get('rm_cds') != '') ? true : false;

        $updated_upcs = array();

        foreach ($linedata as $data) {
            if (!is_array($data)) continue;

            if (!isset($data[$indexes['upc']])) continue;

            // grab data from appropriate columns
            $sku = ($indexes['sku'] !== false) ? $data[$indexes['sku']] : '';
            $sku = str_pad($sku, 7, '0', STR_PAD_LEFT);
            $upc = str_replace("-","",$data[$indexes['upc']]);
            $upc = str_replace(" ","",$upc);
            if ($rm_checks)
                $upc = substr($upc,0,strlen($upc)-1);
            $upc = BarcodeLib::padUPC($upc);
            // zeroes isn't a real item, skip it
            if ($upc == "0000000000000")
                continue;
            $aliases = array($upc);
            if (isset($SKU_TO_PLU_MAP[$sku])) {
                $aliases = array_merge($aliases, $SKU_TO_PLU_MAP[$sku]);
            }
            $reg = trim($data[$indexes['unitCost']]);
            $srp = trim($data[$indexes['srp']]);
            // can't process items w/o price (usually promos/samples anyway)
            if (empty($reg) or empty($srp))
                continue;

            // syntax fixes. kill apostrophes in text fields,
            // trim $ off amounts as well as commas for the
            // occasional > $1,000 item
            $reg = $this->sanitizePrice($reg);
            $srp = $this->sanitizePrice($srp);

            // skip the item if prices aren't numeric
            // this will catch the 'label' line in the first CSV split
            // since the splits get returned in file system order,
            // we can't be certain *when* that chunk will come up
            if (!is_numeric($reg) or !is_numeric($srp)) {
                continue;
            }

            foreach ($aliases as $alias) {
                $dbc->execute($this->extraP, array($reg,$alias));
                $dbc->execute($this->prodP, array($reg,$alias,$VENDOR_ID));
                $updated_upcs[] = $alias;
            }

            $args = array(
                $reg,
                $srp,
                date('Y-m-d H:i:s'),
                $sku,
                $VENDOR_ID,
            );
            $dbc->execute($this->itemP,$args);

            if ($this->srpP) {
                $dbc->execute($this->srpP,array($srp,$upc,$VENDOR_ID));
            }
        }

        $updateModel = new ProdUpdateModel($dbc);
        $updateModel->logManyUpdates($updated_upcs, ProdUpdateModel::UPDATE_EDIT);

        return true;
    }

    private function sanitizePrice($price)
    {
        $price = str_replace('$',"",$price);
        $price = str_replace(",","",$price);

        return $price;
    }

    function preview_content()
    {
        $ret = '<p><div class="form-inline">
            <label><input type="checkbox" name="rm_cds" /> Remove check digits</label>
            </div></p>
        ';

        return $ret;
    }

    function results_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $ret = "<p>Price data import complete</p>";
        $ret .= '<p><a href="'. filter_input(INPUT_SERVER, 'PHP_SELF') . '">Upload Another</a></p>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();


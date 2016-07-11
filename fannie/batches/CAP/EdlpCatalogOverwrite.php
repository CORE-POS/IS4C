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
            'default' => 14,
            'required' => True
        ),
        'srp' => array(
            'display_name' => 'SRP *',
            'default' => 16,
            'required' => True
        ),
        'sku' => array(
            'display_name' => 'SKU *',
            'default' => 1,
            'required' => true
        ),
        'qty' => array(
            'display_name' => 'Case Qty *',
            'default' => 3,
            'required' => True
        ),
        'cost' => array(
            'display_name' => 'Case Cost (Reg) *',
            'default' => 8,
            'required' => True
        ),
        'saleCost' => array(
            'display_name' => 'Case Cost (Sale)',
            'default' => 12,
            'required' => false
        ),
    );

    protected $use_splits = false;
    protected $use_js = true;

    private function buildSkuMap($dbc, $VENDOR_ID)
    {
        // PLU items have different internal UPCs
        // map vendor SKUs to the internal PLUs
        $SKU_TO_PLU_MAP = array();
        $skusP = $dbc->prepare('SELECT sku, upc FROM vendorSKUtoPLU WHERE vendorID=?');
        $skusR = $dbc->execute($skusP, array($VENDOR_ID));
        while($skusW = $dbc->fetch_row($skusR)) {
            $SKU_TO_PLU_MAP[$skusW['sku']] = $skusW['upc'];
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
                saleCost=?,
                srp=?,
                modified=?
            WHERE sku=?
                AND vendorID=?');
        $this->srpP = false;
        if ($dbc->tableExists('vendorSRPs')) {
            $this->srpP = $dbc->prepare("INSERT INTO vendorSRPs (vendorID, upc, srp) VALUES (?,?,?)");
        }
        $this->upcP = $dbc->prepare('SELECT price_rule_id FROM products WHERE upc=? AND inUse=1');
        $this->ruleP = $dbc->prepare('SELECT * FROM PriceRules WHERE priceRuleID=?');
        $this->ruleInsP = $dbc->prepare('
            INSERT INTO PriceRules 
                (priceRuleTypeID, maxPrice, reviewDate, details)
            VALUES 
                (?, ?, ?, ?)
        ');
        $this->ruleUpP = $dbc->prepare('
            UPDATE PriceRules
            SET priceRuleTypeID=?,
                maxPrice=?,
                reviewDate=?,
                details=?
            WHERE priceRuleID=?');
        $this->prodRuleP = $dbc->prepare('UPDATE products SET price_rule_id=? WHERE upc=?');
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

        $ruleType = FormLib::get('ruleType');
        $review = FormLib::get('reviewDate');

        $SKU_TO_PLU_MAP = $this->buildSkuMap($dbc, $VENDOR_ID);

        $updated_upcs = array();

        foreach ($linedata as $data) {
            if (!is_array($data)) continue;

            if (!isset($data[$indexes['upc']])) continue;

            // grab data from appropriate columns
            $sku = ($indexes['sku'] !== false) ? $data[$indexes['sku']] : '';
            $sku = str_pad($sku, 7, '0', STR_PAD_LEFT);
            $qty = $data[$indexes['qty']];
            $upc = substr($data[$indexes['upc']],0,13);
            // zeroes isn't a real item, skip it
            if ($upc == "0000000000000")
                continue;
            if (isset($SKU_TO_PLU_MAP[$sku])) {
                $upc = $SKU_TO_PLU_MAP[$sku];
            }
            $reg = trim($data[$indexes['cost']]);
            $net = ($indexes['saleCost'] !== false) ? trim($data[$indexes['saleCost']]) : 0.00;
            // blank spreadsheet cell
            if (empty($net)) {
                $net = 0;
            }
            $srp = trim($data[$indexes['srp']]);
            // can't process items w/o price (usually promos/samples anyway)
            if (empty($reg) or empty($srp))
                continue;

            // syntax fixes. kill apostrophes in text fields,
            // trim $ off amounts as well as commas for the
            // occasional > $1,000 item
            $reg = $this->sanitizePrice($reg);
            $net = $this->sanitizePrice($net);
            $srp = $this->sanitizePrice($srp);

            // sale price isn't really a discount
            if ($reg == $net) {
                $net = 0;
            }

            // skip the item if prices aren't numeric
            // this will catch the 'label' line in the first CSV split
            // since the splits get returned in file system order,
            // we can't be certain *when* that chunk will come up
            if (!is_numeric($reg) or !is_numeric($srp)) {
                continue;
            }

            // need unit cost, not case cost
            $reg_unit = $reg / $qty;
            $net_unit = $net / $qty;

            $dbc->execute($this->extraP, array($reg_unit,$upc));
            $dbc->execute($this->prodP, array($reg_unit,$upc,$VENDOR_ID));
            $updated_upcs[] = $upc;

            $args = array(
                $reg_unit,
                $net_unit,
                $srp,
                date('Y-m-d H:i:s'),
                $sku,
                $VENDOR_ID,
            );
            $dbc->execute($this->itemP,$args);

            if ($this->srpP) {
                $dbc->execute($this->srpP,array($VENDOR_ID,$upc,$srp));
            }
            $rule_id = $dbc->getValue($this->upcP, array($upc));
            $ruleR = $dbc->execute($this>ruleP, array($rule_id));
            if ($rule_id > 1 && $dbc->numRows($ruleR)) {
                // update existing rule with latest price
                $args = array($ruleType, $srp, $review, 'NCG MAX ' . $srp, $rule_id);
                $dbc->execute($this->ruleUpP, $args);
            } else {
                // create a new pricing rule
                // attach it to the item
                $args = array($ruleType, $srp, $review, 'NCG MAX ' . $srp);
                $dbc->execute($this->ruleInsP, $args);
                $rule_id = $dbc->insertID();
                $dbc->execute($this->prodRuleP, array($rule_id, $upc));
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
        $model = new PriceRuleTypesModel($this->connection);
        $ret = '<p><div class="form-inline">
            <label>Rule type</label>
            <select name="ruleType" class="form-control">
            ' . $model->toOptions() . '
            </select>
            <label>Review Date</label>
            <input type="text" class="form-control date-field" name="reviewDate" required />
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


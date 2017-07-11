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

class EdlpUploadPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    public $title = "Fannie - NCG EDLP Pricing";
    public $header = "Upload NCG EDLP file";

    public $description = '[NCG EDLP Import] imports maximum pricing information
    and attaches an appropriate pricing rule to the items.';
    public $themed = true;

    protected $preview_opts = array(
        'upc' => array(
            'display_name' => 'UPC*',
            'default' => 0,
            'required' => true
        ),
        'price' => array(
            'display_name' => 'Max Price*',
            'default' => 13,
            'required' => true
        ),
        'sku' => array(
            'display_name' => 'SKU',
            'default' => 3,
        ),
    );

    public function process_file($linedata, $indexes)
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $UPC = $this->get_column_index('upc');
        $SKU = $this->get_column_index('sku');
        $PRICE = $this->get_column_index('price');

        $rm_checks = (FormLib::get('rm_cds') != '') ? true : false;
        $ruleType = FormLib::get('ruleType');
        $review = FormLib::get('reviewDate');
        $upcP = $dbc->prepare('SELECT upc, price_rule_id FROM products WHERE upc=? AND inUse=1');
        $ruleP = $dbc->prepare('SELECT * FROM PriceRules WHERE priceRuleID=?');
        $skuP = $dbc->prepare('
            SELECT s.upc,
                p.price_rule_id
            FROM VendorAliases AS s
                INNER JOIN vendors AS v ON s.vendorID=v.vendorID
                ' . DTrans::joinProducts('s', 'p', 'INNER') . '
            WHERE s.sku=?
                AND v.vendorName LIKE \'%UNFI%\'');
        $insP = $dbc->prepare('
            INSERT INTO PriceRules 
                (priceRuleTypeID, maxPrice, reviewDate, details)
            VALUES 
                (?, ?, ?, ?)
        ');
        $upP = $dbc->prepare('
            UPDATE PriceRules
            SET priceRuleTypeID=?,
                maxPrice=?,
                reviewDate=?,
                details=?
            WHERE priceRuleID=?');
        $extraP = $dbc->prepare('UPDATE prodExtra SET variable_pricing=1 WHERE upc=?');
        $prodP = $dbc->prepare('UPDATE products SET price_rule_id=? WHERE upc=?');
        $dbc->startTransaction();
        foreach ($linedata as $data) {
            if (!is_array($data)) continue;

            $upc = str_replace("-","",$data[$UPC]);
            $upc = str_replace(" ","",$upc);
            if ($rm_checks) {
                $upc = substr($upc,0,strlen($upc)-1);
            }
            $upc = BarcodeLib::padUPC($upc);
            $rule_id = 0;

            // try to find item by SKU if not in products
            $lookup = $dbc->execute($upcP, array($upc));
            if ($dbc->numRows($lookup) == 0 && $SKU !== false) {
                $sku = str_replace('-', '', $data[$SKU]);
                $found = false;
                $look2 = $dbc->execute($skuP, array($sku));
                if ($dbc->numRows($look2)) {
                    $w = $dbc->fetchRow($look2);
                    $upc = $w['upc'];
                    $rule_id = $w['price_rule_id'];
                    $found = true;
                }
                $sku = str_pad($sku, 7, '0', STR_PAD_LEFT);
                $look3 = $dbc->execute($skuP, array($sku));
                if ($dbc->numRows($look3)) {
                    $w = $dbc->fetchRow($look3);
                    $upc = $w['upc'];
                    $rule_id = $w['price_rule_id'];
                    $found = true;
                }

                if (!$found) {
                    continue;
                }
            } else {
                $w = $dbc->fetchRow($lookup);
                $rule_id = $w['price_rule_id'];
            }

            $price = trim($data[$PRICE],"\$ ");
            if (strstr($price, '-')) { // pull the max from a range of prices
                list($garbage, $price) = explode('-', $price, 2);
                $price = trim($price);
            }
            if (!is_numeric($price)) {
                continue;
            }
            $ruleR = $dbc->execute($ruleP, array($rule_id));
            if ($rule_id > 1 && $dbc->numRows($ruleR)) {
                // update existing rule with latest price
                $args = array($ruleType, $price, $review, 'NCG MAX ' . $price, $rule_id);
                $dbc->execute($upP, $args);
                $dbc->execute($extraP, array($upc));
            } else {
                // create a new pricing rule
                // attach it to the item
                $args = array($ruleType, $price, $review, 'NCG MAX ' . $price);
                $dbc->execute($insP, $args);
                $rule_id = $dbc->insertID();
                $dbc->execute($extraP, array($upc));
                $dbc->execute($prodP, array($rule_id, $upc));
            }
        }
        $dbc->commitTransaction();

        return true;
    }

    public function form_content()
    {
        return '<div class="well">Upload a CSV or Excel (XLS, not XLSX) file containing 
            UPCs and designated maximum pricing.</div>';
    }

    public function preview_content()
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

    public function results_content()
    {
        $ret = "<p>Import complete</p>";
        $ret .= '<p>
            <a href="EdlpBatchPage.php" class="btn btn-default">Create Price Change Batch</a>
            </p>';

        return $ret;
    }

    public function helpContent()
    {
        return '
            <p>Upload a spreadsheet containing UPCs and maximum prices.
            SKUs may optionally be included as well for items mapped to
            a different UPC/PLU. This tool will create or update the 
            pricing rule associated with each of these items. On the 
            column selection page you must specify a rule type and
            a review date for the rule.</p>
            <p>Default column selections correspond to the
            Field Day spreadsheet and chooses the "East" data.</p>'
            . parent::helpContent();
    }
}

FannieDispatch::conditionalExec();


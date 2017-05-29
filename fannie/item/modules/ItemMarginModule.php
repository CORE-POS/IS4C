<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

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

use \COREPOS\Fannie\API\item\Margin;
use \COREPOS\Fannie\API\item\PriceRounder;
use \COREPOS\Fannie\API\lib\Store;

if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__).'/../../classlib2.0/FannieAPI.php');
}

class ItemMarginModule extends \COREPOS\Fannie\API\item\ItemModule 
{
    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $db = $this->db();
        $product = new ProductsModel($db);
        $product->upc($upc);
        if (!$product->load()) {
            /**
              Lookup vendor cost on new items
            */
            $vendor = new VendorItemsModel($db);
            $vendor->upc($upc);
            foreach ($vendor->find('vendorID') as $v) {
                $product->cost($v->cost());
                break;
            }
        }
        $ret = '<div id="ItemMarginFieldset" class="panel panel-default">';
        $ret .=  "<div class=\"panel-heading\">
                <a href=\"\" onclick=\"\$('#ItemMarginContents').toggle();return false;\">
                Margin
                </a></div>";
        $css = ($expand_mode == 1) ? '' : ' collapse';
        $ret .= '<div id="ItemMarginContents" class="panel-body' . $css . '">';
        $ret .= '<div class="col-sm-5">';
        $ret .= '<div id="ItemMarginMeter">'; 
        $ret .= $this->calculateMargin($product->normal_price(),$product->cost(),$product->department(), $upc);
        $ret .= '</div>';
        $ret .= '</div>';
        $ret .= '<div class="col-sm-6">';
        $ret .= '<div class="form-group form-inline">
                    <label>Pricing Rule</label>
                    <select name="price_rule_id" class="form-control input-sm"
                        onchange="if(this.value>=0)$(\'#custom-pricing-fields :input\').prop(\'disabled\', true);
                        else $(\'#custom-pricing-fields :input\').prop(\'disabled\', false);">
                        <option value="0" ' . ($product->price_rule_id() == 0 ? 'selected' : '') . '>Normal</option>
                        <option value="1" ' . ($product->price_rule_id() == 1 ? 'selected' : '') . '>Variable</option>
                        <option value="-1" ' . ($product->price_rule_id() > 1 ? 'selected' : '') . '>Custom</option>
                    </select>
                    <input type="hidden" name="current_price_rule_id" value="' . $product->price_rule_id() . '" />
                    &nbsp;
                    <label>Avg. Daily Movement</label> ' . sprintf('%.2f', $this->avgSales($upc)) . '
                 </div>';
        $rule = new PriceRulesModel($db);
        if ($product->price_rule_id() > 1) {
            $rule->priceRuleID($product->price_rule_id());
            $rule->load();
        }
        $disabled = $product->price_rule_id() <= 1 ? 'disabled' : '';
        $ret .= '<div id="custom-pricing-fields" class="form-group form-inline">
                    <label>Custom</label>
                    <select ' . $disabled . ' name="price_rule_type" class="form-control input-sm">
                    {{RULE_TYPES}}
                    </select>
                    <input type="text" class="form-control date-field input-sm" name="rule_review_date"
                        ' . $disabled . ' placeholder="Review Date" title="Review Date" value="{{REVIEW_DATE}}" />
                    <input type="text" class="form-control input-sm" name="rule_details"
                        ' . $disabled . ' placeholder="Details" title="Details" value="{{RULE_DETAILS}}" />
                 </div>';
        $types = new PriceRuleTypesModel($db);
        $ret = str_replace('{{RULE_TYPES}}', $types->toOptions($rule->priceRuleTypeID()), $ret);
        $ret = str_replace('{{REVIEW_DATE}}', $rule->reviewDate(), $ret);
        $ret = str_replace('{{RULE_DETAILS}}', $rule->details(), $ret);
        $ret .= '</div>';
        $ret .= '</div>';
        $ret .= '</div>';

        return $ret;
    }

    public function avgSales($upc)
    {
        $config = FannieConfig::factory();
        $dbc = FannieDB::get($config->get('OP_DB'));
        $store = Store::getIdByIp();
        $prodP = $dbc->prepare('SELECT auto_par FROM products WHERE upc=? AND store_id=?');
        $avg = $dbc->getValue($prodP, array($upc, $store));
        if ($avg) {
            return $avg;
        }
        $dbc = FannieDB::get($config->get('ARCHIVE_DB'));
        $avg = 0.0;
        $store = Store::getIdByIp();
        if ($dbc->tableExists('productWeeklyLastQuarter')) {
            $maxP = $dbc->prepare('SELECT MAX(weekLastQuarterID) FROM productWeeklyLastQuarter WHERE upc=?'); 
            $maxR = $dbc->execute($maxP, $upc);
            if ($maxR && $dbc->numRows($maxR)) {
                $maxW = $dbc->fetchRow($maxR);
                $avgP = $dbc->prepare('
                    SELECT SUM((?-weekLastQuarterID)*quantity) / SUM(weekLastQuarterID)
                    FROM productWeeklyLastQuarter
                    WHERE upc=?
                        AND storeID=?');
                $avgR = $dbc->execute($avgP, array($maxW[0], $upc, $store));
                $avgW = $dbc->fetchRow($avgR);
                $avg = $avgW[0] / 7.0;
            }
        } else {
            $dbc = FannieDB::get($config->get('TRANS_DB'));
            $avgP = $dbc->prepare('
                SELECT MIN(tdate) AS min,
                    MAX(tdate) AS max,
                    ' . DTrans::sumQuantity() . ' AS qty
                FROM dlog_90_view
                WHERE upc=?
                    AND store_id=?');
            $avgR = $dbc->execute($avgP, array($upc, $store));
            if ($avgR && $dbc->numRows($avgR)) {
                $avgW = $dbc->fetchRow($avgR);
                $d1 = new DateTime($avgW['max']);
                $d2 = new DateTime($avgW['min']);
                $num_days = $d1->diff($d2)->format('%a') + 1;
                $avg = $avgW['qty'] / $num_days;
            }
        }
        // put the database back where we found it (probably)
        $dbc = FannieDB::get($config->get('OP_DB'));

        return $avg;
    }

    public function saveFormData($upc)
    {
        $dbc = $this->db();
        try {
            $new_rule = $this->form->price_rule_id;
            $old_rule = $this->form->current_price_rule_id;
        } catch (Exception $ex) {
            $new_rule = 0;
            $old_rule = 0;
        }

        if ($new_rule != $old_rule) {
            $prod = new ProductsModel($dbc);
            $prod->upc(BarcodeLib::padUPC($upc));
            $rule = new PriceRulesModel($dbc);
            switch ($new_rule) {
                case 0: // no custom rule
                case 1: // generic variable pricing
                    /**
                      Update the product with the generic rule ID
                      If it was previously set to a custom rule,
                      that custom rule can be deleted
                    */
                    $prod->price_rule_id($new_rule);
                    if ($old_rule > 1) {
                        $rule->priceRuleID($old_rule);
                        $rule->delete();
                    }
                    break;
                default: // custom rule
                    /**
                      If the product is already using a custom rule,
                      just update that rule record. Otherwise create
                      a new one.
                    */
                    try {
                        $rule->reviewDate($this->form->rule_review_date);
                        $rule->details($this->form->rule_details);
                        $rule->priceRuleTypeID($this->form->price_rule_type);
                    } catch (Exception $ex) {}
                    if ($old_rule > 1) {
                        $rule->priceRuleID($old_rule);
                        $prod->price_rule_id($old_rule); // just in case
                        $rule->save();
                    } else {
                        $new_rule_id = $rule->save();
                        $prod->price_rule_id($new_rule_id);
                    }
            }
            $prod->enableLogging(false);
            if (FannieConfig::config('STORE_MODE') === 'HQ') {
                $res = $dbc->query('SELECT storeID FROM Stores WHERE hasOwnItems=1');
                while ($row = $dbc->fetchRow($res)) {
                    $prod->store_id($row['storeID']);
                    $prod->save();
                }
            } else {
                $prod->save();
            }
        }

        return true;
    }

    private function getSRP($cost,$margin)
    {
        $srp = Margin::toPrice($cost, $margin);
        $pr = new PriceRounder();

        return $pr->round($srp);
    }

    private function calculateMargin($price, $cost, $deptID, $upc)
    {
        $dbc = $this->db();

        $desired_margin = 'Unknown';
        $dept = new DepartmentsModel($dbc);
        $dept->dept_no($deptID);
        $dept_name = 'n/a';
        if ($dept->load()) {
            $desired_margin = $dept->margin() * 100;
            $dept_name = $dept->dept_name();
        }

        $ret = "Desired margin on this department (" . $dept_name . ") is " . $desired_margin . "%";
        $ret .= "<br />";

        $vendorP = $dbc->prepare('
            SELECT d.margin,
                n.vendorName,
                d.name,
                s.margin AS specialMargin
            FROM products AS p
                INNER JOIN vendorItems AS v ON p.upc=v.upc AND v.vendorID=p.default_vendor_id
                INNER JOIN vendorDepartments AS d ON v.vendorID=d.vendorID AND v.vendorDept=d.deptID
                INNER JOIN vendors AS n ON v.vendorID=n.vendorID
                LEFT JOIN VendorSpecificMargins AS s ON p.department=s.deptID AND p.default_vendor_id=s.vendorID
            WHERE p.upc = ?
        ');
        $vendorR = $dbc->execute($vendorP, array($upc));
        if ($vendorR && $dbc->num_rows($vendorR) > 0) {
            $w = $dbc->fetch_row($vendorR);
            $desired_margin = $w['margin'] * 100;
            if ($w['specialMargin']) {
                $desired_margin = 100* $w['specialMargin'];
                $w['name'] = 'Custom';
            }
            $ret .= sprintf('Desired margin for this vendor category (%s) is %.2f%%<br />',
                    $w['vendorName'] . ':' . $w['name'],
                    $desired_margin);
        }

        $shippingP = $dbc->prepare('
            SELECT v.shippingMarkup,
                v.discountRate,
                v.vendorName
            FROM products AS p
                INNER JOIN vendors AS v ON p.default_vendor_id = v.vendorID
            WHERE p.upc=?');
        $shippingR = $dbc->execute($shippingP, array($upc));
        $shipping_markup = 0.0;
        $vendor_discount = 0.0;
        if ($shippingR && $dbc->numRows($shippingR) > 0) {
            $w = $dbc->fetchRow($shippingR);
            if ($w['discountRate'] > 0) {
                $ret .= sprintf('Discount rate for this vendor (%s) is %.2f%%<br />',
                        $w['vendorName'],
                        ($w['discountRate']*100));
                $vendor_discount = $w['discountRate'];
            }
            if ($w['shippingMarkup'] > 0) {
                $ret .= sprintf('Shipping markup for this vendor (%s) is %.2f%%<br />',
                        $w['vendorName'],
                        ($w['shippingMarkup']*100));
                $shipping_markup = $w['shippingMarkup'];
            }
        }
        $cost = Margin::adjustedCost($cost, $vendor_discount, $shipping_markup);
        $actual_margin = Margin::toMargin($cost, $price, array(100,2));
        
        if ($actual_margin > $desired_margin && is_numeric($desired_margin)){
            $ret .= sprintf("<span class=\"alert-success\">Current margin on this item is %.2f%%</span><br />",
                $actual_margin);
        } elseif (!is_numeric($price)) {
            $ret .= "<span class=\"alert-danger\">No price has been saved for this item</span><br />";
        } else {
            $ret .= sprintf("<span class=\"alert-danger\">Current margin on this item is %.2f%%</span><br />",
                $actual_margin);
            $srp = $this->getSRP($cost, $desired_margin/100.0);
            $ret .= sprintf("Suggested price: \$%.2f ",$srp);
            $ret .= sprintf("(<a href=\"\" onclick=\"\$('.price-input').val(%.2f); \$('.price-input:visible').trigger('change'); return false;\">Use this price</a>)",$srp);
        }

        return $ret;
    }

    public function getFormJavascript($upc)
    {
        ob_start();
        ?>
        function updateMarginMod(){
            var store_id = $(this).data('store-id');
            $.ajax({
                url: '<?php echo FannieConfig::config('URL'); ?>item/modules/ItemMarginModule.php',
                data: 'p='+$('#price'+store_id).val()+'&d='+$('#department'+store_id).val()+'&c='+$('#cost'+store_id).val()+'&u=<?php echo $upc; ?>',
                cache: false
            }).done(function(data){
                $('#ItemMarginMeter').html(data);
            });
        }
        function nosubmit(event)
        {
            if (event.which == 13) {
                event.preventDefault();
                event.stopPropagation();
                updateMarginMod();
                return false;
            }
        }
        <?php
        return ob_get_clean();
    }

    function AjaxCallback(){
        $p = FormLib::get_form_value('p',0);
        $d = FormLib::get_form_value('d',0);
        $c = FormLib::get_form_value('c',0);
        $u = BarcodeLib::padUPC(FormLib::get('u'));
        echo $this->calculateMargin($p, $c, $d, $u);
    }
}

/**
  This form does some fancy tricks via AJAX calls. This block
  ensures the AJAX functionality only runs when the script
  is accessed via the browser and not when it's included in
  another PHP script.
*/
if (basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__)){
    $obj = new ItemMarginModule();
    $obj->AjaxCallback();   
}

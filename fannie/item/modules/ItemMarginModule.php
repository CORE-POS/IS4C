<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

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

if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__).'/../../classlib2.0/FannieAPI.php');
}

class ItemMarginModule extends ItemModule 
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
        $ret .= '<div id="ItemMarginMeter" class="col-sm-5">';
        $ret .= $this->calculateMargin($product->normal_price(),$product->cost(),$product->department(), $upc);
        $ret .= '</div>';
        $ret .= '</div>';
        $ret .= '</div>';

        return $ret;
    }

    private function getSRP($cost,$margin){
        $srp = sprintf("%.2f",$cost/(1-$margin));
        while (substr($srp,strlen($srp)-1,strlen($srp)) != "5" &&
               substr($srp,strlen($srp)-1,strlen($srp)) != "9")
            $srp += 0.01;
        return $srp;
    }

    private function calculateMargin($price, $cost, $deptID, $upc)
    {
        $dbc = $this->db();

        $dm = 'Unknown';
        $dept = new DepartmentsModel($dbc);
        $dept->dept_no($deptID);
        if ($dept->load()) {
            $dm = $dept->margin() * 100;
        }

        if ((empty($dm) || $dm == 'Unknown') && $dbc->tableExists('deptMargin')) {
            $dmP = $dbc->prepare_statement("SELECT margin FROM deptMargin WHERE dept_ID=?");
            $dmR = $dbc->exec_statement($dmP,array($deptID));
            if ($dbc->num_rows($dmR) > 0){
                $row = $dbc->fetch_row($dmR);
                $dm = sprintf('%.2f',$row['margin']*100);
            }
        }

        $ret = "Desired margin on this department is " . $dm . "%";
        $ret .= "<br />";

        $vendorP = $dbc->prepare('
            SELECT d.margin,
                n.vendorName
            FROM products AS p
                INNER JOIN vendorItems AS v ON p.upc=v.upc AND v.vendorID=p.default_vendor_id
                INNER JOIN vendorDepartments AS d ON v.vendorID=d.vendorID AND v.vendorDept=d.deptID
                INNER JOIN vendors AS n ON v.vendorID=n.vendorID
            WHERE p.upc = ?
        ');
        $vendorR = $dbc->execute($vendorP, array($upc));
        if ($vendorR && $dbc->num_rows($vendorR) > 0) {
            $w = $dbc->fetch_row($vendorR);
            $dm = $w['margin'] * 100;
            $ret .= sprintf('Desired margin for this vendor category (%s) is %.2f%%<br />',
                    $w['vendorName'],
                    $dm);
        }

        $shippingP = $dbc->prepare('
            SELECT v.shippingMarkup,
                v.vendorName
            FROM products AS p
                INNER JOIN vendors AS v ON p.default_vendor_id = v.vendorID
            WHERE p.upc=?');
        $shippingR = $dbc->execute($shippingP, array($upc));
        if ($shippingR && $dbc->num_rows($shippingR) > 0) {
            $w = $dbc->fetch_row($shippingR);
            $ret .= sprintf('Shipping markup for this vendor (%s) is %.2f%%<br />',
                    $w['vendorName'],
                    ($w['shippingMarkup']*100));
            $cost = $cost * (1+$w['shippingMarkup']);
        }
        
        $actual = 0;
        if ($price != 0)
            $actual = (($price-$cost)/$price)*100;
        if ($actual > $dm && is_numeric($dm)){
            $ret .= sprintf("<span class=\"alert-success\">Current margin on this item is %.2f%%</span><br />",
                $actual);
        } elseif (!is_numeric($price)) {
            $ret .= "<span class=\"alert-success\">No price has been saved for this item</span><br />";
        } else {
            $ret .= sprintf("<span class=\"alert-danger\">Current margin on this item is %.2f%%</span><br />",
                $actual);
            $srp = $this->getSRP($cost,$dm/100.0);
            $ret .= sprintf("Suggested price: \$%.2f ",$srp);
            $ret .= sprintf("(<a href=\"\" onclick=\"\$('#price').val(%.2f); updateMarginMod(); return false;\">Use this price</a>)",$srp);
        }

        return $ret;
    }

    public function getFormJavascript($upc)
    {
        ob_start();
        ?>
        function updateMarginMod(){
            $.ajax({
                url: '<?php echo FannieConfig::config('URL'); ?>item/modules/ItemMarginModule.php',
                data: 'p='+$('#price').val()+'&d='+$('#department').val()+'&c='+$('#cost').val()+'&u=<?php echo $upc; ?>',
                cache: false,
                success: function(data){
                    $('#ItemMarginMeter').html(data);
                }
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

    function SaveFormData($upc)
    {
        $upc = BarcodeLib::padUPC($upc);
        $cost = FormLib::get_form_value('cost',0.00);
        $dbc = $this->db();

        $r2 = true;
        if ($dbc->tableExists('prodExtra')) {
            $p = $dbc->prepare_statement('UPDATE prodExtra SET cost=? WHERE upc=?');
            $r2 = $dbc->exec_statement($p,array($cost,$upc));
        }

        if (!$r2) {
            return false;
        } else {
            return true;    
        }
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

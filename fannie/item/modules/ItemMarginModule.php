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

include_once(dirname(__FILE__).'/../../config.php');
include_once(dirname(__FILE__).'/../../classlib2.0/FannieAPI.php');

class ItemMarginModule extends ItemModule {
    
    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $db = $this->db();
        $product = new ProductsModel($db);
        $product->upc($upc);
        $product->load();
        $ret = '<fieldset id="ItemMarginFieldset">';
        $ret .=  "<legend onclick=\"\$('#ItemMarginContents').toggle();\">
                <a href=\"\" onclick=\"return false;\">Margin</a>
                </legend>";
        $css = ($expand_mode == 1) ? '' : 'display:none;';
        $ret .= '<div id="ItemMarginContents" style="' . $css . '">';
        $ret .= '<div id="ItemMarginMeter" style="float:left;">';
        $ret .= $this->calculateMargin($product->normal_price(),$product->cost(),$product->department(), $upc);
        $ret .= '</div>';
        $ret .= '<div id="ItemMarginFields" style="float:left;margin-left:2em;">';
        $ret .= '<label for="cost" style="font-weight:bold;">Unit Cost</label>: ';
        $ret .= sprintf('$<input type="text" size="6" value="%.2f" name="cost" id="cost" /> ', $product->cost());
        $ret .= FannieHelp::ToolTip('Cost from current vendor');
        $ret .= '</div>';
        $ret .= '</div>';
        $ret .= '</fieldset>';

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
        
        $actual = 0;
        if ($price != 0)
            $actual = (($price-$cost)/$price)*100;
        if ($actual > $dm && is_numeric($dm)){
            $ret .= sprintf("<span style=\"color:green;\">Current margin on this item is %.2f%%</span><br />",
                $actual);
        } elseif (!is_numeric($price)) {
            $ret .= "<span style=\"color:green;\">No price has been saved for this item</span><br />";
        } else {
            $ret .= sprintf("<span style=\"color:red;\">Current margin on this item is %.2f%%</span><br />",
                $actual);
            $srp = $this->getSRP($cost,$dm/100.0);
            $ret .= sprintf("Suggested price: \$%.2f ",$srp);
            $ret .= sprintf("(<a href=\"\" onclick=\"\$('#price').val(%.2f); updateMarginMod(); return false;\">Use this price</a>)",$srp);
        }

        return $ret;
    }

    public function getFormJavascript($upc)
    {
        global $FANNIE_URL;
        ob_start();
        ?>
        function updateMarginMod(){
            $('.default_vendor_cost').val($('#cost').val());
            $.ajax({
                url: '<?php echo $FANNIE_URL; ?>item/modules/ItemMarginModule.php',
                data: 'p='+$('#price').val()+'&d='+$('#department').val()+'&c='+$('#cost').val()+'&u=<?php echo $upc; ?>',
                cache: false,
                success: function(data){
                    $('#ItemMarginMeter').html(data);
                }
            });
        }
        $('#price').change(updateMarginMod);
        $('#cost').change(updateMarginMod);
        <?php
        return ob_get_clean();
    }

    function SaveFormData($upc)
    {
        $upc = BarcodeLib::padUPC($upc);
        $cost = FormLib::get_form_value('cost',0.00);

        $dbc = $this->db();
        $pm = new ProductsModel($dbc);
        $pm->upc($upc);
        $pm->cost($cost);
        $r1 = $pm->save();

        if ($dbc->tableExists('prodExtra')) {
            $p = $dbc->prepare_statement('UPDATE prodExtra SET cost=? WHERE upc=?');
            $r2 = $dbc->exec_statement($p,array($cost,$upc));
        }

        if ($r1 === false || $r2 === false) {
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

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

if (!class_exists('FannieAPI'))
    include_once(dirname(__FILE__).'/../../classlib2.0/FannieAPI.php');

class VendorItemModule extends ItemModule {

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        global $FANNIE_CSS_PRIMARY_COLOR;
        $upc = BarcodeLib::padUPC($upc);

        $ret = '<fieldset id="VendorItemsFieldset">';
        $ret .=  "<legend onclick=\"\$('#VendorItemsFieldsetContent').toggle();\">
                <a href=\"\" onclick=\"return false;\">Vendor Items</a>
                </legend>";
        $css = ($expand_mode == 1) ? '' : 'display:none;';
        $ret .= '<div id="VendorItemsFieldsetContent" style="' . $css . '">';

        $dbc = $this->db();
        $p = $dbc->prepare_statement('SELECT vendorID,vendorName FROM vendors ORDER BY vendorName');
        $r = $dbc->exec_statement($p);
        if ($dbc->num_rows($r) == 0) return ''; // no vendors available
        $vendors = array();
        while ($w = $dbc->fetch_row($r)) {
            $vendors[$w['vendorID']] = $w['vendorName'];
        }

        $product = new ProductsModel($dbc);
        $product->upc($upc);
        $product->load();
        $my_vendor = $product->default_vendor_id();
        $matched = false;
        $hilite = 'style="color:' . $FANNIE_CSS_PRIMARY_COLOR . ';"';

        $ret .= '<select onchange="$(\'.vtable\').hide();$(\'#vtable\'+this.value).show();">';
        foreach ($vendors as $id => $name) {
            $ret .= sprintf('<option %s value="%d">%s%s</option>',
                        ($my_vendor == $id ? 'selected ' . $hilite : ''),
                        $id,
                        $name,
                        ($my_vendor == $id ? ' [current]': '')
            );
            if ($my_vendor == $id) {
                $matched = true;
            }
        }
        $ret .= '</select>';

        $prep = $dbc->prepare_statement('SELECT * FROM vendorItems WHERE vendorID=? AND upc=?');
        $style = ($matched) ? 'display:none;' : 'display:table;';
        $cost_class = '';
        foreach ($vendors as $id => $name) {
            if ($matched && $id == $my_vendor) {
                $style = 'display:table;';
                $cost_class = 'class="default_vendor_cost"';
            }
            $ret .= "<table style=\"margin-top:5px;margin-bottom:5px;$style\" 
                    border=1 id=\"vtable$id\"
                    cellpadding=5 cellspacing=0 class=\"vtable\">";
            $row = array('cost'=>0,'sku'=>'','units'=>1);
            $res = $dbc->exec_statement($prep,array($id,$upc)); 
            if ($dbc->num_rows($res) > 0)
                $row = $dbc->fetch_row($res);
            $ret .= '<tr><th>SKU</th><td><input type="text" size="8" name="v_sku[]"
                    value="'.$row['sku'].'" /></td>';
            $ret .= sprintf('<th>Unit Cost</th><td>$<input type="text" size="6"
                    name="v_cost[]" id="vcost%d" %s value="%.2f" onchange="vprice(%d);" /></td></tr>',
                    $id, $cost_class, $row['cost'], $id);
            $ret .= '<tr><th>Units/Case</th><td><input type="text" size="4" name="v_units[]"
                    id="vunits'.$id.'" value="'.$row['units'].'" 
                    onchange="vprice('.$id.');" /></td>';
            $ret .= sprintf('<th>Case Cost</th><td id="vcc%d">$%.2f</td></tr>',
                    $id, ($row['units']*$row['cost']));
            $ret .= '<input type="hidden" name="v_id[]" value="'.$id.'" />';
            
            $ret .= '</table>';

            $style = 'display:none;';
        }
        
        $ret .= '</div>';
        $ret .= '</fieldset>';
        return $ret;
    }

    public function getFormJavascript($upc)
    {
        return "
            function vprice(id){
                var cost = \$('#vcost'+id).val();
                var units = \$('#vunits'+id).val();
                \$('#vcc'+id).html('\$'+(cost*units));
                if (\$('#vcost'+id).hasClass('default_vendor_cost')) {
                    \$('#cost').val(\$('#vcost'+id).val());
                    \$('#cost').trigger('change');
                }
            }
            ";
    }

    function SaveFormData($upc){
        $upc = BarcodeLib::padUPC($upc);
        $ids = FormLib::get_form_value('v_id',array());
        $skus = FormLib::get_form_value('v_sku',array());
        $costs = FormLib::get_form_value('v_cost',array());
        $units = FormLib::get_form_value('v_units',array());

        $dbc = $this->db();
        $chkP = $dbc->prepare_statement('SELECT upc FROM vendorItems WHERE vendorID=? AND upc=?');
        $insP = $dbc->prepare_statement('INSERT INTO vendorItems (upc,vendorID,cost,units,sku)
                    VALUES (?,?,?,?,?)');
        $upP = $dbc->prepare_statement('UPDATE vendorItems SET cost=?,units=?,sku=? WHERE
                    upc=? AND vendorID=?');
    
        $ret = True;
        for ($i=0;$i<count($ids);$i++){
            if (!isset($skus[$i]) || !isset($costs[$i]) || !isset($units[$i]))
                continue; // bad submit
            if (empty($skus[$i]) || empty($costs[$i]))
                continue; // no submission. don't create a record

            $chkR = $dbc->exec_statement($chkP,array($ids[$i],$upc));
            if ($dbc->num_rows($chkR) == 0){
                $try = $dbc->exec_statement($insP,array($upc,$ids[$i],
                    $costs[$i],$units[$i],$skus[$i]));
                if ($try === False) $ret = False;
            }
            else {
                $try = $dbc->exec_statement($upP,array($costs[$i],
                    $units[$i],$skus[$i],$upc,$ids[$i]));
                if ($try === False) $ret = False;
            }
        }

        return $ret;
    }
}

?>

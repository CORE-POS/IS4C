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

if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__).'/../../classlib2.0/FannieAPI.php');
}

class VendorItemModule extends \COREPOS\Fannie\API\item\ItemModule {

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $FANNIE_CSS_PRIMARY_COLOR = FannieConfig::config('CSS_PRIMARY_COLOR');
        $upc = BarcodeLib::padUPC($upc);

        $ret = '<div id="VendorItemsFieldset" class="panel panel-default">';
        $ret .=  "<div class=\"panel-heading\">
                <a href=\"\" onclick=\"\$('#VendorItemsFieldsetContent').toggle(); return false;\">
                Vendor Items
                </a></div>";
        $css = ($expand_mode == 1) ? '' : ' collapse';
        $ret .= '<div id="VendorItemsFieldsetContent" class="panel-body' . $css . '">';

        $dbc = $this->db();
        $p = $dbc->prepare('SELECT vendorID,vendorName FROM vendors ORDER BY vendorName');
        $r = $dbc->execute($p);
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

        $ret .= '<select class="form-control"
            onchange="$(\'.vtable\').hide();$(\'#vtable\'+this.value).show();">';
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

        $prep = $dbc->prepare('SELECT * FROM vendorItems WHERE vendorID=? AND upc=?');
        $style = ($matched) ? 'display:none;' : 'display:table;';
        $cost_class = '';
        foreach ($vendors as $id => $name) {
            if ($matched && $id == $my_vendor) {
                $table_class = '';
                $cost_class = 'default_vendor_cost';
            } else {
                $table_class = 'collapse';
            }
            $ret .= "<table id=\"vtable$id\"
                     class=\"vtable table table-bordered $table_class\">";
            $row = array('cost'=>0,'sku'=>'','units'=>1,'size'=>'');
            $res = $dbc->execute($prep,array($id,$upc)); 
            if ($dbc->num_rows($res) > 0)
                $row = $dbc->fetch_row($res);
            $ret .= '<tr>
                <th>SKU</th>
                <td colspan="3">
                    <input type="text" class="form-control" name="v_sku[]"
                    id="vsku' . $id . '"
                    onchange="$(\'#product-sku-field\').val(this.value);"
                    value="'.$row['sku'].'" />
                </td>';
            $ret .= sprintf('<th>Unit Cost</th><td>
                    <div class="input-group">
                    <span class="input-group-addon">$</span><input type="text" 
                    name="v_cost[]" id="vcost%d" class="form-control %s" value="%.2f" 
                    onchange="vprice(%d);"
                    /></div>
                    </td></tr>',
                    $id, $cost_class, $row['cost'], $id);
            $ret .= '<tr>
                <th>Units/Case</th>
                <td>
                    <input type="text" class="form-control" name="v_units[]"
                    id="vunits'.$id.'" value="'.$row['units'].'" 
                    onchange="vprice('.$id.'); $(\'#product-case-size\').val(this.value);" />
                </td>
                <th>Unit Size</th>
                <td>
                    <input type="text" class="form-control" name="v_size[]"
                    id="vsize'.$id.'" value="'.$row['size'].'" 
                    onchange="$(\'#product-pack-size\').val(this.value); " />
                </td>
                </td>';
            $ret .= sprintf('<th>Case Cost</th><td id="vcc%d">$%.2f</td></tr>',
                    $id, ($row['units']*$row['cost']));
            $ret .= '<input type="hidden" name="v_id[]" value="'.$id.'" />';
            
            $ret .= '</table>';

            $style = 'display:none;';
        }
        
        $ret .= '</div>';
        $ret .= '</div>';

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
        $sizes = FormLib::get_form_value('v_size',array());

        $dbc = $this->db();
        $chkP = $dbc->prepare('SELECT upc FROM vendorItems WHERE vendorID=? AND upc=?');
        $insP = $dbc->prepare('INSERT INTO vendorItems (upc,vendorID,cost,units,sku,size)
                    VALUES (?,?,?,?,?,?)');
        $upP = $dbc->prepare('UPDATE vendorItems SET cost=?,units=?,sku=?,size=? WHERE
                    upc=? AND vendorID=?');
        $initP = $dbc->prepare('
            UPDATE vendorItems
            SET brand=?,
                description=?,
                vendorDept=0
            WHERE upc=?
                AND vendorID=?');
        $prod = new ProductsModel($dbc);
        $prod->upc($upc);
        $prod->load();
    
        $ret = true;
        for ($i=0;$i<count($ids);$i++){
            if (!isset($skus[$i]) || !isset($costs[$i]) || !isset($units[$i])) {
                continue; // bad submit
            }
            // always create record for the default vendor
            // but only initialize an empty one if no
            // record exists.
            if ($ids[$i] == $prod->default_vendor_id()) {
                $defaultR = $dbc->execute($chkP, array($ids[$i], $prod->upc()));
                if ($dbc->numRows($defaultR) == 0) {
                    if (empty($skus[$i])) {
                        $skus[$i] = $prod->upc();
                    }
                    if (empty($costs[$i])) {
                        $costs[$i] = $prod->cost();
                    }
                    if (empty($units[$i])) {
                        $units[$i] = 1;
                    }
                    if (empty($sizes[$i])) {
                        $sizes[$i] = '';
                    }
                }
            }
            if (empty($skus[$i]) || empty($costs[$i])) {
                continue; // no submission. don't create a record
            }

            $chkR = $dbc->execute($chkP,array($ids[$i],$upc));
            if ($dbc->num_rows($chkR) == 0){
                $try = $dbc->execute($insP,array($upc,$ids[$i],
                    $costs[$i],$units[$i],$skus[$i],$sizes[$i]));
                if ($try === false) {
                    $ret = false;
                } else {
                    // initialize new record with product's brand
                    // and description so it isn't blank
                    $dbc->execute($initP, array($prod->brand(), $prod->description(),
                        $upc, $ids[$i]));
                }
            } else {
                $try = $dbc->execute($upP,array($costs[$i],
                    $units[$i],$skus[$i],$sizes[$i],$upc,$ids[$i]));
                if ($try === false) $ret = false;
            }
        }

        return $ret;
    }
}


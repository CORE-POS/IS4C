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

include_once(dirname(__FILE__).'/../../classlib2.0/FannieAPI.php');

class VolumePricingModule extends ItemModule 
{

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $upc = BarcodeLib::padUPC($upc);

        $ret = '<fieldset id="3for1FieldSet">';
        $ret .=  "<legend onclick=\"\$('#VolumeFieldsetContent').toggle();\">
                <a href=\"\" onclick=\"return false;\">\"Three for a dollar\"</a>
                </legend>";
        $css = ($expand_mode == 1) ? '' : 'display:none;';
        $ret .= '<div id="VolumeFieldsetContent" style="' . $css . '">';

        $dbc = $this->db();
        $model = new ProductsModel($dbc);
        $model->upc($upc);
        $model->load();

        $methods = array(0=>'Disabled',2=>'Use this price for full sets',1=>'Always use this price');

        $ret .= "<table style=\"margin-top:5px;margin-bottom:5px;\" border=1 cellpadding=5 cellspacing=0 width='100%'><tr>";
        $ret .= '<tr><th>Enabled</td>
            <th># Items'.FannieHelp::ToolTip('# of items in a set').'</th>
            <th>Price'.FannieHelp::ToolTip('Price for the whole set').'</th>
            <th>Mix/Match'.FannieHelp::ToolTip('Items with the same Mix/Match all count').'</th></tr>';
        $ret .= '<tr><td><select name="vp_method">';
        foreach($methods as $value => $label){
            $ret .= sprintf('<option value="%d"%s>%s</option>',
                    $value, ($value==$model->pricemethod()?' selected':''), $label);
        }
        $ret .= '</select></td>';
        $ret .= '<td><input type="text" name="vp_qty" size="4" value="'.$model->quantity().'" /></td>';
        $ret .= '<td>$<input type="text" name="vp_price" size="4" value="'.sprintf('%.2f',$model->groupprice()).'" /></td>';
        $ret .= '<td><input type="text" name="vp_mm" size="4" value="'.$model->mixmatchcode().'" /></td>';
        $ret .= '</table></div></fieldset>';

        return $ret;
    }

    public function SaveFormData($upc)
    {
        $upc = BarcodeLib::padUPC($upc);
        $dbc = $this->db();

        $model = new ProductsModel($dbc);
        $model->upc($upc);

        $method = FormLib::get_form_value('vp_method',0);
        $qty = FormLib::get_form_value('vp_qty',0);
        $price = FormLib::get_form_value('vp_price',0);
        $mixmatch = FormLib::get_form_value('vp_mm',0);

        $model->pricemethod($method);
        $model->quantity($qty);
        $model->groupprice($price);
        $model->mixmatchcode($mixmatch);
        $r1 = $model->save();

        if ($r1 === false) {
            return false;
        } else {
            return true;    
        }
    }
}

?>

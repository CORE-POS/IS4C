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

class VolumePricingModule extends \COREPOS\Fannie\API\item\ItemModule 
{

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $upc = BarcodeLib::padUPC($upc);

        $ret = '<div id="3for1FieldSet" class="panel panel-default">';
        $ret .=  "<div class=\"panel-heading\">
                <a href=\"\" onclick=\"\$('#VolumeFieldsetContent').toggle();return false;\">
               \"Three for a dollar\"
                </a></div>";
        $css = ($expand_mode == 1) ? '' : ' collapse';
        $ret .= '<div id="VolumeFieldsetContent" class="panel-body' . $css . '">';

        $dbc = $this->db();
        $model = new ProductsModel($dbc);
        $model->upc($upc);
        $model->load();

        $methods = array(0=>'Disabled',2=>'Use this price for full sets',1=>'Always use this price');

        $ret .= "<table class=\"table table-bordered\"><tr>";
        $ret .= '<tr><th>Enabled</td>
            <th># Items'.\COREPOS\Fannie\API\lib\FannieHelp::ToolTip('# of items in a set').'</th>
            <th>Price'.\COREPOS\Fannie\API\lib\FannieHelp::ToolTip('Price for the whole set').'</th>
            <th>Mix/Match'.\COREPOS\Fannie\API\lib\FannieHelp::ToolTip('Items with the same Mix/Match all count').'</th></tr>';
        $ret .= '<tr><td><select name="vp_method" class="form-control">';
        foreach($methods as $value => $label){
            $ret .= sprintf('<option value="%d"%s>%s</option>',
                    $value, ($value==$model->pricemethod()?' selected':''), $label);
        }
        $ret .= '</select></td>';
        $ret .= '<td><input type="text" name="vp_qty" class="form-control" value="'.$model->quantity().'" /></td>';
        $ret .= '<td>
            <div class="input-group">
            <span class="input-group-addon">$</span>
            <input type="text" name="vp_price" class="form-control" value="'.sprintf('%.2f',$model->groupprice()).'" />
            </div>
            </td>';
        $ret .= '<td><input type="text" name="vp_mm" class="form-control" value="'.$model->mixmatchcode().'" /></td>';
        $ret .= '</table></div></div>';

        return $ret;
    }

    public function SaveFormData($upc)
    {
        $upc = BarcodeLib::padUPC($upc);
        $dbc = $this->db();

        $model = new ProductsModel($dbc);
        $model->upc($upc);
        $model->store_id(1);

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


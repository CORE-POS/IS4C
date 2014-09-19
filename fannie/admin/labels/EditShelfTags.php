<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class EditShelfTags extends FanniePage {

    protected $title = 'Fannie - Edit Shelf Tags';
    protected $header = 'Edit Shelf Tags';
    protected $must_authenticate = True;
    protected $auth_classes = array('barcodes');

    public $description = '[Edit Shelf Tags] updates the text information for a set of tags.';

    private $id;

    function preprocess(){
        global $FANNIE_OP_DB;
        $this->id = FormLib::get_form_value('id',0);

        if (FormLib::get_form_value('submit',False) !== False){
            $upcs = FormLib::get_form_value('upc',array());
            $descs = FormLib::get_form_value('desc',array());
            $prices = FormLib::get_form_value('price',array());
            $brands = FormLib::get_form_value('brand',array());
            $skus = FormLib::get_form_value('sku',array());
            $sizes = FormLib::get_form_value('size',array());
            $units = FormLib::get_form_value('units',array());
            $vendors = FormLib::get_form_value('vendors',array());
            $ppos = FormLib::get_form_value('ppo',array());
            $counts = FormLib::get_form_value('counts',array());

            $dbc = FannieDB::get($FANNIE_OP_DB);
            $tag = new ShelftagsModel($dbc);
            for ($i = 0; $i < count($upcs); $i++){
                $upc = $upcs[$i];
                $desc = isset($descs[$i]) ? $descs[$i] : '';
                $price = isset($prices[$i]) ? $prices[$i] : 0;
                $brand = isset($brands[$i]) ? $brands[$i] : '';
                $size = isset($sizes[$i]) ? $sizes[$i] : '';
                $sku = isset($sku[$i]) ? $sku[$i] : '';
                $unit = isset($units[$i]) ? $units[$i] : 1;
                $vendor = isset($vendors[$i]) ? $vendors[$i] : '';
                $ppo = isset($ppos[$i]) ? $ppos[$i] : '';
                $count = isset($counts[$i]) ? $counts[$i] : 1;
            
                $tag->id($this->id);
                $tag->upc($upc);
                $tag->description($desc);
                $tag->normal_price($price);
                $tag->brand($brand);
                $tag->sku($sku);
                $tag->size($size);
                $tag->units($unit);
                $tag->vendor($vendor);
                $tag->pricePerUnit($ppo);
                $tag->count($count);
                $tag->save();
            }
            header("Location: ShelfTagIndex.php");
            return False;
        }

        return True;
    }

    function css_content(){
        return "
        .one {
            background: #ffffff;
        }
        .two {
            background: #ffffcc;
        }";
    }

    function body_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $ret = "<form action=EditShelfTags.php method=post>";
        $ret .= "<table cellspacing=0 cellpadding=4 border=1>";
        $ret .= "<tr><th>UPC</th><th>Desc</th><th>Price</th><th>Brand</th><th>SKU</th>";
        $ret .= "<th>Size</th><th>Units</th><th>Vendor</th><th>PricePer</th><th># Tags</th></tr>";

        $class = array("one","two");
        $c = 1;
        $tags = new ShelftagsModel($dbc);
        $tags->id($this->id);
        foreach($tags->find() as $tag) {
            $ret .= "<tr class=$class[$c]>";
            $ret .= "<td>" . $tag->upc() . "</td><input type=hidden name=upc[] value=\"" . $tag->upc() . "\" />";
            $ret .= "<td><input type=text name=desc[] value=\"" . $tag->description() . "\" size=25 /></td>";
            $ret .= "<td><input type=text name=price[] value=\"" . $tag->normal_price() . "\" size=5 /></td>";
            $ret .= "<td><input type=text name=brand[] value=\"" . $tag->brand() . "\" size=13 /></td>";
            $ret .= "<td><input type=text name=sku[] value=\"" . $tag->sku() . "\" size=6 /></td>";
            $ret .= "<td><input type=text name=size[] value=\"" . $tag->size() . "\" size=6 /></td>";
            $ret .= "<td><input type=text name=units[] value=\"" . $tag->units() . "\" size=4 /></td>";
            $ret .= "<td><input type=text name=vendor[] value=\"" . $tag->vendor() . "\" size=7 /></td>";
            $ret .= "<td><input type=text name=ppo[] value=\"" . $tag->pricePerUnit() . "\" size=10 /></td>";
            $ret .= "<td><input type=text name=counts[] value=\"" . $tag->count() . "\" size=4 /></td>";
            $ret .= "</tr>";
            $c = ($c+1)%2;
        }
        $ret .= "</table>";
        $ret .= "<input type=hidden name=id value=\"".$this->id."\" />";
        $ret .= "<input type=submit name=submit value=\"Update Shelftags\" />";
        $ret .= "</form>";

        return $ret;
    }
}

FannieDispatch::conditionalExec(false);


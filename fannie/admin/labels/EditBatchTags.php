<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
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

class EditBatchTags extends FanniePage 
{
    protected $title = 'Fannie - Edit Batch Shelf Tags';
    protected $header = 'Edit Batch Shelf Tags';
    protected $must_authenticate = true;
    protected $auth_classes = array('barcodes');

    public $description = '[Edit Batch Shelf Tags] updates the text information for a set of tags.';

    public function preprocess()
    {
        $this->ids = FormLib::get('batchID',0);

        if (FormLib::get('submit', false) !== false) {
            $upcs = FormLib::get('upc',array());
            $descs = FormLib::get('desc',array());
            $prices = FormLib::get('price',array());
            $brands = FormLib::get('brand',array());
            $skus = FormLib::get('sku',array());
            $sizes = FormLib::get('size',array());
            $units = FormLib::get('units',array());
            $vendors = FormLib::get('vendor',array());
            $ppos = FormLib::get('ppo',array());

            $dbc = FannieDB::get($this->config->get('OP_DB'));
            $tag = new BatchBarcodesModel($dbc);
            for ($i = 0; $i < count($upcs); $i++){
                $batchID = $this->ids[$i];
                $upc = $upcs[$i];
                $desc = isset($descs[$i]) ? $descs[$i] : '';
                $price = isset($prices[$i]) ? $prices[$i] : 0;
                $brand = isset($brands[$i]) ? $brands[$i] : '';
                $size = isset($sizes[$i]) ? $sizes[$i] : '';
                $sku = isset($skus[$i]) ? $skus[$i] : '';
                $unit = isset($units[$i]) ? $units[$i] : 1;
                $vendor = isset($vendors[$i]) ? $vendors[$i] : '';
                $ppo = isset($ppos[$i]) ? $ppos[$i] : '';
                $count = isset($counts[$i]) ? $counts[$i] : 1;
            
                $tag->batchID($batchID);
                $tag->upc($upc);
                $tag->description($desc);
                $tag->normal_price($price);
                $tag->brand($brand);
                $tag->sku($sku);
                $tag->size($size);
                $tag->units($unit);
                $tag->vendor($vendor);
                $tag->pricePerUnit($ppo);
                $tag->save();
            }
            header("Location: BatchShelfTags.php");

            return false;
        }

        return true;
    }

    function css_content()
    {
        return "
        .one {
            background: #ffffff;
        }
        .two {
            background: #ffffcc;
        }";
    }

    function body_content()
    {
        $dbc = FannieDB::getReadOnly($this->config->get('OP_DB'));

        $ret = "<form method=post>";
        $ret .= "<table class=\"table table-striped\">";
        $ret .= "<tr><th>UPC</th><th>Desc</th><th>Price</th><th>Brand</th><th>SKU</th>";
        $ret .= "<th>Size</th><th>Units</th><th>Vendor</th><th>PricePer</th></tr>";

        if (!is_array($this->ids)) {
            $this->ids = array($this->ids);
        }
        $tags = new BatchBarcodesModel($dbc);

        foreach ($this->ids as $batchID) {

            $tags->batchID($batchID);

            foreach ($tags->find() as $tag) {
                $ret .= $this->tagToRow($batchID, $tag);
            }
        }

        $ret .= "</table>";
        $ret .= '<p>';
        $ret .= "<button type=submit name=submit value=\"1\" 
            class=\"btn btn-default\">Update Batch Tags</button>";
        $ret .= '</p>';
        $ret .= "</form>";

        return $ret;
    }

    private function tagToRow($batchID, $tag)
    {
        $ret = '<tr>';
        $ret .= '<input type="hidden" name="batchID[]" value="' . $batchID . '" />';
        $ret .= "<td>" . $tag->upc() . "</td><input type=hidden name=upc[] value=\"" . $tag->upc() . "\" />";
        $ret .= "<td><input type=text name=desc[] value=\"" . $tag->description() . "\" 
                    class=\"form-control\" /></td>";
        $ret .= "<td><div class=\"input-group\">
                <span class=\"input-group-addon\">\$</span>
                <input type=text name=price[] value=\"" . $tag->normal_price() . "\" 
                    class=\"form-control\" />
                </div></td>";
        $ret .= "<td><input type=text name=brand[] value=\"" . $tag->brand() . "\" 
                class=\"form-control\" /></td>";
        $ret .= "<td><input type=text name=sku[] value=\"" . $tag->sku() . "\" 
                    class=\"form-control\" /></td>";
        $ret .= "<td><input type=text name=size[] value=\"" . $tag->size() . "\" 
                    class=\"form-control\" /></td>";
        $ret .= "<td><input type=text name=units[] value=\"" . $tag->units() . "\" 
                    class=\"form-control\" /></td>";
        $ret .= "<td><input type=text name=vendor[] value=\"" . $tag->vendor() . "\" 
                    class=\"form-control\" /></td>";
        $ret .= "<td><input type=text name=ppo[] value=\"" . $tag->pricePerUnit() . "" . "\" 
                    class=\"form-control\" /></td>";
        $ret .= "</tr>";

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            Edit individual fields in a set of queued shelf tags.
            These changes only impact the queued set of tags. 
            Nothing will change in the actual product, nor will
            changes persist to shelf tags created in the future.
            This is for quick fine-tuning before printing tags.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
        $phpunit->assertNotEquals(0, strlen($this->css_content()));
        $phpunit->assertNotEquals(0, strlen($this->tagToRow(1, new BatchBarcodesModel($this->connection))));
    }
}

FannieDispatch::conditionalExec();


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

class EditShelfTags extends FannieRESTfulPage 
{
    protected $title = 'Fannie - Edit Shelf Tags';
    protected $header = 'Edit Shelf Tags';
    protected $must_authenticate = true;
    protected $auth_classes = array('barcodes');

    public $description = '[Edit Shelf Tags] updates the text information for a set of tags.';
    public $themed = true;

    public function preprocess()
    {
        $this->__routes[] = 'delete<id><upc>';
        $this->__routes[] = 'delete<id><upc><confirm>';
        $this->__routes[] = 'post<newID><oldID><upc>';

        return parent::preprocess();
    }

    public function post_id_handler()
    {
        global $FANNIE_OP_DB;
        $upcs = FormLib::get_form_value('upc',array());
        $descs = FormLib::get_form_value('desc',array());
        $prices = FormLib::get_form_value('price',array());
        $brands = FormLib::get_form_value('brand',array());
        $skus = FormLib::get_form_value('sku',array());
        $sizes = FormLib::get_form_value('size',array());
        $units = FormLib::get_form_value('units',array());
        $vendors = FormLib::get_form_value('vendor',array());
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
            $sku = isset($skus[$i]) ? $skus[$i] : '';
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

        return false;
    }

    public function delete_id_upc_confirm_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $tag = new ShelftagsModel($dbc);
        $tag->id($this->id);
        $tag->upc(BarcodeLib::padUPC($this->upc));
        $tag->delete();

        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $this->id);

        return false;
    }

    public function post_newID_oldID_upc_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        
        $moveP = $dbc->prepare('
            UPDATE shelftags
            SET id=?
            WHERE id=?
                AND upc=?
        ');
        $moveR = $dbc->execute($moveP, array($this->newID, $this->oldID, BarcodeLib::padUPC($this->upc)));

        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $this->oldID);

        return false;
    }

    public function get_id_view()
    {
        $dbc = FannieDB::getReadOnly($this->config->get('OP_DB'));

        $template = <<<HTML
<form action=EditShelfTags.php method=post>
<table class="table table-striped table-bordered small">
    <tr>
        <th>UPC</th><th>Desc</th><th>Price</th><th>Brand</th><th>SKU</th>
        <th>Size</th><th>Units</th><th>Vendor</th><th>PricePer</th><th># Tags</th>
    </tr>
    {%
    <tr>
        <td>{{ tag.upc }}</td>
        <input type="hidden" name="upc[]" value="{{ tag.upc }}" /> 
        <td><input type="text" name="desc[]" value="{{ tag.description }}"
            class="form-control input-sm" /></td>
        <td><div class="input-group">
            <span class="input-group-addon">$</span>
            <input type=text name=price[] value="{{ tag.normal_price }}" 
                class="form-control price-field input-sm" />
            </div>
        </td>
        <td><input type=text name=brand[] value="{{ tag.brand }}"
                class="form-control input-sm" /></td>
        <td><input type=text name=sku[] value="{{ tag.sku }}"
                class="form-control input-sm" /></td>
        <td><input type=text name=size[] value="{{ tag.size }}"
                class="form-control input-sm" /></td>
        <td><input type=text name=units[] value="{{ tag.units }}"
                class="form-control input-sm price-field" /></td>
        <td><input type=text name=vendor[] value="{{ tag.vendor }}"
                class="form-control input-sm" /></td>
        <td><input type=text name=ppo[] value="{{ tag.pricePerUnit }}"
                class="form-control input-sm" /></td>
        <td><input type=number name=counts[] value="{{ tag.count }}"
                class="form-control input-sm price-field" /></td>
        <td><a href="?_method=delete&id={{ id }}&upc={{ tag.upc }}"
                class="btn btn-danger">
                {{ deleteIcon }}
        </a></td>
    </tr>
    %}
</table>
<input type=hidden name=id value="{{ id }}" />
<p>
    <button type=submit name=submit value="1"
        class="btn btn-default">Update Shelftags</button>
</p>
</form>
HTML;
        $tags = new ShelftagsModel($dbc);
        $tags->id($this->id);

        $data = array(
            'id' => $this->id,
            'tag' => $tags->find(),
            'deleteIcon' => \COREPOS\Fannie\API\lib\FannieUI::deleteIcon('Delete Tag OR Change Queues'),
        );
        /*
        $t = new \COREPOS\common\CoreTemplate($template);
        return $t->render($data);
        */

        $ret = "<form action=EditShelfTags.php method=post>";
        $ret .= "<table class=\"table table-striped table-bordered small\">";
        $ret .= "<tr><th>UPC</th><th>Desc</th><th>Price</th><th>Brand</th><th>SKU</th>";
        $ret .= "<th>Size</th><th>Units</th><th>Vendor</th><th>PricePer</th><th># Tags</th></tr>";

        foreach ($tags->find() as $tag) {
            $ret .= '<tr>';
            $ret .= "<td>" . $tag->upc() . "</td><input type=hidden name=upc[] value=\"" . $tag->upc() . "\" />";
            $ret .= "<td><input type=text name=desc[] value=\"" . $tag->description() . "\" 
                        class=\"form-control input-sm\" /></td>";
            $ret .= "<td><div class=\"input-group\">
                    <span class=\"input-group-addon\">\$</span>
                    <input type=text name=price[] value=\"" . $tag->normal_price() . "\" 
                        class=\"form-control price-field input-sm\" />
                    </div></td>";
            $ret .= "<td><input type=text name=brand[] value=\"" . $tag->brand() . "\" 
                        class=\"form-control input-sm\" /></td>";
            $ret .= "<td><input type=text name=sku[] value=\"" . $tag->sku() . "\" 
                        class=\"form-control input-sm\" /></td>";
            $ret .= "<td><input type=text name=size[] value=\"" . $tag->size() . "\" 
                        class=\"form-control input-sm\" /></td>";
            $ret .= "<td><input type=text name=units[] value=\"" . $tag->units() . "\" 
                        class=\"form-control input-sm price-field\" /></td>";
            $ret .= "<td><input type=text name=vendor[] value=\"" . $tag->vendor() . "\" 
                        class=\"form-control input-sm\" /></td>";
            $ret .= "<td><input type=text name=ppo[] value=\"" . $tag->pricePerUnit() . "\" 
                        class=\"form-control input-sm\" /></td>";
            $ret .= "<td><input type=number name=counts[] value=\"" . $tag->count() . "\" 
                        class=\"form-control input-sm price-field\" /></td>";
            $ret .= '<td><a href="?_method=delete&id=' . $this->id . '&upc=' . $tag->upc() . '"
                        class="btn btn-danger">'
                        . \COREPOS\Fannie\API\lib\FannieUI::deleteIcon('Delete OR Change Queue')
                        . '</a></td>';
            $ret .= "</tr>";
        }
        $ret .= "</table>";
        $ret .= "<input type=hidden name=id value=\"".$this->id."\" />";
        $ret .= '<p>';
        $ret .= "<button type=submit name=submit value=\"1\" 
            class=\"btn btn-default\">Update Shelftags</button>";
        $ret .= '</p>';
        $ret .= "</form>";

        return $ret;
    }

    public function delete_id_upc_view()
    {
        $dbc = FannieDB::getReadOnly($this->config->get('OP_DB'));

        $tag = new ShelftagsModel($dbc);
        $tag->id($this->id);
        $tag->upc(BarcodeLib::padUPC($this->upc));
        $tag->load();

        $ret = <<<HTML
<form action="{{SELF}}" method="post">
<div class="panel panel-default">
    <div class="panel-heading">Selected Tag</div>
    <div class="panel-body">
        {{upc}} - {{brand}} {{description}}
    </div>
</div>
<div class="panel panel-default">
    <div class="panel-heading">Delete</div>
    <div class="panel-body">
        <div class="form-group">
            <a href="?_method=delete&id={{id}}&upc={{upc}}&confirm=1" class="btn btn-danger">
                {{ICON}} Remove Tag from Queue
            </a>
        </div>
    </div>
</div>
<div class="panel panel-default">
    <div class="panel-heading">Move</div>
    <div class="panel-body">
        <div class="form-group">
            <label>Move to another Queue</label>
            <select name="newID" class="form-control">
                {{QUEUES}}
            </select>
        </div>
        <div class="form-group">
            <button class="btn btn-default" type="submit">Move Tag</button>
        </div>
        <input type="hidden" name="oldID" value="{{id}}" />
        <input type="hidden" name="upc" value="{{upc}}" />
    </div>
</div>
</form>
HTML;
        $queues = new ShelfTagQueuesModel($dbc);
        $ret = str_replace('{{SELF}}', $_SERVER['PHP_SELF'], $ret);
        $ret = str_replace('{{id}}', $this->id, $ret);
        $ret = str_replace('{{upc}}', $this->upc, $ret);
        $ret = str_replace('{{brand}}', $tag->brand(), $ret);
        $ret = str_replace('{{description}}', $tag->description(), $ret);
        $ret = str_replace('{{QUEUES}}', $queues->toOptions(), $ret);
        $ret = str_replace('{{ICON}}', \COREPOS\Fannie\API\lib\FannieUI::deleteIcon(), $ret);

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
}

FannieDispatch::conditionalExec();


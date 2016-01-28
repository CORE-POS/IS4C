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
        $this->__routes[] = 'get<id><reprice>';

        return parent::preprocess();
    }

    protected function get_id_reprice_handler()
    {
        $tags = new ShelftagsModel($this->connection);
        $tags->id($this->id);
        $priceP = $this->connection->prepare('
            SELECT normal_price
            FROM products
            WHERE upc=?');
        foreach ($tags->find() as $tag) {
            $current_price = $this->connection->getValue($priceP, array($tag->upc()));
            if ($current_price !== false) {
                $tag->normal_price($current_price);
                $ppo = \COREPOS\Fannie\API\lib\PriceLib::pricePerUnit($current_price, $tag->size());
                $tag->pricePerUnit($ppo);
                $tag->save();
            }
        }

        return true;
    }

    protected function get_id_reprice_view()
    {
        return '<div class="alert alert-success">Updated Prices</div>'
            . $this->get_id_view();
    }

    public function post_id_handler()
    {
        $upcs = FormLib::get('upc',array());
        $descs = FormLib::get('desc',array());
        $prices = FormLib::get('price',array());
        $brands = FormLib::get('brand',array());
        $skus = FormLib::get('sku',array());
        $sizes = FormLib::get('size',array());
        $units = FormLib::get('units',array());
        $vendors = FormLib::get('vendor',array());
        $ppos = FormLib::get('ppo',array());
        $counts = FormLib::get('counts',array());

        $tag = new ShelftagsModel($this->connection);
        for ($i = 0; $i < count($upcs); $i++){
            $tag->id($this->id);
            $tag->upc($upcs[$i]);
            $tag->description(isset($descs[$i]) ? $descs[$i] : '');
            $tag->normal_price(isset($prices[$i]) ? $prices[$i] : 0);
            $tag->brand(isset($brands[$i]) ? $brands[$i] : '');
            $tag->sku(isset($skus[$i]) ? $skus[$i] : '');
            $tag->size(isset($sizes[$i]) ? $sizes[$i] : '');
            $tag->units(isset($units[$i]) ? $units[$i] : 1);
            $tag->vendor(isset($vendors[$i]) ? $vendors[$i] : '');
            $tag->pricePerUnit(isset($ppos[$i]) ? $ppos[$i] : '');
            $tag->count(isset($counts[$i]) ? $counts[$i] : 1);
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

        header('Location: ' . filter_input(INPUT_SERVER, 'PHP_SELF') . '?id=' . $this->id);

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

        header('Location: ' . filter_input(INPUT_SERVER, 'PHP_SELF') . '?id=' . $this->oldID);

        return false;
    }

    public function get_id_view()
    {
        $dbc = FannieDB::getReadOnly($this->config->get('OP_DB'));

        $tags = new ShelftagsModel($dbc);
        $tags->id($this->id);

        $ret = "<form action=EditShelfTags.php method=post>";
        $ret .= "<table class=\"table table-striped table-bordered small\">";
        $ret .= "<tr><th>UPC</th><th>Desc</th><th>Price</th><th>Brand</th><th>SKU</th>";
        $ret .= "<th>Size</th><th>Units</th><th>Vendor</th><th>PricePer</th><th># Tags</th></tr>";

        foreach ($tags->find() as $tag) {
            $ret .= $this->tagToRow($tag);
        }
        $ret .= "</table>";
        $ret .= "<input type=hidden name=id value=\"".$this->id."\" />";
        $ret .= '<p>';
        $ret .= "<button type=submit name=submit value=\"1\" 
            class=\"btn btn-default btn-core\">Update Shelftags</button>
            <a href=\"?id=" . $this->id . "&reprice=1\" 
            class=\"btn btn-default btn-reset\">Use Current Pricing</a>";
        $ret .= '</p>';
        $ret .= "</form>";

        return $ret;
    }

    private function tagToRow($tag)
    {
        $ret = '<tr>';
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
        $ret = str_replace('{{SELF}}', filter_input(INPUT_SERVER, 'PHP_SELF'), $ret);
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

    public function unitTest($phpunit)
    {
        $this->id = 1;
        $this->upc = '0000000004011';
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
        $phpunit->assertNotEquals(0, strlen($this->delete_id_upc_view()));
        $phpunit->assertNotEquals(0, strlen($this->get_id_reprice_view()));
        $phpunit->assertNotEquals(0, strlen($this->tagToRow(new ShelftagsModel($this->connection))));
    }
}

FannieDispatch::conditionalExec();


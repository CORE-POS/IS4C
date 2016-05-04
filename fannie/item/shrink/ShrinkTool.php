<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op

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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ShrinkTool extends FannieRESTfulPage
{
    protected $header = 'Enter Shrink';
    protected $title = 'Enter Shrink';
    public $themed = true;
    public $description = '[Shrink Entry] adds items to shrink counts. Duplicates lane functionality to allow backend entry.';
    public $enable_linea = true;

    public function preprocess()
    {
        $this->__routes[] = 'get<msg>';
        $this->__routes[] = 'post<upc><description><department><cost><price><qty><reason>';

        return parent::preprocess();
    }

    public function post_upc_description_department_cost_price_qty_reason_handler()
    {
        global $FANNIE_TRANS_DB, $FANNIE_EMP_NO, $FANNIE_REGISTER_NO;
        $dbc = FannieDB::get($FANNIE_TRANS_DB);

        $record = DTrans::defaults();
        $record['emp_no'] = $FANNIE_EMP_NO;
        $record['register_no'] = $FANNIE_REGISTER_NO;
        $record['trans_no'] = DTrans::getTransNo($dbc, $FANNIE_EMP_NO, $FANNIE_REGISTER_NO);
        $record['trans_id'] = 1;
        $record['upc'] = $this->upc;
        $record['description'] = $this->description;
        $record['department'] = $this->department;
        $record['trans_type'] = 'I';
        $record['quantity'] = $this->qty;
        $record['ItemQtty'] = $this->qty;
        $record['unitPrice'] = $this->price;
        $record['regPrice'] = $this->price;
        $record['total'] = $this->qty * $this->price;
        $record['cost'] = $this->qty * $this->cost;
        $record['numflag'] = $this->reason;
        $record['charflag'] = strlen(FormLib::get('type')) > 0 ? strtoupper(substr(FormLib::get('type'), 0, 1)) : '';
        $record['trans_status'] = 'Z';
        if (FormLib::get('store', false) !== false) {
            $record['store_id'] = FormLib::get('store');
        }

        $info = DTrans::parameterize($record, 'datetime', $dbc->now());
        $query = 'INSERT INTO dtransactions
            (' . $info['columnString'] . ')
            VALUES
            (' . $info['valueString'] . ')';
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $info['arguments']);

        header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=1');

        return false;
    }

    public function get_msg_handler()
    {
        $this->add_onload_command("showBootstrapAlert('#alert-area', 'success', 'Shrink Saved');\n");
        $this->__route_stem = 'get';

        return true;
    }

    public function get_id_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upc = BarcodeLib::padUPC($this->id);

        $product = new ProductsModel($dbc);
        $product->upc($upc);
        $product->store_id($this->config->get('STORE_ID'));
        if (!$product->load()) {
            $this->add_onload_command("showBootstrapAlert('#alert-area', 'danger', 'Item not found');\n");
            $this->__route_stem = 'get';
        } else {
            $this->description = $product->description();
            $this->cost = $product->cost();
            $this->price = $product->normal_price();
            $this->department = $product->department();
            $this->upc = $upc;
        }

        return true;
    }

    public function get_id_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $this->add_onload_command("\$('#qty-field').focus();\n");

        $reasons = new ShrinkReasonsModel($dbc);
        $shrink_opts = '';
        foreach ($reasons->find('description') as $reason) {
            $shrink_opts .= sprintf('<option value="%d">%s</option>',
                $reason->shrinkReasonID(), $reason->description());
        }

        $ret = <<<HTML
<form method="post">
    <div class="form-group">
        <label>UPC</label> {{upc}} {{description}}
        <input type="hidden" name="upc" value="{{upc}}" />
        <input type="hidden" name="description" value="{{description}}" />
        <input type="hidden" name="department" value="{{department}}" />
    </div>
    <div class="row">
        <div class="col-sm-6">
            <div class="row form-group">
                <label class="col-sm-3 text-right">Unit Cost</label>
                <div class="col-sm-9">
                    <div class="input-group">
                        <span class="input-group-addon">$</span>
                        <input type="text" name="cost" class="form-control" value="{{cost}}" />
                    </div> 
                </div> 
            </div> 
            <div class="row form-group">
                <label class="col-sm-3 text-right">Quantity</label>
                <div class="col-sm-9">
                    <input type="text" name="qty" id="qty-field" class="form-control" required />
                </div>
            </div>
            <div class="row form-group">
                <label class="col-sm-3 text-right">Type</label>
                <div class="col-sm-9">
                    <select name="type" class="form-control">
                        <option>Loss</option>
                        <option>Contribute</option>
                    </select>
                </div> 
            </div> 
        </div> <!-- end left column col-sm-6 -->
        <div class="col-sm-6">
            <div class="form-group row">
                <label class="col-sm-3 text-right">Unit Price</label>
                <div class="col-sm-9">
                    <div class="input-group">
                        <span class="input-group-addon">$</span>
                        <input type="text" name="price" class="form-control" value="{{price}}" />
                    </div> 
                </div> 
            </div> 
            <div class="row form-group">
                <label class="col-sm-3 text-right">Reason</label>
                <div class="col-sm-9">
                    <select name="reason" class="form-control">';
                        {{shrink_opts}}
                    </select>
                </div>
            </div>
            <div class="row form-group">
                <label class="col-sm-3 text-right">Store</label>
                <div class="col-sm-9">
                    {{store_select}}
                </div>
            </div>
        </div> <!-- end right column col-sm-6 -->
    </div> <!-- end row containing two col-sm-6 columns -->
    <div class="row form-group">
        <div class="col-sm-2">
            <button type="submit" class="btn btn-default">Enter Shrink</button>
        </div>
        <div class="col-sm-2">
            <a href="{{PHP_SELF}}" class="btn btn-default">Go Back</a>
        </div>
    </div>
</form>
HTML;
        $ret = str_replace('{{upc}}', $this->upc, $ret);
        $ret = str_replace('{{description}}', $this->description, $ret);
        $ret = str_replace('{{department}}', $this->department, $ret);
        $ret = str_replace('{{price}}', $this->price, $ret);
        $ret = str_replace('{{cost}}', $this->cost, $ret);
        $ret = str_replace('{{shrink_opts}}', $shrink_opts, $ret);
        $stores = FormLib::storePicker('store', false);
        $ret = str_replace('{{store_select}}', $stores['html'], $ret);
        $ret = str_replace('{{PHP_SELF}}', $_SERVER['PHP_SELF'], $ret);

        return $ret;
    }

    public function get_view()
    {
        global $FANNIE_URL;
        $this->add_script('../autocomplete.js');
        $ws = $FANNIE_URL . 'ws/';
        $this->add_onload_command("bindAutoComplete('#upc-field', '$ws', 'item');\n");
        $this->add_onload_command("\$('#upc-field').focus();");
        $this->addOnloadCommand("enableLinea('#upc-field');\n");

        return '<form action="' . $_SERVER['PHP_SELF'] . '" method="get">
            <div id="alert-area"></div>
            <div class="form-group">
                <label>UPC</label>
                <input type="text" id="upc-field" name="id" class="form-control" 
                    placeholder="Type UPC or description" />
            </div>
            <p>
                <button type="submit" class="btn btn-default">Continue</button>
                <a href="ShrinkEditor.php" class="btn btn-default">Edit Entries From Today</a>
            </p>
            </form>';
    }

    public function helpContent()
    {
        return '<p>
            The back end tool for entering shrink. Enter a UPC,
            then on the next screen specify a quantity lost
            and a reason. The price and cost should be correct
            but can be adjusted if needed.
            </p>
            <p>
            Loss vs Contribute may be WFC specific. From an inventory
            standpoint, the item is gone either way but if it
            can be donated ("contributed") to charity that may
            be relevant for tax accounting.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->id = 'foo';
        $phpunit->assertEquals(true, $this->get_id_handler());
        $this->id = '4011';
        $phpunit->assertEquals(true, $this->get_id_handler());
        //$phpunit->assertNotEquals(0, strlen($this->get_id_view()));
    }
}

FannieDispatch::conditionalExec();


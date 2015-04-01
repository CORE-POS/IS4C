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

        $record = DTrans::$DEFAULTS;
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

        $ret = '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">
            <div class="form-group">
                <label>UPC</label> ' . $this->upc . ' ' . $this->description . '
                <input type="hidden" name="upc" value="' . $this->upc . '" />
                <input type="hidden" name="description" value="' . $this->description . '" />
                <input type="hidden" name="department" value="' . $this->department . '" />
            </div>
            <div class="form-group form-inline">
                <label>Unit Cost</label>
                <div class="input-group">
                    <span class="input-group-addon">$</span>
                    <input type="text" name="cost" class="form-control" value="' . $this->cost . '" />
                </div> 
                <label>Unit Price</label>
                <div class="input-group">
                    <span class="input-group-addon">$</span>
                    <input type="text" name="price" class="form-control" value="' . $this->price . '" />
                </div> 
            </div>
            <div class="form-group form-inline">
                <label>Quantity</label>
                <input type="text" name="qty" id="qty-field" class="form-control" required />
            </div>
            <div class="form-group form-inline">
                <label>Reason</label>
                <select name="reason" class="form-control">';
        $reasons = new ShrinkReasonsModel($dbc);
        foreach ($reasons->find('description') as $reason) {
            $ret .= sprintf('<option value="%d">%s</option>',
                    $reason->shrinkReasonID(), $reason->description());
        }
        $ret .= '</select>
            </div>
            <div class="form-group form-inline">
                <label>Type</label>
                <select name="type" class="form-control">
                    <option>Loss</option>
                    <option>Contribute</option>
                </select>
            </div>
            <p>
                <button type="submit" class="btn btn-default">Enter Shrink</button>
                <a href="' . $_SERVER['PHP_SELF'] . '" class="btn btn-default">Go Back</a>
            </p>
            </form>';

        return $ret;
    }

    public function get_view()
    {
        global $FANNIE_URL;
        $this->add_script('../autocomplete.js');
        $ws = $FANNIE_URL . 'ws/';
        $this->add_onload_command("bindAutoComplete('#upc-field', '$ws', 'item');\n");
        $this->add_onload_command("\$('#upc-field').focus();");

        return '<form action="' . $_SERVER['PHP_SELF'] . '" method="get">
            <div id="alert-area"></div>
            <div class="form-group">
                <label>UPC</label>
                <input type="text" id="upc-field" name="id" class="form-control" 
                    placeholder="Type UPC or description" />
            </div>
            <p>
                <button type="submit" class="btn btn-default">Continue</button>
                |
                <a href="ShrinkEditor.php" class="btn btn-default">Edit Entries From Today</a>
            </p>
            </form>';
    }
}

FannieDispatch::conditionalExec();


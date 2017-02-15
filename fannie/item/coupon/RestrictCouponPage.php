<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class RestrictCouponPage extends FannieRESTfulPage {

    protected $header = 'Coupon Restrictions';
    protected $title = 'Coupon Restrictions';

    public $description = '[Coupon Restrictions] bans or limits use of broken manufacturer coupons.
    Typically this means the manufacturer put the wrong UPC code on the coupon.';
    public $themed = true;

    function get_view(){
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $ret = '<form onsubmit="restrictCoupon.save();return false;">
            <div class="form-group">
                <label>UPC</label>
                <input type="text" id="upc" class="form-control" required />
            </div>
            <div class="form-group">
                <label>Limit</label> (max uses per transaction)
                <input type="number" id="limit" class="form-control" 
                    value="0" required />
            </div>
            <div class="form-group">
                <label>Reason</label>
                <input type="text" id="reason" class="form-control" required />
            </div>
            <p>
            <button type="submit" class="btn btn-default">Save</button>
            </p>
            </form>
            <hr/>';

        $model = new DisableCouponModel($dbc);
        $ret .= '<table class="table">
            <tr><th>UPC</th><th>Limit</th><th>Reason</th><th></th></tr>';
        foreach($model->find('upc') as $obj){
            $ret .= sprintf('<tr><td><a href="" onclick="restrictCoupon.load(\'%s\');return false;">%s</a></td>
                    <td>%d</td><td>%s</td>
                    <td><a href="" onclick="restrictCoupon.remove(\'%s\');return false;">%s</a></td>
                    </tr>',
                    $obj->upc(), $obj->upc(), $obj->threshold(),
                    $obj->reason(), $obj->upc(), \COREPOS\Fannie\API\lib\FannieUI::deleteIcon()
            );
        }
        $ret .= '</table>';
        $this->add_onload_command("\$('#upc').focus();\n");
        $this->addScript('restrictCoupon.js');

        return $ret;
    }

    function get_id_handler(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $upc = BarcodeLib::padUPC($this->id);
        $model = new DisableCouponModel($dbc);
        $model->upc($upc);
        $model->load();

        $ret = array(
        'limit' => $model->threshold(),
        'reason' => $model->reason()
        );
        echo json_encode($ret);
        return False;
    }

    function post_id_handler(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $upc = BarcodeLib::padUPC($this->id);
        $limit = FormLib::get_form_value('limit',0);
        $reason = FormLib::get_form_value('reason','');

        $model = new DisableCouponModel($dbc);
        $model->upc($upc);
        $model->threshold($limit);
        $model->reason($reason);
        $model->save();

        echo 'Done';
        return False;
    }

    function delete_id_handler(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $upc = BarcodeLib::padUPC($this->id);
        $model = new DisableCouponModel($dbc);
        $model->upc($upc);
        $model->delete();

        echo 'Done';
        return False;
    }

    public function helpContent()
    {
        return '<p>
            Place restrictions on how often a manufacturer coupon
            can be used. Often this is set to zero to "ban" poorly
            formatted coupon UPCs that apply to incorrect items.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->id = 1;
        ob_start();
        $phpunit->assertEquals(false, $this->post_id_handler());
        $phpunit->assertEquals(false, $this->get_id_handler());
        $phpunit->assertEquals(false, $this->delete_id_handler());
        ob_get_clean();
    }
}

FannieDispatch::conditionalExec();


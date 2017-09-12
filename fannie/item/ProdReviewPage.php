<?php
/*******************************************************************************

    Copyright 2017 Whole Foods Community Co-op

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

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ProdReviewPage extends FannieRESTfulPage
{
    protected $header = 'Vendor Product Info Review';
    protected $title = 'Vendor Product Info Review';

    public $description = '[Vendor Prodct Info Review] keep a record of the
		last time product info was verified/updated for individual products.';

    function preprocess()
    {
        $this->__routes[] = 'get<upc>';
        $this->__routes[] = 'get<upc><save>';
        $this->__routes[] = 'get<list>';
        $this->__routes[] = 'get<list><save>';
        $this->__routes[] = 'get<vendor>';
        $this->__routes[] = 'get<vendor><checked>';
        return parent::preprocess();
    }

    public function get_vendor_checked_handler()
    {

        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upcs = FormLib::get('checked');
        $user = FannieAuth::getUID($this->current_user);
        $pr = new ProdReviewModel($dbc);
        $data = array();
        $error = 0;
        foreach ($upcs as $upc) {
            $pr->reset();
            $pr->upc($upc);
            $pr->user($user);
            $pr->reviewed(date('Y-m-d'));
            if (!$pr->save()) {
                $error = 1;
            }
        }
        if (!$error) {
            header('Location: '.$_SERVER['PHP_SELF'].'?saved=true');
        } else {
            header('Location: '.$_SERVER['PHP_SELF'].'?saved=false');
        }

        return false;
    }

    public function get_vendor_view()
    {
        $vid = FormLib::get('vendor');

        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $p = new ProductsModel($dbc);
        $p->default_vendor_id($vid);
        $p->store_id(1);
        $p->inUse(1);

        $table = '<table class="table table-condensed small">';
        $table .= '<thead><th>UPC</th><th>Brand</th><th>Description</th>
            <th></th></thead><tbody><td></td><td></td><td></td><td></td><td>
            <input type="checkbox" id="checkAll" style="border: 1px solid red;"></td>';

        $pr = new ProdReviewModel($dbc);
        foreach ($p->find() as $obj) {
            $table .= '<tr>';
            $table .= '<td>'.$obj->upc().'</td>';
            $table .= '<td>'.$obj->brand().'</td>';
            $table .= '<td>'.$obj->description().'</td>';
            $pr->reset();
            $pr->upc($obj->upc());
            if ($pr->load()) {
                $table .= '<td>'.$pr->reviewed().'</td>';
            } else {
                $table .= '<td><i>no review date</i></td>';
            }
            $table .= '<td><input type="checkbox"class="chk" name="checked[]" value="'.$obj->upc().'"></td>';
            $table .= '</tr>';
        }
        $table .= '</tbody></table>';

        return <<<HTML
<form class="form-inline" method="get">
    {$table}
    <input type="hidden" name="vendor" value="1">
    <input type="submit" class="btn btn-warning" value="Mark checked items as Reviewed" />
</form><br />
HTML;
    }

    public function draw_table($data,$dbc)
    {
        $table = '<table class="table table-condensed small">';
        $table .= '<thead><th>UPC</th><th>Brand</th><th>Description</th>
            <th>Last Reviewed</th></thead><tbody>';
        $pr = new ProdReviewModel($dbc);
        $table .= '<tr>';
        foreach ($data as $upc => $arr) {
            foreach ($arr as $k => $v) {
                if ($k == 'upc') {
                    $pr->reset();
                    $pr->upc($v);
                    $table .= '<td>'.$v.'</td>';
                } elseif ($k == 'brand') {
                    $table .= '<td>'.$v.'</td>';
                } elseif ($k == 'description') {
                    $table .= '<td>'.$v.'</td>';
                    if ($pr->load()) {
                        $table .= '<td>'.$pr->reviewed().'</td>';
                    } else {
                        $table .= '<td><i>no review date</i></td>';
                    }
                    $table .= '</tr><tr>';
                }
            }

        }
        $table .= '</tr>';
        $table .= '</tbody></table>';

        return $table;
    }

    public function get_upc_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upc = BarcodeLib::padUPC(FormLib::get('upc'));        
        $p = new ProductsModel($dbc);
        $p->upc($upc);
        $p->store_id(1);
        $data = array();
        foreach ($p->find() as $obj) {
            $data[$upc]['upc'] = $obj->upc();
            $data[$upc]['brand'] = $obj->brand();
            $data[$upc]['description'] = $obj->description();
        }
        $table = $this->draw_table($data,$dbc);
        return <<<HTML
<form class="form-inline" method="get">
    {$table}
    <input type="hidden" name="upc" value="{$upc}">
    <input type="hidden" name="save" value="1">
    <input type="submit" class="btn btn-warning" value="Mark as Reviewed" />
</form><br />
HTML;

    }

    public function get_upc_save_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upc = BarcodeLib::padUPC(FormLib::get('upc'));
        $user = FannieAuth::getUID($this->current_user);
        $pr = new ProdReviewModel($dbc);
        $pr->upc($upc);
        $pr->user($user);
        $pr->reviewed(date('Y-m-d'));
        if ($pr->save()) {
            header('Location: '.$_SERVER['PHP_SELF'].'?saved=true');
        } else {
            header('Location: '.$_SERVER['PHP_SELF'].'?saved=false');
        }

        return false;
    }

    public function get_list_save_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upcs = FormLib::get('upcs');
        $user = FannieAuth::getUID($this->current_user);
        $pr = new ProdReviewModel($dbc);
        $data = array();
        $error = 0;
        foreach ($upcs as $upc) {
            $pr->reset();
            $pr->upc($upc);
            $pr->user($user);
            $pr->reviewed(date('Y-m-d'));
            if (!$pr->save()) {
                $error = 1;
            }
        }
        if (!$error) {
            header('Location: '.$_SERVER['PHP_SELF'].'?saved=true');
        } else {
            header('Location: '.$_SERVER['PHP_SELF'].'?saved=false');
        }


        return false;
    }

    public function get_list_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $list = FormLib::get('list');
        $upcs = array();
        $plus = array();
        $chunks = explode("\r\n", $list);
        foreach ($chunks as $key => $str) {
            $upcs[] = BarcodeLib::padUPC($str);
        }
        $data = array();
        $p = new ProductsModel($dbc);
        $input = '';
        foreach ($upcs as $upc) {
            $p->reset();
            $p->upc($upc);
            foreach($p->find() as $obj) {
                $data[$upc]['upc'] = $obj->upc();
                $data[$upc]['brand'] = $obj->brand();
                $data[$upc]['description'] = $obj->description();
            }
            $input .= '<input type="hidden" name="upcs[]" value="'.$upc.'">';
        }
        $table = $this->draw_table($data,$dbc);

        return <<<HTML
<form class="form-inline" method="get">
    {$table}
    {$input}
    <input type="hidden" name="list" value="1">
    <input type="hidden" name="save" value="1">
    <input type="submit" class="btn btn-warning" value="Mark as Reviewed" />
</form><br />
HTML;

    }

    function get_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $model = new VendorsModel($dbc);
        $vselect = '';
        $exclude = array(-1,1,2);
        foreach ($model->find() as $obj) {
            if (!in_array($obj->vendorID(),$exclude)) {
                $vid = $obj->vendorID();
                $vname = $obj->vendorName();
                $vselect .= '<option value="'.$vid.'">'.$vname.'</option>';
            }
        }

        if ($saved = FormLib::get('saved')) {
            $alert = '';
            if ($saved == 'false') {
                $alert = '<div class="alert alert-danger">Save Unsuccessful</div>';
            } else {
                $alert = '<div class="alert alert-success">Save Successful</div>';
            }
        }

        return <<<HTML
<div align="center"><div class="panel panel-default main">
    {$alert}
    <div class="row">
        <div class="col-md-6">
            <form class="form" method="get">
                <div class="form-group input-group-sm">
                    <label>Review a single UPC</label>
                    <input type="text" class="form-control" name="upc" value="">
                </div>
                <div class="form-group">
                    <button class="btn btn-default" type="submit">Update Reviewed Date</button>
                </div>
             </form>
             <div class="divider"></div>
            <form class="form" method="get">
                <div class="form-group">
                    <label>Review Products by Vendor</label>
                    <select class="form-control" name="vendor">
                        <option value="1">Select a Vendor</option>
                        {$vselect}
                    </select>
                </div>
                <div class="form-group">
                    <button class="btn btn-default" type="submit">View Items by Vendor</button>
                </div>
            </form>
            <div class="divider hidden-md hidden-lg"></div>
        </div>
    <form class="form" method="get">
        <div class="col-md-6">
            <div class="form-group">
                <label>Review a list of UPCs</label>
                <textarea class="form-control" rows="10" rows="25"
                    name="list"></textarea>
            </div>
            <div class="form-group">
                <button class="btn btn-default" type="submit">Update List as Reviewed</button>
            </div>
        </div>
    </form>
    </div>
</div></div>
HTML;

    }

   public function javascript_content()
   {
       ob_start();
       ?>
$(document).ready( function() {
    $('#checkAll').click( function () {
       checkAll();
    });
});

function checkAll()
{
    if ( $('#checkAll').prop("checked", true) ) {
        $c = confirm('Mark all as Reviewed?');
        if ($c == true) {
            $('.chk').each( function() {
                this.checked = true;
            });
        }
    } else {
        $('.chk').each( function() {
            this.checked = false;
        });
    }
}



       <?php
       return ob_get_clean();

    }

    public function css_content()
    {
        return <<<HTML
div.main {
    max-width: 600px;
    text-align: left;
    padding:15px;
}
#checkAll {
    border: 5px solid blue;
    background-color: red;
    padding: 55px;
}
.divider {
    background-color: lightgrey;
    height: 2px;
    margin: 10px;
    border-radius: 2px;
}
HTML;
    }

    public function helpContent()
    {
        return <<<HTML
        <p>Mark a product as reviewed once the following
            information has been verified as current:
            <ul>
                <li>Cost</li>
                <li>Sku</li>
                <li>Default Vendor</li>
            </ul>
        </p>
HTML;
    }

}

FannieDispatch::conditionalExec();


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

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class NewClassPage extends FannieRESTfulPage
{
    protected $header = 'New WFC-U Class Page';
    protected $title = 'New WFC-U Class Page';

    public $description = '[New WFC-U Class Page] create product info 
        for new WFC-U classes.';

    function preprocess()
    {
        $this->__routes[] = 'get<create>';
        return parent::preprocess();
    }
    
    protected function get_create_handler()
    {
        
        global $FANNIE_OP_DB;
        $local = FannieDB::get($FANNIE_OP_DB);
        include(dirname(__FILE__) . '/../../src/Credentials/OutsideDB.tunneled.php');
        $remote = $dbc;
        $cons = array(1=>$local,5=>$remote);
        
        $upc = FormLib::get('upc');
        $upc = BarcodeLib::padUPC($upc);
        $pDesc = FormLib::get('pDesc');
        $pBrand = FormLib::get('pBrand');
        $price = FormLib::get('price');
        $likeCode = FormLib::get('likeCode');
        $pDept = FormLib::get('pDept');
        $size = FormLib::get('size');
        $sellonline = FormLib::get('sellonline');
        $expires = FormLib::get('expires');
        $wBrand = FormLib::get('wBrand');
        $wDesc = FormLib::get('wDesc');
        $adText = FormLib::get('adText');
        $saved = array();
        $error = 0;
        
        foreach ($cons as $k => $dbc) {
            $p = new ProductsModel($dbc);
            $p->upc($upc);
            $p->description($pDesc);
            $p->brand($pBrand);
            $p->normal_price($prices);
            $p->department($pDept);
            $p->size($size);
            if (!$saved[] = $p->save()) {
                $error+=$k;
            }
            
            $ul = new UpcLikeModel($dbc);
            $ul->upc($upc);
            $ul->likeCode($likeCode);
            if (!$saved[] = $ul->save()) {
                $error+=10*$k;
            }

            $pu = new ProductUserModel($dbc);
            $pu->upc($upc);
            $pu->enableOnline($sellonline);
            $pu->description($wDesc);
            $pu->brand($wBrand);
            $pu->long_text($adText);
            if (!$saved[] = $pu->save()) {
                $error+=100*$k;
            }
            
            $args = array($upc,$expires);
            $prep = $dbc->prepare("INSERT INTO productExpires (upc, expires)VALUES (?, ?)");
            $dbc->execute($prep,$args);
            if ($dbc->error()) {
                $error+=1000*$k;
            }
        }

        if ($error) {
            header('Location: NewClassPage.php?created=failed&error=' . $error);
            return false;
        } else {
            header('Location: NewClassPage.php?created=success&upc=' . $upc);
            return false;
        }

    }
    
    public function get_view()
    {
        $alert = '';
        $upc = FormLib::get('upc');
        if (FormLib::get('created') == 'success') {
            $alert = '<div class="alert alert-success">Class Created Successfully!<br/>
                View: <a href="../ItemEditorPage.php?searchupc='.$upc.
                '&ntype=UPC&searchBtn=">'.$upc.'</a></div>';
        } elseif (FormLib::get('created') == 'failed') {
            $error = FormLib::get('error');
            $alert = '<div class="alert alert-danger">Page Failed to Create Class. Error-code: '.$error.'</div>';
        }
        
        return <<<HTML
        create a new POS product for a WFC-U Class<br/><br/>
        {$alert}
        {$this->form_content()}
HTML;
    }
    
    public function form_content()
    {
        return <<<HTML
<div>
    <form class="" method="get"> 
        <div class="col-md-3">
            <div class="form-group">
                <label for="upc">UPC</label>
                <input type="text" class="form-control len-md" name="upc" id="upc" autofocus value="99" />
            </div>
            <div class="form-group">
                <label for="pDesc">POS Description</label>
                <input type="text" class="form-control len-lg" name="pDesc" value="CLASS - " />
            </div>
            <div class="form-group">
                <label for="pBrand">POS Brand</label>
                <input type="text" class="form-control len-md" name="pBrand" value="WFC-U" readonly="readonly" />
            </div>
            <div class="form-group">
                <label for="pPrice">Price</label><br/>
                <input type="radio" name="price" value="$12"/> $12 <span style="color: grey">| </span>
                <input type="radio" name="price" value="$15"/> $15 <span style="color: grey">| </span>
                <input type="radio" name="price" value="$20"/> $20 <span style="color: grey">| </span>
                <input type="radio" name="price" value="$25"/> $25 
            </div>
            <div class="form-group">
                <label for="likeCode">Like Code</label><br/>
                <input type="radio" name="likeCode" value="7000"/> 7000 <span style="color: grey">| </span>$25 -> $20<br/>
                <input type="radio" name="likeCode" value="7001"/> 7001 <span style="color: grey">| </span>$15 -> $12<br/>
                <input type="radio" name="likeCode" value="7004"/> 7004 <span style="color: grey">| </span>$15 -> $10<br/>
                <input type="radio" name="likeCode" value="7003"/> 7003 <span style="color: grey">| </span>$20 -> $15<br/>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="form-group">
                <label for="pDept">POS Department</label>
                <input type="text" class="form-control len-sm" name="pDept" value="708" readonly="readonly" />
            </div>
            <div class="form-group">
                <label for="size">Class Size</label>
                <input type="text" class="form-control len-sm" name="pSize" value="" />
            </div>
            <div class="form-group">
                <label for="sellonline">Sell Online</label>
                <input type="checkbox" name="sellonline" value="1" checked="checked"/>
            </div>
            <div class="form-group">
                <label for="expires">Expires</label>
                <input type="date" class="form-control date-field len-md" name="expires" value="" />
            </div>
        </div>
       
        <div class="col-md-6">
            <div class="form-group">
                <label for="wBrand">Web Brand</label>
                <input type="text" class="form-control len-md" name="wBrand" value="WFC-U" readonly="readonly"/>
            </div>
            <div class="form-group">
                <label for="wDesc">Web Description</label>
                <textarea type="text" class="form-control len-lg" name="wDesc" value="" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label for="adText">Web Paragraph</label>
                <textarea type="text" class="form-control  len-lg" name="adText" value="" rows="5"></textarea>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default" name="create" value="1">Create WFC-U Class</button>
            </div>
        </div>
    </form>
</div>   
HTML;
    }
    
    public function css_content()
    {
        return <<<HTML
.btn-default {
    border: 2px solid brown;
    color: brown;
}
.len-sm {
    max-width: 50px;
}
.len-md {
    max-width: 200px;
}
.len-lg {
    max-width: 400px;
}

HTML;
    }
    
    public function javascriptContent()
    {
        return <<<HTML
$(document).ready(function() {
    var input = $("#upc");
    var len = input.val().length;
    input[0].focus();
    input[0].setSelectionRange(len, len);
});
HTML;
    }

    public function helpContent()
    {
        return '<p></p>';
    }
}

FannieDispatch::conditionalExec();


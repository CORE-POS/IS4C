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
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
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
        $this->__routes[] = 'post<exists>';
        return parent::preprocess();
    }

    protected function post_exists_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upc = FormLib::get('upc');
        $upc = BarcodeLib::padUPC($upc);
        $args = array($upc);
        $prep = $dbc->prepare('SELECT upc FROM products WHERE upc = ?');
        $res = $dbc->execute($prep, $args);
        $row = $dbc->fetchRow($res);
        echo $exists = $row['upc'];

        return false;

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
        $sellonline = 0;
        $expires = FormLib::get('expires') . ' 00:00:00';
        $wBrand = FormLib::get('wBrand');
        $wDesc = FormLib::get('wDesc');
        $adText = FormLib::get('adText');
        $saved = array();
        $error = 0;

        $ul = new UpcLikeModel($local);
        $ul->upc($upc);
        $ul->likeCode($likeCode);
        if (!$saved[] = $ul->save()) {
            $error+=10;
        }
        
        foreach ($cons as $k => $dbc) {
            $p = new ProductsModel($dbc);
            $p->upc($upc);
            $p->description($pDesc);
            $p->brand($pBrand);
            $p->normal_price($price);
            $p->department($pDept);
            $p->size($size);
            $p->inUse(1);
            $p->tax(0);
            if ($k === 5) {
                $p->store_id(1);
            }
            if (!$saved[] = $p->save()) {
                $error+=$k;
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
            $prep = $dbc->prepare("INSERT INTO productExpires (upc, expires) VALUES (?, ?)");
            $dbc->execute($prep,$args);
            if ($dbc->error()) {
                $error+=1000*$k;
            }
        }

        if ($error) {
            header('Location: NewClassPage.php?created=failed&error=' . $error . '&upc=' . $upc);
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
        $upc = BarcodeLib::padUPC($upc);
        $ln = '<a href="../ItemEditorPage.php?searchupc='.$upc.
                '&ntype=UPC&searchBtn=">'.$upc.'</a>';
        if (FormLib::get('created') == 'success') {
            $alert = '<div class="alert alert-success">Class Created Successfully!<br/>
                View: '.$ln.' | 
                <a href="NewClassPage.php">Create Another</a></div>';
        } elseif (FormLib::get('created') == 'failed') {
            $error = FormLib::get('error');
            $alert = '<div class="alert alert-danger">Something went wrong. Error-code: 
                '.$error.'<br/>View: '.$ln.'</div>';
        }

        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $prep = $dbc->prepare("SELECT upc FROM products WHERE upc LIKE '0000099%'
            ORDER BY upc DESC LIMIT 1;");
        $res = $dbc->execute($prep);
        $row = $dbc->fetchRow($res);
        $newClassUpc = $row['upc'] + 1;
        
        return <<<HTML
        <div align="center">
        {$alert}
        <div class="panel panel-default" style="max-width: 900px;">
        <div class="panel-heading" id="heading"><strong>Create a new WFC-U Class</strong></div>
        <div class="panel-body" style="text-align: left;">
            {$this->form_content($newClassUpc)}
        </div></div></div>
HTML;
    }
    
    public function form_content($newClassUpc)
    {
        $this->addOnloadCommand("$('#date').datepicker({dateFormat: 'mm-dd-yy'});");

        return <<<HTML
<div>
    <form class="" method="get"> 
        <div class="col-md-3">
            <div class="form-group">
                <label for="upc">UPC</label><span id="upc-warning"></span>
                <input type="text" class="form-control len-md" name="upc" id="upc" autofocus value="00000$newClassUpc" required/>
            </div>
            <div class="form-group">
                <label for="date">Date</label>
                <input type="text" class="form-control len-md" name="date" id="date" required/>
            </div>
            <div class="form-group">
                <label for="pDesc">POS Description</label>
                <input type="text" class="form-control len-lg" name="pDesc" id="pDesc" value="CLASS - "
                    maxlength="30"  onkeyup="this.value = this.value.toUpperCase();" required/>
            </div>
            <div class="form-group">
                <label for="pBrand">POS Brand</label>
                <input type="text" class="form-control len-md" name="pBrand" value="WFC-U" readonly="readonly" required/>
            </div>
            <div class="form-group">
                <label for="Price">Price</label><br/>
                <input type="number" class="form-control len-lg" name="price" min="0" />
            </div>
            <!--
            <div class="form-group">
                <label for="likeCode">Like Code</label><br/>
                <input type="radio" name="likeCode" value="7001"/> 7001 <span style="color: grey">| </span>$15 -> $12<br/>
                <input type="radio" name="likeCode" value="7004"/> 7004 <span style="color: grey">| </span>$15 -> $10<br/>
                <input type="radio" name="likeCode" value="7003"/> 7003 <span style="color: grey">| </span>$20 -> $15<br/>
                <input type="radio" name="likeCode" value="7003"/> 7000 <span style="color: grey">| </span>$20 -> $25<br/>
                <input type="radio" name="likeCode" value="7005"/> 7005 <span style="color: grey">| </span>$40 -> $30<br/>
                <input type="radio" name="likeCode" value="7006"/> 7006 <span style="color: grey">| </span>$60 -> $40<br/>
            </div>
            -->
            <input type="hidden" name="likeCode" value="" selected/> None <br/>
            <div class="form-group">
                <label for="expires">Expires</label>
                <input type="text" class="form-control date-field len-md" name="expires" id="expires" value="" required/>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="form-group">
                <label for="pDept">POS Department</label>
                <input type="text" class="form-control len-sm" name="pDept" value="708" readonly="readonly" />
            </div>
            <div class="form-group">
                <label for="size">Class Size</label>
                <input type="text" class="form-control len-sm" name="size" value="" required/>
            </div>
            <div class="form-group">
                <label for="classname">Class Name</label>
                <input type="text" class="form-control len-md" name="classname" id="classname" value="" required/>
            </div>
            <div class="form-group">
                <label for="start">Start Time</label>
                <input type="text" class="form-control len-lg" name="start" id="start" value="" required/>
            </div>
            <div class="form-group">
                <label for="end">End Time</label>
                <input type="text" class="form-control len-lg" name="end" id="end" value="" required/>
            </div>
            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" class="form-control len-md" name="location" id="location" value="" required/>
            </div>
            <div class="form-group">
                <label for="sellonline">Sell Online</label>
                <input type="checkbox" name="sellonline" value="1" checked=""/>
            </div>
        </div>
       
        <div class="col-md-6">
            <div class="form-group">
                <label for="wBrand">Web Brand</label>
                <input type="text" class="form-control len-md" name="wBrand" value="WFC-U" readonly="readonly"/>
            </div>
            <div class="form-group">
                <label for="wDesc">Web Description</label>
                <textarea type="text" class="form-control len-lg" name="wDesc" id="wDesc" value="" rows="2" 
                     style="overflow-y: hidden">
MM-DD-YYYY CLASS_NAME &#13;&#10;
START - END LOCATION
                </textarea>
            </div>
            <div class="form-group">
                <label for="adText">Web Paragraph</label>
                <textarea type="text" class="form-control  len-lg" name="adText" value="" 
rows="20"></textarea>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default" name="create" value="1" id="submit-btn">Create WFC-U Class</button>
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
.alert {
    max-width: 900px;
}

HTML;
    }
    
    public function javascriptContent()
    {
        return <<<JAVASCRIPT
$('#upc').keyup(function(e){
    var upc = $(this).val();
    var length = upc.length;
    if (length == 8) {
        $.ajax({
            type: 'post',
            data: 'upc='+upc+'&exists=1',
            success: function(response)
            {
                if (response != '') {
                    $('#upc-warning').html("<div class='alert alert-danger'>Product Already Exists</div>");
                    $('#submit-btn').attr('disabled', true);
                } else {
                    $('#upc-warning').html("");
                    $('#submit-btn').attr('disabled', false);
                }
            }
        });
    }
});
$('#date').change(function(){
    var dateVal = $(this).val();

    var wDesc = $('#wDesc').val();  
    wDesc = wDesc.replace('MM-DD-YYYY', dateVal);
    $('#wDesc').val(wDesc);

    //var month = dateObj.getMonth();
    //var day = dateObj.getDate();
    //var year = dateObj.getFullYear();
    //var expireDate = year+'-'+month+'-'+day;
    //$('#expires').val(expireDate);
});
$(document).ready(function() {
    var input = $("#upc");
    var len = input.val().length;
    input[0].focus();
    input[0].setSelectionRange(len, len);
    autofill();
});
function autofill() {
    $('#upc').change( function() {
        var upc = $('#upc').val();
        mo = upc.substr(2,2);
        da = upc.substr(4,2);
        ye = upc.substr(6,2);
        var date = mo+'-'+da+'-20'+ye;
        var wDesc = $('#wDesc').val();  
        wDesc = wDesc.replace('MM-DD-YYYY',date);
        $('#wDesc').val(wDesc);
        if (da - 2 > 0) {
            da = da - 2;
        } else if (mo - 1 > 0) {
            mo = mo - 1;
            da = 28;
        } else {
            ye = ye - 1;
            mo = 12;
            da = 28;
        }
        exp = '20'+ye+'-'+mo+'-'+da;
        $('#expires').val(exp);
    });
    $('#classname').change( function() {
        var name = $('#classname').val();
        var wDesc = $('#wDesc').val();
        wDesc = wDesc.replace('CLASS_NAME',name);
        $('#wDesc').val(wDesc);
    });
    $('#start').change( function() {
        var start = $('#start').val();
        start = start.toUpperCase();
        var wDesc = $('#wDesc').val();
        wDesc = wDesc.replace('START',start);
        $('#wDesc').val(wDesc);
    });
    $('#end').change( function() {
        var end = $('#end').val();
        end = end.toUpperCase();
        var wDesc = $('#wDesc').val();
        wDesc = wDesc.replace('END',end);
        $('#wDesc').val(wDesc);
    });
    $('#location').change( function() {
        var loc = $('#location').val();
        var wDesc = $('#wDesc').val();
        wDesc = wDesc.replace('LOCATION',loc);
        $('#wDesc').val(wDesc);
    });
}
    $('#pDesc').change( function() {
        var temp = $('#pDesc').val();
        temp = temp.toUpperCase();
        $('#pDesc').val(temp);
    });
JAVASCRIPT;
    }

    public function helpContent()
    {
        return '<p></p>';
    }
}

FannieDispatch::conditionalExec();


<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class PriceRuleEditor extends FannieRESTfulPage
{
    protected $header = 'Price Rule Editor';
    protected $title = 'Price Rule Editor';

    public $description = '[Price Rule Editor] update price rules for a
        set of products.';

    protected $thead = <<<HTML
<th>UPC</th> <th>Brand</th> <th>Description</th> <th>PRT</th> <th>PRT Name</th> <th>PRID</th> <th>Max Price</th> <th>Details</th> <th>Review Date</th> <th>MSRP</th>
HTML;

    public function preprocess()
    {
        $this->__routes[] = "get<brandList>";
        $this->__routes[] = "get<list>";
        $this->__routes[] = "get<upc>";

        return parent::preprocess();
    }

    public function get_upc_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upcs = FormLib::get('upcs');
        $upcs = explode(",", $upcs);

        $prts = FormLib::get('prts');
        $prts = explode(",", $prts);

        $detailses = FormLib::get('detailses');
        $detailses = explode(",", $detailses);

        $reviewDates = FormLib::get('reviewDates');
        $reviewDates = explode(",", $reviewDates);

        $items = array();
        foreach ($upcs as $k => $upc) {
            $items[$upc]['prt'] = $prts[$k];
            $items[$upc]['details'] = $detailses[$k];
            $items[$upc]['reviewDate'] = $reviewDates[$k];
        }

        $testVal = '';
        $model = new PriceRulesModel($dbc);
        $saved = 'yep!';

        $prep = $dbc->prepare("UPDATE products SET price_rule_id = ? WHERE upc = ?");

        foreach ($items as $upc => $row) {
            $priceRuleTypeID = $row['prt'];
            $details = $row['details'];
            $reviewDate = $row['reviewDate'];
            $maxPrice = (float) filter_var($details, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            $model->priceRuleTypeID($priceRuleTypeID);
            $model->details($details);
            $model->maxPrice($maxPrice);
            $model->reviewDate($reviewDate);
            $prid = $model->save();
            if ($saved !== false) {
                if ($row['prt'] != 0) {
                    $res = $dbc->execute($prep, array($prid, $upc));
                } else {
                    $res = $dbc->execute($prep, array(0, $upc));
                }
            }
        }

        $json = array('items' => $items,
            'prid-eg' => $prid);
        echo json_encode($json);

        return false;
    }

    private function getPriceRuleTypes($dbc)
    {
        $types = array();

        $prep = $dbc->prepare("SELECT * FROM PriceRuleTypes");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $prtID = $row['priceRuleTypeID'];
            $desc = $row['description'];
            $types[$prtID] = $desc;
        }

        return $types;
    }

    private function getPriceRuleOpts($prTypes, $cur='')
    {
        $opts = '<option value="0"></option>';
        foreach ($prTypes as $prtID => $desc) {
            $sel = ($cur == $desc) ? ' selected ' : '';
            $opts .= "<option value=\"$prtID\" $sel>$desc</option>";
        }

        return $opts;
    }

    private function getTableData($dbc, $searchType, $searchValue)
    {
        $items = array();
        $where = ($searchType == 'VENDOR') ? ' default_vendor_id = ? ' : ' p.brand = ? ';
        if ($searchType == 'UPCS') {
            $tmp = '';
            foreach ($searchValue as $k => $upc) {
                if (strlen($upc) > 1) {
                    $tmp .= "$upc";
                    if (array_key_exists($k+1, $searchValue) && strlen($searchValue[$k+1]) > 1) {
                        $tmp .= ", ";
                    }
                }
            }
            $args = array();
            $where = " p.upc IN ($tmp) ";
        } else {
            $args = array($searchValue);
        }

        $query = "SELECT p.upc, p.brand, p.description, priceRuleID,
            t.priceRuleTypeID, maxPrice, details, t.description AS tdesc,
            v.srp, r.reviewDate
            FROM products AS p
                LEFT JOIN PriceRules AS r ON r.priceRuleID=p.price_rule_id
                LEFT JOIN PriceRuleTypes AS t ON t.priceRuleTypeID=r.priceRuleTypeID
                LEFT JOIN vendorItems AS v ON v.upc=p.upc AND v.vendorID=p.default_vendor_id
            WHERE $where
            GROUP BY p.upc";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        while ($row = $dbc->fetchRow($res)) {
            $upc = $row['upc'];
            $brand = $row['brand'];
            $desc = $row['description'];
            $prtID = $row['priceRuleTypeID'];
            $prID = $row['priceRuleID'];
            $maxPrice = $row['maxPrice'];
            $details = $row['details'];
            $tdesc = $row['tdesc'];
            $srp = $row['srp'];
            $reviewDate = $row['reviewDate'];
            $items[$upc]['brand'] = $brand;
            $items[$upc]['desc'] = $desc;
            $items[$upc]['prtID'] = $prtID;
            $items[$upc]['tdesc'] = $tdesc;
            $items[$upc]['prID'] = $prID;
            $items[$upc]['maxPrice'] = $maxPrice;
            $items[$upc]['details'] = $details;
            $items[$upc]['reviewDate'] = $reviewDate;
            $items[$upc]['srp'] = $srp;
        }

        echo $dbc->error();

        return $items;
    }

    public function get_list_view()
    {
        return $this->get_brandList_view();
    }

    public function get_brandList_view()
    {

        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $brand = FormLib::get('brandList', false);
        $vendorID = FormLib::get('vendorList', false);
        $list = Formlib::get('list', array());
        if (empty($list)) {
            $SEARCH_TYPE = ($vendorID == false) ? 'BRAND' : 'VENDOR';
            $SEARCH_REF = ($vendorID == false) ? $brand : $vendorID;
        } else {
            $SEARCH_TYPE = 'UPCS'; 
            $SEARCH_REF = explode("\n", $list);
        }

        $prTypes = $this->getPriceRuleTypes($dbc);

        $vendorID = FormLib::get('vendorList', false);
        $items = array();
        $td = "<tr><td colspan=\"4\" class=\"grey\"></td><td><select class=\"form-control price-rule-select-all alert-notify\">{$this->getPriceRuleOpts($prTypes)}</select></td>
            <td colspan=\"2\" class=\"grey\"></td>
            <td><input class=\"form-control alert-notify edit-details-all\" type=\"text\" /></td>
            <td><input class=\"form-control alert-notify edit-reviewDate-all date-field\" type=\"text\" /></td>
            <td class=\"grey\"></td>
            </tr>";

        $items = $this->getTableData($dbc, $SEARCH_TYPE, $SEARCH_REF);

        foreach ($items as $upc => $row) {
            $td .= "<tr><td>$upc</td>";
            foreach ($row as $k => $v) {
                if ($k == 'tdesc') {
                    $td .= "<td><select name=\"\" data-upc=\"$upc\" class=\"form-control price-rule-select\">{$this->getPriceRuleOpts($prTypes, $v)}</select></td>";
                } elseif ($k == 'details') {
                    $td .= "<td contentEditable=\"true\" data-upc=\"$upc\" class=\"editable-details\"> $v</td>";
                } elseif ($k == 'reviewDate') {
                    $td .= "<td contentEditable=\"true\" data-upc=\"$upc\" class=\"editable-reviewDate\"> $v</td>";
                } else {
                    $td .= "<td> $v</td>";
                }
            }
            $td .= "</tr>";
        }

        return <<<HTML
<div class="row">
    <div class="col-lg-4">
        <h5><a href="#" onClick="window.location.href = 'PriceRuleEditor.php';" class="btn btn-default">Back</a></h5>
    </div>
    <div class="col-lg-4"></div>
    <div class="col-lg-4" align="right">
        <div class="form-group">
            <a href="#" class="btn btn-danger" onClick="save(); return false;">Save Changes</a>
        </div>
    </div>
</div>
<table class="table table-bordered" id="mytable"><thead>{$this->thead}</thead><tbody>$td</tbody></table>
HTML;
    }

    public function get_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $vendOpts = "<option value=0></option>";
        $brandOpts = "";
        $list = FormLib::get('list', array());
        $list = explode("\n", $list);

        $vendListP = $dbc->prepare("SELECT vendorName, vendorID
            FROM vendors");
        $vendListR = $dbc->execute($vendListP);
        while ($row = $dbc->fetchRow($vendListR)) {
            $id = $row['vendorID'];
            $name = $row['vendorName'];
            $vendOpts .= "<option value=\"$id\">$name</option>";
        }

        $brandListP = $dbc->prepare("SELECT brand FROM products WHERE inUse = 1 GROUP BY brand");
        $brandListR = $dbc->execute($brandListP);
        while ($row = $dbc->fetchRow($brandListR)) {
            $name = $row['brand'];
            $brandOpts .= "<option value=\"$name\">$name</option>";
        }

        return <<<HTML
<div class="row" style="padding-top: 25px">
    <div class="col-lg-2"></div>
    <div class="col-lg-8">
        <div class="row">
            <div class="col-lg-3"></div>
            <div class="col-lg-6">
                <form>
                    <label>Enter UPCs</label>
                    <div class="form-group">
                        <textarea name="list" class="form-control" rows=10>$list</textarea>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-default form-control" />
                    </div>
                </form>
            </div>
            <div class="col-lg-3"></div>
        </div>
    </div>
    <div class="col-lg-2"></div>
</div>
HTML;
    }

    public function javascriptContent()
    {
        return <<<JAVASCRIPT
var saved = false;

var calcLimit = function() {
    $('tr').each(function(){
        var srp = $(this).find('td:eq(8)').text();
        var per = $('#modPercent').val();
        var v = srp - (per * (0.01 * srp));
        v = v.toFixed(2);
        console.log('srp: '+srp+', per: '+per+', v: '+v);
        var curText = $(this).find('td:eq(7)').text();
        $(this).find('td:eq(7)').text(curText + ' ' + v);
    });
}

// use this to edit the price rule type
$('.price-rule-select').on('change', function(){
    var newValue = $(this).find(":selected").val();
    var upc = $(this).attr('data-upc');
    console.log(newValue+', '+upc);
});

$('.price-rule-select-all').on('change', function(){
    var text = $(this).find(':selected').text();
    $(".price-rule-select option").filter(function() {
        return $(this).text() == text;
    }).prop('selected', true);
});

$('.edit-details-all').on('keydown', function(e){
    let pressed = e.keyCode;
    if (pressed == 188) {
        e.preventDefault();
    }
});

$('.edit-reviewDate-all').on('change', function(e){
    var value = $(this).val();
    $('.editable-reviewDate').text(value + " 00:00:00");
});

$('.edit-details-all').on('change', function(){
    var value = $(this).val();
    $('.editable-details').text(value);
});

// use this to edit details
var lastText = '';
$('.editable-details').focus(function(){
    lastText = $(this).text();
    console.log(lastText);
});

var save = function()
{
    c = confirm('Make all changes permanent?');
    if (c == true) {
        var items = [];
        var upcs = [];
        var prts = [];
        var detailses = [];
        var rvds = [];
        $('#mytable tr').each(function(){
            var upc = $(this).find('td:eq(0)').text();
            var prt = $(this).find('td:eq(4)').find(":selected").val();
            $(this).find(":selected").val();
            var details = $(this).find('td:eq(7)').text();
            details = encodeURIComponent(details);
            var rvd = $(this).find('td:eq(8)').text();
            rvd = encodeURIComponent(rvd);
            items.push([upc, prt, details, rvd]);

            upcs.push(upc);
            prts.push(prt);
            detailses.push(details);
            rvds.push(rvd);
        });
        $.ajax({
            type : 'get',
            data: 'upc=123'+'&upcs='+upcs+'&prts='+prts+'&detailses='+detailses+'&reviewDates='+rvds,
            url: 'PriceRuleEditor.php',
            dataType: 'json',
            beforeSend: function() {
                let alertWait = document.createElement('div');

                alertWait.classList.add('well');
                alertWait.innerHTML = "Processing, please wait";
                alertWait.id = "alertWaitID";

                $('#primary-content').prepend(alertWait);
            },
            success: function(resp) {
                let alertSuccess = document.createElement('div');

                alertSuccess.classList.add('alert');
                alertSuccess.classList.add('alert-success');
                alertSuccess.innerHTML = 'Save successful!';

                $('#primary-content').prepend(alertSuccess);
                $('#alertWaitID').remove();
                console.log(resp);
            },
            error: function(resp) {
                alert("error!");
                console.log(resp);
            }
        });
    }
}

JAVASCRIPT;
    }

    public function css_content()
    {
        return <<<HTML
.alert-notify {
    background-color: #F2F2F2;
}
.grey {
    background-color: #F2F2F2;
}
HTML;
    }

    public function helpContent()
    {
        return <<<HTML
HTML;
    }
}

FannieDispatch::conditionalExec(); 

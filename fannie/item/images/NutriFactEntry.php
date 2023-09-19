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
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class NutriFactEntry extends FannieRESTfulPage
{
    protected $header = 'NutriFacts Image Entry';
    protected $title = 'NutriFacts Image Entry';

    public $description = '[NutriFacts Image Entry] saves Nutrition Facts information in POS';
    public $has_unit_tests = true;

    public $valSuffix = array(
        "servingSize" => '',
        "numServings" => '',
        "calories" => '',
        //"fatCalories" => '',
        "totalFat" => 'g',
        "saturatedFat" => 'g',
        "transFat" => 'g',
        "cholesterol" => 'mg',
        "sodium" => 'mg',
        "totalCarbs" => 'g',
        "fiber" => 'g',
        "sugar" => 'g',
        "protein" => 'g'       
    ); 

    function preprocess()
    {
        $this->__routes[] = 'post<nutriFacts>';
        $this->__routes[] = 'post<nutrient>';
        $this->__routes[] = 'post<ingredients>';

        return parent::preprocess();
    }

    private function normalizeVal($col)
    {
        $suffix = isset($this->valSuffix[$col]) ? $this->valSuffix[$col] : '';
        return $suffix;
    }

    public function post_ingredients_handler()
    {
        $upc = FormLib::get('upc');
        $upc = BarcodeLib::padUPC($upc);
        $ingredients = FormLib::get('ingredients');
        $dbc = $this->connection;
        //$json = array();
        $json = array('test'=>'passed');
        $json['upc'] = $upc;
        $json['ingredients'] = $ingredients;

        $args = array($ingredients, $upc);
        $prep = $dbc->prepare("UPDATE productUser set long_text = ? WHERE upc = ?");
        $dbc->execute($prep, $args);

        $args = array($upc);
        $prep = $dbc->prepare("SELECT long_text FROM productUser WHERE upc = ?");
        $res = $dbc->execute($prep, $args);
        while ($row = $dbc->fetchRow($res)) {
            $json['saved'] = $row['long_text'];
        }

        echo json_encode($json); 
        return false;
    }

    public function post_nutrient_handler()
    {
        $dbc = $this->connection;
        $mode = FormLib::get('nutrient');
        $upc = FormLib::get('upc');
        $upc = BarcodeLib::padUPC($upc);
        $col = FormLib::get('col');
        $val = FormLib::get('value');
        $val = preg_replace('/[^0-9.]+/', '', $val);
        $name = FormLib::get('name');
        $stdDVs = array();

        // lastly, if both numbers were already entered, don't make a change to the other if it's within 1 percent of being correct?

        if ($mode == 1) {
            $prep = $dbc->prepare("SELECT * FROM NutriFactStd");
            $res = $dbc->execute($prep);
            while ($row = $dbc->fetchRow($res)) {
                $stdDVs[$row['name']]['units'] = $row['units'];
                $stdDVs[$row['name']]['stdUnit'] = $row['stdUnit'];
            }
            $perDV = 100 * ($val / $stdDVs[$name]['units']);
            $val = $val . $stdDVs[$name]['stdUnit'];

            $updateA = array($val, $perDV, $upc, $name);
            $updateP = $dbc->prepare("UPDATE NutriFactOptItems SET amount = ?, percentDV = ? WHERE upc = ? AND name = ?"); 
            $updateW = $dbc->execute($updateP, $updateA);
        }
        if ($mode == 2) {
            $prep = $dbc->prepare("SELECT * FROM NutriFactStd");
            $res = $dbc->execute($prep);
            while ($row = $dbc->fetchRow($res)) {
                $stdDVs[$row['name']]['units'] = $row['units'];
                $stdDVs[$row['name']]['stdUnit'] = $row['stdUnit'];
            }
            //$perDV = 100 * $val / $stdDVs[$name]['units'];
            $perDV = $val;
            //$val = $val . $stdDVs[$name]['stdUnit'];
            $val = $stdDVs[$name]['units'] * ($perDV / 100);
            $val = $val . $stdDVs[$name]['stdUnit'];

            $updateA = array($val, $perDV, $upc, $name);
            $updateP = $dbc->prepare("UPDATE NutriFactOptItems SET amount = ?, percentDV = ? WHERE upc = ? AND name = ?"); 
            $updateW = $dbc->execute($updateP, $updateA);
        }

        $json = array('test'=>'yes, the test was successful, whoo');
        $json['col'] = $col;
        $json['upc'] = $upc;
        $json['val'] = $val;
        $json['name'] = $name;
        $json['dv'] = floor($perDV);
        $json['mode'] = $mode;

        echo json_encode($json); 
        return false;
    }

    public function post_nutriFacts_handler()
    {
        $upc = FormLib::get('upc');
        $upc = BarcodeLib::padUPC($upc);
        $dbc = $this->connection;
        $json = array();

        $args = array($upc);
        $optP = $dbc->prepare("SELECT * FROM NutriFactOptItems WHERE upc = ?");
        $res = $dbc->execute($optP, $args);
        $numRows = $dbc->numRows($res);
        while ($row = $dbc->fetchRow($res)) {
            $json[$row['name']]['amount'] = $row['amount'];
            $json[$row['name']]['percentDV'] = $row['percentDV'];
        }

        if ($numRows == 0) {
            $args = array($upc, $upc, $upc, $upc);
            $prep = $dbc->prepare("
                INSERT INTO NutriFactOptItems (upc, name, amount, percentDV) VALUES (?, 'Calcium', null, null);
                INSERT INTO NutriFactOptItems (upc, name, amount, percentDV) VALUES (?, 'Iron', null, null);
                INSERT INTO NutriFactOptItems (upc, name, amount, percentDV) VALUES (?, 'Potassium', null, null);
                INSERT INTO NutriFactOptItems (upc, name, amount, percentDV) VALUES (?, 'Vitamin D', null, null);
            ");
            $dbc->execute($prep, $args);

            $args = array($upc);
            $res = $dbc->execute($optP, $args);
            $numRows = $dbc->numRows($res);
            while ($row = $dbc->fetchRow($res)) {
                $json[$row['name']]['amount'] = $row['amount'];
                $json[$row['name']]['percentDV'] = $row['percentDV'];
            }
        }

        $prep = $dbc->prepare("SELECT long_text FROM productUser WHERE upc = ?");
        $res = $dbc->execute($prep, $args);
        while ($row = $dbc->fetchRow($res)) {
            $json['ingredients'] = $row['long_text'];
        }

        echo json_encode($json); 
        return false;
    }

    public function post_id_handler()
    {
        $col = FormLib::get('col');
        $upc = FormLib::get('upc');
        $val = FormLib::get('val');
        if ($col != 'servingSize') {
            $val = preg_replace('/[^0-9.]+/', '', $val);
            if (substr($val, - strlen($this->normalizeVal($col))) != $this->normalizeVal($col)) {
                $val = $val . $this->normalizeVal($col);
            }
        }

        $dbc = $this->connection;
        $args = array($upc, $val, $val );
        $query = "INSERT INTO NutriFactReqItems (upc, $col) VALUES (?, ?) ON DUPLICATE KEY UPDATE $col = ?";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);

        $json = array('error' => 0);
        if ($dbc->error())
            $json['error'] = $dbc->error();
        $json['query'] = $query;
        $json['upc'] = $upc;
        $json['col'] = $col;
        $json['val'] = $val;

        $query = "SELECT $col FROM NutriFactReqItems WHERE upc = ?";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($upc));
        $row = $dbc->fetchRow($res);
        $newval = $row[$col];
        $json['newval'] = $newval;

        echo json_encode($json);

        return false;
    }

    public function get_view()
    {
        $dbc = $this->connection;

        //$nutriCols = array("servingSize","calories","fatCalories","totalFat","saturatedFat","transFat","cholesterol","sodium","totalCarbs","fiber","sugar","addedSugar","protein");
        $nutriCols = array("servingSize","calories","totalFat","saturatedFat","transFat","cholesterol","sodium","totalCarbs","fiber","sugar","addedSugar","protein");
        $th = "<th>upc</th><th>description</th>";
        foreach ($nutriCols as $col) {
            $th .= "<th>$col</th>";
        }

        $prep = $dbc->prepare("SELECT 
*, p.upc AS upc FROM products AS p 
LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
LEFT JOIN NutriFactReqItems AS n ON p.upc=n.upc 
LEFT JOIN NutriFactOptItems AS o ON o.upc=n.upc
WHERE p.upc < 1000
#AND p.inUse = 1
AND m.superID = 1
#AND (
#    (p.last_sold > NOW() - INTERVAL 90 DAY OR p.last_sold IS NULL)
#OR
#    ((p.created > NOW() - INTERVAL 30 DAY) OR (p.modified > NOW() - INTERVAL 7 DAY))
#)
OR p.upc IN (
# NON BULK ITEMS TO INCLUDE (For bulk kombucha)
'0086001000062','0086001000061','0086001000060','0086001000064','0086001000063',
'0009103711780', '0001396446924','0083765483417','0009103711785','0009103711788','0000376548341'
)

GROUP BY p.upc
");
        $res = $dbc->execute($prep);
        $td = "";

        while ($row = $dbc->fetchRow($res)) {
            $upc = $row['upc'];
            $desc = $row['description'];
            $td .= "<tr>";
            $td .= "<td class=\"upc\">$upc</td>";
            $td .= "<td class=\"desc\">$desc</td>";
            foreach ($nutriCols as $col) {
                $value = $row[$col];
                $td .= "<td data-test=\"$col\">$value</td>";
            }
            $td .= "</tr>";
        }

        return <<<HTML
<input type="hidden" id="mode" value=1 />
<ul>
    <li><a href= "ScannieBulkWrapper.php">Print Bulk Bin Labels</a></li>
    <li><a href= "IngredientSOPFormatter.php">Ingredients SOP Formatter</a></li>
</ul>
<div class="form-group">
    <button id="show-all" class="btn btn-default">Show All</button>
</div>
<div class="form-group">
    <label>Find UPC <i> must key in upc, don't paste</i></label>
    <input id="search-upc" class="form-control" type="text"/>
</div>
<div class="table-responsive">
    <table class="table table-bordered table-condensed" id="mytable">
        <thead>$th</thead>
        <tbody>$td</tbody>
    </table>
</div>
<div class="table-responsive">
    <table class="table table-bordered table-condensed" id="nutriTable">
        <thead>
            <th>Nutrient Name</th>
            <th>Amount</th>
            <th>PercentDV</th>
        </thead>
        <tbody id="nutriTableBody">
        </tbody>
    </table>
</div>
<div class="form-group" id="ingredients-placeholder">
    <label>Ingredients</label>
</div>
HTML;
    }

    public function javascriptContent()
    {
        $dbc = $this->connection;
        $json = array();
        $prep = $dbc->prepare("SELECT * FROM NutriFactStd");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $json[$row['name']] = $row['units'];
        }
        $json = json_encode($json);

        return <<<JAVASCRIPT
var nutriFactStd = $json;
var mode = $('#mode');
var upc = 0;
var amSA = null;
var dvSA = null;
// don't allow this, it messes up the ajax for nutrients and ingredients, they are listening for keypreess
//$('.upc').click(function(){
//    upc = $(this).text();
//    $('.upc').each(function(){
//        let curupc = $(this).text();
//        if (curupc != upc) {
//            $(this).parent().hide();
//        }
//    });
//});
$('#show-all').click(function(){
    $('.upc').each(function(){
        $(this).parent().show();
    });
    $('#search-upc').val('')
        .focus();
});
$('td').each(function(){
    if (!$(this).hasClass('upc') && !$(this).hasClass('desc')) {
        $(this).attr('contentEditable', 'true');
    }
});
last = '';
$('td').click(function(){
    last = $(this).text();
});
$('td').focusout(function(){
    var val = $(this).text();
    var col = $(this).attr('data-test');
    var upc = $(this).parent().find('td:nth-child(1)').text();
    var cell = $(this);
    if (last != val) {
        $.ajax({
            type: 'post', 
            data: 'id=1&col='+col+'&upc='+upc+'&val='+val,
            dataType: 'json',
            url: 'NutriFactEntry.php',
            success: function(resp)
            {
                //console.log(value); console.log(upc); console.log(col);
                console.log(resp);
                cell.text(resp.newval);
            }
        });
    }
});
var ajaxCall2 = function(upc) {
    $('#nutriTableBody').text("");
    $('#ingredients-placeholder').text("");
    $.ajax({
        type: 'post', 
        data: 'nutriFacts=1&upc='+upc,
        dataType: 'json',
        url: 'NutriFactEntry.php',
        success: function(resp)
        {
            //console.log(resp);
            if ($('#nutriTableBody').text() == "") {
                $.each(resp, function(name, value) {
                    if (name != 'ingredients') {
                        let amount = value.amount;
                        let dv = value.percentDV;
                        let content = "<tr><td>"+name+"</td><td class='nutriChangeElement' contentEditable='true' onFocus='amSA=this.innerHTML;' onFocusOut='nutriChange(\"amount\", this.innerHTML, "+upc+", \""+name+"\" );'>"+amount+"</td><td contentEditable='true' onFocus='dvSA=this.innerHTML;' onFocusOut='dvChange(\"dv\", this.innerHTML, "+upc+", \""+name+"\" );'>"+dv+"</td></tr>";
                        $('#nutriTableBody').append(content);
                    }
                    if (name == 'ingredients') {
                        let content = '<label>Ingredients</label><textarea id="ingredients" rows=4 onFocusOut="ingredientChange('+upc+', this.value);" class="form-control">'+value+'</textarea>';
                        $("#ingredients-placeholder").append(content);
                        //console.log(name+','+value);
                    }
                    //console.log(name+','+amount+','+dv);
                    console.log(resp);
                });
            }
        }
    });
}
var nutriChange = function(col, value, upc, name)
{
    if (value != amSA) {
        $.ajax({
            type: 'post', 
            data: 'upc='+upc+'&col='+col+'&value='+value+'&name='+name+'&nutrient=1',
            dataType: 'json',
            url: 'NutriFactEntry.php',
            success: function(resp)
            {
                //console.log(value); console.log(upc); console.log(col);
                console.log(resp);
                
                // calculate & replace PercentDV text
                let target = $("td:contains('"+name+"')");
                let testvalue = nutriFactStd[name];
                var v = value.replace('mg', '');
                v = v.replace('mcg', '');
                let dv = v / parseFloat(testvalue);
                dv = dv * 100;
                dv = Math.round(dv);
                target.parent().find('td:nth-child(3)').text(resp.dv);
            }
        });
    }
}

var dvChange = function(col, value, upc, name)
{
    if (value != dvSA) {
        $.ajax({
            type: 'post', 
            data: 'upc='+upc+'&col='+col+'&value='+value+'&name='+name+'&nutrient=2',
            dataType: 'json',
            url: 'NutriFactEntry.php',
            success: function(resp)
            {
                //console.log(value); console.log(upc); console.log(col);
                console.log(resp);
                
                // calculate & replace PercentDV text
                let target = $("td:contains('"+name+"')");
                target.parent().find('td:nth-child(2)').text(resp.val);

            }
        });
    }
    
}
var ingredientChange = function(upc, value)
{
    value = encodeURI(value)
    $.ajax({
        type: 'post', 
        data: 'upc='+upc+'&ingredients='+value,
        dataType: 'json',
        url: 'NutriFactEntry.php',
        success: function(resp)
        {
            //console.log(value); console.log(upc); console.log(col);
            console.log(resp);
        }
    });
}

// search upc
var lookupUpc = null;
var lastKey = null;
var numericKeyCodes = [97,98,99,100,101,102,103,104,105,50,51,52,53,54,55,56,57,48,49,50,51,52,53,54,55,56,57];
$('#search-upc').keyup(function(e){
    lastKey = e.keyCode;
    console.log(lastKey);
    // only allow numeric chars in search
    if (!numericKeyCodes.includes(lastKey)) {
        return false;
    }
    //console.log(lastKey);
    lookupUpc = $(this).val();
    $('#mytable tr').each(function(){
        $(this).show();
        mode.val(1);
    });
    if (lookupUpc.length > 2) {
        $('#mytable tr td:nth-child(1)').each(function(){
            let text = $(this).text();
            if (text.includes(lookupUpc)) {
                $(this).closest('tr').show();
                mode.val(2);
                ajaxCall2(lookupUpc);
            } else {
                $(this).closest('tr').hide();
            }
        });
    }
});

JAVASCRIPT;
    }
}

FannieDispatch::conditionalExec();


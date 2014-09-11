<?php
/*******************************************************************************

    Copyright 2007 Authors: Christof Von Rabenau - Whole Foods Co-op Duluth, MN
    Joel Brock - People's Food Co-op Portland, OR
    Update copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
//  TODO -- Add javascript for batcher product entry popup window       ~joel 2007-08-21

/* #'F --FUNCTIONALITY of itemParse() - - - - - - - - - - - - - - - - - - - - - - -
 * Major functions.  Lines grepped from this listing.
 * N.B. The add and edit screens are composed in separate code blocks.
*/
    // 1. Lookup on the request argument.
    // 2. Compose code in the HEAD of the HTML page.
    // 3. No match. Compose form for creation of a new item.
        // 3a. Create - First Block
        // 3b. Create - Second Block
        // 3c. Create - Deli-Scale Fieldset
        // 3d. Create - Operations Fieldset
        // 3e. Create - Extra Info Fieldset
        // 3f. Create - Multiples Fieldset
        // 3g. Create - Cost Fieldset
        // 3h. Create - Sale Fieldset
        // 3i. Create - Margin Fieldset
        // 3j. Create - Likecode Fieldset
    // 4. More than one match. Make a list to choose from.
    // 5. One match. Compose form for editing an existing item.
        // 5a. Update - First Block
        // 5b. Update - Second Block
        // 5c. Update - Deli-Scale Fieldset
        // 5d. Update - Operations Fieldset
        // 5e. Update - Extra Info Fieldset
        // 5f. Update - Multiples Fieldset
        // 5g. Update - Cost Fieldset
        // 5h. Update - Sale Fieldset
        // 5i. Update - Margin Fieldset
        // 5j. Update - Likecode Fieldset
        // 5k. Update - Lane Status Fieldset
/* -- */

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    *
    *  4Sep2014 AT Put chainedSelectors class directly into this file. It is
    *              no longer used anywhere else.
    *  1May2013 EL Support product flags (Qualifications) in products.numflag
    *  8Mar2013 EL Better, I think, support for lookup by SKU
    * 22Feb2013 Eric Lee Add support for editing
    *           products.quantity, .groupprice, .pricemethod, .mixmatchcode
    *           products.size, .unitofmeasure
    *           vendorItems.sku
    *
*/

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!function_exists('checkLogin')) {
    include_once('../auth/login.php');
}
if (!function_exists('GetLikecodeItems')) {
    include_once('ajax.php');
}

function itemParse($upc){
    global $FANNIE_OP_DB,$FANNIE_URL;
    global $FANNIE_STORE_ID;
    global $FANNIE_COOP_ID;
    global $FANNIE_COMPOSE_PRODUCT_DESCRIPTION;
    global $FANNIE_COMPOSE_LONG_PRODUCT_DESCRIPTION;
    $dbc = FannieDB::get($FANNIE_OP_DB);
    //global $FANNIE_ITEM_MODULES;
    // This is both whether-or-not and sequence-on-page
    //"ThreeForDollar",
    $Fannie_Item_Modules = array("Operations","ItemFlags","ExtraInfo",
    "ThreeForDollar",
    "Cost","Sale","Margin", "LikeCode", "LaneStatus");

    $logged_in = checkLogin();

    // 1. Lookup on the request argument.
    $queryItem = "";
    $numType = (isset($_REQUEST['ntype'])?$_REQUEST['ntype']:'UPC');
    // 8Mar13 EL || SKU
    if(is_numeric($upc) || $numType == 'SKU'){
        switch($numType){
            case 'UPC':
                $upc = str_pad($upc,13,0,STR_PAD_LEFT);
                $savedUPC = $upc;
                $queryItem = "SELECT p.*,x.distributor,x.manufacturer
                    ,v.sku,v.vendorDept
                    FROM products AS p LEFT JOIN
                    prodExtra AS x ON p.upc=x.upc
                    LEFT JOIN vendorItems AS v ON v.upc=p.upc
                    WHERE (p.upc = '$upc' or x.upc = '$upc')
                    AND p.store_id=0";
                break;
            case 'SKU':
                $queryItem = "SELECT p.*,x.distributor,x.manufacturer
                    ,v.sku,v.vendorDept
                    FROM products AS p INNER JOIN
                    vendorItems AS v ON p.upc=v.upc
                    LEFT JOIN prodExtra AS x on p.upc=x.upc
                    WHERE v.sku='$upc'
                    AND p.store_id=0";
                break;
            case 'Brand Prefix':
                $queryItem = "SELECT p.*,x.distributor,x.manufacturer
                    ,v.sku,v.vendorDept
                    FROM products AS p LEFT JOIN
                    prodExtra AS x ON p.upc=x.upc
                    LEFT JOIN vendorItems AS v ON v.upc=p.upc
                    WHERE p.upc like '%$upc%'
                    AND p.store_id=0
                    ORDER BY p.upc";
                break;
        }
    }else{
        /* note: only search by HQ records (store_id=0) to avoid duplicates */
        $queryItem = "SELECT p.*,x.distributor,x.manufacturer
        FROM products AS p LEFT JOIN
        prodExtra AS x ON p.upc=x.upc
        WHERE description LIKE '%$upc%'
        AND p.store_id=0
        ORDER BY description";
    }
    $resultItem = $dbc->query($queryItem);
    $num = $dbc->num_rows($resultItem);

    $likeCodeQ = "SELECT u.*,l.likeCodeDesc FROM upcLike as u, likeCodes as l
            WHERE u.likeCode = l.likeCode and u.upc = '$upc'";
    $likeCodeR = $dbc->query($likeCodeQ);
    $likeCodeRow = $dbc->fetch_row($likeCodeR);
    $likeCodeNum = $dbc->num_rows($likeCodeR);
    $likecode = ($likeCodeNum > 0)?$likeCodeRow[1]:'';

    // 2. Compose code in the HEAD of the HTML page.
    echo "<script type=\"text/javascript\">";
    echo "function shelftag(u){";
    echo "testwindow= window.open (\"addShelfTag.php?upc=\"+u, \"New Shelftag\",\"location=0,status=1,scrollbars=1,width=300,height=220\");";
    echo "testwindow.moveTo(50,50);";
    echo "}";
    echo "</script>";
    echo "<script type='text/javascript'>
        \$(document).ready(function(){
            \$('#start_date').datepicker(); 
            \$('#end_date').datepicker(); 
        });
        </script>";

        // 3. No match. Compose form for creation of a new item.
    if($num == 0 || !$num){
        $data = array();
        if (is_numeric($upc) || $numType == 'SKU'){
            $searchField = ($numType == 'SKU')?'sku':'upc';
            $dataQ = "SELECT description,brand,cost/units as cost,vendorName,margin,i.vendorID,
                    i.size,i.units,i.cost as case_cost,i.sku,i.upc
                FROM vendorItems AS i LEFT JOIN vendors AS v ON i.vendorID=v.vendorID
                LEFT JOIN vendorDepartments AS d ON i.vendorDept=d.deptID
                WHERE $searchField = '$upc'";
            // When would this be set?
            if (isset($_REQUEST['vid']))
                $dataQ .= " AND i.vendorID=".((int)$_REQUEST['vid']);
            $dataR = $dbc->query($dataQ);
            if ($dbc->num_rows($dataR) > 0){
                $data = $dbc->fetch_row($dataR);
                // ->EL vendorItems.cost is case_cost but data[cost] is unit_cost.
                if (is_numeric($data['cost']) && is_numeric($data['margin']))
                    $data['srp'] = getSRP($data['cost'],$data['margin']);
                if ( preg_match("/(\d+) *([a-z]+)/", $data['size'], $matches) ) {
                    $numeric_size = $matches[1];
                    $unitofmeasure = $matches[2];
                }
            }
        }

        $char_count_class = "char_count";
        if ( isset($FANNIE_COMPOSE_PRODUCT_DESCRIPTION) && $FANNIE_COMPOSE_PRODUCT_DESCRIPTION == "1" ) {
            $char_count_class = "char_count_pkg";
        }

        // Is <head> ever closed for Create?
        echo "<BODY onLoad='putFocus(0,1);'>";
        noItem();
        if (count($data) > 0){
            echo "<br /><i>This product is in the ".$data['vendorName']." catalog. Values have
                been filled in where possible</i><br />";
            while($vendorW = $dbc->fetch_row($dataR)){
                printf('This product is also in <a href="?upc=%s&vid=%d">%s</a><br />',
                    $upc,$vendorW['vendorID'],$vendorW['vendorName']);
            }
        }
        echo "<form name=pickSubDepartment action=insertItem_WEFC_Toronto.php method=post>";

        // 3a. Create - First Block
    echo "<div id='createBlock'><table style=\"margin-bottom:5px;\" width=\"100%\" border=1 cellpadding=5 cellspacing=0>";
        // 8Mar13 Use looked-up upc if there was one.
        echo "<tr><td align=right><b>UPC</b></td><td><font color='red'></font><input type=text value='";
            echo empty($data['upc'])?$upc:$data['upc'];
            echo "' name=upc></td><td colspan=2>&nbsp;</td>";
        echo "</tr><tr><td><b>Description</b></td><td><input type=text size=30 name='descript' id='descript' class='$char_count_class' ";
            echo (isset($data['description']))?"value=\"{$data['description']}\"":"";
            echo " />";
                echo '<div id="descript_count" style="width:1.5em; height:1.4em; text-align:center; padding-top: 0.1em; float:right; margin:0em 3em 0em 0em; border:1px solid #666666;">Char ct</div>';
            echo "</td>";
        echo "<td align=right><b>Price</b> $</td>";
            echo "<td><input type=text id=price name=price size=6 value='" . showAsMoney($data,"srp") . "' /></td></tr>";

//New{
        echo "<tr><td align=right><b>Package Size</b></td><td colspan=1><input type=text name=size id=size size=4 value=\"".(isset($numeric_size)?$numeric_size:"")."\" /> &nbsp; <span title='g oz qt l ml ct'><b>Unit of measure</b></span> <input type=text name=unitofmeasure id=unitofmeasure size=4 value=\"".
                (isset($unitofmeasure)?$unitofmeasure:"")."\" /></td>";
        echo "<td align=right><b>Vendor SKU</b></td><td colspan=1><input type=text name=sku size=8 ";
            printf("value=\"%s\"",isset($data['sku'])?$data['sku']:'');
            echo "></td></tr>";

        echo "<tr><td align=right><b>Long Desc.</b></td><td colspan=2><input type=text name=puser_description size=60 ";
            echo (isset($data['description']))?"value=\"{$data['description']}\"":"";
            echo " /></td>";
            echo "<td colspan=1> &nbsp; </td></tr>";
//new}
        echo "<tr><td><b>Manufacturer</b></td><td><input type=text name=manufacturer size=30 ";
        echo (isset($data['brand']))?"value=\"{$data['brand']}\"":"";
        echo "/></td>
        <td><b>Distributor</b></td><td><input type=text size=8 name=distributor ";
        echo (isset($data['vendorName']))?"value=\"{$data['vendorName']}\"":"";
        echo "/></td></tr>";
        echo "</table>";

        // 3b. Create - Second Block
        echo "<table style=\"margin-bottom:5px;\" width='100%' border=1 cellpadding=5 cellspacing=0><tr>";
        echo "<th>Dept</th><th>Tax</th><th>FS</th><th>Scale</th><th>QtyFrc</th><th>NoDisc</th>";
    echo "</tr>";
        echo "<tr align=top>";
        echo "<td align=left width=5px>";
        /**
            **  BEGIN CHAINEDSELECTOR CLASS
            **/
                //prepare names
                $selectorNames = array(
                    CS_FORM=>"pickSubDepartment",
                    CS_FIRST_SELECTOR=>"department",
                    CS_SECOND_SELECTOR=>"subdepartment");

                //      $department = $rowItem[12];
                //      $subdepartment = $rowItem[27];

                //query database, assemble data for selectors
                $Query = "SELECT d.dept_no AS dept_no, d.dept_name AS dept_name,
                    CASE WHEN s.subdept_no IS NULL THEN 0 ELSE s.subdept_no END as subdept_no,
                    CASE WHEN s.subdept_name IS NULL THEN 'None' ELSE s.subdept_name END AS subdept_name
                    FROM departments AS d LEFT JOIN
                    subdepts AS s ON d.dept_no=s.dept_ID
                    ORDER BY d.dept_no,s.subdept_no";
                if(!($DatabaseResult = $dbc->query($Query)))
                {
                    print("The query failed!<br>\n");
                    exit();
                }

                while($row = $dbc->fetch_object($DatabaseResult))
                {
                    $selectorData[] = array(
                        CS_SOURCE_ID=>$row->dept_no,
                        CS_SOURCE_LABEL=>$row->dept_name,
                        CS_TARGET_ID=>$row->subdept_no,
                        CS_TARGET_LABEL=>$row->subdept_name);
                } 

                //instantiate class
                $subdept = new chainedSelectors(
                    $selectorNames,
                    $selectorData);
                ?>
                    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html40/loose.dtd">
                    <html>
                    <head>
                    <script type="text/javascript" language="JavaScript">
                    <?php
                        $subdept->printUpdateFunction($row); //rowItem
                    ?>
                    </script>
                    </head>
                    <body>
                    <!-- <form name="pickSubDepartment" action="insertItem.php"> -->
                    <?php
                        $subdept->printSelectors($row); //rowItem
                    ?>
                    <script type="text/javascript" language="JavaScript">
                    <?php
                        $subdept->initialize();
                    ?>
                    </script>
                    </body>
                    </html>
                <?php
               /**
                **  CHAINEDSELECTOR CLASS ENDS . . . . . . . NOW
                **/
        echo "</td><td align=left>";
        $taxQ = "SELECT id,description FROM taxrates ORDER BY id";
        $taxR = $dbc->query($taxQ);
        $rates = array();
        while ($taxW = $dbc->fetch_row($taxR))
            array_push($rates,array($taxW[0],$taxW[1]));
        array_push($rates,array("0","NoTax"));
        echo "<select name=tax>";
        foreach($rates as $r){
            echo "<option value=$r[0]";
            if ($r[0] == "0") echo " selected";
            echo ">$r[1]</option>";
        }
        echo "</select></td>";
        echo "<td align=center><input type=checkbox value=1 name=FS";
        echo "></td><td align=center><input type=checkbox value=1 name=Scale";
        echo "></td><td align=center><input type=checkbox value=1 name=QtyFrc";
        echo "></td><td align=center><input type=checkbox value=1 name=NoDisc";
        echo "></td>";
        echo "</tr></table><!-- /div #createBlock?-->";

        echo "<div style='margin: 1.0em 0.0em 0.5em 0.0em;'>";
        echo "<input type=submit value=\"Create Item\" />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href='../item/itemMaint_WEFC_Toronto.php'><span style='font-size:1.1em;'>Back</span></a>";
            echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <span style='color:darkmagenta;'>Last modified: ".date('r')."</span>";
        echo "</div>";
        echo "</div><!-- /#createBlock --> ";

    // 's Create - Deli scale item
    if (substr($upc,0,3) == "002"){
        // 3c. Create - Deli-Scale Fieldset
        echo "<br /><div align=center><fieldset><legend>Scale</legend>";
        echo "<input type=hidden value=\"$upc\" name=s_plu />";

        echo "<table style=\"background:#ffffcc;\" cellpadding=5 cellspacing=0 border=1>";

        echo "<tr><th colspan=2>Longer description</th><td colspan=4><input size=35 type=text name=s_longdesc maxlength=100";
        echo " /></td></tr>";

        echo "<tr><td colspan=6 style=\"font-size:1px;\">&nbsp;</td></tr>";

        echo "<tr><th>Weight</th><th>By Count</th><th>Tare</th><th>Shelf Life</th>";
        echo "<th>Label</th><th>Safehandling</th></tr>";

        echo "<tr>";
        echo "<td>";
        echo "<input type=radio name=s_type value=\"Random Weight\" checked /> Random<br />";
        echo "<input type=radio name=s_type value=\"Fixed Weight\" /> Fixed<br />";
        echo "</td>";

        echo "<td align=center><input type=checkbox name=s_bycount ";
        echo "/></td>";

        echo "<td align=center><input type=text size=5 name=s_tare ";
        echo "value=0 /></td>";

        echo "<td align=center><input type=text size=5 name=s_shelflife ";
        echo "value=0 /></td>";

        echo "<td><select name=s_label size=2>";
        echo "<option value=horizontal selected>Horizontal</option>";
        echo "<option value=vertical>Vertical</option>";
        echo "</td>";

        echo "<td align=center><input type=checkbox name=s_graphics ";
        echo "/></td>";
        echo "</tr>";

        echo "<tr><td colspan=6 style=\"font-size:1px;\">&nbsp;</td></tr>";

        echo "<tr><td colspan=6>";
        echo "<b>Expanded text:<br /><textarea name=s_text rows=4 cols=50>";
        echo "</textarea></td></tr>";

        echo "</table></fieldset></div>";
    // Create - Deli-Scale
    }

    $fieldsetWidth = 13;
    // 3d. Create - Operations Fieldset
    echo "<div id='Operations' style='float:left; margin-left:10px; width:{$fieldsetWidth}em; " .
                 "display:" . (array_search('Operations',$Fannie_Item_Modules) !== False?'block':'none') . ";'>";
    echo "<fieldset><legend>Operations</legend>";
    echo "<div style=\"float:left;\"><ul style='margin:0.0em 0.0em 0.0em 0.0em;'>";
    echo "<li style='margin-left:-2.0em;'><input type=checkbox name=newshelftag /> New Shelf Tag</a></li>";
    echo "<li style='margin-left:-2.0em;'>Recent Sales<br />History</li>";
    echo "<li style='margin-left:-2.0em;'>Price History</li>";
    echo "</ul></div>";
    echo "</fieldset>";
    echo "</div><!-- /#Operations -->";

    //'c 3ea. Create - Item Flags Fieldset
    echo "<div id='Flags' style='float:left; margin-left:10px; width:{$fieldsetWidth}em; " .
                 "display:" . (array_search('ItemFlags',$Fannie_Item_Modules) !== False?'block':'none') . ";'>";
    echo "<fieldset><legend>Item Flags</legend>";
    echo "<table>";

    $q = "SELECT f.description,
        f.bit_number,
        (1<<(f.bit_number-1)) & p.numflag AS flagIsSet
        FROM products AS p, prodFlags AS f
        WHERE p.upc=$upc";
    $r = $dbc->query($q);

    // item does not exist. Just get the flag names.
    if ($dbc->num_rows($r) == 0){
        $q = 'SELECT f.description,f.bit_number,0 AS flagIsSet
                FROM prodFlags AS f';
        $r = $dbc->query($q);
    }

    $ret = '';
    $i=0;
    while($w = $dbc->fetch_row($r)){
        if ($i==0) $ret .= '<tr>';
        // n-column table
        if ($i != 0 && $i % 2 == 0) $ret .= '</tr><tr>';
        $ret .= sprintf('<td><input type="checkbox" name="flags[]" value="%d" %s /></td>
            <td>%s</td>',$w['bit_number'],
            ($w['flagIsSet']==0 ? '' : 'checked'),
            $w['description']
        );
        $i++;
    }
    if ( strlen($ret) > 0 )
        $ret .= "</tr>";
    echo "$ret";

    echo "</table>";
    echo "</fieldset>";
    echo "</div><!-- /#ItemFlags -->";

    // 3eb. Create - Extra Info Fieldset
    echo "<div id='ExtraInfo' style='float:left; margin-left:10px; width:{$fieldsetWidth}em; " .
                 "display:" . (array_search('ExtraInfo',$Fannie_Item_Modules) !== False?'block':'none') . ";'>";
    echo "<fieldset><legend>Extra Info</legend>";
    echo "<table><th align=right>Deposit</th>";
    echo "<td> <input type='text'";
    echo "name='deposit' size='5' value=0></td>";
    echo "</tr><th align-right>Location</th>";
    echo "<td><input type=text size=5 value=\"\" name=location /></td>";
    echo "</tr><th align=right>Local</th>";
    echo "<td><input type=checkbox name=local /></td>";
    echo "</tr><th align=right>InUse</th>";
    echo "<td><input type=checkbox name=inUse checked /></td>";
    echo "</tr></table>";
    echo "</fieldset>";
    echo "</div><!-- /#ExtraInfo -->";

    // 3f. Create - Multiples Fieldset
    // There will never be existing values.
    echo "<div id='ThreeForDollar' style='float:left; margin-left:10px; width:{$fieldsetWidth}em; " .
                 "display:" . (array_search('ThreeForDollar',$Fannie_Item_Modules) !== False?'block':'none') . ";'>";
    echo '<fieldset><legend>"Three for a dollar"</legend>';
    echo "<div id='ThreeForDollarRegIn' style='display:block;'>";
    echo "<table style='margin:0.0em 0.0em 0.8em 0.0em;'>";
    echo "<tr><th align-right>#items</th>";
    echo "<td> <input type='text' name='quantity' size='5' value='0'></td>";

    echo "</tr><tr><th align=right>For <span style='font-weight:400;'>\$</span></th>";
    echo "<td><input type=text name='groupprice' size=5 value='0.00'></td>";

    // ?pricemethod must default to 0.
    echo "</tr><th align-right>Method</th>";
    echo "<td><input type='text' name='pricemethod' size='5' value='0'></td>";

    echo "</tr><th align-right>MixMatch</th>";
    echo "<td><input type='text' name='mixmatchcode' size='5' value='0'></td>";

    echo "</tr></table>";
    echo "</div><!-- /#ThreeForDollarRegIn -->";
    echo "</fieldset>";
    echo "</div><!-- /#ThreeForDollar -->";

    // 3g. Create - Cost Fieldset
    echo "<div id='Cost' style='float:left; margin-left:10px; width:{$fieldsetWidth}em; " .
                 "display:" . (array_search('Cost',$Fannie_Item_Modules) !== False?'block':'none') . ";'>";
    echo "<fieldset><legend>Cost</legend>";
    echo "<!-- div style=\"float:left;margin-left:20px;\" -->";
    echo "<table style='margin:0.0em 0.0em 0.0em 0.0em;'>";
    echo "<tr><th align=right>Single <span style='font-weight:400;'>\$</span></th>";
    echo "<td><input type=text size=5 value='" . showAsMoney($data,'cost') . "' id=cost name=cost onchange='cscallback();' /></td>";
    echo "</tr><tr><th align=right>Case <span style='font-weight:400;'>\$</span></th>";
    echo "<td><input type=text name='case_cost' size=5 value='" .  showAsMoney($data,'case_cost') . "' /></td>";
    echo "</tr><tr><th align=right>Items/case</th>";
    printf("<td><input type=text name='case_quantity' size=5 value=\"%s\" /></td>",
        (isset($data['units']) && is_numeric($data['units'])?$data['units']:'') );

    echo "</tr></table>";
    echo "<!-- /div -->";
    echo "</fieldset>";
    echo "</div><!-- /#Cost -->";

    // 3h. Create - Sale Fieldset
    echo "<div id='Sale' style='float:left; margin-left:10px; width:{$fieldsetWidth}em; " .
                 "display:" . (array_search('Sale',$Fannie_Item_Modules) !== False?'block':'none') . ";'>";
    echo '<fieldset><legend>Sale</legend>';
    echo "<!-- div style=\"float:left;margin-left:20px;\" -->";
    echo "<table style='margin:0.0em 0.0em 0.0em 0.0em;'>";

    echo "<tr><th align-right>Start Date</th>";
    echo "<td><input type=text size=6 value='" . date('Y-m-d') . "' name=start_date id=start_date /></td>";

    echo "</tr><tr><th align-right>End Date</th>";
    echo "<td><input type=text size=6 value='" . date('Y-m-d') . "' name=end_date id=end_date /></td>";

    echo "</tr><tr><th align-right>Sale for</th>";
    // This should really be in the db and be per-coop-configurable.
    $discounttypes = array(0 => "None", 1 => "All", 2 => "Mem.");
    echo "<td><select name=discounttype>";
     $data['discounttype'] = 0;
    for ($n=0 ; $n<3 ; $n++) {
        echo "<option value='$n'";
        if ( $n == $data['discounttype'] )
            echo " SELECTED";
        echo ">{$discounttypes[$n]}</option>";
    }
    echo "</select></td>";

    echo "</tr><tr><th align=right>Price <span style='font-weight:400;'>\$</span></th>";
    echo "<td><input type=text size=5 value='0.00' id=special_price name=special_price /></td>";
    echo "</tr></table>";

    echo '<fieldset><legend>"Three for a dollar"</legend>';
    echo "<table style='margin:0.0em 0.0em 0.0em 0.0em;'>";

    // quantity must default to 0.
    echo "<tr><th align-right>#Items</th>";
    echo "<td> <input type='text' name='specialquantity' size='5' value='0'></td>";

    echo "</tr><tr><th align=right>For <span style='font-weight:400;'>\$</span></th>";
    echo "<td><input type=text name='specialgroupprice' size=5 value='0.00'></td>";

    // ?pricemethod must default to 0.
    echo "</tr><th align-right>Method</th>";
    echo "<td><input type='text' name='specialpricemethod' size='5' value='0'></td>";
    echo "</tr></table>";
    echo '</fieldset><!-- 3/$1 -->';

    echo "<!-- /div -->";
    echo '</fieldset><!-- /#sale -->';
    echo "</div><!-- /#Sale -->";

    // 3i. Create - Margin Fieldset
    echo "<div id='Margin' style='float:left; margin-left:10px; width:{$fieldsetWidth}em; " .
                 "display:" . (array_search('Margin',$Fannie_Item_Modules) !== False?'block':'none') . ";'>";
    echo "<fieldset id=marginfs>";
    echo "<legend>Margin</legend>";
    echo "</fieldset>";
    echo "<script type=\"text/javascript\">cscallback();</script>";
    echo "</div><!-- /#Margin -->";

    // 3j. Create - Likecode Fieldset
    echo "<div id='LikeCode' style='float:left; margin-left:10px; width:{$fieldsetWidth}em; " .
                 "display:" . (array_search('LikeCode',$Fannie_Item_Modules) !== False?'block':'none') . ";'>";
    echo '<fieldset><legend>Likecode</legend>';
    echo "<table border=0><tr><td><b>Like code</b> ";
    $lcWidth = ($fieldsetWidth - 4); // Was 175px
    echo "<tr><td><select name=likeCode style=\"width: {$lcWidth}em;\"
        onchange=\"updateLC(this.value);\">";
    echo "<option value=-1>(none)</option>";
    $likelistQ = "select * from likeCodes order by likecode";
    $likelistR = $dbc->query($likelistQ);
    while ($llRow = $dbc->fetch_array($likelistR)){
      echo "<option value={$llRow[0]}";
      echo ">{$llRow[0]} {$llRow[1]}</option>";
    }
    echo "</select></td></tr>";
    echo "<tr><td><input type=checkbox name=update value='no'>Check to not update like code items</td></tr>";
    echo "<tr id=lchidden style=\"display:none;\"";
    echo "><td><b>Like Code Linked Items</b><div id=lctable>";
    echo '</div></td><td valign=top><a href="../reports/RecentSales/?likecode='.$likeCodeRow[1].'" target="_recentlike">';
    echo 'Likecode Sales History</td>';
    echo '</tr></table></fieldset>';
    echo "</div><!-- /#LikeCode -->";

        // Form for Create.
    }
        // 4. More than one match. Make a list to choose from.
        elseif($num > 1){
            moreItems($upc);
            for($i=0;$i < $num;$i++){
                $rowItem= $dbc->fetch_array($resultItem);
                $upc = $rowItem['upc'];
                echo "<a href='../item/itemMaint_WEFC_Toronto.php?upc=$upc'>" . $upc . " </a>- " . $rowItem['description'];
                if($rowItem['discounttype'] == 0) { echo "-- $" .$rowItem['normal_price']. "<br>"; }
                else { echo "-- <font color=green>$" .$rowItem['special_price']. " onsale</font><br>"; }
            }
    }
        // 5. One match. Compose form for editing an existing item.
        else{

        if ($FANNIE_STORE_ID != 0){
            /* if this isn't HQ, revise the lookup query to search
               for HQ records AND store records
               ordering by store_id descending means we'll get the
               store record if there is one and the HQ record if
               there isn't */
            $clause = sprintf("p.store_id IN (0,%d)",$FANNIE_STORE_ID);
            $queryItem = str_replace("p.store_id=0",$clause,$queryItem);
            if (strstr($queryItem, "ORDER BY"))
                $queryItem = array_shift(explode("ORDER BY",$queryItem));
            $queryItem .= " ORDER BY p.store_id DESC";
            $resultItem = $dbc->query($queryItem);
        }

        // products.* and prodExtra .manufacturer and .distributor
        $rowItem = $dbc->fetch_array($resultItem);

        // All of prodExtra
        $upc = $rowItem['upc'];
        $xtraQ = "SELECT * FROM prodExtra WHERE upc='$upc'";
        $xtraR = $dbc->query($xtraQ);
        $xtraRow = $dbc->fetch_row($xtraR);
        //EL+ All of productUser
        $userQ = "SELECT * FROM productUser WHERE upc='$upc'";
        $userR = $dbc->query($userQ);
        $userRow = $dbc->fetch_row($userR);

        /* For WEFC_Toronto only
         * Raw versions of products.description and productUser.descriptio
         *  are in products_WEFC_Toronto
        */
        if ( isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == "WEFC_Toronto" ) {
            $coopQ = "SELECT * FROM products_{$FANNIE_COOP_ID} WHERE upc='$upc'";
            $coopR = $dbc->query($coopQ);
            $coopRow = $dbc->fetch_row($coopR);
            if ( !empty($coopRow['description']) ) {
                $rowItem['description'] = $coopRow['description'];
            }
            if ( !empty($coopRow['search_description']) ) {
                $userRow['description'] = $coopRow['search_description'];
            }
        }

        $char_count_class = "char_count";
        if ( isset($FANNIE_COMPOSE_PRODUCT_DESCRIPTION) && $FANNIE_COMPOSE_PRODUCT_DESCRIPTION == "1" ) {
            $char_count_class = "char_count_pkg";
        }

        // Get the previous and next upc from the same department.
        $pnQ = "SELECT upc FROM products WHERE department=".$rowItem['department']." ORDER BY upc";
        $prevUPC = False;
        $nextUPC = False;
        $passed_it = False;
        $pnR = $dbc->query($pnQ);
        while($pnW = $dbc->fetch_row($pnR)){
            if (!$passed_it && $upc != $pnW[0])
                $prevUPC = $pnW[0];
            else if (!$passed_it && $upc == $pnW[0])
                $passed_it = True;
            else if ($passed_it){
                $nextUPC = $pnW[0];
                break;
            }
        }

        echo "<head><title>Update Item</title>";

        echo "</head>";

        echo "<body onload='putFocus(0,3);'>";
        oneItem($upc);
        echo "<form name=pickSubDepartment action='updateItems_WEFC_Toronto.php' method=post>";

        // 5a. Update - First Block
        echo "<div id='updateBlock' style=''><table border=1 cellpadding=5 cellspacing=0>";
        echo "<tr><td align=right><b>UPC</b></td><td><font color='red'>".$rowItem[0]."</font><input type=hidden value='$rowItem[0]' id=upc name=upc>";
            if ($prevUPC) echo " <a style=\"font-size:85%;\" href=itemMaint_WEFC_Toronto.php?upc=$prevUPC>Previous</a>";
            if ($nextUPC) echo " <a style=\"font-size:85%;\" href=itemMaint_WEFC_Toronto.php?upc=$nextUPC>Next</a>";
            echo '</td>';
        echo '<td colspan=2>';
            echo '<input type="hidden" name="store_id" value="'.$rowItem['store_id'].'" />';
            echo ($rowItem['store_id']==0 ? 'Master' : 'Store').' record';
            echo '</td></tr><tr><td align=right><b>Description</b></td><td>';
            echo '<input type=text size=30 value="' .
                    $rowItem['description'] .
                    '" name="descript" id="descript" ' . "class='$char_count_class' />";
                echo '<div id="descript_count" style="width:1.5em; height:1.4em; text-align:center; padding-top: 0.1em; float:right; margin:0em 3em 0em 0em; border:1px solid #666666;">Char ct</div>';
                echo '</td>';
        // If the ThreeForDollar (Multiples) fieldset/module is enabled support only normal_price editing here.
        if ( array_search('ThreeForDollar',$Fannie_Item_Modules) !== False ) {
            echo "<td align=right><b>Price</b></td>";
                echo '<td><span id=price1 style="display:'.(True?'inline':'none').';">$ <input id=price type=text value="'
                . showAsMoney($rowItem,2) . '" name=price size=6></span></td></tr>';
        }
        // If the ThreeForDollar (Multiples) fieldset/module is not enabled support both normal_price and that editing here.
        else {
            echo "<td><select onchange=\"if(this.value=='Price'){
                document.getElementById('price2').style.display='none';
                document.getElementById('price1').style.display='inline';
                }else{
                document.getElementById('price1').style.display='none';
                document.getElementById('price2').style.display='inline';
                }\">
                <option".($rowItem[4]==0?' SELECTED':'').">Price</option>
                <option".($rowItem[4]!=0?' SELECTED':'').">Volume Price</option></select></td>";
            echo '<td><span id=price1 style="display:'.($rowItem[4]==0?'inline':'none').';">$ <input id=price type=text value="' .
                    showAsMoney($rowItem,2) . '" name=price size=6></span>';
                echo '<span id=price2 style="display:'.($rowItem[4]==0?'none':'inline').';"><input type=text size=4 name=vol_qtty value="'.($rowItem[5]!=0?$rowItem[5]:'').'" />';
                echo " for $<input type=text size=4 name=vol_price value=".($rowItem[4] != 0 ? $rowItem[4] : "\"\"")." />";
                echo '<input type=checkbox name=doVolume '.($rowItem[4]!=0?'checked':'').' /></span>';
                echo '<input type=hidden name=vol_pricemethod value='.$rowItem[3].' />';
                echo '</td></tr>';
        }

        echo "<tr><td align=right><b>Package Size</b></td><td colspan=1><input type=text name=size id=size size=4 value=\"".(isset($rowItem['size'])?$rowItem['size']:"")."\" /> &nbsp; <span title='g oz qt l ml ct'><b>Unit of measure</b></span> <input type=text name=unitofmeasure id=unitofmeasure size=4 value=\"".(isset($rowItem['unitofmeasure'])?$rowItem['unitofmeasure']:"")."\" /></td>";
        echo "<td align=right><b>Vendor SKU</b></td><td colspan=1><input type=text name=sku size=8 value=\"".(isset($rowItem['sku'])?$rowItem['sku']:"")."\" /></td></tr>";

        echo "<tr><td align=right><b>Long Desc.</b></td><td colspan=2><input type=text name=puser_description size=60 value=\"".(isset($userRow['description'])?$userRow['description']:"")."\" /></td>";
        echo "<td colspan=1> &nbsp; </td></tr>";

        echo "<tr><td align=right><b>Manufacturer</b></td><td><input type=text name=manufacturer size=30 value=\"".(isset($xtraRow['manufacturer'])?$xtraRow['manufacturer']:"")."\" /></td>";
        echo "<td align=right><b>Distributor</b></td><td><input type=text name=distributor size=8 value=\"".(isset($xtraRow['distributor'])?$xtraRow['distributor']:"")."\" /></td></tr>";

        // If the item is on sale.
        if($rowItem[6] <> 0){
            $batchQ = "SELECT b.batchName FROM batches AS b
                LEFT JOIN batchList as l ON b.batchID=l.batchID
                WHERE '".date('Y-m-d')."' BETWEEN b.startDate
                AND b.endDate AND (l.upc='$upc' OR l.upc='LC$likecode')";
            $batchR = $dbc->query($batchQ);
            $batch = "Unknown";
            if ($dbc->num_rows($batchR) > 0)
                $batch = array_pop($dbc->fetch_row($batchR));
            echo "<tr><td><font color=green><b>Sale Price:</b></font></td><td><font color=green>$rowItem[6]</font> (<em>Batch: $batch</em>)</td>";
            echo "<td><font color=green>End Date:</td><td><font color=green>$rowItem[11]</font></td><tr>";
        }
        echo "</table>";

        // 5b. Update - Second Block
        echo "<table style=\"margin-top:5px;margin-bottom:5px;\" border=1 cellpadding=5 cellspacing=0 width='100%'><tr>";
        echo "<th>Dept</th><th>Tax</th><th>FS</th><th>Scale</th><th>QtyFrc</th><th>NoDisc</th>";
        echo "</tr>";
        echo "<tr align=top>";
        echo "<td align=left>";
       /**
        **  BEGIN CHAINEDSELECTOR CLASS
        **/
            $selectorNames = array(
                CS_FORM=>"pickSubDepartment",
                CS_FIRST_SELECTOR=>"department",
                CS_SECOND_SELECTOR=>"subdepartment");

            $Query = "SELECT d.dept_no AS dept_no, d.dept_name AS dept_name,
                CASE WHEN s.subdept_no IS NULL THEN 0 ELSE s.subdept_no END as subdept_no,
                CASE WHEN s.subdept_name IS NULL THEN 'None' ELSE s.subdept_name END AS subdept_name
                FROM departments AS d LEFT JOIN
                subdepts AS s ON d.dept_no=s.dept_ID
                ORDER BY d.dept_no,s.subdept_no";

            $DatabaseResult = False;
            if(!($DatabaseResult = $dbc->query($Query)))
            {
                print("The query failed!<br>\n");
                exit();
            }
            while($row = $dbc->fetch_object($DatabaseResult))
            {
                $selectorData[] = array(
                    CS_SOURCE_ID=>$row->dept_no,
                    CS_SOURCE_LABEL=>$row->dept_no." - ".$row->dept_name, 
                    CS_TARGET_ID=>$row->subdept_no, 
                    CS_TARGET_LABEL=>$row->subdept_name);
            } 

            $subdept = new chainedSelectors(
                $selectorNames, 
                $selectorData);
            ?>
                <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html40/loose.dtd">
                <html>
                <head>
                <script type="text/javascript" language="JavaScript">
                <?php
                    $subdept->printUpdateFunction($rowItem);
                ?>
                </script>
                </head>
                <body>
                <!-- <form name="pickSubDepartment" action="updateItems.php"> -->
                <?php
                    $subdept->printSelectors($rowItem);
                ?>
                <script type="text/javascript" language="JavaScript">
                <?php
                    $subdept->initialize();
                ?>
                </script>
                </body>
                </html>
            <?php
           /**
            **  CHAINEDSELECTOR CLASS ENDS . . . . . . . NOW
            **/
//                echo " </td>";
        echo "</td><td align=left>";
        $taxQ = "SELECT id,description FROM taxrates ORDER BY id";
        $taxR = $dbc->query($taxQ);
        $rates = array();
        while ($taxW = $dbc->fetch_row($taxR))
            array_push($rates,array($taxW[0],$taxW[1]));
        array_push($rates,array("0","NoTax"));
        echo "<select name=tax>";
        foreach($rates as $r){
            echo "<option value=$r[0]";
            if ($rowItem['tax'] == $r[0]) echo " selected";
            echo ">$r[1]</option>";
        }
        echo "</select>";
                echo "</td><td align=center><input type=checkbox value=1 name=FS";
                        if($rowItem["foodstamp"]==1){
                                echo " checked";
                        }
                echo "></td><td align=center><input type=checkbox value=1 name=Scale";
                        if($rowItem[16]==1){
                                echo " checked";
                        }
                echo "></td><td align=center><input type=checkbox value=1 name=QtyFrc";
                        if($rowItem["qttyEnforced"]==1){
                                echo " checked";
                        }
                echo "></td><td align=center><input type=checkbox value=0 name=NoDisc";
                        if($rowItem["discount"]==0){
                                echo " checked";
                        }
                echo "></td>";
                echo "</tr><tr></table>";
            echo "<div style='margin: 1.0em 0.0em 0.5em 0.0em;'>";
            echo "<input type='submit' name='submit' value='Update Item'>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href='../item/itemMaint_WEFC_Toronto.php'><span style='font-size:1.1em;'>Back</span></a>";
            echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <span style='color:darkmagenta;'>Last modified: {$rowItem['modified']}</span>";
            echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                     <a href=\"deleteItem_WEFC_Toronto.php?upc=$upc&submit=submit\">Delete this item</a>";
            echo "</div> ";
            echo "</div><!-- /#updateBlock --> ";

        if (substr($upc,0,3) == "002"){
            // 5c. Update - Deli-Scale Fieldset
            // Might be better to do top whitespace with margin.
            echo "<br /><div align=center><fieldset><legend>Scale</legend>";
            echo "<input type=hidden value=\"$upc\" name=s_plu />";
            $scaleR = $dbc->query("SELECT * FROM scaleItems WHERE plu='$upc'");
            $scale = array();
            if ($dbc->num_rows($scaleR) > 0)
                $scale = $dbc->fetch_row($scaleR);
            echo "<table style=\"background:#ffffcc;\" cellpadding=5 cellspacing=0 border=1>";

            echo "<tr><th colspan=2>Longer description</th><td colspan=4><input size=35 type=text name=s_longdesc maxlength=100";
            if (isset($scale['itemdesc']) && $scale['itemdesc'] != $rowItem['description'])
                echo " value=\"".$scale['itemdesc']."\"";
            echo " /></td></tr>";

            echo "<tr><td colspan=6 style=\"font-size:1px;\">&nbsp;</td></tr>";

            echo "<tr><th>Weight</th><th>By Count</th><th>Tare</th><th>Shelf Life</th>";
            echo "<th>Label</th><th>Safehandling</th></tr>";

            echo "<tr>";
            echo "<td>";
            if (!isset($scale['weight']) || $scale['weight']==0){
                echo "<input type=radio name=s_type value=\"Random Weight\" checked /> Random<br />";
                echo "<input type=radio name=s_type value=\"Fixed Weight\" /> Fixed<br />";
            }
            else {
                echo "<input type=radio name=s_type value=\"Random Weight\" /> Random<br />";
                echo "<input type=radio name=s_type value=\"Fixed Weight\" checked /> Fixed<br />";
            }
            echo "</td>";

            echo "<td align=center><input type=checkbox name=s_bycount ";
            if (isset($scale['bycount']) && $scale['bycount']==1)
                echo "checked ";
            echo "/></td>";

            echo "<td align=center><input type=text size=5 name=s_tare ";
            echo "value=".((isset($scale['tare']))?$scale['tare']:'0');
            echo " /></td>";

            echo "<td align=center><input type=text size=5 name=s_shelflife ";
            echo "value=".((isset($scale['shelflife']))?$scale['shelflife']:'0');
            echo " /></td>";

            echo "<td><select name=s_label size=2>";
            if (isset($scale['label']) && ($scale['label']==133 || $scale['label']==63)){
                echo "<option value=horizontal selected>Horizontal</option>";
                echo "<option value=vertical>Vertical</option>";
            }
            else if (!isset($scale['label'])){
                echo "<option value=horizontal selected>Horizontal</option>";
                echo "<option value=vertical>Vertical</option>";
            }
            else {
                echo "<option value=horizontal>Horizontal</option>";
                echo "<option value=vertical selected>Vertical</option>";
            }
            echo "</td>";

            echo "<td align=center><input type=checkbox name=s_graphics ";
            if (isset($scale['graphics']) && $scale['graphics']==1)
                echo "checked ";
            echo "/></td>";
            echo "</tr>";

            echo "<tr><td colspan=6 style=\"font-size:1px;\">&nbsp;</td></tr>";

            echo "<tr><td colspan=6>";
            echo "<b>Expanded text:<br /><textarea name=s_text rows=4 cols=50>";
            echo (isset($scale['text'])?$scale['text']:'');
            echo "</textarea></td></tr>";

            echo "</table></fieldset></div>";
        // Update - Deli-Scale
        }

        // 5d. Update - Operations Fieldset
        $fieldsetWidth = 13;
        echo "<div id='Operations' style='float:left; margin-left:10px; width:{$fieldsetWidth}em; " .
                     "display:" . (array_search('Operations',$Fannie_Item_Modules) !== False?'block':'none') . ";'>";
        echo "<fieldset><legend>Operations</legend>";
        echo "<div style=\"float:left;\"><ul style='margin:0.0em 0.0em 0.0em 0.0em;'>";
        echo "<li style='margin-left:-2.0em;'><a href=\"javascript:shelftag('$upc');\" title='Using the un-edited data.' >Current Shelf Tag</a></li>";
        echo "<li style='margin-left:-2.0em;'><input type=checkbox name=newshelftag title='Using the edited data, after you click Update.' /> <span title='Using the edited data, after you click Update.'>New Shelf Tag</span></li>";
        echo "<li style='margin-left:-2.0em;'><a href=\"../reports/RecentSales/?upc=$upc\" target='_recentsales'>";
        echo "Recent Sales<br /> History</a></li>";
        //echo "</ul></div>";
        //echo "<div style=\"float:left;\"><ul style='margin-top:0.0em;'>";
        echo "<li style='margin-left:-2.0em;'><a href=\"../reports/PriceHistory/?upc=$upc\" target=\"_price_history\">Price History</a></li>";
        echo "</ul></div>";
        echo "</fieldset>";
        echo "</div>";
        echo "</div><!-- /#Operations -->";

        //'u 5ea. Update - Item Flags Fieldset
        echo "<div id='Flags' style='float:left; margin-left:10px; width:{$fieldsetWidth}em; " .
                     "display:" . (array_search('ItemFlags',$Fannie_Item_Modules) !== False?'block':'none') . ";'>";
        echo "<fieldset><legend>Item Flags</legend>";
        echo "<table>";
        $q = "SELECT f.description,
            f.bit_number,
            (1<<(f.bit_number-1)) & p.numflag AS flagIsSet
            FROM products AS p, prodFlags AS f
            WHERE p.upc=$upc
            ORDER BY f.bit_number";
        $r = $dbc->query($q);

        // item does not exist. Just get the flag names.
        if ($dbc->num_rows($r) == 0){
            $q = 'SELECT f.description,f.bit_number,0 AS flagIsSet
                    FROM prodFlags AS f';
            $r = $dbc->query($q);
        }

        $ret = '';
        $i=0;
        while($w = $dbc->fetch_row($r)){
            if ($i==0) $ret .= '<tr>';
            // n-column table
            if ($i != 0 && $i % 2 == 0) $ret .= '</tr><tr>';
            $ret .= sprintf('<td><input type="checkbox" name="flags[]" value="%d" %s /></td>
                <td>%s</td>',$w['bit_number'],
                ($w['flagIsSet']==0 ? '' : 'checked'),
                $w['description']
            );
            $i++;
        }
        if ( strlen($ret) > 0 )
            $ret .= "</tr>";
        echo "$ret";

        echo "</table>";
        echo "</fieldset>";
        echo "</div><!-- /#ItemFlags -->";

        // 5e. Update - Extra Info Fieldset
        // ->EL Why does the top of this fieldset align below the top of Operations here but even with it in Create?
        echo "<div id='ExtraInfo' style='float:left; margin-left:10px; width:{$fieldsetWidth}em; " .
                     "display:" . (array_search('ExtraInfo',$Fannie_Item_Modules) !== False?'block':'none') . ";'>";
        echo "<fieldset><legend>Extra Info</legend>";
        echo "<table style='margin:0.0em 0.0em 0.0em 0.0em;'>";
        echo "<tr><th align=right>Deposit</th>";
        echo "<td> <input type='text' name='deposit' ";
                    if (!isset($rowItem['deposit']) || $rowItem['deposit'] == 0) {
                        echo "value='0'";
                    } else {
                        echo "value='{$rowItem['deposit']}'";
                    }
                echo " size='5'></td>";
        echo "</tr><th align-right>Location</th>";
        echo "<td><input type=text name=location size=5 value=\"{$xtraRow['location']}\" /></td>";
        echo "</tr><th align=right>Local</th>";
        echo "<td><input type=checkbox name=local ".($rowItem['local']==1?'checked':'')." /></td>";
        echo "</tr><th align=right>InUse</th>";
        echo "<td><input type=checkbox name=inUse ".($rowItem['inUse']==1?'checked':'')." /></td>";
        echo "</tr></table>";
        echo "</fieldset>";
        echo "</div><!-- /#ExtraInfo -->";

        // 5f. Update - Multiples Fieldset
        echo "<div id='ThreeForDollar' style='float:left; margin-left:10px; width:{$fieldsetWidth}em; " .
                     "display:" . (array_search('ThreeForDollar',$Fannie_Item_Modules) !== False?'block':'none') . ";'>";
        echo '<fieldset><legend>"Three for a dollar"</legend>';
        echo "<div id='ThreeForDollarRegIn' style='display:block;'>";
        echo "<table style='margin:0.0em 0.0em 0.8em 0.0em;'>";
        echo "<tr><th align-right>#items</th>";
        echo "<td> <input type='text'";
                    if (!isset($rowItem['quantity']) || $rowItem['quantity'] == 0) {
                        echo "value='0'";
                    } else {
                        echo "value='{$rowItem['quantity']}'";
                    }
                echo "name='quantity' size='5'></td>";

        echo "</tr><tr><th align=right>For <span style='font-weight:400;'>\$</span></th>";
        echo "<td><input type=text size=5 value='" . showAsMoney($rowItem,"groupprice") . "' id=groupprice name=groupprice /></td>";

        // ?pricemethod must default to 0.
        echo "</tr><th align-right>Method</th>";
        echo "<td> <input type='text'";
                    if (!isset($rowItem['pricemethod']) || $rowItem['pricemethod'] == 0) {
                        echo "value='0'";
                    } else {
                        echo "value='{$rowItem['pricemethod']}'";
                    }
                echo "id='pricemethod' name='pricemethod' size='5'></td>";

        echo "</tr><th align-right>MixMatch</th>";
        echo "<td><input type=text size=5 value=\"{$rowItem['mixmatchcode']}\" name=mixmatchcode /></td>";

        echo "</tr></table>";
        echo "</div><!-- /#ThreeForDollarRegIn -->";
        echo "</fieldset>";
        echo "</div><!-- /#ThreeForDollar -->";

        // 5g. Update - Cost Fieldset
        echo "<div id='Cost' style='float:left; margin-left:10px; width:{$fieldsetWidth}em; " .
                     "display:" . (array_search('Cost',$Fannie_Item_Modules) !== False?'block':'none') . ";'>";
        echo "<fieldset><legend>Cost</legend>";
        echo "<table style='margin:0.0em 0.0em 0.0em 0.0em;'>";
        echo "<tr><th align=right>Single <span style='font-weight:400;'>\$</span></th>";
        echo "<td><input type=text size=5 value='" . showAsMoney($rowItem,'cost') . "' id=cost name=cost onchange='cscallback();' /></td>";
        echo "</tr><tr><th align=right>Case <span style='font-weight:400;'>\$</span></th>";
        echo "<td><input type=text name='case_cost' size=5 value='" .  showAsMoney($xtraRow,'case_cost') .  "' /></td>";
        echo "</tr><tr><th align=right>Items/case</th>";
        printf("<td><input type=text name='case_quantity' size=5 value=\"%s\" /></td>",
            (isset($xtraRow['case_quantity']) && is_numeric($xtraRow['case_quantity'])?$xtraRow['case_quantity']:'') );

        echo "</tr></table>";
        echo "</fieldset>";
        echo "</div><!-- /#Cost -->";

        // 5h. Update - Sale Fieldset
        echo "<div id='Sale' style='float:left; margin-left:10px; width:{$fieldsetWidth}em; " .
                     "display:" . (array_search('Sale',$Fannie_Item_Modules) !== False?'block':'none') . ";'>";
        echo '<fieldset><legend>Sale</legend>';
        echo "<!-- div style=\"float:left;margin-left:20px;\" -->";
        echo "<table style='margin:0.0em 0.0em 0.0em 0.0em;'>";

        echo "<tr><th align-right>Start Date</th>";
        echo "<td><input type=text name=start_date size=6 value=\"{$rowItem['start_date']}\" id=start_date /></td>";

        echo "</tr><tr><th align-right>End Date</th>";
        echo "<td><input type=text name=end_date size=6 value=\"{$rowItem['end_date']}\" id=end_date /></td>";

        echo "</tr><tr><th align-right>Sale for</th>";
        // This should really be in the db and be per-coop-configurable.
        $discounttypes = array(0 => "None", 1 => "All", 2 => "Mem.");
        echo "<td><select name=discounttype>";
        if ( $rowItem['discounttype'] == "") {
             $rowItem['discounttype'] = 0;
        }
        for ($n=0 ; $n<3 ; $n++) {
            echo "<option value='$n'";
            if ( $n == $rowItem['discounttype'] )
                echo " SELECTED";
            echo ">{$discounttypes[$n]}</option>";
        }
        echo "</select></td>";

        echo "</tr><tr><th align=right>Price <span style='font-weight:400;'>\$</span></th>";
        echo "<td><input type=text size=5 value='" . showAsMoney($rowItem,"special_price") . "' id=special_price name=special_price /></td>";
        echo "</tr></table>";

        echo '<fieldset><legend>"Three for a dollar"</legend>';
        echo "<table style='margin:0.0em 0.0em 0.0em 0.0em;'>";
        // quantity must default to 0.
        echo "<tr><th align-right>#Items</th>";
        echo "<td> <input type='text'";
                    if (!isset($rowItem['specialquantity']) || $rowItem['specialquantity'] == 0) {
                        echo "value='0'";
                    } else {
                        echo "value='{$rowItem['specialquantity']}'";
                    }
                echo "name='specialquantity' size='5'></td>";

        echo "</tr><tr><th align=right>For <span style='font-weight:400;'>\$</span></th>";
        echo "<td><input type=text size=5 value='" . showAsMoney($rowItem,"specialgroupprice") . "' id=specialgroupprice name=specialgroupprice /></td>";

        // ?pricemethod must default to 0.
        echo "</tr><th align-right>Method</th>";
        echo "<td> <input type='text'";
                    if (!isset($rowItem['specialpricemethod']) || $rowItem['specialpricemethod'] == 0) {
                        echo "value='0'";
                    } else {
                        echo "value='{$rowItem['specialpricemethod']}'";
                    }
                echo "name='specialpricemethod' size='5'></td>";
        echo "</tr></table>";
        echo '</fieldset><!-- 3/$1 -->';

        echo "<!-- /div -->";
        echo '</fieldset><!-- /#sale -->';
        echo "</div><!-- /#Sale -->";


        // 5i. Update - Margin Fieldset
        echo "<div id='Margin' style='float:left; margin-left:10px; width:{$fieldsetWidth}em; " .
                     "display:" . (array_search('Margin',$Fannie_Item_Modules) !== False?'block':'none') . ";'>";
        echo "<fieldset id=marginfs>";
        echo "<legend>Margin</legend>";
        MarginFS($rowItem['upc'],$rowItem['cost'],$rowItem['department']);
        echo "</fieldset>";
        echo '</div>'; //of what?
        echo "</div><!-- /#Margin -->";

        // 5j. Update - Likecode Fieldset
        echo "<div id='LikeCode' style='float:left; margin-left:10px; width:{$fieldsetWidth}em; " .
                     "display:" . (array_search('LikeCode',$Fannie_Item_Modules) !== False?'block':'none') . ";'>";
        echo "<fieldset><legend>Likecode</legend>";
        echo "<table border=0>";
        echo "<tr><td><b>Like code</b></td></tr>";
        $lcWidth = ($fieldsetWidth - 4); // Was 175px
        echo "<tr><td><select name=likeCode style=\"width: {$lcWidth}em;\"
            onchange=\"updateLC(this.value);\">";
        echo "<option value=-1>(none)</option>";
        $likelistQ = "select * from likeCodes order by likecode";
        $likelistR = $dbc->query($likelistQ);
        while ($llRow = $dbc->fetch_array($likelistR)){
            echo "<option value={$llRow[0]}";
            if ($llRow[0] == $likecode){
                echo " selected";
            }
            echo ">{$llRow[0]} {$llRow[1]}</option>";
        }
        echo "</select></td></tr>";

        echo "<tr><td><input type=checkbox name=update value='no'>Check to not update like code items</td></tr>";

        echo "<tr id=lchidden";
        if ($likeCodeNum <= 0)
            echo ' style="display:none;"';
        echo "><td><b>Like Code Linked Items</b><div id=lctable>";
        GetLikecodeItems($likecode);
        echo '</div></td></tr>';
        echo '<tr><td valign=top><a href="../reports/RecentSales/?likecode='.$likeCodeRow[1].'" target="_recentlike">';
        echo 'Likecode Sales History</td>';
        echo '</tr>';
        echo '</table></fieldset>';
        echo '</div>';
        echo "</div><!-- /#LikeCode -->";

         echo "<br style='clear:both;' /><br />";
        // 5k. Update - Lane Status Fieldset
        echo "<div id='LaneStatus' style='display:" . (array_search('LaneStatus',$Fannie_Item_Modules) !== False?'block':'none') . ";'>";
        echo '<fieldset id="lanefs">';
        echo '<legend>Lane Status</legend>';
        include('prodAllLanes_WEFC_Toronto.php');
        echo allLanes($upc);
        echo '</fieldset>';
        echo "</div><!-- /#LaneStatus -->";

    }
    return $num;

// itemParse
}

function likedtotable($query,$border,$bgcolor)
{
    global $FANNIE_OP_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);
    //global $FANNIE_ITEM_MODULES;
        $results = $dbc->query($query) or
                die("<li>errorno=".$dbc->errno()
                        ."<li>error=" .$dbc->error()
                        ."<li>query=".$query);
        $number_cols = $dbc->num_fields($results);
        //display query
        //echo "<b>query: $query</b>";
        //layout table header
        echo "<table border = $border bgcolor=$bgcolor>\n";
        echo "<tr align left>\n";
        /*for($i=0; $i<5; $i++)
        {
                echo "<th>" . $dbc->field_name($results,$i). "</th>\n";
        }
        echo "</tr>\n"; *///end table header
        //layout table body
        while($row = $dbc->fetch_row($results))
        {
                echo "<tr align=left>\n";
                echo "<td >";
                        if(!isset($row[0]))
                        {
                                echo "NULL";
                        }else{
                                 ?>
                                 <a href="itemMaint_WEFC_Toronto.php?upc=<?php echo $row[0]; ?>">
                                 <?php echo $row[0]; ?></a>
                        <?php echo "</td>";
                        }
                for ($i=1;$i<$number_cols-1; $i++)
                {
                echo "<td>";
                        if(!isset($row[$i])) //test for null value
                        {
                                echo "NULL";
                        }else{
                                echo $row[$i];
                        }
                        echo "</td>\n";
                } echo "</tr>\n";
        } echo "</table>\n";
}

function noItem()
{
    //echo "<h3>No Items Found</h3>";
        echo "<span style=\"color:red;\">Item not found. You are creating a new one.</span>";
}

function moreItems($upc)
{
    echo "More than 1 item found for:<h3> " . $upc . "</h3><br>";
}

function oneItem($upc)
{
    echo "One item found for: " . $upc;
}

/* Return the number of decimal places to use in a price format spec: %.#f .
 * Minimum is 2.
*/
function sig_decimals ($num) {
    $dec = 2;
    if ( preg_match('/\.\d{3}/',$num) )
        $num = rtrim($num,'0');
    for ($n=5 ; $n > $dec ; $n--) {
        $pattern ='/\.\d{'.$n.'}$/';
        if ( preg_match($pattern,$num) ) {
            $dec = $n;
            break;
        }
    }
    return $dec;
}

/* Return numbers that are in named arrays formatted %.2f
 * If entered without decimals, add them.
 * Return non-numbers as $none
 * Treat non-existent, empty, False and 0 values as $none and do not format.
 * Does not change the original value.
*/
function saveAsMoney(&$arr,$index,$none="0") {
    if ( !empty($arr["$index"]) && is_numeric($arr["$index"]) ) {
        if ( strpos($arr["$index"],'.') === False )
            $retVal = sprintf("%.2f", $arr["$index"]/100);
        else
            $dec = sig_decimals($arr["$index"]);
            $retVal = sprintf("%.{$dec}f", $arr["$index"]);
    } else {
        $retVal = $none;
    }
    return $retVal;
// asMoney()
}

/* Return numbers that are in named arrays formatted %.2f for display or edit.
 * Return non-numbers as $none
 * Treat non-existent, empty, False and 0 values as $none and do not format.
 * Does not change the original value.
*/
function showAsMoney(&$arr,$index,$none="") {
    if ( is_int($index) ) {
        // For a MySQL double with value 0, == 0 is True, === 0 is false.
        //  The money fields in products are double (float).
        //  The money fields in vendorItems, prodExtra are decimal(10,2).
        // This 0.00 test for dec(10,2)'s that are 0.00 in the db doesn't seem to change anything.
        //if ( array_key_exists($index,$arr) && ($arr[$index] == 0 || $arr[$index] === 0.00) ) {}
        if ( array_key_exists($index,$arr) && $arr[$index] == 0 ) {
            $retVal = "0.00";
        } elseif ( !empty($arr[$index]) && is_numeric($arr[$index]) ) {
            $dec = sig_decimals($arr["$index"]);
            $retVal = sprintf("%.{$dec}f", $arr["$index"]);
        } else {
            $retVal = $none;
        }
    }
    else {
        // For a MySQL double with value 0, == 0 is True, === 0 is false.
        //  The money fields in products are double (float).
        //  The money fields in vendorItems, prodExtra are decimal(10,2).
        // This 0.00 test for dec(10,2)'s that are 0.00 in the db doesn't seem to change anything.
        //if ( array_key_exists("$index",$arr) && ($arr["$index"] == 0 || $arr["$index"] === 0.00) ) {}
        if ( array_key_exists("$index",$arr) && $arr["$index"] == 0 ) {
            $retVal = "0.00";
        } elseif ( !empty($arr["$index"]) && is_numeric($arr["$index"]) ) {
            $retVal = sprintf("%.2f", $arr["$index"]);
        } else {
            $retVal = $none;
        }
    }
    return $retVal;
// showAsMoney()
}

function promptForUPC($upc="") {
    $retVal = '';
    $retVal .= "Enter the code for, or words from the description of, an existing product:";
    $retVal .= "<br />";

    $retVal .= "<input name=upc type=text id=upc>";
    $retVal .= " <input name=submit type=submit value=Go> ";
    $retVal .= " is a ";
    $retVal .= "<select name=\"ntype\">
        <option value='UPC'>UPC or PLU</option>
        <option>SKU</option>
        <option>Brand Prefix</option>
    </select>";

    $retVal .= "<br />";
    $retVal .= "To add a product enter its UPC or PLU or Vendor SKU.";
    if ( !empty($upc) )
        $retVal .= "<br /> &nbsp; <a href='itemMaint_WEFC_Toronto.php?upc=$upc'>Edit $upc again</a>";
    $retVal .= "<br />";

    return $retVal;
}

// $flags is an array of powers of 2.
function setProductFlags($flags){
    $numflag = 0;   
    if (is_array($flags)) {
        foreach($flags as $f){
            if ($f != (int)$f) continue;
            $numflag = $numflag | (1 << ($f-1));
        }
    }
    return $numflag;
}

/*
** Class: chainedSelectors
** Description: This class allows you to create two selectors.  Selections
** made in the first selector cause the second selector to be updated.
** PHP is used to dynamically create the necessary JavaScript.
*/

//These constants make the code a bit more readable.  They should be
//used in in the creation of the input data arrays, too.
define("CS_FORM", 0);
define("CS_FIRST_SELECTOR", 1);
define("CS_SECOND_SELECTOR", 2);

define("CS_SOURCE_ID", 0);
define("CS_SOURCE_LABEL", 1);
define("CS_TARGET_ID", 2);
define("CS_TARGET_LABEL", 3);

class chainedSelectors
{
    /*
    ** Properties
    */
    
    //Array of names for the form and the two selectors.
    //Should take the form of array("myForm", "Selector1", "Selector2")
    var $names;
    
    //Array of data used to fill the two selectors
    var $data;
    
    //Unique set of choices for the first selector, generated on init
    var $uniqueChoices;
    
    //Calculated counts
    var $maxTargetChoices;
    var $longestTargetChoice;


    /*
    ** Methods
    */
    
    //constructor
    function chainedSelectors($names, $data)
    {
        /*
        **copy parameters into properties
        */
        $this->names = $names;
        $this->data = $data;

        /*
        ** traverse data, create uniqueChoices, get limits
        */        
        foreach($data as $row)
        {
            //create list of unique choices for first selector
            $this->uniqueChoices[($row[CS_SOURCE_ID])] = $row[CS_SOURCE_LABEL];    

            //find the maximum choices for target selector
            //added @ before var to fix maxPerChoice var error - jb
            @$maxPerChoice[($row[CS_SOURCE_ID])]++;

            //find longest value for target selector
            if(strlen($row[CS_TARGET_LABEL]) > $this->longestTargetChoice)
            {
                $this->longestTargetChoice=strlen($row[CS_TARGET_LABEL]);
            }
        }
        
        $this->maxTargetChoices = max($maxPerChoice);
    }

    //prints the JavaScript function to update second selector
    function printUpdateFunction($selected_item)
    {
        /*
        ** Create some variables to make the code
        ** more readable.
        */

        $selected_index = 0;

        $sourceSelector = "document." . $this->names[CS_FORM] . "." . 
            $this->names[CS_FIRST_SELECTOR];
        $targetSelector = "document." . $this->names[CS_FORM] . "." . 
            $this->names[CS_SECOND_SELECTOR];
    
        /*
        ** Start the function
        */
        print("function update" .$this->names[CS_SECOND_SELECTOR] . "()\n");

        print("{\n");

        /*
        ** Add code to clear out next selector
        */
        print("\t//clear " . $this->names[CS_SECOND_SELECTOR] . "\n");
        print("\tfor(index=0; index < $this->maxTargetChoices; index++)\n");
        print("\t{\n");
        print("\t\t" . $targetSelector . ".options[index].text = '';\n");
        print("\t\t" . $targetSelector . ".options[index].value = '';\n");
        print("\t}\n\n");
        print("\t" . $targetSelector . ".options[" . $selected_index . "].selected = true;\n\n");

        /*
        ** Add code to find which was selected
        */
        print("whichSelected = " . $sourceSelector . ".selectedIndex;\n");

        /*
        ** Add giant "if" tree that puts values into target selector
        ** based on which selection was made in source selector
        */

        //loop over each value of this selector
        foreach($this->uniqueChoices as $sourceValue=>$sourceLabel)
        {
            if($sourceValue == $selected_item[12]) {		// [12] = Department number
                $selected_index = $selected_item[28];		// [28] = subdept. #
                $selected_index = $selected_index - ($sourceValue * 100);	// the index of the subdept just happens to be preceded by the dept_no
            } else {
                $selected_index = 0;
            }

            print("\tif(" . $sourceSelector .
                ".options[whichSelected].value == " .
                "'$sourceValue')\n");
            print("\t{\n");

            $count=0;
            foreach($this->data as $row)
            {
                if($row[0] == $sourceValue)
                {
                    $optionValue = $row[CS_TARGET_ID];
                    $optionLabel = $row[CS_TARGET_LABEL];

                    print("\t\t" . $targetSelector .
                        ".options[$count].value = '$optionValue';\n");
                    print("\t\t" . $targetSelector .
                        ".options[$count].text = '$optionLabel';\n\n");
                    if($count + 1 == $selected_index){
                          print("\t\t" . $targetSelector .
                                ".options[$count].selected = true;\n");
                    }
                    $count++;
                }
            }

            print("\t}\n\n");
        }

    print("\tif(window.cscallback){\n");
    print("\t\tcscallback();\n");
    print("\t}\n");
        print("\treturn true;\n");
        print("}\n\n");

    }

    //print the two selectors
    function printSelectors($item_selected)
    {
        /*
        **create prefilled first selector
        */
        $selected=FALSE;
        print("<select name=\"" . $this->names[CS_FIRST_SELECTOR] . "\" " .
    "id=\"" .$this->names[CS_FIRST_SELECTOR] . "\" " .
            "onChange=\"update".$this->names[CS_SECOND_SELECTOR]."();\">\n");
        foreach($this->uniqueChoices as $key=>$value)
        {
            print("\t<option value=\"$key\"");
            if($key == $item_selected[12]) 	// [12] = department
            {
                print(" selected=\"selected\"");
                $selected=FALSE;
            }
            print(">$value</option>\n");
        }
        print("</select>\n");

        /*
        **create empty target selector
        */
        $dummyData = str_repeat("X", $this->longestTargetChoice);
        
        print("<select name=\"".$this->names[CS_SECOND_SELECTOR]."\">\n");
        for($i=0; $i < $this->maxTargetChoices; $i++)
        {
            print("\t<option value=\"\">$dummyData</option>\n");
        }
        print("</select>\n");

    }
    
    //prints a call to the update function
    function initialize()
    {
        print("update" .$this->names[CS_SECOND_SELECTOR] . "();\n");
    }
}

//
// PHP INPUT DEBUG SCRIPT  -- very helpful!
//

/*
function debug_p($var, $title)
{
    print "<h4>$title</h4><pre>";
    print_r($var);
    print "</pre>";
}

debug_p($_REQUEST, "all the data coming in");
*/
?>


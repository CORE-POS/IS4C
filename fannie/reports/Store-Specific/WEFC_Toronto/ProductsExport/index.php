<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op
    Copyright 2013 West End Food Co-op, Toronto, Ontario, Canada

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


/* --FUNCTIONALITY- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 27Nov13 Andy Theuninck revised to use FannieDB
    * 29Jan13 Eric Lee
    * - Produce a table containing the same data for each upc as is
    *    in the CSV data imported using
    *    /var/www/IS4C/fannie/item/import/loadWEFCTorontoProducts.php
    *   This is part of a cycle of import and export used to maintain product data
    * - The sequence of fields in the export is not the same as in the import.
    * - Data comes from products, productUser, prodExtra, products_WEFC_Toronto,
    *   departments, vendorItems,
    * - The "Excel" version is what will actually be used.
*/


/* #'Z--COMMENTZ - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    *  3May13 EL Change order of some fields and add product flags from numflag bits.
    *            New args[] in select_to_table
    * 15Feb13 EL Translate margin to markup.
    *             MySQL gags on the comma in ROUND(markup,2). Tolerable.
    * 29Jan13 EL Begun.
    *         

*/

include('../../../../config.php');
//include($FANNIE_ROOT.'config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);
include($FANNIE_ROOT.'src/functions.php');

/* from EOMreport
if (isset($_GET["excel"])){
header('Content-Type: application/ms-excel');
header('Content-Disposition: attachment; filename="EOMreport.xls"');
$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF']; // grab excel from cache
$_SERVER['REQUEST_URI'] = str_replace("index.php","",$_SERVER['REQUEST_URI']);
}
*/


/* This program has two modes:
 * 1. Display the form for specifying the report.
 *    Test: the submit button has not been clicked so $_REQUEST['submit'] is not set.
 * 2. Prepare and display the report.
 *    Test: the submit button has been clicked so $_REQUEST['submit'] is set.
*/
if (isset($_REQUEST['submit'])){

    $dateStamp = str_replace(' ', '_', date('Y-m-d H-i'));

    if (isset($_REQUEST['excel'])){
        header("Content-Disposition: inline; filename=products_export_{$dateStamp}.xls");
        header("Content-type: application/vnd.ms-excel; name='excel'");
    }
    else {
        echo "<H3 style='margin-bottom:0;'>Products Export: $dateStamp</H3>\n";
        echo "<br /><a href=index.php?submit=yes&excel=yes>Save to Excel</a>";
    }

    $taxNames = array(0 => '');
    $taxRates = array(0 => 0);
    $tQ = "SELECT id, description,rate FROM core_op.taxrates WHERE id > 0 ORDER BY id";
    $tR = $dbc->query($tQ);
    while ( $trow = $dbc->fetch_array($tR) ) {
        $taxNames[$trow['id']] = $trow['description'];
        $taxRates[$trow['id']] = $trow['rate'];
    }

        //$productTables = array("products", "productUser", "products_WEFC_Toronto", "vendorItems", "prodExtra");

        /* Indexes to the array returned by csv_parser, the column number in the .csv file.
            "first" = 0.
        'c
        $UPC = 0;                               // G:A:0 "Supplier UPC". UPC or PLU.
        $SEARCH = 1;                        // B:B:1 search_description, for productUser.description
        $VENDOR_NAME = 2;               // E:C:2 Distributor
        $ORDER_CODE = 3;                // -:D:3 ORDER_CODE, from Buying system
        $DEPT_NAME = 4;                 // F:E:4 Department
        $BRAND_NAME = 5;                // C:F:5 Brand deptMargin.dept_ID
//
        $IN_USE = 6;                        // -:G:6 products:inUse
// H - qtyfrc (1 for true, 0 or blank for false, new as per email this evening)
        $QTYFRC = 7;                        // -:H:7 products:qttyEnforced
// I - like code (has to be manually created in IS4C) 
        $MIXMATCH = 8;                  // -:I:8 products:mixmatchcode
// J - qcount (count of items of a quantity price, like "3" for "3 lemons for a dollar", blank means no q price)
        $QUANTITY = 9;                  // -:J:9 products:quantity qcount
// K - qprice (the quantity price, new this evening, blank for none)
        $GROUP_PRICE = 10;          // -:K:10 products:groupprice qprice
// L - qstrict (whether or not the quantity price requires that many at least,
//              or just over-rides the per-unit price, new this evening,
//              1 for true -> 2, 0 or blank for false -> 1 )
//              Error if 2 and normal_price = ""
        $PRICE_METHOD = 11;         // -:L:11 products:pricemethod qstrict
// :M-N are not used here
+       $DISCOUNTABLE = 12;         // -:M:12 Item discountable 0=no 1=yes
+       $DISCOUNT_TYPE = 13;        // -:N:13 Whether to use "special" i.e. sale prices and for which kind of customer:

        $DESCRIPTION = 14;          // D:O:14 Item
        $CASE_COST = 15;                // H:P:15 "Case Cost" 9999.99
        $CASE_SIZE = 16;                // J:Q:16 "Case Size" 99
        $UNIT_SIZE = 17;                // K:R:17 "Unit Size" 99 
        $UNIT_NAME = 18;                // L:S:18 "Units "g"/"ml"/"bags"
        $UNIT_COST = 19;                // M:T:19 Cost per Unit 999.99 "Cost"
//
        $CALC_PRICE = 20;               // N:U:20 "Set Price" but in fact CASE_COST/CASE_SIZE
        $SET_PRICE = 21;                // O:V:21 "Price" Jiggered price
//
        $TAX_TYPE = 22;                 // R:W:22 Tax 0/1/2 "Taxes"
        $MARKUP = 23;                       // S:X:23 Markup "150%" -> 1.42 "Markup"
+       $SKU = 24;                          // D:Y:24 Vendor SKU
//
+       $SALE_PRICE = 25;               // Z:25 "Sale Price"
+       $SALE_COST = 26;                // AA:26 "Sale Cost"
+       $TEMP_COST = 27;                // AB:27 "Temp Cost" ? Case sale price.
// :Y-AC are superdepts
+       $CATEGORY_SD = 28;          // W:AC:28 Category SuperDept.
//
        $SCALE = 30;                        // Z:AE:30 BULK. "BULK" means scale=1
        $VENDOR_ID = 34;                // AE:AI:34 Vendor ID.
//
// 30Apr13 Columns AJ/35 - BN/64 are for Qualification flags.
//         "" means not-assigned, probably implies No. 0=No 1=Yes
//
-       $SALE_PRICE = 38;               // Q:AM:38 "Sale Price"
-       $SALE_COST = 39;                // P:AN:39 "Sale Cost"
-       $TEMP_COST = 40;                // I:AO:40 "Temp Cost" ? Case sale price.
//-- :End
    //  $ONTARIO = 19;                  // T YES/NO/"" (very few examples)
    //  $CANADA = 20;                       // U YES/NO/"" (very few examples)

    // 'b
                $insQ = sprintf("INSERT INTO products (upc, description, normal_price, 
                    pricemethod, groupprice, quantity, special_price, specialpricemethod, 
                    specialgroupprice, specialquantity, start_date, end_date, department, 
                    size, tax, foodstamp, scale, scaleprice, mixmatchcode, modified, advertised, 
                    tareweight, discount, discounttype, unitofmeasure, wicable, qttyEnforced, 
                    idEnforced, cost, inUse, numflag, subdept, deposit, local)
                    VALUES (%s, %s, %.2f,
                    %d, %.2f, %d, %.2f, %d,
                    %.2f, %d, %s, %s, %d,
                    %d, %d, %d, %d, %.2f, %s, %s, %d,
                    %.2f, %d, %d, %s, %d, %d,
                    %d, %.2f, %d, %d, %d, %.2f, %d)",
                    $dbc->escape($upc), $dbc->escape($description), $normal_price,
                    $pricemethod, $groupprice, $quantity, $special_price, $specialpricemethod,
                    $specialgroupprice, $specialquantity, $start_date, $end_date, $department,
                    $size, $tax, $fs, $scale, $scaleprice, $dbc->escape($mixmatchcode), $dbc->now(), $advertised,
                    $tareweight, $discount, $discounttype, $dbc->escape($unitofmeasure), $wicable, $qttyEnforced,
                    $idEnforced, $cost, $inUse, $numflag, $subdept, $deposit, $local);


        $UPC = 0;                               // G:A:0 "Supplier UPC". UPC or PLU.
                    p.upc upc,
        $SEARCH = 1;                        // B:B:1 search_description, for productUser.description
                    w.search_description search_desc,
        $VENDOR_NAME = 2;               // E:C:2 Distributor -> vendorID from AI VENDOR_ID. vendors.vendorId is stale, not being maintained.
                    v.vendorID distrib_id,
                    e.distributor distrib_name,
        $SKU = 3;                               // A:D:3 SKU
                    v.sku sku,
        $DEPT_NAME = 4;                 // F:E:4 Department
                    p.department dept_id,
                    d.dept_name dept_name,
        $BRAND_NAME = 5;                // C:F:5 Brand deptMargin.dept_ID
                    u.brand brand,

        $IN_USE = 6;                        // -:G:6 products:inUse
                    p.inUse inUse,
        $QTYFRC = 7;                        // -:H:7 products:qttyEnforced
                    p.qttyEnforced qttyEnforced,
        $MIXMATCH = 8;                  // -:I:8 products:mixmatchcode
                    p.mixmatchcode mixmatchCode,
        $QUANTITY = 9;                  // -:J:9 products:quantity qcount
                    p.quantity groupCount,
        $GROUP_PRICE = 10;          // -:K:10 products:groupprice qprice
                    p.groupprice groupPrice,
        $PRICE_METHOD = 11;         // -:L:11 products:pricemethod qstrict
                    p.pricemethod priceMethod,

        $DESCRIPTION = 14;          // D:O:[14] Item
                    w.description desc,
        $CASE_COST = 15;                // H:P:[15] "Case Cost" 9999.99
                    e.case_cost caseCost,
        $CASE_SIZE = 16;                // J:Q:[16] "Case Size" 99
                    v.units caseSize,
        $UNIT_SIZE = 17;                // K:R:[17] "Unit Size" 99 
                    p.size unitSize,
        $UNIT_NAME = 18;                // L:S:[18] "Units "g"/"ml"/"bags"
                    p.unitofmeasure unitOfMeasure,
        $UNIT_COST = 19;                // M:T:[19] Cost per Unit 999.99 "Cost" -> No point in calculating this.
//
        $CALC_PRICE = 20;               // N:U:20 "Set Price" but in fact CASE_COST/CASE_SIZE -> No point in calculating this.
        $SET_PRICE = 21;                // O:V:21 "Price" Jiggered price
                    p.normal_price setPrice,
//
        $TAX_TYPE = 22;                 // R:W:22 Tax 0/1/2 "Taxes"
                    p.tax taxType,
        $MARKUP = 23;                       // S:X:23 Markup "150%" -> 1.42 "Markup"
                    e.margin markup,
//
        $SCALE = 30;                        // Z:AE:30 BULK. "BULK" means scale=1
                    WHEN p.scale = 1 THEN 'BULK' ELSE 'COUNT' END scale,
    */

    $productFlags = "";
    $j=0;
    for($i=0;$i<30;$i++) {
        $j++;
        $productFlags .= "\n,CASE WHEN ((1<<$i) & p.numflag) = 0 THEN 0 ELSE 1 END pFlag$j";
    }
    /* 'a The select is in the order of the final excel format.
     * Column aliases are extracted for column heads.
    */
    $pQ = "SELECT
                    p.upc upc,
                    w.search_description searchDesc,
                    v.vendorID distribId,
                    e.distributor distribName,
                    w.order_code orderCode,
                    p.department deptId,
                    d.dept_name deptName,
                    u.brand brand,
                    p.inUse inUse,
                    p.qttyEnforced qttyEnforced,
                    p.mixmatchcode mixmatchCode,
                    p.quantity groupCount,
                    p.groupprice groupPrice,
                    p.pricemethod priceMethod,
                    p.discount discount,
                    p.discounttype discountType,
                    w.description description,
                    e.case_cost caseCost,
                    v.units caseSize,
                    p.size unitSize,
                    p.unitofmeasure unitOfMeasure,
                    p.normal_price setPrice,
                    p.tax taxType,
                    CASE WHEN e.margin > 1.00 THEN e.margin ELSE ((1/e.margin)/((1/e.margin)-1)) END markup,
                    CASE WHEN p.scale = 1 THEN 'BULK' ELSE 'COUNT' END scale,
                    v.sku SKU,
                    p.special_price salePrice,
                    p.specialpricemethod salePriceMethod,
                    p.specialgroupprice saleGroupPrice,
                    p.specialquantity saleQuantity,
                    p.start_date saleStartDate,
                    p.end_date saleEndDate$productFlags
                FROM
                    core_op.products AS p
                    LEFT JOIN core_op.productUser AS u ON p.upc = u.upc
                    LEFT JOIN core_op.prodExtra AS e ON p.upc = e.upc
                    LEFT JOIN core_op.products_WEFC_Toronto AS w ON p.upc = w.upc
                    LEFT JOIN core_op.vendorItems AS v ON p.upc = v.upc
                    LEFT JOIN core_op.departments AS d ON p.department = d.dept_no
                WHERE
                    p.upc < '9000000000000'
                ORDER BY
                    p.upc";

    // Create column head labels from field aliases
    $hstr = preg_replace("/\t|\n/", "", $pQ);
    $hstr = preg_replace("/(^SELECT)(.*)(FROM.*$)/", "$2", $hstr);
    $hraw = explode(",", $hstr);
    $headers = preg_replace("/^.*( [a-zA-Z0-9]*$)/", "$1", $hraw);

    //select_to_table($pQ,array(),1,'ffffff');
    select_to_table2($pQ,array(),1,'ffffff', '', '0', '2', $headers, False);
    //select_to_table2($query,$arguments,$border,$bgcolor,$width="120",$spacing="0",$padding="0",$headers=array(),$nostart=False)

}
// Form for specifying the report.
else {

$page_title = "Fannie : WEFC Toronto Products Export Report";
$header = "WEFC Toronto Products Export Report";
include($FANNIE_ROOT.'src/header.html');
?>

<form action=index.php method=get>
<style type="text/css">
/* This makes the input and label look like they have the same baseline. */
input[type="radio"] ,
input[type="checkbox"] {
    height: 8px;
}
</style>
<table cellspacing=4 cellpadding=4 border='0'>
<tr><td>Excel <input type='checkbox' name='excel' /></td>
<td colspan='99'><input type='submit' name='submit' value="Submit" /></td>
</tr>
</table>
</form>
<?php
include($FANNIE_ROOT.'src/footer.html');
}
?>

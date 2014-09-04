<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

/* #'Z--COMMENTZ { -  - - - - - - - - - - - - - - - - - - - - - -

    20Aug13 Quantity-enforced prices with more than two decimals.
     4Jul13 Require admin priv.
    20Jun13 7-digit PLU in UPC field.
            Flag empty case_cost if hasPrice.
    19Jun13 ORDER_CODE to seven digits from five.
            Allow for decimal-number CASE_SIZE when UNIT_COST not supplied.
                 Used for BULK, where "case" is treated as a container of CASE_SIZE pounds.
                      CASE_COST is cost of that.
                      UNIT_SIZE is 1, UNIT_NAME lb.  For things sold by weight.
                 If $size, from CASE_SIZE, is not an integer, e.g. for BULK, vendorItems.units winds up NULL.
                      Other uses of $size not as problematic, AFAIK.
                 This may need to be addressed one day.
    17Jun13 Some real changes. remove $upcProblem, $local references.
    14Jun13 Just some expendable debug messages, commented at this point.
    30Apr13 - ORDER_CODE in D, SKU to Y.
            o Qualifications flags in AJ-BN assigned to bits of products.numflag.
            o Drop use of products_WEFC_Toronto.km100, ontario, canada
            o Put ORDER_CODE in products_WEFC_Toronto.order_code
    23Mar13 + vendorItem.cost s/N/b case_cost as in prodExtra; revert to unit_cost
          + A non-manual control of checking for dev_side for add-to-vendors.
    16Mar12 Add unknown vendorID to vendors; should only do dev_side. Manual.
             Advises anyway.
    11Mar12 Fix misunderstandings and errors in vendorItems assignment.
            + .size s/b $sizing
                    + .units s/b case_size as in prodExtra.case_quantity
                    + .cost s/b case_cost as in prodExtra
                    + .vendorID - value in source spreadsheet fixed
    15Feb13 Convert markup in source data to margin.
            dept.Margin.margin is now a margin, not a markup.
            Calculate price using a margin-based function.
            prodExtra.margin as %.5f for better translation to markup in export.
                      But prodExtra.margin only takes two decimals.
            Discountable yes/no in source col M.
    30Jan13 Change/fix what is in some fields:
            - products.size from UNIT_SIZE ($unitsize), not CASE_SIZE
            - vendorItems.size from UNIT_NAME ($unitofmeasure), not composed $sizing
            - vendorItems.units from UNIT_SIZE ($unitsize), not CASE_SIZE
    25Jan13 PV+ description in source Col B.
            Keep complete original description in products_WEFC_Toronto.description
            Keep Col B search description in products_WEFC_Toronto.search_description
    24Jan13 Change skip-field-label test to SET_PRICE because for bulk there is no CASE_COST.
            Test for legit absence of CASE_COST when deriving per-unit price.
            PV+ desc with " | " separators.
    17Jan13 EL MC> interpret a "blank" or -1 price as follows:
                 - if it's for an existing product, maintain the current price as in IS4C at the time of the upload
                   EL> UPDATE, assigning all fields except price and cost.
                 - if it's for a new product (as for a new UPC / scan code), then default to the department margin price.
                   EL> INSERT, using dept margin, but no price. inUse = 0.
                   EL> ?"department margin price" - meaningless
                             - for [new?]products that have no price, there is also no case cost
                               (I don't have one!  The cost of broccoli changes constantly...).
                               How would you recommend I handle this situation?
                         Change name and meaning of former UNIT_PRICE to SET_PRICE and former SET_PRICE to CALC_PRICE
                          The newly-named SET_PRICE is still the preferred one.
                         Populate productUser.description with $brand . $description
    15Jan13 EL Fix products.scale missing in INSERT.
    14Jan13 EL New layout of source data, for NEW_Products.txt of this day.
                Several additions for table products.
                Data now has real tax codes.
                products.pricemethod (q_strict) values not settled at Mike's end.
    24Oct12 EL New layout of source data, for 2012.10.23_-_Products_Fork.txt onwards.
    20Oct12 EL Populate prodExtra, which is used in fannie/item/productList reports.
               Support deleteing data files from form choice.
    10Oct12 EL Source col Z for scale/each. Change test to use SCALE instead of DEPT_NAME.
               Support truncating or not truncating tables from form choice.
     6Oct12 EL Changes for new Department scheme.
     3Oct12 EL Fix error in margin/Markup assignment.
               Prefer price from col O "Price" to calculating price.
    25Sep12 EL Changes to columns and data formats.
    18Sep12 EL Treat CaseCost = -1 as a flag for data known to be incomplete and
                ignore the record silently.

     6Sep12 Eric Lee loadWEFCTorontoProducts.php , based on
             loadWEFCTorontoDepts.php , based on
                        fannie/batches/UNFI/load-scripts/loadUNFIprices.php
--commentz }    
*/

/* #'F--FUNCTIONALITY - - - - - - - - - - - - - - - - - - - - - -
    -=proposed  o=in-progress  +=in-production  x=removed

    + Add records to products
    - Option to truncate table
        o Interface
        - Operation
    + Option to overwrite on UPC match
        + Interface
        + Operation
    + Lookup to departments on dept_name to get dept_no
    + Lookup to deptMargin on dept_ID to get margin
    + Populate vendorItems.  May be useful for labels.
    - Option to delete file

    + Preserve, the file-splitting apparatus, altho the file here is not big enough
        to need it.
        + Defeat the file-splitting.

    - May want an option to upload the CSV. In the model, upload is a separate script.

*/


/*
    This page merits a little explanation. Here's the basic rundown:
    We take a provided csv file, parse out the info we need,
    calculate margins, and stick it into the database.

    This gets complicated because the csv files can easily be large
    enough to run headlong into PHP's memory limit. To address that,
    I take this approach:

    * split the csv file into 2500 line chunks
    * make a list of these chunks
    * process one of these files
    * redirect back to this page (resetting the memory limit)
      and process the next file
*/

/* configuration for your module - Important */
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
$dbc = FannieDB::get($FANNIE_OP_DB);
require($FANNIE_ROOT.'auth/login.php');

if ( !validateUserQuiet('admin') ) {
    $redirect = $_SERVER['REQUEST_URI'];
    $url = $FANNIE_URL.'auth/ui/loginform.php';
    header('Location: '.$url.'?redirect='.$redirect);
}

$tpath = sys_get_temp_dir()."/misc/";

// Are we dev-side?
$isposdev = preg_match("/0A-38/", php_uname('n'))?True:False;

/* Return the number of decimal places to use in a price format spec: %.#f .
 * Minimum is 2.
 * > 2 only if the price is in a quantity-enforced situation.
 * 
 * I renamed this sigdecimals2 to prevent a name conflict with another
 * sigdecimals function in prodFunction_WEFC_Toronto
 * Andy - 26Aug14
*/
function sig_decimals2 ($num, $qtty_enforced=0) {
    $dec = 2;
    if ( $qtty_enforced ) {
        if ( preg_match('/\.\d{3}/',$num) )
            $num = rtrim($num,'0');
        for ($n=5 ; $n > $dec ; $n--) {
            $pattern ='/\.\d{'.$n.'}$/';
            if ( preg_match($pattern,$num) ) {
                $dec = $n;
                break;
            }
        }
    }
    return $dec;
}

/* Based on whether we have the name of a file to load,
        is this a request-to-upload or delete, or an initial display of the get-file-to-process form?
*/
if ( isset($_REQUEST['product_csv']) && $_REQUEST['product_csv'] != "" ) {

    $product_csv = $_REQUEST['product_csv'];

    /* EL Not pertinent to departments or WEFC products
    require($FANNIE_ROOT.'batches/UNFI/lib.php');
    $VENDOR_ID = getVendorID(basename($_SERVER['SCRIPT_FILENAME']));
    if ($VENDOR_ID === False){
        echo "Error: no vendor has this load script";
        exit;
    }
    */

    $PRICEFILE_USE_SPLITS = False;
    //$PRICEFILE_USE_SPLITS = True;

    // Messages to display when the run finished.
    $messages = array();
    $mct = -1;
    $messages[++$mct] = "Issues and Information:";

    if ( isset($_REQUEST['deleteFile']) ) {
        if ( unlink("{$tpath}{$product_csv}") ) {
            $messages[++$mct] = "$product_csv deleted.";
        } else {
            $messages[++$mct] = "$product_csv could not be deleted.";
        }
        $filestoprocess = array();
    }
    // Process the file
    else {

        // The tables affected by this script.
        $productTables = array("products", "productUser", "products_WEFC_Toronto", "vendorItems", "prodExtra");

        /*
            filestoprocess is the array of split files.
            If it hasn't been created yet, it implies this is the first pass.
            So the csv is split and a list of splits is built.
            On 2nd+ pass take the existing list of splits from GET.
        */
        $filestoprocess = array();
        $i = 0;
        $fp = 0;
        // We've already done this.
        //$tpath = sys_get_temp_dir()."/misc/";
        if ($PRICEFILE_USE_SPLITS){
            if (!isset($_GET["filestoprocess"])){
                system("split -l 2500 {$tpath}{$product_csv} {$tpath}DEPTSPLIT");
                $dir = opendir($tpath);
                while ($current = readdir($dir)){
                    if (!strstr($current,"DEPTSPLIT"))
                        continue;
                    $filestoprocess[$i++] = $current;
                }

                if ( isset($_REQUEST['clear_products']) && ! isset($_REQUEST['dry_run'])) {
                    foreach ($productTables as $table) {
                        $truncateQ = "truncate table $table";
                        $truncateR = $dbc->query($truncateQ);
                        if ( ! $truncateR ) {
                            $messages[++$mct] = "** Error: Failed: $truncateQ";
                        } else {
                            $messages[++$mct] = "Table $table truncated (emptied)";
                        }
                    }
                    $messages[++$mct] = "";
                }
                /* EL Maybe, if doing vendorItems
                $delQ = "DELETE FROM vendorItems WHERE vendorID=$VENDOR_ID";
                $delR = $dbc->query($delQ);
                $delQ = "DELETE FROM vendorSRPs WHERE vendorID=$VENDOR_ID";
                $delR = $dbc->query($delQ);
                */
            }
            else {
                $filestoprocess = unserialize(base64_decode($_GET["filestoprocess"]));  
            }
        }
        else {
            if ( isset($_REQUEST['clear_products']) && ! isset($_REQUEST['dry_run'])) {
                foreach ($productTables as $table) {
                    $truncateQ = "truncate table $table";
                    $truncateR = $dbc->query($truncateQ);
                    if ( ! $truncateR ) {
                        $messages[++$mct] = "** Error: Failed: $truncateQ";
                    } else {
                        $messages[++$mct] = "Table $table truncated (emptied)";
                    }
                }
                $messages[++$mct] = "";
            }
            $filestoprocess[] = "$product_csv";
        }

        // Remove one (there may only be one) split from the list and process it.
        $current = array_pop($filestoprocess);

        // #'C --CONSTANTS- - - - - - - - - - - - - - - - - - - - - - - - - - - -

        /* Indexes to the array returned by fgetcsv, the column number in the .csv file.
            "first" = 0.
        {
        */
        $UPC = 0;                               // G:A:0 "Supplier UPC". UPC or PLU.
        $SEARCH = 1;                        // B:B:1 search_description, for productUser.description
//  $STD = 1;                               // B:B:1 STD y/n "in use". Values?
        $VENDOR_NAME = 2;               // E:C:2 Distributor
        $ORDER_CODE = 3;                // -:D:3 ORDER_CODE, from Buying system
        $DEPT_NAME = 4;                 // F:E:4 Department
        $BRAND_NAME = 5;                // C:F:5 Brand deptMargin.dept_ID
// 14Jan13 PRODUCER_MEMBER  apparently dropped.
// $PRODUCER_MEMBER = 8;        // S:I:8 Producer Member
// 15Jan13 New.
// G - "in use" (this is new, as per email this evening)
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
// 15Jan13 End New.
//
// 15Feb13 New.
        $DISCOUNTABLE = 12;         // -:M:12 Item discountable 0=no 1=yes
        $DISCOUNT_TYPE = 13;        // -:N:13 Whether to use "special" i.e. sale prices and for which kind of customer:
/*
discounttype indicates if an item is on sale
    0 => not on sale
    1 => on sale for everyone
    2 => on sale for members
Values greater than 2 may be used, but results will
vary based on whose code you're running
*/
        $DESCRIPTION = 14;          // D:O:14 Item
        $CASE_COST = 15;                // H:P:15 "Case Cost" 9999.99
        $CASE_SIZE = 16;                // J:Q:16 "Case Size" 99, but 99.9999 pounds per container for bulk
        $UNIT_SIZE = 17;                // K:R:17 "Unit Size" 99 
        $UNIT_NAME = 18;                // L:S:18 "Units "g"/"ml"/"bags"
        $UNIT_COST = 19;                // M:T:19 Cost per Unit 999.99 "Cost"
//
        $CALC_PRICE = 20;               // N:U:20 "Set Price" but in fact CASE_COST/CASE_SIZE
        $SET_PRICE = 21;                // O:V:21 "Price" Jiggered price
//
        $TAX_TYPE = 22;                 // R:W:22 Tax 0/1/2 "Taxes"
        $MARKUP = 23;                       // S:X:23 Markup "150%" -> 1.42 "Markup"
        $SKU = 24;                          // D:Y:24 Vendor SKU
//
        $SALE_PRICE = 25;               // Z:25 "Sale Price"
        $SALE_COST = 26;                // AA:26 "Sale Cost"
        $TEMP_COST = 27;                // AB:27 "Temp Cost" ? Case sale price.
//
        $CATEGORY_SD = 28;          // W:AC:28 Category SuperDept.
//
        $SCALE = 30;                        // Z:AE:30 BULK. "BULK" means scale=1
        $VENDOR_ID = 34;                // AE:AI:34 Vendor ID.
//
// 30Apr13 Columns AJ/35 - BN/64 are for Qualification flags.
//         "" means not-assigned, probably implies No. 0=No 1=Yes
//-- :End }


        // Defaults for:
        //  products table
        $dept_fs = 0;
        $dept_limit = 999.00;
        $dept_minimum = 0.01;
        $dept_discount = 0;
        // $modified = "now()"; // Use literal $dbc->(now())
        $modifiedby = 1;

        $inUse = 0;
        $qttyEnforced = 0;
        // For group pricing.
        $quantity = 0;
        $groupprice = 0;
        $pricemethod = 0;
        $quantity = 0;

        /* Default values for products table fields that are not assigned from data derived from the spreadsheet.
         * Maybe they s/b prefixed p_ for uniqueness.
        */
        //2
        $special_price=0;   // %.2f
        $specialpricemethod=0; // %d
        //3         
        $specialgroupprice=0;   // %.2f
        $specialquantity=0;// %d
        $start_date="'1900-01-01'"; // %s
        $end_date="'1900-01-01'";   // %s
        //4
        $scaleprice=0;  // %.2f
        $advertised=0; // %d
        //5         
        $discounttype=0;// %d
        $tareweight=0;  // %.2f
        $wicable=0;// %d
        //6         
        $idEnforced=0;// %d
        $numflag=0;// %d
        $subdept=0;// %d
        $deposit=0; // %.2f
        // End of products defaults.

        // Counters for items sold by each method.  Report at end.
        $weightCount = 0;
        $eachCount = 0;

        /* Cache departments and deptMargin data in an array on dept_name */
        $defaults_table = array();
        $defQ = "SELECT dept_no, dept_name, dept_tax, dept_fs, dept_discount, margin
        FROM departments, deptMargin
        WHERE dept_no = dept_ID";
        $defR = $dbc->query($defQ);
        while($defW = $dbc->fetch_row($defR)){
            $departments[$defW['dept_name']] = array(
                'id' => $defW['dept_no'],
                'tax' => $defW['dept_tax'],
                'fs' => $defW['dept_fs'],
                'discount' => $defW['dept_discount'],
                'margin' => $defW['margin']
            );
        }

        // First line/record is column heads.
        $skip_one = isset($_REQUEST['skip_one'])?True:False;

        // Lines read from CSV, including column headings.
        $lineCount = 0;
        // Product records written.
        $productCount = 0;
            // Breakdown of writes:
            $insertCount = 0;
            $updateCount = 0;
        // Records flagged as incomplete.
        $incompletes = 0;

        // SET_PRICE in source data exists or no.
        $hasPrice = 0;

        // Known in products.  Only matters if !$hasPrice
        $inProducts = 0;

        // Order code
        $order_code = 'NULL';

        // #'L --LOOP - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        $fp = fopen($tpath.$current,'r');
        while( !feof($fp) ) {

            $data = fgetcsv($fp, 0, "\t", '"');
            if (count($data) == 0) {
                continue;
            }
            $lineCount++;
            /* skip header row
                Maybe better to let the tax-check-trick handle this.
            */
            if ($skip_one){
                $skip_one = False;
                continue;
            }

            //EL The data is tab-delimited, but no embedded commas.
            //      But fields with embedded commas are quoted.
            //      Why not just use explode()?
            if ( !is_array($data) ) {
                echo "Line $lineCount is not a valid CSV line.";
                $messages[++$mct] = "Line $lineCount is not a valid CSV line.";
                continue;
            }

            if ( $data[$TAX_TYPE] == "#NAME?" ) {
                $data[$TAX_TYPE] = "";
            }

            // 17Jan13 Case-cost tests formerly here.

            /* Compose values for the db in vars with same names as table rows.
                    Assume table products unless qualified.
            */

            // Row cannot be valid without this.
            // Skipping the row of column labels is done later.
            // In early versions some don't have numbers yet, useless.
            if ( $data[$UPC] == "" ) {
                $messages[++$mct] = "<br />Skipping line $lineCount {$data[$DESCRIPTION]} because it has no UPC/PLU.";
                continue;
            }

            $upc = $data[$UPC];
            // Incoming data is expected to be %012d and UPC's have checkdigit at the end which must be removed.
            // Check for the expected base format.
            if ( ! preg_match("/^\d{12}/", $upc) ) {
                $messages[++$mct] = "<br />Skipping line $lineCount {$data[$DESCRIPTION]} because its UPC &gt;{$upc}&lt; is not 12 digits.";
                continue;
            } else {
                // If it's a PLU (up to 7 digits with left-0 padding), leave as-is.
                if ( ! preg_match("/^0{5}/", $upc) ) {
                    // Remove checkdigit.
                    $upc = substr($upc, 0, 11);
                }
                $upc = str_pad($upc,13,'0',STR_PAD_LEFT);
            }

            // Don't include duplicate UPC.
            if ( !isset($_REQUEST['overwrite_products']) ) {
                $checkQ = "SELECT upc FROM products WHERE upc = " . $dbc->escape($upc);
                $checkR = $dbc->query($checkQ);
                if ($dbc->num_rows($checkR) > 0) {
                    $messages[++$mct] = "<br />Skipping line $lineCount upc duplicate: $upc for all tables.";
                    continue;
                }
            }

            if ( $data[$ORDER_CODE] == '' ) {
                $order_code = 'NULL';
            } elseif ( is_numeric($data[$ORDER_CODE]) ) {
                if ( $data[$ORDER_CODE] <= 9999999 ) {
                    $order_code = $data[$ORDER_CODE];
                } else {
                    $messages[++$mct] = "<br />Order number >{$data[$ORDER_CODE]}< for upc: $upc is too long; set to NULL.";
                    $order_code = 'NULL';
                }
            } else {
                $messages[++$mct] = "<br />Order number >{$data[$ORDER_CODE]}< for upc: $upc is not numeric; set to NULL.";
                $order_code = 'NULL';
            }

            // PV description defaults to receipt description
            $data[$DESCRIPTION] = trim($data[$DESCRIPTION]);
            if ( $data[$SEARCH] == "" ) {
                $data[$SEARCH] = $data[$DESCRIPTION];
            }
            $data[$SEARCH] = trim($data[$SEARCH]);

            // Compose description: "Juice - Orange 946ml"
            // Used in products and 17Jan13 productUser.
            $package = "";
            // For productUser.sizing, #NvendorItems.size
            $sizing = "";
            // For products.size, XvendorItems.units
            $unitsize = trim($data[$UNIT_SIZE]);
            if ( $data[$UNIT_SIZE] != "" && $data[$UNIT_NAME] != "" ) {
                $package = " " . $data[$UNIT_SIZE] . "" . $data[$UNIT_NAME];
                $sizing = $data[$UNIT_SIZE] . " " . $data[$UNIT_NAME];
            }
            $plen = strlen($package);
            $description = $data[$DESCRIPTION];
            $dlen = strlen($description);
            $MAX_DESC_LEN = 30;
            $wlen = ($dlen + $plen);

            if ( $wlen <= $MAX_DESC_LEN ) {
                $description .= $package;
            } else {
                $description = substr($description,0,($dlen - ($wlen - $MAX_DESC_LEN))) . $package;
            }
            //echo "<br />$lineCount ", strlen($description), " $description\n";

            if ( array_key_exists($data["$DEPT_NAME"], $departments) ) {
                $department = $departments[$data["$DEPT_NAME"]]['id'];
                $departmentTax = $departments[$data["$DEPT_NAME"]]['tax'];
                $departmentFS = $departments[$data["$DEPT_NAME"]]['fs'];
                $departmentDiscount = $departments[$data["$DEPT_NAME"]]['discount'];
                $departmentMargin = $departments[$data["$DEPT_NAME"]]['margin'];
                if ( $data[$SCALE] == "BULK" ) {
                    $scale = 1;
                    $weightCount++;
                }
                else {
                    $scale = 0;
                    $eachCount++;
                }
            }
            else {
                $messages[++$mct] = "Line $lineCount Department >{$data[$DEPT_NAME]}< is not known.";
                echo "$messages[$mct]";
                continue;
            }

            // If these are not set in the product record use the department defaults.
            if ( $data[$TAX_TYPE] != "" ) {
                $tax = $data[$TAX_TYPE];
            }
            else {
                $tax = $departmentTax;
            }

            if ( $data[$MARKUP] == "#NAME?" ) {
                $data[$MARKUP] = "";
            }
            //if ( is_numeric($data[$MARKUP]) != "" ) {}
            if ( is_numeric($data[$MARKUP]) ) {
                $margin = sprintf("%.5f",(($data[$MARKUP]-1)/$data[$MARKUP]));
                //$messages[++$mct] = "Line $lineCount created margin $margin";
                /* From when we were markup-based.
                $margin = $data[$MARKUP];
                if ( $margin == "0.00" ) {
                    $margin = "1.00";
                    $messages[++$mct] = "Line $lineCount changed markup 0.00 to 1.00";
                }
                */
            }
            else {
                $margin = $departmentMargin;
                //$messages[++$mct] = "Line $lineCount department margin $margin";
            }
            //  6Sep2012 These never come from the spreadsheet.
            $fs = $departmentFS;

            /* 15Feb13 Support getting discountable from spreadsheet.
            if ( strpos($data[$DEPT_NAME],"B") === 0 )
                 $data[$DISCOUNTABLE] = "1";
            */
            if ( $data[$DISCOUNTABLE] == "" ) {
                $discount = $departmentDiscount;
            }
            elseif ( $data[$DISCOUNTABLE] != "0" && $data[$DISCOUNTABLE] != "1" ) {
                $messages[++$mct] = "Line $lineCount invalid discountable >{$data[$DISCOUNTABLE]}< : set to $departmentDiscount";
                $discount = $departmentDiscount;
            }
            else {
                $discount = $data[$DISCOUNTABLE];
            }

            /* 15Feb13 Support getting discounttype from spreadsheet.
            if ( $data[$DISCOUNT_TYPE] == "" ) {
                $discounttype = 0;
            }
            elseif ( !is_numeric($data[$DISCOUNT_TYPE]) || ($data[$DISCOUNT_TYPE] != "0" && $data[$DISCOUNT_TYPE] != "1" && $data[$DISCOUNT_TYPE] != "2") ) {
                $messages[++$mct] = "Line $lineCount invalid discounttype >{$data[$DISCOUNT_TYPE]}< : set to 0";
                $discounttype = 0;
            }
            else {
                $discounttype = $data[$DISCOUNT_TYPE];
            }
            */

            // Margin sanity check.
            if ( !preg_match("/^\d\.\d+$/", $margin) || ($margin * 1.00) < 0.00 || ($margin * 1.00) > 0.50 ) {
                $messages[++$mct] = "Line $lineCount $description has suspect margin >{$margin} : markup >{$data[$MARKUP]}<";
            }
            /* Markup sanity check.
            if ( !preg_match("/^\d\.\d+$/", $margin) || ($margin * 1.00) < 1.00 || ($margin * 1.00) > 3.00 ) {
                $messages[++$mct] = "Line $lineCount $description has suspect markup >{$margin}<";
            }
            */

            /* 's 17Jan13 The special cases of no price.
             *            May also need to check CALC_PRICE
             * If the item already exists:
             *  - UPDATE except for prices
             * If the item does no exist:
             *  - INSERT ?without prices
            */
            if ( $data[$SET_PRICE] == "" || $data[$SET_PRICE] == "-1" ) {
                $hasPrice = 0;
                $checkQ = "SELECT upc FROM products WHERE upc = " . $dbc->escape($upc);
                $checkR = $dbc->query($checkQ);
                if ($dbc->num_rows($checkR) > 0) {
                    $inProducts = 1;
                } else {
                    $inProducts = 0;
                }
//$messages[++$mct] = "No price upc: $upc inProducts: $inProducts";
//echo "<br />$messages[$mct]";
            } else {
                $hasPrice = 1;
            }

            // 20Jun13 Bulk does have CASE_COST, the cost of the bulk container,
            //          but keep testing SET_PRICE.
            // 24Jan13 Change test to SET_PRICE because for bulk there is no CASE_COST.
            // 17Jan13 CASE_COST tests moved here.
            /* Skip the item if case-cost isn't numeric
                    this will catch the 'label' line in the first CSV split
                    since the splits get returned in file system order,
                    we can't be certain *when* that chunk will come up
            */
            if ( $hasPrice && !is_numeric($data[$SET_PRICE]) ) {
                $messages[++$mct] = "<br />Skipping line $lineCount UPC: {$data[$UPC]} {$data[$DESCRIPTION]} because of invalid price (col V): >{$data[$SET_PRICE]}<";
                continue;
            }

            if ( $hasPrice && empty($data[$CASE_COST]) ) {
                $messages[++$mct] = "<br />Skipping line $lineCount UPC: {$data[$UPC]} {$data[$DESCRIPTION]} because of no case cost (col P): >{$data[$CASE_COST]}<";
                continue;
            }

            // Treat CASE_COST == -1 as flag for data known to be incomplete and skip and note.
            if ( $data[$CASE_COST] == -1 ) {
                $messages[++$mct] = "<br />Skipping line $lineCount UPC: {$data[$UPC]} {$data[$DESCRIPTION]} because flagged-as-incomplete item >{$data[$CASE_COST]}<";
                $incompletes++;
                continue;
            }

            /* Also calculate or compose:
                price: (cost/units)*margin
                cost: (cost/units)
                size: should it default to 1?
            */
            $cost = "";
            $normal_price = "";
            $size = "";
            // Or better NULL
            if ( !$hasPrice )
                $size = 0;
            $unitofmeasure = "";
            if ( $data[$CASE_COST] != "" ) {
                $data[$CASE_COST] = sprintf("%.2f", $data[$CASE_COST]);
            }
            $set_price_pattern = '/^\d+\.\d{2}$/';
            if ( $data[$SET_PRICE] != "" ) {
                $dec = ($data[$QTYFRC])?sig_decimals2($data[$SET_PRICE],1):2;
                $set_price_pattern = '/^\d+\.\d{'.$dec.'}$/';
//$messages[++$mct] = "UPC: {$data[$UPC]} set_price : {$data[$SET_PRICE]} qttyF: {$data[$QTYFRC]}";
                $data[$SET_PRICE] = sprintf("%.{$dec}f", $data[$SET_PRICE]);
//$messages[$mct] .= " becomes {$data[$SET_PRICE]}";
            }
            if ( $data[$UNIT_COST] != "" ) {
                $data[$UNIT_COST] = sprintf("%.2f", $data[$UNIT_COST]);
            }

            // If price not supplied skip the assignments that involve it
            //  but still assign $size.
            // Prefer the pre-calculated or pre-set price
            // 24Jan13 Require CASE_COST
            if ( $hasPrice && $data[$CASE_COST] != "" ) {
                //if ( preg_match("/^\d+\.\d\d$/",$data[$SET_PRICE]) && preg_match("/^\d+\.\d\d$/",$data[$UNIT_COST]) ) {   }
                if ( preg_match($set_price_pattern,$data[$SET_PRICE]) && preg_match("/^\d+\.\d\d$/",$data[$UNIT_COST]) ) {
                    $normal_price = $data[$SET_PRICE];
                    $cost = $data[$UNIT_COST];
                    if ( preg_match("/^\d+$/",$data[$CASE_SIZE]) ) {
                        $size = $data[$CASE_SIZE];
                    } else {
                        $size = "";
                    }
                    //echo "<br />1. $lineCount upc >{$upc}<  dept_no {$department} *SET_PRICE: {$data[$SET_PRICE]}  normal_price >{$normal_price}<";
                    // Compare set and calculated prices. For checking margin-based function.  Seldom == but ususally ~=.
                    if ( FALSE && preg_match("/^\d+\.\d\d$/",$data[$CASE_COST]) && preg_match("/^\d+$/",$data[$CASE_SIZE]) && preg_match("/^\d\.\d+$/",$margin) ) {
                        // Markup-based price
                        $normal_price2 = sprintf("%.2f", ((($data[$CASE_COST] * 100) / ($data[$CASE_SIZE] * 100)) / (1 - $margin)));
                        echo "<br />";
                        if ( $normal_price != $normal_price2 ) {
                            echo "*****";
                        }
                        echo "1. $lineCount upc >{$upc}<  dept_no {$department} SET_PRICE: {$data[$SET_PRICE]} normal_price >{$normal_price}< normal_price2 >{$normal_price2}<";
                    }
                }
                // If no pre-set, or no unit_cost then calculate as needed.
                elseif ( preg_match("/^\d+\.\d\d$/",$data[$CASE_COST]) &&
                                (preg_match("/^\d+$/",$data[$CASE_SIZE]) || preg_match("/^\d+\.\d+$/",$data[$CASE_SIZE])) &&
                                 preg_match("/^\d\.\d+$/",$margin) ) {
                    //if ( preg_match("/^\d+\.\d\d$/",$data[$SET_PRICE]) ) {}
                    if ( preg_match($set_price_pattern,$data[$SET_PRICE]) ) {
                        $normal_price = $data[$SET_PRICE];
                    } else {
                        // Markup-based price
                        $normal_price = sprintf("%.2f", ((($data[$CASE_COST] * 100) / ($data[$CASE_SIZE] * 100)) / (1 - $margin)));
                        /* Markup-based price
                        $normal_price = sprintf("%.2f", ((($data[$CASE_COST] * 100) / ($data[$CASE_SIZE] * 100)) * $margin));
                        */
                    }
                    $cost = sprintf("%.2f", (($data[$CASE_COST] * 100) / ($data[$CASE_SIZE] * 100)));
                    $size = $data[$CASE_SIZE];
                    //echo "<br />2. $lineCount upc >{$upc}<  dept_no {$department} SET_PRICE: {$data[$SET_PRICE]}  *normal_price >{$normal_price}<";
                } else {
                    $messages[++$mct] = sprintf("Line $lineCount $description Cannot derive a per-unit price from CASE_COST >%s<  CASE_SIZE >%s<  margin >%s<.", $data[$CASE_COST], $data[$CASE_SIZE], $margin);
                    continue;
                }
            } else {
                if ( preg_match("/^\d+$/",$data[$CASE_SIZE]) || preg_match("/^\d+\.\d+$/",$data[$CASE_SIZE]) )
                    $size = $data[$CASE_SIZE];
            }
//$messages[++$mct] = sprintf("--- Ultimately from SET_PRICE >%s<  we get normal_price >%s< .", $data[$SET_PRICE], $normal_price);

            // May need some massaging/regularization: mL -> ml, gm -> g, ...
            $unitofmeasure = $data[$UNIT_NAME];

            $inUse = ( $data[$IN_USE] != "" ) ? $data[$IN_USE] : 0;

            $qttyEnforced = ( $data[$QTYFRC] != "" ) ? $data[$QTYFRC] : 0;
            /*
            $qttyEnforced = 0;
            if ( $data[$QUANTITY] != "" ) {
                $qttyEnforced = $data[$QTYFRC];
            }
            */

            $quantity = ( $data[$QUANTITY] != "" ) ? $data[$QUANTITY] : 0;

            $mixmatchcode = ( $data[$MIXMATCH] != "" ) ? $data[$MIXMATCH] : "";

            $groupprice = "";
            if ( $data[$GROUP_PRICE] != "" ) {
                $groupprice = sprintf("%.2f", $data[$GROUP_PRICE]);
            }

            // 0 or 1 or 2.  Must default to 0.
            $pricemethod = ( $data[$PRICE_METHOD] != "" ) ? $data[$PRICE_METHOD] : 0;
            if ( $pricemethod == 2 && $normal_price == "" ) {
                $messages[++$mct] = "<br />Line $lineCount missing normal_price when pricemethod = 2";
            }

            if ( !$hasPrice && $inProducts ) {
                $dbMode = "update";
                $updateCount++;
//$messages[++$mct] = "No price upc: $upc inProducts: $inProducts dmMode: $dbmode\n";
//echo "<br />$messages[$mct]";
            }
            else {
                $dbMode = "add/replace";
                $insertCount++;
            }

            if ( True && !$hasPrice ) {
                //echo "<br />No price. $dbMode $upc $description";
                $messages[++$mct] = "<br />No price. Col V >{$data[$SET_PRICE]}< To $dbMode products. $upc $description";
            }

            /* Check for unknown vendors.
             * Report-only on live-side.
             * On dev-side, Report if dry-run, add if live.
            */
            $vendorID = $data[$VENDOR_ID];
            $vendorName = $data[$VENDOR_NAME];
            if ( is_numeric($vendorID) && $vendorID < 1000 && $vendorName != "" ) {
                $venQ = "SELECT * from vendors WHERE vendorID = $vendorID";
                $venR = $dbc->query("$venQ");
                if ( $dbc->num_rows($venR) == 0 ) {
                    if ( $isposdev ) {
                        if ( ! isset($_REQUEST['dry_run']) ) {
                            $venQ = "INSERT INTO vendors (vendorID, vendorName) VALUES ($vendorID, " . $dbc->escape($vendorName) . ")";
                            $dbc->query($venQ);
                            $messages[++$mct] = "Added vendor $vendorID $vendorName";
                        } else {
                            $messages[++$mct] = "Will add vendor $vendorID $vendorName";
                        }
                    } else {
                        $messages[++$mct] = "Need to add vendor $vendorID $vendorName";
                    }
                }
            }


            /* Qualifications flags
            */
            // Offset of first qualifications flag in $data
            $first_flag = 35;
            $numflag = 0;
            //echo "Start: $numflag\n";
            for ($i=0 ; $i<30 ; $i++) {
                if (
                    array_key_exists(($first_flag+$i), $data) &&
                    preg_match("/^(1|yes|y|t|true|x)$/", strtolower($data[$first_flag+$i]))
                )
                    $numflag = $numflag | (1 << $i);
            }

            /* All problems should be in $messages[] at this point.
            */
            if ( isset($_REQUEST['dry_run']) ) {
                continue;
            }

            /* --products - - - - */
            /* #'P--PRODUCTS - - - - */

            $table = "products";

            if ( $dbMode == "add/replace" ) {

                // Should this depend on inProducts?
                if ( isset($_REQUEST['overwrite_products']) ) {
                    $dbc->query("DELETE FROM products WHERE upc=".$dbc->escape($upc));
                }

                /*
        1       $insQ = sprintf("INSERT INTO products (upc, description, normal_price, 
        2           pricemethod, groupprice, quantity, special_price, specialpricemethod, 
        3           specialgroupprice, specialquantity, start_date, end_date, department, 
        4           size, tax, foodstamp, scale, scaleprice, mixmatchcode, modified, advertised, 
        5           tareweight, discount, discounttype, unitofmeasure, wicable, qttyEnforced, 
        6           idEnforced, cost, inUse, numflag, subdept, deposit)
        1           VALUES (%s, %s, %.2f,
        2           %d, %.2f, %d, .0, 0,
        3           .0, 0, '1900-01-01', '1900-01-01', %d,
        4           %s, %d, %d, %d, .0, %s, %s, 0,
        5           .0, %d, 0, %s, 0, %d,
        6           0, %.2f, %d, 0, 0, .0, %d)",
        1           $dbc->escape($upc), $dbc->escape($desc), $normal_price,
        2     $pricemethod, $groupprice, $quantity,
        3           $department,
        4           $dbc->escape($unitsize), $tax, $fs, $scale, $dbc->escape($mixmatchcode), $dbc->now(),
        5           $discount, $unitofmeasure, $qttyEnforced,
        6           $cost, $inUse

    1           upc, description, normal_price, 
    2               pricemethod, groupprice, quantity, $special_price=0; $specialpricemethod=0; 
    3               $specialgroupprice=0; $specialquantity=0; $start_date="'1900-01-01'"; $end_date="'1900-01-01'"; department, 
    4               size, tax, foodstamp, scale, $scaleprice=0; mixmatchcode, modified, $advertised=0; 
    5               $tareweight=0; discount, $discounttype=0; unitofmeasure, $wicable=0; qttyEnforced, 
    6               $idEnforced=0; cost, inUse, $numflag=0; $subdept=0; $deposit=0)
        );
                */

    /* Default values for products fields that are not assigned from data derived from the spreadsheet.
    //2
    $special_price=0;   // %.2f
    $specialpricemethod=0; // %d
    //3         
    $specialgroupprice=0;   // %.2f
    $specialquantity=0;// %d
    $start_date="'1900-01-01'"; // %s
    $end_date="'1900-01-01'";   // %s
    //4
    $scaleprice=0;  // %.2f
    $advertised=0; // %d
    //5         
    $tareweight=0;  // %.2f
    $discounttype=0;// %d
    $wicable=0;// %d
    //6         
    $idEnforced=0;// %d
    $numflag=0;// %d
    $subdept=0;// %d
    $deposit=0; // %.2f
    */

                $insQ = sprintf("INSERT INTO products (upc, description, normal_price, 
                    pricemethod, groupprice, quantity, special_price, specialpricemethod, 
                    specialgroupprice, specialquantity, start_date, end_date, department, 
                    size, tax, foodstamp, scale, scaleprice, mixmatchcode, modified, advertised, 
                    tareweight, discount, discounttype, unitofmeasure, wicable, qttyEnforced, 
                    idEnforced, cost, inUse, numflag, subdept, deposit)
                    VALUES (%s, %s, %.2f,
                    %d, %.2f, %d, %.2f, %d,
                    %.2f, %d, %s, %s, %d,
                    %s, %d, %d, %d, %.2f, %s, %s, %d,
                    %.2f, %d, %d, %s, %d, %d,
                    %d, %.2f, %d, %d, %d, %.2f)",
                    $dbc->escape($upc), $dbc->escape($description), $normal_price,
                    $pricemethod, $groupprice, $quantity, $special_price, $specialpricemethod,
                    $specialgroupprice, $specialquantity, $start_date, $end_date, $department,
                    $dbc->escape($unitsize), $tax, $fs, $scale, $scaleprice, $dbc->escape($mixmatchcode), $dbc->now(), $advertised,
                    $tareweight, $discount, $discounttype, $dbc->escape($unitofmeasure), $wicable, $qttyEnforced,
                    $idEnforced, $cost, $inUse, $numflag, $subdept, $deposit);

    /* Woodshed
                $insQ = sprintf("INSERT INTO products (upc, description, normal_price, 
                    pricemethod, groupprice, quantity, special_price, specialpricemethod, 
                    specialgroupprice, specialquantity, start_date, end_date, department, 
                    size, tax, foodstamp, scale, scaleprice, mixmatchcode, modified, advertised, 
                    tareweight, discount, discounttype, unitofmeasure, wicable, qttyEnforced, 
                    idEnforced, cost, inUse, numflag, subdept, deposit)
                    VALUES (%s, %s, %.2f,
                    %d, %.2f, %d, %.2f, %d,
    3               %.2f, %d, %s, %s, %d,
    4               %d, %d, %d, %d, %.2f, %s, %s, %d,
    5               %.2f, %d, %d, %s, %d, %d,
    6               0, %.2f, %d, 0, 0, .0, %d)",
                    $dbc->escape($upc), $dbc->escape($description), $normal_price,
    2
                    $pricemethod, $groupprice, $quantity,
                    $special_price=0;   // %.2f
                    $specialpricemethod=0; // %d
    3
    $specialgroupprice=0;   // %.2f
    $specialquantity=0;// %d
    $start_date="'1900-01-01'"; // %s
    $end_date="'1900-01-01'";   // %s
    $department,
    4
    $size, $tax, $fs, $scale,
    $scaleprice=0;  // %.2f
    $dbc->escape($mixmatchcode),
    $dbc->now(),
    $advertised=0; // %d
    5               
    $tareweight=0;  // %.2f
    $discount,
    $discounttype=0;// %d
    $dbc->escape($unitofmeasure),
    $wicable=0;// %d
    $qttyEnforced,
    6               
    $idEnforced=0;// %d
    $cost, $inUse,
    $numflag=0;// %d
    $subdept=0;// %d
    $deposit=0);    // %.2f
    //woodshed
    */

                if ( $insQ == "" ) {
                    echo "<br />$table $dbMode query empty\n";
                } else {
                    $dbc->query($insQ);
                }

            // End of products insert
            }
            else {
                // UPDATE. Only the non-price and non-cost fields that come from the spreadsheet.
                $updQ = "UPDATE products SET
                    description = " . $dbc->escape($description) .",
                    department = $department,
                    size = " . $dbc->escape($unitsize) .",
                    tax = $tax,
                    scale = $scale,
                    mixmatchcode = " . $dbc->escape($mixmatchcode) . ",
                    modified = " . $dbc->now() . ",
                    discount = $discount,
                    unitofmeasure = " . $dbc->escape($unitofmeasure) . ",
                    qttyEnforced = $qttyEnforced,
                    inUse = $inUse
                WHERE upc=".$dbc->escape($upc);

                if ( $updQ == "" ) {
                    echo "<br />$table $dbMode query empty\n";
                } else {
                    $dbc->query($updQ);
                }
            }

            /* #'U --productUser - - - - */

            $table = "productUser";

            $brand = $data[$BRAND_NAME];

            $sep = " | ";

            // Description for use by PV Product Verification at lane.
            if ( $brand ) {
                $search_description = trim(strtoupper($brand));
            } else {
                $search_description = trim($data[$DEPT_NAME]);
            }
            $search_description .= ($sep . $data[$SEARCH]);
            if ( trim($package) != "" ) {
                $search_description .= ($sep . trim($package));
            }

            if ( $dbMode == "add/replace" ) {

                if ( isset($_REQUEST['overwrite_products']) ) {
                    $dbc->query("DELETE FROM productUser WHERE upc=".$dbc->escape($upc));
                }

                $insQ = sprintf("INSERT INTO productUser
                    (upc, description, brand, 
                    sizing, photo, long_text, enableOnline)
                    VALUES (%s, %s, %s,
                    %s, '', '', 0)",
                    $dbc->escape($upc),
                    $dbc->escape($search_description),
                    $dbc->escape($brand),
                    $dbc->escape($sizing));

                $dbc->query($insQ);

            // insert
            }
            else {
                $updQ = "UPDATE productUser SET
                    description = ". $dbc->escape($search_description) . ",
                    brand = ". $dbc->escape($brand) . ",
                    sizing = ". $dbc->escape($sizing) . 
                " WHERE upc=".$dbc->escape($upc);

                if ( $updQ == "" ) {
                    echo "<br />$table $dbMode query empty\n";
                } else {
                    $dbc->query($updQ);
                }
            }

            /* #'T --products_WEFC_Toronto - - - - */
            $table = "products_WEFC_Toronto";

            if ( $dbMode == "add/replace" ) {

            if ( isset($_REQUEST['overwrite_products']) ) {
                $dbc->query("DELETE FROM products_WEFC_Toronto WHERE upc=".$dbc->escape($upc));
            }

            $insQ = sprintf("INSERT INTO products_WEFC_Toronto (upc, order_code,
                description, search_description)
                VALUES (%s, %s,
                %s, %s)",
                $dbc->escape($upc), $order_code,
                $dbc->escape($data[$DESCRIPTION]), $dbc->escape($data[$SEARCH])
                );

            $dbc->query($insQ);

            // insert
            }
            else {
                $updQ = sprintf("UPDATE products_WEFC_Toronto SET
                    order_code = %s,
                    description = %s, search_description = %s
                WHERE upc=%s",
                $order_code,
                $dbc->escape($data[$DESCRIPTION]), $dbc->escape($data[$SEARCH]),
                $dbc->escape($upc));

                if ( $updQ == "" ) {
                    echo "<br />$table $dbMode query empty\n";
                } else {
                    $dbc->query($updQ);
                }
            }

            /* #'V--vendorItems - - - - */
            $table = "vendorItems";

            //Done earlier
            //$brand = $data[$BRAND_NAME];
            $sku = $data[$SKU];
            // Revert to using unit_cost, i.e. cost.
            //$case_cost =  $data[$CASE_COST];
            // Can be longer than the earlier one.
            $description = substr($data[$DESCRIPTION], 0, 50);
            // Use productUser.sizing for size: "300 ml"
            // Use unitsize for units: 500, e.g. grams. Not the case size.
            $vendorID = $data[$VENDOR_ID];
            $vendorDept = 'NULL';
            // If $size is not an integer, e.g. for BULK, vendorItems.units winds up NULL.
            //  This may need to be addressed one day.
            $vi_size = ($size == 0 || $size == "")?'NULL':$size;

            if ( $dbMode == "add/replace" ) {

                if ( isset($_REQUEST['overwrite_products']) ) {
                    $dbc->query("DELETE FROM vendorItems WHERE upc=".$dbc->escape($upc));
                }

                $insQ = sprintf("INSERT INTO vendorItems (upc, sku, brand,
                    description, size, units, cost, vendorDept, vendorID)
                    VALUES (%s, %s, %s,
                    %s, %s, %s, %.2f, %s, %d)",
                    $dbc->escape($upc), $dbc->escape($sku), $dbc->escape($brand),
//old           $dbc->escape($description), $dbc->escape($unitofmeasure), $unitsize, $cost, $vendorDept, $vendorID);
                    $dbc->escape($description), $dbc->escape($sizing), $vi_size, $cost, $vendorDept, $vendorID);

                $dbc->query($insQ);

            // insert
            }
            else {
                // All but cost and vendorDept
                $updQ = sprintf("UPDATE vendorItems SET
                    sku = %s, brand = %s,
                    description = %s, size = %s, units = %s, vendorID = %d
                WHERE upc = %s",
                    $dbc->escape($sku), $dbc->escape($brand),
                    $dbc->escape($description), $dbc->escape($sizing), $vi_size, $vendorID,
                $dbc->escape($upc));

                if ( $updQ == "" ) {
                    echo "<br />$table $dbMode query empty\n";
                } else {
                    $dbc->query($updQ);
                }
            }

            /* #'E--prodExtra - - - -
                * Use of this table is deprecated but the item/productList reports use
                *  the distributor field.
                *  It seems to be the only place in prod* it's available.
                * Unassigned fields variable_pricing, location, case_info I don't know
                *  what they should contain.
            */
            $table = "prodExtra";
            $vendorName = $data[$VENDOR_NAME];
            $case_cost =    $data[$CASE_COST];

            // prodExtra defaults
            $pe_variable_pricing = 0;
            $pe_location = "";
            $pe_case_info = "";

            if ( $dbMode == "add/replace" ) {

                if ( isset($_REQUEST['overwrite_products']) ) {
                    $dbc->query("DELETE FROM prodExtra WHERE upc=".$dbc->escape($upc));
                }


                $insQ = sprintf("INSERT INTO prodExtra
                (upc, distributor, manufacturer,
                    cost, margin, variable_pricing,
                    location, case_quantity, case_cost, case_info)
                VALUES (%s, %s, %s,
                    %.2f, %.5f, %d,
                    %s, %s, %.2f, %s)",
                $dbc->escape($upc), $dbc->escape($vendorName), $dbc->escape($brand),
                $cost, $margin, $pe_variable_pricing,
                $dbc->escape($pe_location), $dbc->escape($size), $case_cost, $dbc->escape($pe_case_info));

                $dbc->query($insQ);

            // insert
            }
            else {
                //All but: cost, case_cost
                $updQ = sprintf("UPDATE prodExtra SET
                distributor = %s, manufacturer = %s,
                margin = %.5f, variable_pricing = %d,
                location = %s, case_quantity = %s, case_info = %s
                WHERE upc = %s",
                    $dbc->escape($vendorName), $dbc->escape($brand),
                    $margin, $pe_variable_pricing,
                    $dbc->escape($pe_location), $dbc->escape($size), $dbc->escape($pe_case_info),
                $dbc->escape($upc));

                if ( $updQ == "" ) {
                    echo "<br />$table $dbMode query empty\n";
                } else {
                    $dbc->query($updQ);
                }
            }

            // Add unknown vendors.  Only dev-side.
            if ( False ) {
                $venQ = "SELECT * from vendors WHERE vendorID = $vendorID";
                $venR = $dbc->query("$venQ");
                if ( $dbc->num_rows("$venQ") == 0 ) {
                    $venQ = "INSERT INTO vendors (vendorID, vendorName) VALUES ($vendorID, '" . $dbc->escape($vendorName) . "')";
                    $dbc->query($venQ);
                    $messages[++$mct] = "Added vendor $vendorID $vendorName";
                }
            }

            // End of table work.

            $productCount++;

        // Each CSV line
        }

        fclose($fp);

    // Process (not delete)
    }

    if ( isset($_REQUEST['deleteFile']) ) {

        /* html header, including navbar */
        $page_title = "Done deleteing file";
        $header = "Done deleteing file";
        include($FANNIE_ROOT."src/header.html");

        // Display messages accumulated during the run.
        for ( $n=0; $n<count($messages); $n++ ) {
            if ( substr($messages[$n], 0, 3) == "<br" ) {
                echo "$messages[$n]";
            } else {
                echo "<br />$messages[$n]";
            }
        }
        echo "<br />";
        
        echo "<div style='margin-top: 0.5em;'>";
        echo "<a href='loadWEFCTorontoProducts.php'>Home: Load WEFC-Toronto Products</a>";
        echo "</div>\n";

        /* html footer */
        include($FANNIE_ROOT."src/footer.html");

    } else {

        /* 
            if the filestoprocess list is empty, it means all the splits
            have been processed (the load is complete):
            - update tables vendorSKUtoPLU, vendorItems, prodExtra.
            + stop and print some summary info
                including what files were processed
                (for sanity's sake) and
            + clean up by deleting all the splits
                (this is actually important since the
                next price file might not split into the same
                number of pieces and we process all the splits in
                tmp. So it isn't concurrency-safe, either)

            otherwise:
            + add the current file to the list of
                splits that have already been processed and
            + redirect back to this page, passing both lists
                (files to be done and file that are already processed)

            serialize & base64 encoding are used to make the
            arrays URL-safe
        */
        if (count($filestoprocess) == 0){

            /* html header, including navbar */
            $page_title = "Done loading items";
            $header = "Done loading items";
            include($FANNIE_ROOT."src/header.html");

            echo "Finished processing Products file<br />";
            if ($PRICEFILE_USE_SPLITS){
                echo "Files processed:<br />";
                foreach (unserialize(base64_decode($_GET["processed"])) as $p){
                    echo $p."<br />";
                    unlink($tpath.$p);
                }
                echo $current."<br />";
                //unlink($tpath.$current);
            }
            else {
                echo "$product_csv<br />";
            }

            if ( isset($_REQUEST['dry_run']) )
                echo "<br />Dry run. ";
            echo "<br />Read $lineCount CSV lines.  Wrote $productCount records to the database.";
            echo "<br /> &nbsp; $insertCount add/replace.";
            echo "<br /> &nbsp; $updateCount updates.";
            echo "<br />$incompletes records flagged as known-to-be incomplete.";
            if ( $weightCount == 0 ) {
                echo "<br />**TROUBLE? 0 items marked to be sold by weight.";
            } else {
                echo "<br />$weightCount items to be sold by weight.";
            }
            echo "<br />$eachCount items to be sold by each.";
            echo "<br />";
            // Display messages accumulated during the run.
            for ( $n=0; $n<count($messages); $n++ ) {
                if ( substr($messages[$n], 0, 3) == "<br" ) {
                    echo "$messages[$n]";
                } else {
                    echo "<br />$messages[$n]";
                }
            }
            echo "<br />";

            // Not unlink
            // unlink($tpath."$product_csv");
            echo "<br />You might want to delete: {$tpath}{$product_csv}<br />";
            
            echo "<div style='margin-top: 0.5em;'>";
            echo "<a href='loadWEFCTorontoProducts.php'>Home: Load WEFC-Toronto Products</a>";
            echo "</div>\n";

            /* html footer */
            include($FANNIE_ROOT."src/footer.html");

        }
        else {
            $processed = array();
            if (isset($_GET["processed"]))
                $processed = unserialize(base64_decode($_GET["processed"]));
            array_push($processed,$current);

            $sendable_data = base64_encode(serialize($filestoprocess));
            $encoded2 = base64_encode(serialize($processed));
            header("Location: loadWEFCTorontoProducts.php?filestoprocess=$sendable_data&processed=$encoded2");

        }

    // Process (not delete) finish
    }

// /We know the file to process or delete.
}
// Get the file to process and other params.
else {

    /* Form to get the name of csv file to load and launch load. */
    /* html header, including navbar */
    $page_title = "Fannie - Load WEFC-Toronto Products";
    $header = "Load WEFC-Toronto Products";
    include($FANNIE_ROOT.'src/header.html');

    // Get a list of files
    $dh = opendir($tpath);
    $opts = "<option value='' SELECTED>No file chosen yet</option>\n";
    $selectSize = 1;
    while (($file = readdir($dh)) !== false) {
        if ( substr($file, 0, 1) != "." ) {
            $opts .= "<option value='$file'>$file</option>\n";
            $selectSize++;
        }
            // With full path.  But we don't need that.
            //$opts .= "<option value='$tpath.$file'>$file</option>\n";
    }
    closedir($dh);

    if ($selectSize > 5)
        $selectSize = 5;

?>

<div style="font-weight:bold;">Choose a source CSV file:</div>
<form action="loadWEFCTorontoProducts.php" method="post">
<select name="product_csv" size="<?php echo $selectSize; ?>">
<?php echo "$opts"; ?>
</select>
<div style="margin-top: 0.5em;"><a href="UploadAnyFile.php" target="_upload">File upload utility</a></div>
<!-- div style="margin-top: 0.5em;">
</div -->

<table border=0 width="350px" style="margin-top: 0.5em;">
<tr style="text-align:top;" vAlign="top">
    <td><input type="checkbox" name="skip_one" CHECKED /></td><td style="padding-top:5px;">First line contains column headings</td>
</tr>

<tr style="text-align:top;" vAlign="top">
    <td><input type="checkbox" name="clear_products" /></td><td style="padding-top:5px;">Clear 'products' and related tables before load.
    <br />It is very unlikely from 18Jan2013 onwards that you want to do this unless starting the products table over.</td>
</tr>

<tr style="text-align:top;" vAlign="top">
    <td><input type="checkbox" name="overwrite_products" CHECKED /></td><td style="padding-top:5px;">Overwrite existing 'products' and related records on UPC/PLU match.
    <br />Items with price empty or -1 will:
    <br />- if there is an existing record, update it rather than overwriting it, but change no prices
    <br />- if there is no existing record, be added</td>
</tr>

<!-- tr style="text-align:top;" vAlign="top">
    <td><input type="checkbox" name="round_up" /></td><td style="padding-top:5px;">Round prices up to .#5 and .#9 - Not ready yet.</td>
</tr -->

<tr style="text-align:top;" vAlign="top">
    <td><input type="checkbox" name="dry_run" /></td><td style="padding-top:5px;">Dry run - report problems but don't change the database.</td>
</tr>

<!-- tr style="text-align:top;" vAlign="top">
<td><input type="checkbox" name="delete_file" /></td><td style="padding-top:5px;">Delete the selected file without loading it. --></td>
</tr -->
<table>
<div style="margin-top: 0.5em;">
<input type="submit" id="loadProducts" name="loadProducts" value="Load Products" /> &nbsp;
<input type="submit" id="deleteFile" name="deleteFile" value="Delete File" />
</div>
</form>

<?php
    /* html footer */
    include($FANNIE_ROOT.'src/footer.html');

// Form to get the name of the file to process.
}

?>

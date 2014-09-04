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

/* --COMMENTS - -  - - - - - - - - - - - - - - - - - - - - - -

    15Feb13 EL Convert markup in source data to margin for deptMargin.margin
    14Jan13 EL All superdept cols empty; only one superdept in list
.             Jigger to use the "list"[10] as original source for SuperDept, as TopLevel.
.             Change superdept number ranges to accommodate.
.            Fix data:
.             Assign PROD_BUL_LEGACY and YCHEESES to RETAIL SuperDept.
.             Fix spelling of ZSEAFOOODS
.             Add COOPKITCHEN
COOPKITCHEN 2005    0                               COOPKITCHEN 2005 - COOPKITCHEN (COOPKITCHEN)
    21Nov12 EL Use the list-of-superdepts field to tag by superdept.
.             If the "YES" supderdept turns out to be a non-issue some deadwood
.              commented code can be deleted.
    10Oct12 EL News superdepts regime: Top Tax Buyer Category Bulk
     6Oct12 EL Tax_SD new, drop Subcategory_SD
     3Oct12 EL Trap superdepts dups. Enhancements to file-choice page.
    25Sep12 EL Rejig colums, bulk departemts, add Subcategory SD
     2Sep12 Eric Lee loadWEFCTorontoDepts.php , based on
                                        fannie/batches/UNFI/load-scripts/loadUNFIprices.php
    http://107.20.205.138/IS4C/fannie/item/departments/loadWEFCTorontoDepts.php
    
*/

/* #'F--FUNCTIONALITY - - - - - - - - - - - - - - - - - - - - - -
    -=proposed  o=in-progress  +=in-production  x=removed

    + To load departments and deptMargin from CSV, deleting current rows.
    + To load superdepts and superDeptNames from the same CSV, deleting current rows.
    + Initial front end to choose the source .csv
        The model for this program is called from the upload script and does not have
         a front end of its own until it is finished loading.
        In the model for this program csv-to-load is already known and must be unfi.csv

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

$tpath = sys_get_temp_dir()."/misc/";


/* Is this a request-to-upload or an initial display of the get-file-to-process form? */
if ( isset($_REQUEST['dept_csv']) && $_REQUEST['dept_csv'] != "" ) {

    $dept_csv = $_REQUEST['dept_csv'];

// 14Jan13 Layout.
//0A         |1B  |2C |3D   |4E      |5F    |6G    |7H      |8I         |9J     |10K             |11L
//Department |Dept|Tax|ORDER|CATEGORY|Parent|Tax SD|Buyer SD|Category SD|Bulk SD|Super Department|Summary
//MEMBERSHIPS|1000|0  |     |        |      |      |        |           |       |MEMBERSHIPS     |1000 - MEMBERSHIPS (MEMBERSHIPS)
//PRODUCE    |3000|0  |     |        |      |      |        |           |       |RETAIL          |3000 - PRODUCE (RETAIL)
//WPRODUCE   |3100|0  |     |        |      |      |        |           |       |RETAIL          |3100 - WPRODUCE (RETAIL)
    /* the column number in the array returned by fgetcsv where various information is stored
        "first" = 0.
    */
    $DEPT_NAME = 0;             // A 
    $DEPT_NO = 1;                   // B deptMargin.dept_ID
    $DEPT_TAX = 2;              // C 
    $BULK = 3;                      // D If not "" scale = 1, except there is no departments.scale
    $MARGIN = 4;                    // E deptMargin.margin
    $TOP_SD = 5;                    // F 
    //$ACCOUNTING_SD = 5;       // F - 6Oct12
    $TAX_SD = 6;                    // G  - 6Oct12 was TOPLEVEL_SD
    $BUYER_SD = 7;              // H 
    //$PURCHASING_SD = 7;       // H - 6Oct12
    $CATEGORY_SD = 8;           // I 
    $BULK_SD = 9;                   // J
    //$SUBCATEGORY_SD = 9;  // J  - 6Oct12 now empty; do not rebuild.
    $SUPERDEPTS = 10;                   // K

    // EL This lib may not be needed.
    require($FANNIE_ROOT.'batches/UNFI/lib.php');
    /* EL Not pertinent to departments or WEFC products
    $VENDOR_ID = getVendorID(basename($_SERVER['SCRIPT_FILENAME']));
    if ($VENDOR_ID === False){
        echo "Error: no vendor has this load script";
        exit;
    }
    */
    $PRICEFILE_USE_SPLITS = False;
    //$PRICEFILE_USE_SPLITS = True;

    // The tables affected by this script.
    $departmentTables = array("departments", "deptMargin", "superdepts", "superDeptNames");

    // Messages to display when the run finished.
    $messages = array();
    $mct = -1;
    $messages[++$mct] = "Issues and Information:";

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
            system("split -l 2500 {$tpath}{$dept_csv} {$tpath}DEPTSPLIT");
            $dir = opendir($tpath);
            while ($current = readdir($dir)){
                if (!strstr($current,"DEPTSPLIT"))
                    continue;
                $filestoprocess[$i++] = $current;
            }
            foreach ($departmentTables as $table) {
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
        else {
            $filestoprocess = unserialize(base64_decode($_GET["filestoprocess"]));  
        }
    }
    else {
        foreach ($departmentTables as $table) {
            $truncateQ = "truncate table $table";
            $truncateR = $dbc->query($truncateQ);
            if ( ! $truncateR ) {
                $messages[++$mct] = "** Error: Failed: $truncateQ";
            } else {
                $messages[++$mct] = "Table $table truncated (emptied)";
            }
        }
        $messages[++$mct] = "";

        $filestoprocess[] = "$dept_csv";
    }

    // Remove one split from the list and process that
    $current = array_pop($filestoprocess);

    $dept_fs = 0;
    $dept_limit = 999.00;
    $dept_minimum = 0.01;
    $dept_discount = 0;
    $modified = $dbc->now();
    $modifiedby = 1;

    $superDeptCounter = 0;
    /* Try to put superdepts.superID's in some meaningful sequence and ranges.
        new: TOP 1-9, TAX 10-19, BUYER 2xx, CATEGORIES 1xx, BULK 20-29
        obs: ACCTG 2xx, TOP 1-9, PURCH 3xx, CATEGORIES 1xx, SUBCATEGORIES 4xx
        obs: ACCTG 2xx, TAX 1-9, PURCH 3xx, CATEGORIES 1xx
    */
    // 14Jan13 Change for no-real-superdepts
    $superDeptCounters = array (0,0,0,0,0, 1, 30, 200, 100, 50);
    //$superDeptCounters = array (0,0,0,0,0, 1, 10, 200, 100, 20);

    // Lines read from CSV, including column headings.
    $lineCount = 0;
    // Department records written.
    $departmentCount = 0;
    // Records flagged as incomplete.
    //$incompletes = 0;

    $fp = fopen($tpath.$current,'r');
    while( !feof($fp) ) {

        $data = fgetcsv($fp, 0, "\t", '"');
        $lineCount++;
        //EL The data is tab-delimited, but no embedded commas.
        //      Why not just use explode()?
        // $line = preg_replace("\t",",",$line);
        /* csv parser takes a comma-, or other-, separated line and returns its elements
             as an array */
        if (!is_array($data)) continue;

        // Row cannot be valid without this.
        // Skipping the row of column labels is done later.
        // In early versions some don't have numbers yet, useless.
        if ( !isset($data[$DEPT_NO]) || $data[$DEPT_NO] == "" ) {
            $messages[++$mct] = "Skipping line $lineCount Name >{$data[$DEPT_NAME]}< because it has no number.";
            continue;
        }

        // Could also use VALUES ($dbc->escape($foo) ...)
        $dept_name = fix_text_for_db($data[$DEPT_NAME]);
        $dept_no = $data[$DEPT_NO];
        $dept_tax = $data[$DEPT_TAX];
        if ( $data[$MARGIN] == "" ) {
            $margin = 0;
            $messages[++$mct] = "Line $lineCount Name >{$data[$DEPT_NAME]}< has empty markup; set to 0.";
        }
        elseif ( $data[$MARGIN] == 0 ) {
            $messages[++$mct] = "Line $lineCount Name >{$data[$DEPT_NAME]}< has markup 0.";
        }
        elseif ( !is_numeric($data[$MARGIN]) ) {
            $messages[++$mct] = "Line $lineCount Name >{$data[$DEPT_NAME]}< has no-numeric markup >{$data[$MARGIN]}<; set to 0.";
            $margin = 0;
        }
        else {
            // Convert markup to margin
            $margin = sprintf("%.5f",(($data[$MARGIN]-1)/$data[$MARGIN]));
            //$messages[++$mct] = "Line $lineCount Name >{$data[$DEPT_NAME]}< converted markup >{$data[$MARGIN]}< to $margin .";
        }

        // Don't include duplicate departments by either number or name.
        $checkQ = "SELECT dept_no FROM departments WHERE dept_no='$dept_no'";
        $checkR = $dbc->query($checkQ);
        if ($dbc->num_rows($checkR) > 0) {
            $messages[++$mct] = "<br />Skipping dept_no duplicate: $dept_no $dept_name";
            continue;
        }
        $checkQ = "SELECT dept_name FROM departments WHERE dept_name='$dept_name'";
        $checkR = $dbc->query($checkQ);
        if ($dbc->num_rows($checkR) > 0) {
            $messages[++$mct] = "<br />Skipping dept_name duplicate: $dept_no $dept_name";
            continue;
        }

        /* Skip the item if tax isn't numeric
                this will catch the 'label' line in the first CSV split
                since the splits get returned in file system order,
                we can't be certain *when* that chunk will come up
        */
        if ( !is_numeric($dept_tax) ) {
            $colHeads = $data;
            continue;
        }

        // Add the record to departments
        $insQ = "INSERT INTO departments
        (dept_no, dept_name, dept_tax, dept_fs, dept_limit, dept_minimum, dept_discount, modified, modifiedby)
        VALUES
        ($dept_no, '$dept_name', $dept_tax, $dept_fs, $dept_limit, $dept_minimum, $dept_discount, $modified, $modifiedby)";
        $insR = $dbc->query($insQ);

        // Add the record to deptMargin
        $insQ = "INSERT INTO deptMargin
        (dept_ID, margin)
        VALUES
        ($dept_no, '$margin')";
        $insR = $dbc->query($insQ);

        $departmentCount++;

        /* Begin superdepartments
        */
        // 14Jan13 - No proper SuperDepts. List of SuperDepts has only one item.
        if ( ! $data[$TOP_SD] ) {
            $data[$TOP_SD] = $data[$SUPERDEPTS];
        }
        $data[$TOP_SD]          = fix_text_for_db($data[$TOP_SD]);
        $data[$TAX_SD]          = fix_text_for_db($data[$TAX_SD]);
        $data[$BUYER_SD]        = fix_text_for_db($data[$BUYER_SD]);
        $data[$CATEGORY_SD] = fix_text_for_db($data[$CATEGORY_SD]);
        $data[$BULK_SD]         = fix_text_for_db($data[$BULK_SD]);
        // 14Jan13 - No proper SuperDepts
        // $data[$SUPERDEPTS]   = fix_text_for_db($data[$SUPERDEPTS]);

        // foreach of the four superdepartment fields
        //  Build the table of superdepartment names and id#s.
        for ( $sdk=5; $sdk<=9; $sdk++) {
            //  Check by super_name whether superDeptNames record exists
            $selQ = "SELECT superId, super_name from superDeptNames where super_name = '{$data[$sdk]}'";
            $selR = $dbc->query($selQ);
            // if it doesn't, add it
            if ($dbc->num_rows($selR) == 0) {
                // increment counter, by group, for superID
                $superDeptCounter = $superDeptCounters[$sdk]++;
                // All one sequence.
                // $superDeptCounter++;
                // Add to superDeptNames
                $insQ = "INSERT INTO superDeptNames (superID, super_name ) values ($superDeptCounter, '{$data[$sdk]}')";
                $insR = $dbc->query($insQ);
                /* Add link via superdepts - Done later now.
                $insQ = "INSERT INTO superdepts (superID, dept_ID ) values ($superDeptCounter, $dept_no)";
                $insR = $dbc->query($insQ);
                */
            }
            /* if it does, add an item. - Done later now.
            else {
                $row = $dbc->fetch_row($selR);
                // Check for duplicate.
                $selQ1 = "SELECT superId, dept_ID from superdepts where superId = {$row[0]} and dept_ID = $dept_no";
                $selR1 = $dbc->query($selQ1);
                // if unique, add it
                if ($dbc->num_rows($selR1) == 0) {
                    // Add link via superdepts
                    $insQ = "INSERT INTO superdepts (superID, dept_ID ) values ({$row[0]}, $dept_no)";
                    $insR = $dbc->query($insQ);
                }
                else {
                    $messages[++$mct] = "<br />For Department $dept_no $dept_name column $colHeads[$sdk] >$data[$sdk]< skipping superdepts duplicate: {$row[0]} $dept_no";
                }
            }
            */
        // Each superdept field
        }

        // Foreach superdept named in the list, link the dept to it.
        foreach ( preg_split("/ +/", $data[$SUPERDEPTS]) as $sdName ) {
            $sdName = fix_text_for_db($sdName);
            // Check that it exists and get its ID#
            $selQ = "SELECT superId, super_name from superDeptNames where super_name = '{$sdName}'";
            $selR = $dbc->query($selQ);
            // if it doesn't, complain.
            if ($dbc->num_rows($selR) == 0) {
                $messages[++$mct] = "<br />For Department $dept_no $dept_name column listed superdept >{$sdName}< is not known";
            // if it does, add a link for it.
            }
            else {
                $row = $dbc->fetch_row($selR);
                // Check for duplicate.
                $selQ1 = "SELECT superId, dept_ID from superdepts where superId = {$row[0]} and dept_ID = $dept_no";
                $selR1 = $dbc->query($selQ1);
                // if unique, add it
                if ($dbc->num_rows($selR1) == 0) {
                    // Add link via superdepts
                    $insQ = "INSERT INTO superdepts (superID, dept_ID ) values ({$row[0]}, $dept_no)";
                    $insR = $dbc->query($insQ);
                }
                else {
                    $messages[++$mct] = "<br />For Department $dept_no $dept_name column listed superdept >{$sdName}< is a duplicate of: {$row[0]} $dept_no and is being skipped.";
                }
            }
        }

    // Each CSV line
    }

    fclose($fp);

    /* 
        if filestoprocess is empty, it means all the splits
        have been processed (the load is complete):
        + update tables vendorSKUtoPLU, vendorItems, prodExtra.
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

        echo "Finished processing Departments file<br />";
        if ($PRICEFILE_USE_SPLITS){
            echo "Files processed:<br />";
            foreach (unserialize(base64_decode($_GET["processed"])) as $p){
                echo $p."<br />";
                unlink($tpath.$p);
            }
            echo $current."<br />";
            unlink($tpath.$current);
        }
        else {
            echo "$dept_csv<br />";
        }

        echo "Read $lineCount CSV lines.  Wrote $departmentCount records to the departments table.";
        echo "<br />";
        //echo "$incompletes records flagged as known-to-be incomplete.";
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
        // unlink($tpath."$dept_csv");
        echo "<br />You might want to delete: {$tpath}{$dept_csv}<br />";
        
        echo "<div style='margin-top: 0.5em;'>";
        echo "<a href='loadWEFCTorontoDepts.php'>Home: Load WEFC-Toronto Departments</a>";
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
        header("Location: loadWEFCTorontoDepts.php?filestoprocess=$sendable_data&processed=$encoded2");

    }

// /We know the file to process.
}
else {

    /* Form to get the name of csv file to load and launch load. */
    /* html header, including navbar */
    $page_title = "Fannie - Load WEFC-Toronto Departments";
    $header = "Load WEFC-Toronto Departments";
    include($FANNIE_ROOT.'src/header.html');

    // Get a list of files
    $dh = opendir($tpath);
    $opts = "<option value='' SELECTED>Choose a source CSV file</option>\n";
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

<form action="loadWEFCTorontoDepts.php" method="post">
<select name="dept_csv" size="<?php echo $selectSize; ?>">
<?php echo "$opts"; ?>
</select>
<div style="margin-top: 0.5em;"><a href="../import/uploadAnyFile.php" target="_upload">File upload utility</a></div>
<div style="margin-top: 0.5em;"><input type="submit" value="Load Departments" /></div>
</form>

<?php
    /* html footer */
    include($FANNIE_ROOT.'src/footer.html');

// Form to get the name of the file to process.
}

?>

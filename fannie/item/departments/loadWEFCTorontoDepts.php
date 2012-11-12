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
include("../../config.php");
require($FANNIE_ROOT.'src/csv_parser.php');
require($FANNIE_ROOT.'src/mysql_connect.php');
require($FANNIE_ROOT.'src/tmp_dir.php');

$tpath = sys_get_temp_dir()."/misc/";


/* Is this a request-to-upload or an initial display of the get-file-to-process form? */
if ( isset($_REQUEST['dept_csv']) && $_REQUEST['dept_csv'] != "" ) {

	$dept_csv = $_REQUEST['dept_csv'];

	/* the column number in the array returned by csv_parser where various information is stored
		"first" = 0.
	*/
	$DEPT_NAME = 0;			// A 
	$DEPT_NO = 1;				// B deptMargin.dept_ID
	$DEPT_TAX = 2;			// C 
	$MARGIN = 3;				// D deptMargin.margin
	$ACCOUNTING_SD = 4;	// E 
	$TOPLEVEL_SD = 5;		// F 
	$PURCHASING_SD = 6;	// G 
	$CATEGORY_SD = 7;		// H 

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
			
			$truncateQ = "truncate table departments";
			$truncateR = $dbc->query($truncateQ);
			$truncateQ = "truncate table deptMargin";
			$truncateR = $dbc->query($truncateQ);
			/* Also superdepts, at some point.
			*/
			$truncateQ = "truncate table superdepts";
			$truncateR = $dbc->query($truncateQ);
			$truncateQ = "truncate table superDeptNames";
			$truncateR = $dbc->query($truncateQ);
			/* EL Not pertinent to departments
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
		$truncateQ = "truncate table departments";
		$truncateR = $dbc->query($truncateQ);
		$truncateQ = "truncate table deptMargin";
		$truncateR = $dbc->query($truncateQ);
		/* Also superdepts, at some point.  */
		$truncateQ = "truncate table superdepts";
		$truncateR = $dbc->query($truncateQ);
		$truncateQ = "truncate table superDeptNames";
		$truncateR = $dbc->query($truncateQ);
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
		ACCTG 40-69, TOP 1-9, PURCH 79-99, CATEGORIES 10-39
	*/
	$superDeptCounters = array (0,0,0,0, 200, 1, 300, 100);

	$fp = fopen($tpath.$current,'r');
	while( !feof($fp) ) {

		$line = fgets($fp);
		//EL The data is tab-delimited, but no embedded commas.
		//		Why not just use explode()?
		// $line = preg_replace("\t",",",$line);
		/* csv parser takes a comma-, or other-, separated line and returns its elements
			 as an array */
		$data = csv_parser($line, "", "\t");
		if (!is_array($data)) continue;

		// Row cannot be valid without this.
		// Skipping the row of column labels is done later.
		// In early versions some don't have numbers yet, useless.
		if ( !isset($data[$DEPT_NO]) || $data[$DEPT_NO] == "" ) {
			echo "Skipping {$data[$DEPT_NAME]} because it has no number.<br />\n";
			continue;
		}

		// Could also use VALUES ($dbc->escape($foo) ...)
		$dept_name = fix_text_for_db($data[$DEPT_NAME]);
		$dept_no = $data[$DEPT_NO];
		$dept_tax = $data[$DEPT_TAX];
		$margin = $data[$MARGIN];

		// Don't include duplicate departments by either number or name.
		$checkQ = "SELECT dept_no FROM departments WHERE dept_no='$dept_no'";
		$checkR = $dbc->query($checkQ);
		if ($dbc->num_rows($checkR) > 0) {
			echo "<br />Skipping dept_no duplicate: $dept_no $dept_name";
			continue;
		}
		$checkQ = "SELECT dept_name FROM departments WHERE dept_name='$dept_name'";
		$checkR = $dbc->query($checkQ);
		if ($dbc->num_rows($checkR) > 0) {
			echo "<br />Skipping dept_name duplicate: $dept_no $dept_name";
			continue;
		}

		/* Skip the item if tax isn't numeric
				this will catch the 'label' line in the first CSV split
				since the splits get returned in file system order,
				we can't be certain *when* that chunk will come up
		*/
		if ( !is_numeric($dept_tax) ) {
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

		/* Begin superdepartments
		*/
		$data[$ACCOUNTING_SD]	= fix_text_for_db($data[$ACCOUNTING_SD]);
		$data[$TOPLEVEL_SD]		= fix_text_for_db($data[$TOPLEVEL_SD]);
		$data[$PURCHASING_SD]	= fix_text_for_db($data[$PURCHASING_SD]);
		$data[$CATEGORY_SD]		= fix_text_for_db($data[$CATEGORY_SD]);

		// foreach of the four superdepartment fields
		for ( $sdk=4; $sdk<=7; $sdk++) {
			// 	Check by super_name whether superDeptNames record exists
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
				// Add link via superdepts
				$insQ = "INSERT INTO superdepts (superID, dept_ID ) values ($superDeptCounter, $dept_no)";
				$insR = $dbc->query($insQ);
			}
			// if it does, add an item.
			else {
				$row = $dbc->fetch_row($selR);
				// Add link via superdepts
				$insQ = "INSERT INTO superdepts (superID, dept_ID ) values ({$row[0]}, $dept_no)";
				$insR = $dbc->query($insQ);
			//
			}
		// Each superdept
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
	while (($file = readdir($dh)) !== false) {
		if ( substr($file, 0, 1) != "." )
			$opts .= "<option value='$file'>$file</option>\n";
			// With full path.  But we don't need that.
			//$opts .= "<option value='$tpath.$file'>$file</option>\n";
	}
	closedir($dh);

?>

<form action="loadWEFCTorontoDepts.php" method="post">
<select name="dept_csv" size="2">
<?php echo "$opts"; ?>
</select>
<div style="margin-top: 0.5em;"><input type="submit" value="Load Departments" /></div>
</form>

<?php
	/* html footer */
	include($FANNIE_ROOT.'src/footer.html');

// Form to get the name of the file to process.
}

?>

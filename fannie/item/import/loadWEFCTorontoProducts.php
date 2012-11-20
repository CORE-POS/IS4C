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

	18Sep12 EL Treat CaseCost = -1 as a flag for data known to be incomplete and
	            ignore the record silently.
	 6Sep12 Eric Lee loadWEFCTorontoProducts.php , based on
          	 loadWEFCTorontoDepts.php , based on
	 				 	fannie/batches/UNFI/load-scripts/loadUNFIprices.php
	
*/

/* #'F--FUNCTIONALITY - - - - - - - - - - - - - - - - - - - - - -
	-=proposed  o=in-progress  +=in-production  x=removed

	- Add records to products
	- Option to truncate table
		o Interface
		- Operation
	- Option to overwrite on UPC match
		+ Interface
		+ Operation
	- Lookup to departments on dept_name to get dept_no
	- Lookup to deptMargin on dept_ID to get margin
	? Populate vendorItems?  May be useful for labels.

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


/* Based on whether we have the name of a file to load,
		is this a request-to-upload or an initial display of the get-file-to-process form?
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
			
			if ( isset($_REQUEST['clear_products']) ) {
				$truncateQ = "truncate table products";
				$truncateR = $dbc->query($truncateQ);
				/* Also others, at some point.
				$truncateQ = "truncate table deptMargin";
				$truncateR = $dbc->query($truncateQ);
				$truncateQ = "truncate table superdepts";
				$truncateR = $dbc->query($truncateQ);
				$truncateQ = "truncate table superDeptNames";
				$truncateR = $dbc->query($truncateQ);
				*/
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
		if ( isset($_REQUEST['clear_products']) ) {
			echo "Would truncate products, but won't.";
			/* Also others, at some point.
			$truncateQ = "truncate table products";
			$truncateR = $dbc->query($truncateQ);
			$truncateQ = "truncate table deptMargin";
			$truncateR = $dbc->query($truncateQ);
			$truncateQ = "truncate table superdepts";
			$truncateR = $dbc->query($truncateQ);
			$truncateQ = "truncate table superDeptNames";
			$truncateR = $dbc->query($truncateQ);
			*/
		}
		$filestoprocess[] = "$product_csv";
	}

	// Remove one split from the list and process that
	$current = array_pop($filestoprocess);

	/* Indexes to the array returned by csv_parser, the column number in the .csv file.
		"first" = 0.
	*/
	// To think in spreadsheet terms.
	//$LINE[A] = "";
	$VENDOR_ID = 0;					// A STD
	$COL_A = 0;							// A STD
	$BRAND_NAME = 1;				// B Brand deptMargin.dept_ID
	$DESCRIPTION = 2;				// C Item
	$VENDOR_NAME = 3;				// D Distributor
	$SKU = 4;								// E SKU
	$DEPT_NAME = 5;					// F Department
	$UPC = 6;								// G Supplier UPC
	//$IGNORE2 = 7;						// H CHK (Calculated UPC checkdigit)
	$COL_H = 7;							// H CHK
	$CASE_COST = 8;					// I Case Cost 9999.99
	$CASE_SIZE = 9;					// J Case Size 99
	$UNIT_SIZE = 10;				// K Unit Size 99
	$UNIT_NAME = 11;				// L Units "g"/"ml"/"bags"
	$UNIT_COST = 12;				// M Cost per Unit 999.99
	$OVERRIDE_PRICE = 13;		// N Override Price
	$UNIT_PRICE = 14;				// O Price Per Unit
	$TAX_TYPE = 15;					// P Tax 0/1/2
	$PLU = 16;							// Q PLU 999 (?, very few examples). Put in UPC.
	$WEFC_UPC = 17;					// R WEFC UPC - No examples
	$PRODUCER_MEMBER = 18;	// S Producer Member
	$ONTARIO = 19;					// T YES/NO/"" (very few examples)
	$CANADA = 20;						// U YES/NO/"" (very few examples)
	$MARKUP = 21;						// V Markup "150%"

	// Defaults for:
	//  products table
	$dept_fs = 0;
	$dept_limit = 999.00;
	$dept_minimum = 0.01;
	$dept_discount = 0;
	// $modified = "now()"; // Use literal $dbc->(now())
	$modifiedby = 1;

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
	$skip_one = isset($_REQUEST['skip'])?True:False;

	// Messages to display when the run finished.
	$messages = array();
	$mct = -1;
	$messages[++$mct] = "Issues:";

	// Lines read from CSV, including column headings.
	$lineCount = 0;
	// Product records written.
	$productCount = 0;
	// Records flagged as incomplete.
	$incompletes = 0;

	$fp = fopen($tpath.$current,'r');
	while( !feof($fp) ) {

		$line = fgets($fp);
		$line = preg_replace("/[\r\n|\n]$/", "", $line);
		if ( preg_match("/^\t*$/", $line) ) {
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
		//		But fields with embedded commas are quoted.
		//		Why not just use explode()?
		/* csv parser takes a comma-, or other-, separated line and returns its elements
			 as an array */
		$data = csv_parser($line, '"', "\t");
		if ( !is_array($data) ) {
			echo "Line $lineCount is not a valid CSV line.";
			$messages[++$mct] = "Line $lineCount is not a valid CSV line.";
			continue;
		}

		/* Skip the item if tax isn't numeric
				this will catch the 'label' line in the first CSV split
				since the splits get returned in file system order,
				we can't be certain *when* that chunk will come up
		*/
		if ( !is_numeric($data[$TAX_TYPE]) ) {
			$messages[++$mct] = "<br />Line $lineCount skipping invalid tax-type: >{$data[$TAX_TYPE]}<";
			//echo "$messages[$mct]";
			continue;
		}

		// Treat CASE_COST == -1 as flag for data known to be incomplete and ignore silently.
		if ( $data[$CASE_COST] == -1 ) {
			//$messages[++$mct] = "<br />Line $lineCount skipping flagged-as-incomplete item >{$data[$CASE_COST]}<";
			$incompletes++;
			continue;
		}

		/* Compose values for the db in vars with same names as table rows.
				Assume table products unless qualified.

	$VENDOR_ID = 0;					// B Brand deptMargin.dept_ID
	$BRAND_NAME = 1;				// B Brand deptMargin.dept_ID
	$DESCRIPTION = 2;				// C Item
	$VENDOR_NAME = 3;				// D Distributor
	$SKU = 4;								// E SKU
	$DEPT_NAME = 5;					// F Department
	$UPC = 6;								// G Supplier UPC
	$COL_H = 7;							// H CHK
	$CASE_COST = 8;					// I Case Cost 9999.99
	$CASE_SIZE = 9;					// J Case Size 99
	$UNIT_SIZE = 10;				// K Unit Size 99
	$UNIT_NAME = 11;				// L Units "g"/"ml"/"bags"
	$UNIT_COST = 12;				// M Cost per Unit 999.99
	$OVERRIDE_PRICE = 13;		// N Override Price
	$UNIT_PRICE = 14;				// O Price Per Unit
	$TAX_TYPE = 15;					// P Tax 0/1/2
	$PLU = 16;							// Q PLU 999 (?, very few examples)
	$WEFC_UPC = 17;					// R WEFC UPC - No examples
	$PRODUCER_MEMBER = 18;	// S Producer Member
	$ONTARIO = 19;					// T YES/NO/"" (very few examples)
	$CANADA = 20;						// U YES/NO/"" (very few examples)
	$MARKUP = 21;						// V Markup "150%"

		*/

		// Row cannot be valid without this.
		// Skipping the row of column labels is done later.
		// In early versions some don't have numbers yet, useless.
		if ( $data[$UPC] == "" ) {
			if ( $data[$PLU] == "" ) {
				$messages[++$mct] = "<br />Skipping line $lineCount {$data[$DESCRIPTION]} because it has no UPC or PLU.";
				//echo "$messages[$mct]";
				continue;
			} else {
				$data[$UPC] = $data[$PLU];
			}
		}

		$upc = $data[$UPC];

		/*
		$new_upc = $upc;
		$new_upc = preg_replace("/-\d$/", "",$new_upc);
		$new_upc = str_replace(" ","",$new_upc);
		$new_upc = str_replace("-","",$new_upc);
		$new_upc = str_pad($new_upc,13,'0',STR_PAD_LEFT);
		*/

		// Remove checkdigit if present.
		$upc = preg_replace("/-\d$/", "",$upc);
		$upc = str_replace(" ","",$upc);
		$upc = str_replace("-","",$upc);
		$upc = str_pad($upc,13,'0',STR_PAD_LEFT);

		/*
		$old_upc = $upc;
		$old_upc = str_replace(" ","",$old_upc);
		$old_upc = str_replace("-","",$old_upc);
		$old_upc = str_pad($old_upc,13,'0',STR_PAD_LEFT);
		$upc = $new_upc;
		echo "<br />upc: $upc old: $old_upc";
		//continue;
		*/

		// Don't include duplicate UPC.
		if ( !isset($_REQUEST['overwrite_products']) ) {
			$checkQ = "SELECT upc FROM products WHERE upc = " . $dbc->escape($upc);
			$checkR = $dbc->query($checkQ);
			if ($dbc->num_rows($checkR) > 0) {
				$messages[++$mct] = "<br />Skipping line $lineCount upc duplicate: $upc";
				continue;
			}
		}

		// Compose description: "Juice - Orange 946ml"
		$package = "";
		// For productUser.sizing
		$sizing = "";
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
		if ( $data[$MARKUP] != "" ) {
			// Format is "150%". Want 1.50
			$margin = sprintf("%.2f", (str_replace('%', "", $data[$MARKUP]) / 100.00));
			if ( $margin == "0.00" ) {
				$margin = "1.00";
				$messages[++$mct] = "Line $lineCount changed markup 0.00 to 1.00";
			}
		}
		else {
			$margin = $departmentMargin;
		}
		//  6Sep2012 These never come from the spreadsheet.
		$fs = $departmentFS;
		$discount = $departmentDiscount;

		// Margin sanity check.
		if ( !preg_match("/^\d\.\d+$/", $margin) || ($margin * 1.00) < 1.00 || ($margin * 1.00) > 3.00 ) {
			$messages[++$mct] = "Line $lineCount $description has invalid markup >{$margin}";
		}
		// Convert to float.
		// $margin = $margin * 1.00;

		/* Also calculate or compose:
			price: (cost/units)*margin
			cost: (cost/units)
			size: should it default to 1?
		*/
		$cost = "";
		$normal_price = "";
		$size = "";
		$unitofmeasure = "";
		if ( is_numeric($data[$OVERRIDE_PRICE]) ) {
			//if ( substr($data[$OVERRIDE_PRICE], -3, 1) != "." ) {}
			if ( preg_match("/\./", $data[$OVERRIDE_PRICE]) ) {
				$normal_price = sprintf("%.2f", $data[$OVERRIDE_PRICE]);
				//$normal_price = $data[$OVERRIDE_PRICE] . ".00";
			} else {
				$normal_price = $data[$OVERRIDE_PRICE];
			}
			$cost = $normal_price;
			if ( preg_match("/^\d+$/",$data[$CASE_SIZE]) ) {
				$size = $data[$CASE_SIZE];
			} else {
				$size = "";
			}
		} elseif ( preg_match("/^\d+\.\d\d$/",$data[$CASE_COST]) && preg_match("/^\d+$/",$data[$CASE_SIZE]) && preg_match("/^\d\.\d+$/",$margin) ) {
			$normal_price = sprintf("%.2f", ((($data[$CASE_COST] * 100) / ($data[$CASE_SIZE] * 100)) * $margin));
			$cost = sprintf("%.2f", (($data[$CASE_COST] * 100) / ($data[$CASE_SIZE] * 100)));
			$size = $data[$CASE_SIZE];
			// echo "<br />$lineCount upc >{$upc}<  dept_no {$department} UNIT_PRICE: {$data[$UNIT_PRICE]}  *normal_price >{$normal_price}<";
		} elseif ( preg_match("/$\d+\.\d\d$/",$data[$UNIT_PRICE]) && preg_match("/$\d+\.\d\d$/",$data[$UNIT_COST]) ) {	
			$normal_price = $data[$UNIT_PRICE];
			$cost = $data[$UNIT_COST];
			if ( preg_match("/^\d+$/",$data[$CASE_SIZE]) ) {
				$size = $data[$CASE_SIZE];
			} else {
				$size = "";
			}
			// echo "<br />$lineCount upc >{$upc}<  dept_no {$department} *UNIT_PRICE: {$data[$UNIT_PRICE]}  normal_price >{$normal_price}<";
		} else {
			$messages[++$mct] = sprintf("Line $lineCount $description Cannot derive a per-unit price from CASE_COST >%s<  CASE_SIZE >%s<  margin >%s<.", $data[$CASE_COST], $data[$CASE_SIZE], $margin);
			continue;
		}

		// May need some massaging/regularization: mL -> ml, gm -> g, ...
		$unitofmeasure = $data[$UNIT_NAME];

		$local = 0; $canada = 0; $ontario = 0;
		if ( preg_match("/^(1|YES)$/i", $data[$CANADA]) ) {
			//$local = 1;
			$canada = 1;
		} elseif ( preg_match("/^(0|NO)$/i", $data[$CANADA]) ) {
			//$local = 0;
			$canada = 0;
		} else {
			//$local = -1;	# Really want NULL
			$canada = -1;	# Really want NULL
		}
		if ( preg_match("/^(1|YES)$/i", $data[$ONTARIO]) ) {
			//$local = 1;
			$ontario = 1;
		} elseif ( preg_match("/^(0|NO)$/i", $data[$ONTARIO]) ) {
			//$local = 0;
			$ontario = 0;
		} else {
			//$local = -1;	# Really want NULL
			$ontario = -1;	# Really want NULL
		}
		if ( $ontario == 1 || $canada == 1 ) {
			$local = 1;
		} elseif ( $ontario == 0 && $canada == 0 ) {
			$local = 0;
		} else {
			$local = -1;	# Really want NULL
		}
		// Could do similar for "KM100" - 100-km diet
		 //echo "<br />>{$data[$CANADA]}<  canada: $canada";
		 //echo "<br />>{$data[$ONTARIO]}<  ontario: $ontario";


		if ( isset($_REQUEST['dry_run']) ) {
			continue;
		}

		/* --products - - - - */
		if ( isset($_REQUEST['overwrite_products']) ) {
			$dbc->query("DELETE FROM products WHERE upc=".$dbc->escape($upc));
		}

		/*
1		$insQ = sprintf("INSERT INTO products (upc, description, normal_price, 
2			pricemethod, groupprice, quantity, special_price, specialpricemethod, 
3			specialgroupprice, specialquantity, start_date, end_date, department, 
4			size, tax, foodstamp, scale, scaleprice, mixmatchcode, modified, advertised, 
5			tareweight, discount, discounttype, unitofmeasure, wicable, qttyEnforced, 
6			idEnforced, cost, inUse, numflag, subdept, deposit, local)
1			VALUES (%s, %s, %.2f,
2			0, .0, 0, .0, 0,
3			.0, 0, '1900-01-01', '1900-01-01', %d,
4			%d, %d, %d, 0, .0, '', %s, 0,
5			.0, %d, 0, %s, 0, 0,
6			0, %.2f, 1, 0, 0, .0, %d)",
1			$dbc->escape($upc), $dbc->escape($desc), $normal_price,
2
3			$department,
4			$size, $tax, $fs, $dbc->now(),
5			$discount, $unitofmeasure,
6			$cost, $local
);
		*/

		$insQ = sprintf("INSERT INTO products (upc, description, normal_price, 
			pricemethod, groupprice, quantity, special_price, specialpricemethod, 
			specialgroupprice, specialquantity, start_date, end_date, department, 
			size, tax, foodstamp, scale, scaleprice, mixmatchcode, modified, advertised, 
			tareweight, discount, discounttype, unitofmeasure, wicable, qttyEnforced, 
			idEnforced, cost, inUse, numflag, subdept, deposit, local)
			VALUES (%s, %s, %.2f,
			0, .0, 0, .0, 0,
			.0, 0, '1900-01-01', '1900-01-01', %d,
			%d, %d, %d, 0, .0, '', %s, 0,
			.0, %d, 0, %s, 0, 0,
			0, %.2f, 1, 0, 0, .0, %d)",
			$dbc->escape($upc), $dbc->escape($description), $normal_price,
			$department,
			$size, $tax, $fs, $dbc->now(),
			$discount, $dbc->escape($unitofmeasure),
			$cost, $local);

		$dbc->query($insQ);

		/* --productUser - - - - */
		if ( isset($_REQUEST['overwrite_products']) ) {
			$dbc->query("DELETE FROM productUser WHERE upc=".$dbc->escape($upc));
		}

		$brand = $data[$BRAND_NAME];

		$insQ = sprintf("INSERT INTO productUser (upc, description, brand, 
			sizing, photo, long_text, enableOnline)
			VALUES (%s, '', %s,
			%s, '', '', 0)",
			$dbc->escape($upc), $dbc->escape($brand),
			$dbc->escape($sizing));

		$dbc->query($insQ);

		/* --products_WEFC_Toronto - - - - */
		if ( isset($_REQUEST['overwrite_products']) ) {
			$dbc->query("DELETE FROM products_WEFC_Toronto WHERE upc=".$dbc->escape($upc));
		}

		$km100 = "";
		$insQ = sprintf("INSERT INTO products_WEFC_Toronto (upc, km100, ontario, canada)
			VALUES (%s, NULL, %d, %d)",
			$dbc->escape($upc), $ontario, $canada);

		$dbc->query($insQ);

		/* --vendorItems - - - - */
		if ( isset($_REQUEST['overwrite_products']) ) {
			$dbc->query("DELETE FROM vendorItems WHERE upc=".$dbc->escape($upc));
		}

		//Done earlier
		//$brand = $data[$BRAND_NAME];
		$sku = $data[$SKU];
		// Can be longer than the earlier one.
		$description = substr($data[$DESCRIPTION], 0, 50);
		// Use productUser.sizing for size: "300 ml"
		// Use products.size for units: 12
		$vendorID = $data[$VENDOR_ID];

		$insQ = sprintf("INSERT INTO vendorItems (upc, sku, brand,
			description, size, units, cost, vendorDept, vendorID)
			VALUES (%s, %s, %s,
			%s, %s, %d, %.2f, NULL, %d)",
			$dbc->escape($upc), $dbc->escape($sku), $dbc->escape($brand),
			$dbc->escape($description), $dbc->escape($sizing), $size, $cost, $vendorID);

		$dbc->query($insQ);

		$productCount++;

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
			//unlink($tpath.$current);
		}
		else {
			echo "$product_csv<br />";
		}
		echo "<br />";
		if ( isset($_REQUEST['dry_run']) )
			echo "Dry run. ";
		echo "Read $lineCount CSV lines.  Wrote $productCount records to the database.";
		echo "<br />";
		echo "$incompletes records flagged as known-to-be incomplete.";
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

// /We know the file to process.
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
<div style="margin-top: 0.5em;"><a href="uploadAnyFile.php" target="_upload">File upload utility</a></div>
<div style="margin-top: 0.5em;">
<input type="checkbox" name="skip_one" /> First line contains column headings
<!-- <br /><input type="checkbox" name="clear_products" /> Clear 'products' and related tables before load -->
<br /><input type="checkbox" name="overwrite_products" /> Overwrite existing 'products' and related records on UPC match
<br /><input type="checkbox" name="round_up" /> Round prices up to .#5 and .#9 - Not ready yet.
<br /><input type="checkbox" name="dry_run" /> Dry run - report problems but don't change the database.
</div>
<div style="margin-top: 0.5em;"><input type="submit" value="Load Products" /></div>
</form>

<?php
	/* html footer */
	include($FANNIE_ROOT.'src/footer.html');

// Form to get the name of the file to process.
}

?>

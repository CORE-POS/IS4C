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
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
require('laneUpdates.php');
$page_title = 'Fannie - Product List';
$header = 'Product List';

/*
 * standard form for when I
 * do ajax stuff
 */
if (isset($_GET['action'])){
	$ret = $_GET['action']."`";
	
	switch($_GET['action']){
	// simple - read in arguments, update the products table
	case 'update':
		$upc = $_GET['upc'];
		$desc = $_GET['desc'];
		$dept = $_GET['dept'];
		$price = rtrim($_GET['price'],' ');
		$tax = $_GET['tax'];
		$supplier = $_GET['supplier'];

		$fs = $_GET['fs'];
		if ($fs == 'true')
			$fs = 1;
		else
			$fs = 0;
		$disc = $_GET['disc'];
		if ($disc == 'true')
			$disc = 1;
		else
			$disc = 0;
		$wgt = $_GET['wgt'];
		if ($wgt == 'true')
			$wgt = 1;
		else
			$wgt = 0;

		$loc = $_GET['local'];
		if ($loc == 'true')
			$loc = 1;
		else
			$loc = 0;

		$upQ = "update products set
				description='$desc',
				department=$dept,		
				normal_price=$price,
				tax=$tax,		
				foodstamp=$fs,		
				scale=$wgt,		
				discount=$disc,
				local=$loc,
				modified=".$dbc->now()."
				where upc='$upc'";
		//$ret .= $upQ;
		$dbc->query($upQ);
		
		if ($dbc->table_exists("prodUpdate")){
			$q = "INSERT INTO prodUpdate
				SELECT upc,description,normal_price,
				department,tax,foodstamp,scale,0,
				modified,-1,qttyEnforced,discount,
				inUse FROM products WHERE upc='$upc'";
			$dbc->query($q);
		}

		$up2Q = sprintf("UPDATE prodExtra SET distributor=%s WHERE upc='%s'",
				$dbc->escape($supplier),$upc);	
		$dbc->query($up2Q);

		updateProductAllLanes($upc);
		break;
	case 'deleteCheck':
		$upc = $_GET['upc'];
		$encoded_desc = $_GET['desc'];
		$desc = base64_decode($encoded_desc);
		
		$fetchQ = "select normal_price,special_price,
			t.description,
			case when foodstamp = 1 then 'Yes' else 'No' end as fs,
			case when scale = 1 then 'Yes' else 'No' end as s
			from products as p left join taxrates as t
			on p.tax = t.id
			where upc='$upc' and p.description='$desc'";
		$fetchR = $dbc->query($fetchQ);
		$fetchW = $dbc->fetch_array($fetchR);

		$ret .= "Delete item $upc - $desc?\n";
		$ret .= "Normal price: ".rtrim($fetchW[0])."\n";
		$ret .= "Sale price: ".rtrim($fetchW[1])."\n";
		$ret .= "Tax: ".rtrim($fetchW[2])."\n";
		$ret .= "Foodstamp: ".rtrim($fetchW[3])."\n";
		$ret .= "Scale: ".rtrim($fetchW[4])."\n";
		$ret .= "`".$upc."`".$encoded_desc;
		break;
	case 'doDelete':
		$upc = $_GET['upc'];
		$desc = base64_decode($_GET['desc']);
		
		$delQ = "delete from products where upc='$upc' and description='$desc'";
		//$ret .= $delQ;
		$delR = $dbc->query($delQ);

		$delXQ = "delete from prodExtra where upc='$upc'";
		$delXR = $dbc->query($delXQ);

		deleteProductAllLanes($upc);
		break;
	}
	
	echo $ret;
	return;
}

// get a | delimited list of department names
// and numbers for building select boxes in javascript later
$deptQ = "select dept_no,dept_name from departments WHERE dept_no NOT IN (60) order by dept_no";
$deptR = $dbc->query($deptQ);
$depts = "";
$dept_nos = "";
while ($deptW = $dbc->fetch_array($deptR)){
	$depts .= $deptW[1]."|";
	$dept_nos .= $deptW[0]."|";
}
$depts = substr($depts,0,strlen($depts)-1);
$dept_nos = substr($dept_nos,0,strlen($dept_nos)-1);

if (!isset($_GET['excel'])){
//include($FANNIE_ROOT.'src/header.html');
?>
<script type="text/javascript">
/* ajax request */
function createRequestObject() {
    var ro;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
        ro = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
        ro = new XMLHttpRequest();
    }
    return ro;
}

/* global request object */
var http = createRequestObject();
var busy = false;

/* send action to this page 
   tack on more arguments as needed with '&' and '='
*/
function phpSend(action) {
	/*
		concurrent requests to an xmlhttp request object
		don't stack well. if a second one starts before
		the first finishes, the currently running request
		just crashes. in some cases this doesn't matter, but
		here it breaks the page. so the global variable 'busy'
		gets used to do some basic pseduo-spin locking
	*/
	if (busy){
		setTimeout('phpSend(\''+action+'\')',20)
		return 0;
	}
    http.open('get', 'productListCallback.php?action='+action);
    http.onreadystatechange = handleResponse;
    busy = true;
    http.send(null);
    return 1;
}

/* ajax callback function 
   by convention, results return [actionname]`[data]
   splitting on backtick separates, then switch on action name
   allows different actions to be handled differently
*/
function handleResponse() {
    if(http.readyState == 4){
    		busy = false;
        var response = http.responseText;
        var array = response.split('`');
        switch(array[0]){
        case 'update':
        		break;
	case 'deleteCheck':
		if (confirm(array[1]))
			phpSend('doDelete&upc='+array[2]+'&desc='+array[3]);
		break;
	case 'doDelete':
		window.location.reload(true);
		break;
        }
	}
}

/*
	get the department names and numbers from PHP,
	then split them into javascript arrays
*/
var dept_nos = '<?php echo $dept_nos; ?>';
var depts = '<?php echo $depts; ?>';
var select_list = depts.split('|');
var select_values = dept_nos.split('|');

/*
	change the specified row to inputs
	by convention, all table cells in a row are given 
	ids prefixed with the upc
	similarly, all inputs are given ids prefixed
	'f'+upc (f for form I guess)
*/
function edit(upc){
	var desc = document.getElementById(upc+'desc').innerHTML;
	var content = "<input type=text id=\"f"+upc+"desc\" value=\""+desc+"\" />";
	document.getElementById(upc+'desc').innerHTML = content;
	
	var dept = document.getElementById(upc+'dept').innerHTML;
	var select = "<select id=\"f"+upc+"dept\">";
	for (var i = 0; i < select_list.length; i++){
		select += "<option value=\""+select_values[i]+"|"+select_list[i]+"\"";
		if (select_list[i] == dept)
			select += " selected";
		select += ">"+select_values[i]+" "+select_list[i]+"</option>";
	}
	select += "</select>";
	document.getElementById(upc+'dept').innerHTML = select;
	
	var supplier = document.getElementById(upc+'supplier').innerHTML;
	document.getElementById(upc+'supplier').innerHTML = "<input type=text id=\"f"+upc+"supplier\" size=4 value=\""+supplier+"\" />";

	var price = document.getElementById(upc+'price').innerHTML;
	document.getElementById(upc+'price').innerHTML = "<input type=text id=\"f"+upc+"price\" size=4 value=\""+price+"\" />";
	
	var tax = document.getElementById(upc+'tax').innerHTML;
	var sel_str = "<select id=\"f"+upc+"tax\">";
	var taxrates = document.getElementById('taxrates').value.split(':');
	var taxnames = document.getElementById('taxnames').value.split(':');
	if (tax == 'X') sel_str += "<option value=1 selected>Regular</option>";
	else sel_str += "<option value=1>Regular</option>";
	for (var i=2; i<taxrates.length; i++){
		if (tax == taxnames[i].charAt(0)) sel_str += "<option value="+taxrates[i]+" selected>"+taxnames[i]+"</option>";
		else sel_str += "<option value=2>Deli</option>";
	}
	if (tax == '-') sel_str += "<option value=0 selected>No Tax</option>";
	else sel_str += "<option value=0>No Tax</option>";
	document.getElementById(upc+'tax').innerHTML = sel_str;
		
	var fs = document.getElementById(upc+'fs').innerHTML;
	if (fs == 'X')
		document.getElementById(upc+'fs').innerHTML = "<input type=checkbox id=\"f"+upc+"fs\" checked />";
	else
		document.getElementById(upc+'fs').innerHTML = "<input type=checkbox id=\"f"+upc+"fs\" />";
		
	var disc = document.getElementById(upc+'disc').innerHTML;
	if (disc == 'X')
		document.getElementById(upc+'disc').innerHTML = "<input type=checkbox id=\"f"+upc+"disc\" checked />";
	else
		document.getElementById(upc+'disc').innerHTML = "<input type=checkbox id=\"f"+upc+"disc\" />";
		
	var wgt = document.getElementById(upc+'wgt').innerHTML;
	if (wgt == 'X')
		document.getElementById(upc+'wgt').innerHTML = "<input type=checkbox id=\"f"+upc+"wgt\" checked />";
	else
		document.getElementById(upc+'wgt').innerHTML = "<input type=checkbox id=\"f"+upc+"wgt\" />";

	var loc = document.getElementById(upc+'local').innerHTML;
	if (loc == 'X')
		document.getElementById(upc+'local').innerHTML = "<input type=checkbox id=\"f"+upc+"local\" checked />";
	else
		document.getElementById(upc+'local').innerHTML = "<input type=checkbox id=\"f"+upc+"local\" />";
		
	var lnk = "<img src=\"<?php echo $FANNIE_URL;?>src/img/buttons/b_save.png\" alt=\"Save\" border=0 />";
	document.getElementById(upc+'cmd').innerHTML = "<a href=\"\" onclick=\"save('"+upc+"'); return false;\">"+lnk+"</a>";
}

/*
	grab the input values and restore them into the
	row's table cells
	send the input values to php in the background for
	a DB update
*/
function save(upc){
	var desc = document.getElementById('f'+upc+'desc').value;
	var dept = document.getElementById('f'+upc+'dept').value.split('|');
	var supplier = document.getElementById('f'+upc+'supplier').value;
	var price = document.getElementById('f'+upc+'price').value;
	var tax = document.getElementById('f'+upc+'tax').value;
	var fs = document.getElementById('f'+upc+'fs').checked;
	var disc = document.getElementById('f'+upc+'disc').checked;
	var wgt = document.getElementById('f'+upc+'wgt').checked;
	var loc = document.getElementById('f'+upc+'local').checked;
	
	document.getElementById(upc+'desc').innerHTML = desc;
	document.getElementById(upc+'dept').innerHTML = dept[1];
	document.getElementById(upc+'supplier').innerHTML = supplier;
	document.getElementById(upc+'price').innerHTML = price;
	
	if (tax == "0")
		document.getElementById(upc+'tax').innerHTML = '-';
	else if (tax == "1")
		document.getElementById(upc+'tax').innerHTML = 'X';
	else {
		var taxnames = document.getElementById('taxnames').value.split(':');
		document.getElementById(upc+'tax').innerHTML = taxnames[tax].charAt(0);
	}
		
	if (fs)
		document.getElementById(upc+'fs').innerHTML = 'X';
	else
		document.getElementById(upc+'fs').innerHTML = '-';
		
	if (disc)
		document.getElementById(upc+'disc').innerHTML = 'X';
	else
		document.getElementById(upc+'disc').innerHTML = '-';
		
	if (wgt)
		document.getElementById(upc+'wgt').innerHTML = 'X';
	else
		document.getElementById(upc+'wgt').innerHTML = '-';

	if (loc)
		document.getElementById(upc+'local').innerHTML = 'X';
	else
		document.getElementById(upc+'local').innerHTML = '-';
	
	var lnk = "<img src=\"<?php echo $FANNIE_URL;?>src/img/buttons/b_edit.png\" alt=\"Edit\" border=0 />";
	var cmd = "<a href=\"\" onclick=\"edit('"+upc+"'); return false;\">"+lnk+"</a>";
	document.getElementById(upc+'cmd').innerHTML = cmd;
	
	phpSend('update&upc='+upc+'&desc='+desc+'&dept='+dept[0]+'&price='+price+'&tax='+tax+'&fs='+fs+'&disc='+disc+'&wgt='+wgt+'&supplier='+supplier+'&local='+loc);
}

function deleteCheck(upc,description){
	phpSend('deleteCheck&upc='+upc+'&desc='+description);
}
</script>

<?php
} // end if block declaring javascript only on non-excel runs

	if (!isset($_GET['excel'])){
		$taxQ = "select id,description from taxrates";
		$taxR = $dbc->query($taxQ);
		$rates = "0";
		$names = "No Tax";
		while($taxW = $dbc->fetch_row($taxR)){
			$rates .= ":".$taxW[0];
			$names .= ":".$taxW[1];
		}
		echo "<input type=hidden id=taxrates value=\"$rates\" />";
		echo "<input type=hidden id=taxnames value=\"$names\" />";
	}
	
	/* set the allowed order by columns */
	$default_sort = 'dept_name';

	$supertype = 'dept';
	if (isset($_GET["supertype"]))
		$supertype = $_GET["supertype"];
	
	/*if order is not set, or it is not in the allowed list,
	* then set it to a default value. Otherwise,
	* set it to what was passed in.*/
	$deptStart = $_GET['deptStart'];
        $deptEnd = $_GET['deptEnd'];	

	$manufacturer = $_GET["manufacturer"];
	$mtype = $_GET["mtype"];
	$whereclause = "x.manufacturer like '%$manufacturer%'";
	if ($mtype == "prefix")
		$whereclause = "i.upc like '%$manufacturer%'";
		
	$order= $default_sort;
	if(isset($_GET['sort'])){ 
		$order= $_GET['sort'];
		$deptStart = $_GET['deptStart'];
		$deptEnd = $_GET['deptEnd'];
	}
	//$order = "upc";
	$dir = "asc";
	$otherdir = "desc";
	//if (isset($_GET['sort']))
	// $order = $_GET['sort'];
	if (isset($_GET['dir']) && $_GET['dir'] != "asc"){
		$dir = "desc";
		$otherdir = "asc";
	}
	$deptSub = "";
	if (isset($_GET['deptSub']))
		$deptSub = $_GET['deptSub'];
	if ($deptSub == 0)
		unset($_GET['deptSub']);
	
	//echo $order;

	//echo $_GET['deptStart'];
		
	//printf($date1); //listed here for debugging purposes
	//printf($deptEnd); // same as above
	
	//Following lines creates a header for the report, listing sort option chosen, report date, date and department range.

	if (isset($_GET['excel'])){
	   header('Content-Type: application/ms-excel');
	   header('Content-Disposition: attachment; filename="itemList.xls"');
	}

	ob_start();

	echo "Report sorted by ";
	print $order; 
	echo "    ";
	echo "<br>";
	echo "    Department range: ";
	if ($supertype == 'dept' && !isset($_GET['deptSub'])){
		print $deptStart;
		echo " to ";	
		print $deptEnd;
		echo "<br>";
	}
	else if ($supertype == 'dept'){
		echo "Sub department $deptSub<br />";
	}
	else {
		echo "Manufacturer $manufacturer<br />";
	}
	
	$today = date("F j, Y, g:i a"); 

	echo $today."<br />";

	if (!isset($_GET['excel']) and !isset($_GET['deptSub']) and $supertype=='dept')
		echo "<a href=productListCallback.php?deptStart=$deptStart&deptEnd=$deptEnd&sort=$order&dir=$dir&excel=yes&supertype=dept>Save to Excel</a><br />";
	else if (!isset($_GET['excel']) and isset($_GET['deptSub']) and $supertype=='dept')
		echo "<a href=productListCallback.php?deptSub=$deptSub&sort=$order&dir=$dir&excel=yes&supertype=dept>Save to Excel</a><br />";
	else if (!isset($_GET['excel']))
		echo "<a href=productListCallback.php?manufacturer=$manufacturer&mtype=$mtype&supertype=manu&excel=yes>Save to Excel</a><br />";

	//Query dumps summed transact data into temp6 for speed
	//`$trunTemp6 = $dbc->query("TRUNCATE temp6;");

	/*$temp6_items = $dbc->query("INSERT INTO temp6 SELECT UPC, description, dept, price,flag1 
				    FROM items
				    WHERE dept BETWEEN $deptStart AND $deptEnd");
	*/
	//if sorting by PLU, alter query to add item description from PLURelate
		
		// added like code to selection
		// andy
		$query = "SELECT i.UPC,i.description,d.dept_name as department,
			i.normal_price,                      
			(CASE WHEN i.tax = 1 THEN 'X' WHEN i.tax=0 THEN '-' ELSE LEFT(t.description,1) END) as Tax,              
	 	        (CASE WHEN i.foodstamp = 1 THEN 'X' ELSE '-' END) as FS,
                        (CASE WHEN i.discount = 0 THEN '-' ELSE 'X'END) as DISC,
                        (CASE WHEN i.scale = 1 THEN 'X' ELSE '-' END) as WGHd,
                        (CASE WHEN i.local = 1 THEN 'X' ELSE '-' END) as local,
			x.distributor
                        FROM Products as i LEFT JOIN departments as d ON i.department = d.dept_no
			LEFT JOIN taxrates AS t ON t.id = i.tax
			LEFT JOIN prodExtra as x on i.upc = x.upc
                        WHERE i.department BETWEEN $deptStart AND $deptEnd 
                        ORDER BY $order $dir";
			//ORDER BY i.$order,i.UPC";
		if (isset($_GET['deptSub']) && $supertype=='dept'){
			$query = "SELECT i.UPC,i.description,d.dept_name as department,
			i.normal_price,                      
			(CASE WHEN i.tax = 1 THEN 'X' WHEN i.tax=0 THEN '-' ELSE LEFT(t.description,1) END) as Tax,              
	 	        (CASE WHEN i.foodstamp = 1 THEN 'X' ELSE '-' END) as FS,
                        (CASE WHEN i.discount = 0 THEN '-' ELSE 'X'END) as DISC,
                        (CASE WHEN i.scale = 1 THEN 'X' ELSE '-' END) as WGHd,
                        (CASE WHEN i.local = 1 THEN 'X' ELSE '-' END) as local,
			x.distributor
                        FROM Products as i LEFT JOIN superdepts as s ON i.department = s.dept_ID
			LEFT JOIN taxrates AS t ON t.id = i.tax
			LEFT JOIN departments as d on i.department = d.dept_no
			LEFT JOIN prodExtra as x on i.upc = x.upc
                        WHERE s.superID = $deptSub 
                        ORDER BY $order $dir";
			
		}
		else if ($supertype == 'manu'){
			$query = "SELECT i.UPC,i.description,d.dept_name as department,
			i.normal_price,                      
			(CASE WHEN i.tax = 1 THEN 'X' WHEN i.tax=0 THEN '-' ELSE LEFT(t.description,1) END) as Tax,              
	 	        (CASE WHEN i.foodstamp = 1 THEN 'X' ELSE '-' END) as FS,
                        (CASE WHEN i.discount = 0 THEN '-' ELSE 'X'END) as DISC,
                        (CASE WHEN i.scale = 1 THEN 'X' ELSE '-' END) as WGHd,
                        (CASE WHEN i.local = 1 THEN 'X' ELSE '-' END) as local,
			x.distributor
                        FROM Products as i LEFT JOIN departments as d ON i.department = d.dept_no
			LEFT JOIN prodExtra as x on i.upc = x.upc
			LEFT JOIN taxrates AS t ON t.id = i.tax
			WHERE $whereclause
                        ORDER BY $order $dir";
		}
		//echo $query;
		if ($order != "i.upc")
			$query .= ",i.upc";

		$result = $dbc->query($query);

		/* make sure data was retrieved */
		$numrows = $dbc->num_rows($result);
		if($numrows==0){
			echo "No data to display!";
			exit;
		}
		
		echo "<table border=1 cellspacing=0 cellpadding =3><tr>\n"; 
		//echo "<tr>\n";
		/*

		foreach ($row as $heading=>$column){
			/*check if the heading is in our allowed_order
			* array. If it is, hyperlink it so that we can
			* order by this column */
			
		/*	echo "<td><div align=center><b>";
			if(in_array ($heading,$allowed_order)){
				echo "<a href=\"{$_SERVER['PHP_SELF']}?sort=$heading&deptStart=$deptStart&deptEnd=$deptEnd\">$heading</a>";
			}else{
				echo $heading;
			}
			echo "</b></div></td>\n";
		}
		echo "</tr>\n"; 

		/* reset the $result set back to the first row and
		* display the data */
		//$UPC = "<a href=\"$_SERVER['PHP_SELF']}?sort=UPC&deptStart=$deptStart&deptEnd=$deptEnd\">UPC</a>";
		// format for html header 
		//echo "<th><a href=\"{$_SERVER['PHP_SELF']}?sort=UPC&deptStart=$deptStart&deptEnd=$deptEnd\">UPC</a></th>\n";
		//echo "<th>UPC</th><th>Description</th><th>Dept</th><th>Price</th>";
		if (!isset($_GET['excel'])){
			$urlbase = "productListCallback.php?deptStart=$deptStart&deptEnd=$deptEnd&supertype=dept";
			if (isset($_GET['deptSub']) && $supertype=='dept')
				$urlbase = "productListCallback.php?deptSub=$deptSub&supertype=dept";
			else if ($supertype=='manu')
				$urlbase = "productListCallback.php?manufacturer=".urlencode($manufacturer)."&mtype=$mtype&supertype=manu";
			echo "<th><a href=$urlbase&sort=i.upc&dir=";
			if ($order == 'i.upc')
				echo "$otherdir>UPC</a></th>";
			else
				echo "asc>UPC</a></th>";	
			echo "<th><a href=$urlbase&sort=i.description&dir=";
			if ($order == 'description')
				echo "$otherdir>Description</a></th>";
			else
				echo "asc>Description</a></th>";	
			echo "<th><a href=$urlbase&sort=dept_name&dir=";
			if ($order == 'dept_name')
				echo "$otherdir>Dept</a></th>";
			else
				echo "asc>Dept</a></th>";	
			echo "<th><a href=$urlbase&sort=x.distributor&dir=";
			if ($order == 'x.distributor')
				echo "$otherdir>Supplier</a></th>";
			else
				echo "desc>Supplier</a></th>";	
			echo "<th><a href=$urlbase&sort=normal_price&dir=";
			if ($order == 'normal_price')
				echo "$otherdir>Price</a></th>";
			else
				echo "desc>Price</a></th>";	
		}
		else
			echo "<th>UPC</th><th>Description</th><th>Dept</th><th>Supplier</th><th>Price</th>";
		echo "<th>Tax</th><th>FS</th><th>Disc</th><th>Wg'd</th><th>Local</th><th>&nbsp;</th></tr>";
		
		/*
		 * build the table with cells id'd so that javascript can see them
		 * by convention, the id of each cell in a row is prefixed by that row's
		 * upc (standard XHTML ids aren't supposted to start with a digit, but most - 
		 * if not all - browsers don't care). Using this edit feature with duplicate upc
		 * items will [read: should - I haven't tried it] only update the display for the
		 * first row with that upc (regardless of which edit/save link is used) while silently
		 * updating all items with that upc in the background.
		 */
		
		while($row = $dbc->fetch_row($result)) {
			echo "<tr id=$row[0]>\n";
		    $enc = base64_encode($row[1]);
			if (!isset($_GET['excel'])){
				echo "<td align=center id=$row[0]upc><a href=itemMaint.php?upc=$row[0]>$row[0]</a>"; 
				echo "<a href=\"\" onclick=\"deleteCheck('$row[0]','$enc'); return false;\">";
				echo "<img src=\"{$FANNIE_URL}src/img/buttons/trash.png\" border=0 /></a>";
				echo "</td>\n";
			}
			else
				echo "<td align=center>$row[0]</td>";
			echo "<td align=center id=$row[0]desc>$row[1]</td>";
			echo "<td align=center id=$row[0]dept>$row[2]</td>";
			echo "<td align=center id=$row[0]supplier>$row[9]</td>";
			echo "<td align=center id=$row[0]price>$row[3]</td>";
			echo "<td align=center id=$row[0]tax>$row[4]</td>";
			echo "<td align=center id=$row[0]fs>$row[5]</td>";
			echo "<td align=center id=$row[0]disc>$row[6]</td>";
			echo "<td align=center id=$row[0]wgt>$row[7]</td>";
			echo "<td align=center id=$row[0]local>$row[8]</td>";
			if (!isset($_GET['excel']))
				echo "<td align=center id=$row[0]cmd><a href=\"\" onclick=\"edit('$row[0]'); return false;\"><img src=\"{$FANNIE_URL}src/img/buttons/b_edit.png\" alt=\"Edit\" border=0 /></a></td>";
			echo "</tr>\n";
		}
		echo "</table>\n";
		
	//$drop=$dbc->query("DELETE FROM temp6",$db);
	
	
	$output = ob_get_contents();
	ob_end_clean();

if (!isset($_GET['excel'])){
	echo $output;
}
else {
	include($FANNIE_ROOT.'src/ReportConvert/HtmlToArray.php');
	include($FANNIE_ROOT.'src/ReportConvert/ArrayToXls.php');
	$array = HtmlToArray($output);
	$xls = ArrayToXls($array);
	echo $xls;
}

?>

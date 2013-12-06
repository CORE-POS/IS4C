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
//	TODO -- Add javascript for batcher product entry popup window		~joel 2007-08-21

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	* 22Feb2013 Eric Lee Add support for editing
	*           products.quantity, .groupprice, .pricemethod, .mixmatchcode
	*           products.size, .unitofmeasure
	* 10Feb2013 Eric Lee In itemParse add FANNIE_STORE_ID to globals.

*/

include_once('../src/mysql_connect.php');
include_once('../auth/login.php');
include_once('ajax.php');
include(dirname(__FILE__).'/../classlib2.0/FannieAPI.php');

function itemParse($upc){
    global $dbc,$FANNIE_URL;
    global $FANNIE_STORE_ID;
    global $FANNIE_COOP_ID;

    $logged_in = checkLogin();

    $queryItem = "";
    $numType = (isset($_REQUEST['ntype'])?$_REQUEST['ntype']:'UPC');
    if(is_numeric($upc)){
			switch($numType){
				case 'UPC':
                    $upc = BarcodeLib::padUPC($upc);
					$savedUPC = $upc;
					$queryItem = "SELECT p.*,x.distributor,x.manufacturer 
						FROM products as p left join 
						prodExtra as x on p.upc=x.upc 
						WHERE (p.upc = '$upc' or x.upc = '$upc')
						AND p.store_id=0";
					break;
				case 'SKU':
					$queryItem = "SELECT p.*,x.distributor,x.manufacturer 
						FROM products as p inner join 
						vendorItems as v ON p.upc=v.upc 
						left join prodExtra as x on p.upc=x.upc 
						WHERE v.sku='$upc'
						AND p.store_id=0";
					break;
				case 'Brand Prefix':
					$queryItem = "SELECT p.*,x.distributor,x.manufacturer 
						FROM products as p left join 
						prodExtra as x on p.upc=x.upc 
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

    echo "<script type=\"text/javascript\">";
    echo "function shelftag(u){";
    echo "testwindow= window.open (\"addShelfTag.php?upc=\"+u, \"New Shelftag\",\"location=0,status=1,scrollbars=1,width=300,height=220\");";
    echo "testwindow.moveTo(50,50);";
    echo "}";
    echo "</script>";

    if($num == 0 || !$num){
        noItem();
	$data = array();
	if (is_numeric($upc)){
		$dataQ = "SELECT description,brand,cost/units as cost,vendorName,margin,i.vendorID
			FROM vendorItems AS i LEFT JOIN vendors AS v ON i.vendorID=v.vendorID
			LEFT JOIN vendorDepartments AS d ON i.vendorDept=d.deptID
			WHERE upc='$upc'";
		if (isset($_REQUEST['vid'])) $dataQ .= " AND i.vendorID=".((int)$_REQUEST['vid']);
		$dataR = $dbc->query($dataQ);
		if ($dbc->num_rows($dataR) > 0){
			$data = $dbc->fetch_row($dataR);
			if (is_numeric($data['cost']) && is_numeric($data['margin']))
				$data['srp'] = getSRP($data['cost'],$data['margin']);
		}
	}
        echo "<BODY onLoad='putFocus(0,1);'>";
        echo "<span style=\"color:red;\">Item not found.  You are creating a new one.  </span>";
	if (count($data) > 0){
		echo "<br /><i>This product is in the ".$data['vendorName']." catalog. Values have
			been filled in where possible</i><br />";
		while($vendorW = $dbc->fetch_row($dataR)){
			printf('This product is also in <a href="?upc=%s&vid=%d">%s</a><br />',
				$upc,$vendorW['vendorID'],$vendorW['vendorName']);
		}
	}
		echo "<form name=pickSubDepartment action=insertItem.php method=post>";
        echo "<div><table style=\"margin-bottom:5px;\" width=\"100%\" border=1 cellpadding=5 cellspacing=0>";
		echo "<tr><td align=right><b>UPC</b></td><td><font color='red'></font>
			<input type=text value=$upc name=upc></td><td colspan=2>&nbsp;</td>";
		echo "</tr><tr><td><b>Description</b></td><td>
			<input type=text size=30 name=descript ";
		echo (isset($data['description']))?"value=\"{$data['description']}\"":"";
		echo "></td>";
		echo "<td><b>Price</b></td>";
		echo "<td>$<input id=price type=text name=price size=6 ";
		printf("value=\"%s\"",isset($data['srp'])?$data['srp']:'');
		echo "></td></tr>";
		echo "<tr><td><b>Manufacturer</b></td><td><input type=text name=manufacturer size=30 ";
		echo (isset($data['brand']))?"value=\"{$data['brand']}\"":"";
		echo "/></td>
		<td><b>Distributor</b></td><td><input type=text size=8 name=distributor ";
		echo (isset($data['vendorName']))?"value=\"{$data['vendorName']}\"":"";
		echo "/></td></tr>";
	echo "</table>";
        echo "<table style=\"margin-bottom:5px;\" width='100%' border=1 cellpadding=5 cellspacing=0><tr>";
        echo "<th>Dept</th><th>Tax</th><th>FS</th><th>Scale</th><th>QtyFrc</th><th>NoDisc</th>";
	echo "</tr>";
        echo "<tr align=top>";
    	echo "<td align=left width=5px>";	
		/**
			**	BEGIN CHAINEDSELECTOR CLASS
			**/
				require('../src/chainedSelectors.php');

				//prepare names
				$selectorNames = array(
					CS_FORM=>"pickSubDepartment", 
					CS_FIRST_SELECTOR=>"department", 
					CS_SECOND_SELECTOR=>"subdepartment");

				//		$department = $rowItem[12];
				//		$subdepartment = $rowItem[27];

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
				**	CHAINEDSELECTOR CLASS ENDS . . . . . . . NOW
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
        echo "</tr></table></div>";
	echo "<input type=submit value=\"Create Item\" /><br />";

	if (substr($upc,0,3) == "002"){
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
	}

	echo "<br /><fieldset><legend>Extra Info</legend>";
	echo "<div style=\"float:left;\"><ul>";	
	echo "<li><input type=checkbox name=newshelftag /> New Shelf Tag</a></li>";
	echo "<li>Recent Sales History</li>";
	echo "<li>Price History</li>";
	echo "</ul></div>";
	echo "<div style=\"float:left;margin-left:20px;\">";
	echo "<table></tr><th align=right>Deposit</th>";
	echo "<td> <input type='text'";
	echo "name='deposit' size='5' value=0></td>";
	echo "</tr><tr><th align=right>Cost</th>";
	printf("<td>$<input type=text size=5 value=\"%.2f\" id=cost name=cost onchange=\"cscallback();\" /></td>",
		(isset($data['cost']) && is_numeric($data['cost'])?$data['cost']:0) );
	echo "</tr><th align-right>Location</th>";
	echo "<td><input type=text size=5 value=\"\" name=location /></td>";
	echo "</tr><th align=right>Local</th>";
	echo "<td><input type=checkbox name=local /></td>";
	echo "</tr><th align=right>InUse</th>";
	echo "<td><input type=checkbox name=inUse checked /></td>";
	echo "</tr></table></div>";
	echo "<div style=\"clear:left;text-align:left;color:darkmagenta;\">Last modified: ".date('r');
	echo "</div></fieldset>";

	echo '<br /><fieldset><legend>Likecode</legend>';
	echo "<table border=0><tr><td><b>Like code</b> ";
	echo "<select name=likeCode style=\"{width: 175px;}\"
		onchange=\"updateLC(this.value);\">";
	echo "<option value=-1>(none)</option>";
	$likelistQ = "select * from likeCodes order by likecode";
	$likelistR = $dbc->query($likelistQ);
	while ($llRow = $dbc->fetch_array($likelistR)){
	  echo "<option value={$llRow[0]}";
	  echo ">{$llRow[0]} {$llRow[1]}</option>";
	}
	echo "</select></td>";
	echo "<td><input type=checkbox name=update value='no'>Check to not update like code items</td>
		</tr><tr id=lchidden style=\"display:none;\"";
	echo "><td><b>Like Code Linked Items</b><div id=lctable>";
	echo '</div></td><td valign=top><a href="../reports/RecentSales/?likecode='.$likeCodeRow[1].'" target="_recentlike">';
	echo 'Likecode Sales History</td>';
	echo '</tr></table></fieldset>';

	echo "<br /><fieldset id=marginfs>";
	echo "<legend>Margin</legend>";
	echo "</fieldset>";
	echo "<script type=\"text/javascript\">cscallback();</script>";


    }elseif($num > 1){
        moreItems($upc);
			for($i=0;$i < $num;$i++){
        		$rowItem= $dbc->fetch_array($resultItem);
	    		$upc = $rowItem['upc'];
	    		echo "<a href='../item/itemMaint.php?upc=$upc'>" . $upc . " </a>- " . $rowItem['description'];
	 			if($rowItem['discounttype'] == 0) { echo "-- $" .$rowItem['normal_price']. "<br>"; }
				else { echo "-- <font color=green>$" .$rowItem['special_price']. " onsale</font><br>"; }
    		}
    }else{
		oneItem($upc);

		if ($FANNIE_STORE_ID != 0){
			/* if this isn't HQ, revise the lookup query to search
			   for HQ records AND store records
			   ordering by store_id descendings means we'll get the
			   store record if there is one and the HQ record if
			   there isn't */
			$clause = sprintf("p.store_id IN (0,%d)",$FANNIE_STORE_ID);
			$queryItem = str_replace("p.store_id=0",$clause,$queryItem);
			if (strstr($queryItem, "ORDER BY"))
				$queryItem = array_shift(explode("ORDER BY",$queryItem));
			$queryItem .= " ORDER BY p.store_id DESC";
			$resultItem = $dbc->query($queryItem);
		}

		$rowItem = $dbc->fetch_array($resultItem);
		$upc = $rowItem['upc'];
		$xtraQ = "SELECT * FROM prodExtra WHERE upc='$upc'";
		$xtraR = $dbc->query($xtraQ);
		$xtraRow = $dbc->fetch_row($xtraR);

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
        echo "<form name=pickSubDepartment action='updateItems.php' method=post>";
        echo "<div style=\"\"><table border=1 cellpadding=5 cellspacing=0>";
        echo "<tr><td align=right><b>UPC</b></td><td><font color='red'>".$rowItem[0]."</font><input type=hidden value='$rowItem[0]' id=upc name=upc>";
	if ($prevUPC) echo " <a style=\"font-size:85%;\" href=itemMaint.php?upc=$prevUPC>Previous</a>";
	if ($nextUPC) echo " <a style=\"font-size:85%;\" href=itemMaint.php?upc=$nextUPC>Next</a>";
	echo '</td>';
        echo '<td colspan=2>';
	echo '<input type="hidden" name="store_id" value="'.$rowItem['store_id'].'" />';
	echo ($rowItem['store_id']==0 ? 'Master' : 'Store').' record';
	echo '</td></tr><tr><td><b>Description</b></td><td><input type=text size=30 value="' . $rowItem[1] . '" name=descript></td>'; 
        echo "<td><select onchange=\"if(this.value=='Price'){
		document.getElementById('price2').style.display='none';
		document.getElementById('price1').style.display='inline';
		}else{
		document.getElementById('price1').style.display='none';
		document.getElementById('price2').style.display='inline';
		}\">
		<option>Price</option><option>Volume Price</option></select></td>";
	echo '<td><span id=price1 style="display:inline;">$<input id=price type=text value="' . $rowItem[2] . '" name=price size=6></span>';
	echo '<span id=price2 style="display:none;"><input type=text size=4 name=vol_qtty value="'.($rowItem[5]!=0?$rowItem[5]:'').'" />';
	echo " for $<input type=text size=4 name=vol_price value=".($rowItem[4] != 0 ? $rowItem[4] : "\"\"")." />";
	echo '<input type=checkbox name=doVolume '.($rowItem[4]!=0?'checked':'').' /></span>';
	echo '<input type=hidden name=pricemethod value='.$rowItem[3].' />';
	echo '</td></tr>';
	echo "<tr><td align=right><b>Manufacturer</b></td><td><input type=text name=manufacturer size=30 value=\"".(isset($xtraRow['manufacturer'])?$xtraRow['manufacturer']:"")."\" /></td>";
	echo "<td align=right><b>Distributor</b></td><td><input type=text name=distributor size=8 value=\"".(isset($xtraRow['distributor'])?$xtraRow['distributor']:"")."\" /></td></tr>";

			if($rowItem[6] <> 0){
				$batchQ = "SELECT b.batchName FROM batches AS b LEFT JOIN batchList as l
					on b.batchID=l.batchID WHERE '".date('Y-m-d')."' BETWEEN b.startDate
					AND b.endDate AND (l.upc='$upc' OR l.upc='LC$likecode')";
				$batchR = $dbc->query($batchQ);
				$batch = "Unknown";
				if ($dbc->num_rows($batchR) > 0)
					$batch = array_pop($dbc->fetch_row($batchR));
	   			echo "<tr><td><font color=green><b>Sale Price:</b></font></td><td><font color=green>$rowItem[6]</font> (<em>Batch: $batch</em>)</td><td>";
           		echo "<font color=green>End Date:</td><td><font color=green>$rowItem[11]</font></td><tr>";
			}
		echo "</table>";
        echo "<table style=\"margin-top:5px;margin-bottom:5px;\" border=1 cellpadding=5 cellspacing=0 width='100%'><tr>";
        echo "<th>Dept</th><th>Tax</th><th>FS</th><th>Scale</th><th>QtyFrc</th><th>NoDisc</th>";
        echo "</tr>";
        echo "<tr align=top>";
    	echo "<td align=left>";	
	   /**
		**	BEGIN CHAINEDSELECTOR CLASS
		**/
			require('../src/chainedSelectors.php');

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
			**	CHAINEDSELECTOR CLASS ENDS . . . . . . . NOW
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
		/*
		echo "<td colspan='3'><b>Deposit</b> <input type='text'";
 					if (!isset($rowItem['deposit']) || $rowItem['deposit'] == 0) {
						echo "value='0'";
					} else {
						echo "value='{$rowItem['deposit']}'"; 
					}
				echo "name='deposit' size='5'></td>";
		echo "</tr>";
		*/
               	echo "<input type='submit' name='submit' value='Update Item'>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<a href='../item/itemMaint.php'><font size='-1'>Back</font></a>";
				echo "</div> "; 

		if (substr($upc,0,3) == "002"){
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
		}

		echo "<br /><fieldset><legend>Extra Info</legend>";
		echo "<div style=\"float:left;\"><ul>";	
		echo "<li><a href=\"javascript:shelftag('$upc');\">New Shelf Tag</a></li>";
		echo "<li><a href=\"../reports/RecentSales/?upc=$upc\" target=\"_recentsales\">";
		echo "Recent Sales History</a></li>";
		echo "<li><a href=\"../reports/PriceHistory/?upc=$upc\" target=\"_price_history\">Price History</a></li>";
		echo "<li><a href=\"deleteItem.php?upc=$upc&submit=submit\">Delete this item</a></li>";
		echo "</ul></div>";
		echo "<div style=\"float:left;margin-left:20px;\">";
		echo "<table></tr><th align=right>Deposit</th>";
		echo "<td> <input type='text'";
 					if (!isset($rowItem['deposit']) || $rowItem['deposit'] == 0) {
						echo "value='0'";
					} else {
						echo "value='{$rowItem['deposit']}'"; 
					}
				echo "name='deposit' size='5'></td>";
		echo "</tr><tr><th align=right>Cost</th>";
		printf("<td>$<input type=text size=5 value=\"%.2f\" id=cost name=cost onchange=\"cscallback();\" /></td>",$rowItem['cost']);
		echo "</tr><th align-right>Location</th>";
		echo "<td><input type=text size=5 value=\"{$xtraRow['location']}\" name=location /></td>";
		echo "</tr><th align=right>Local</th>";
		echo "<td><input type=checkbox name=local ".($rowItem['local']==1?'checked':'')." /></td>";
		echo "</tr><th align=right>InUse</th>";
		echo "<td><input type=checkbox name=inUse ".($rowItem['inUse']==1?'checked':'')." /></td>";
		echo "</tr></table></div>";
		echo "<div style=\"clear:left;text-align:left;color:darkmagenta;\">Last modified: {$rowItem['modified']}";
		echo "</div></fieldset>";
			echo '<br /><fieldset><legend>Likecode</legend>';
			echo "<table border=0><tr><td><b>Like code</b> ";
			echo "<select name=likeCode style=\"{width: 175px;}\"
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
			echo "</select></td>";
			echo "<td><input type=checkbox name=update value='no'>Check to not update like code items</td>
				</tr><tr id=lchidden";
			if ($likeCodeNum <= 0) echo ' style="display:none;"';
			echo "><td><b>Like Code Linked Items</b><div id=lctable>";
			GetLikecodeItems($likecode);
			echo '</div></td><td valign=top><a href="../reports/RecentSales/?likecode='.$likeCodeRow[1].'" target="_recentlike">';
			echo 'Likecode Sales History</td>';
			echo '</tr></table></fieldset>';

			echo "<br /><fieldset id=marginfs>";
			if ( isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == "WEFC_Torontx" ) {
				echo "<legend>Markup</legend>";
				MarkupFS($rowItem['upc'],$rowItem['cost'],$rowItem['department']);
			} else {
				echo "<legend>Margin</legend>";
				MarginFS($rowItem['upc'],$rowItem['cost'],$rowItem['department']);
			}
			echo "</fieldset>";

			echo '<fieldset id="lanefs">';
			echo '<legend>Lane Status</legend>';
			include('prodAllLanes.php');
			echo allLanes($upc);
			echo '</fieldset>';
		
	}
    return $num;
}

function likedtotable($query,$border,$bgcolor)
{
	global $dbc;
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
                                 <a href="itemMaint.php?upc=<?php echo $row[0]; ?>">
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
   	echo "<h3>No Items Found</h3>";
}

function moreItems($upc)
{
    echo "More than 1 item found for:<h3> " . $upc . "</h3><br>";
}

function oneItem($upc)
{
    echo "One item found for: " . $upc;
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


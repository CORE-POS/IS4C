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
<<<<<<< HEAD
=======
/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  * 26Feb2013 Andy Theuninck re-drafted as modular
  * 18Feb2013 Eric Lee In itemParse add FANNIE_STORE_ID to globals.
*/

>>>>>>> 4b40f9d6f4fc2789d0f9d407c22182b9655b3a7e

include_once('../src/mysql_connect.php');
include_once('../auth/login.php');
include_once('ajax.php');

function itemParse($upc){
    global $dbc,$FANNIE_URL;

    $logged_in = checkLogin();

    $queryItem = "";
    $numType = (isset($_REQUEST['ntype'])?$_REQUEST['ntype']:'UPC');
    if(is_numeric($upc)){
	switch($numType){
	case 'UPC':
		$upc = str_pad($upc,13,0,STR_PAD_LEFT);
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
        $queryItem = "SELECT p.*,x.distributor,x.manufacturer 
		FROM products AS p LEFT JOIN 
		prodExtra AS x ON p.upc=x.upc
		WHERE description LIKE '%$upc%' 
		AND p.store_id=0
		ORDER BY description";
    }
    /* note: only search by HQ records (store_id=0) to avoid duplicates */
    $resultItem = $dbc->query($queryItem);
    $num = $dbc->num_rows($resultItem);
   
    if($num == 0 || !$num){
        noItem();
	$data = array();

        echo '<body onload="$(\'#price\').focus();">';
	echo "<form name=pickSubDepartment action=insertItem.php method=post>";

	include(dirname(__FILE__).'/modules/BaseItemModule.php');	
	$mod = new BaseItemModule();
	echo $mod->ShowEditForm($upc);

	echo "<input type=submit value=\"Create Item\" /><br />";

	if (substr($upc,0,3) == "002"){

		include(dirname(__FILE__).'/modules/ScaleItemModule.php');	
		$mod = new ScaleItemModule();
		echo $mod->ShowEditForm($upc);
		
	}

	include(dirname(__FILE__).'/modules/ExtraInfoModule.php');	
	$mod = new ExtraInfoModule();
	echo $mod->ShowEditForm($upc);

	include(dirname(__FILE__).'/modules/ItemLinksModule.php');	
	$mod = new ItemLinksModule();
	echo $mod->ShowEditForm($upc);

	include(dirname(__FILE__).'/modules/LikeCodeModule.php');	
	$mod = new LikeCodeModule();
	echo $mod->ShowEditForm($upc);

	include(dirname(__FILE__).'/modules/ItemFlagsModule.php');	
	$mod = new ItemFlagsModule();
	echo $mod->ShowEditForm($upc);

	include(dirname(__FILE__).'/modules/ItemMarginModule.php');	
	$mod = new ItemMarginModule();
	echo $mod->ShowEditForm($upc);


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
			/* multi-store not finished / in use yet
			$clause = sprintf("p.store_id IN (0,%d)",$FANNIE_STORE_ID);
			$queryItem = str_replace("p.store_id=0",$clause,$queryItem);
			if (strstr($queryItem, "ORDER BY"))
				$queryItem = array_shift(explode("ORDER BY",$queryItem));
			$queryItem .= " ORDER BY p.store_id DESC";
			$resultItem = $dbc->query($queryItem);
			*/
		}

		$rowItem = $dbc->fetch_array($resultItem);
		$upc = $rowItem['upc'];

		echo "<head><title>Update Item</title>";
		
		echo "</head>";
		echo '<body onload="$(\'#price\').focus();">';
		echo "<form name=pickSubDepartment action='updateItems.php' method=post>";

		include(dirname(__FILE__).'/modules/BaseItemModule.php');	
		$mod = new BaseItemModule();
		echo $mod->ShowEditForm($upc);

               	echo "<input type='submit' name='submit' value='Update Item'>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<a href='../item/itemMaint.php'><font size='-1'>Back</font></a>";

		if (substr($upc,0,3) == "002"){

			include(dirname(__FILE__).'/modules/ScaleItemModule.php');	
			$mod = new ScaleItemModule();
			echo $mod->ShowEditForm($upc);
		
		}


		include(dirname(__FILE__).'/modules/ExtraInfoModule.php');	
		$mod = new ExtraInfoModule();
		echo $mod->ShowEditForm($upc);

		include(dirname(__FILE__).'/modules/ItemLinksModule.php');	
		$mod = new ItemLinksModule();
		echo $mod->ShowEditForm($upc);

		include(dirname(__FILE__).'/modules/LikeCodeModule.php');	
		$mod = new LikeCodeModule();
		echo $mod->ShowEditForm($upc);

		include(dirname(__FILE__).'/modules/ItemMarginModule.php');	
		$mod = new ItemMarginModule();
		echo $mod->ShowEditForm($upc);

		include(dirname(__FILE__).'/modules/ItemFlagsModule.php');	
		$mod = new ItemFlagsModule();
		echo $mod->ShowEditForm($upc);

		include(dirname(__FILE__).'/modules/AllLanesItemModule.php');	
		$mod = new AllLanesItemModule();
		echo $mod->ShowEditForm($upc);
		
	}
	return $num;
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


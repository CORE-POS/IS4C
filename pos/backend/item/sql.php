<?php
	require_once($_SERVER["DOCUMENT_ROOT"].'/src/htmlparts.php');

	function search($backoffice) {
		$link=mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
		if ($link) {
			$query='SELECT
			`products`.`advertised`, 
			`products`.`department`, 
			`products`.`deposit`,
			`products`.`description`,
			`products`.`discount`,
			`products`.`foodstamp`, 
			`products`.`id`,
			`products`.`modified`, 
			`products`.`normal_price`,
			`products`.`scale`, 
			`products`.`size`, 
			`products`.`subdept`,
			`products`.`tareweight`,
			`products`.`tax`, 
			`products`.`unitofmeasure`, 
			`products`.`upc`,
			`products`.`wicable`,
			`products`.`inUse` 
			FROM `is4c_op`.`products` WHERE `products`.`upc`='.$_REQUEST['q'];
			$result=mysql_query($query, $link);
			if ($result) {
				if (mysql_num_rows($result)==1) {
					$backoffice['product_detail']=$row=mysql_fetch_array($result);
				} else if (mysql_num_rows($result)==0) {
					array_push($backoffice['status'], 'No search results found for '.$_REQUEST['q']);
					// TODO - At the Wedge, this leads to a new product being added
				} else {
					$backoffice['multiple_results']=mysql_fetch_array($result);
				}
			} else {
				array_push($backoffice['status'], 'Error with MySQL query: '.mysql_error($link));
			}
		} else {
			array_push($backoffice['status'], 'Error connecting to MySQL');
		}
	}
	
	function similarproducts($backoffice) {
		/*
		 * For most products, select all products from brand using the vendor code to match.
		 * For PLUs, use custom defined PLU ranges
		 */
		
		// TODO - Code for PLU ranges
		$link=mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
		if ($link) {
			$query='SELECT `products`.`upc`, `products`.`description`, `products`.`normal_price` FROM `is4c_op`.`products` WHERE `products`.`upc` LIKE \'00'.substr($backoffice['product_detail']['upc'],2,5).'%\' ORDER BY `products`.`upc`';
			$result=mysql_query($query, $link);
			if ($result) {
				if (mysql_num_rows($result)==0) {
					array_push($backoffice['status'], 'No similar products found');
				} else {
					while ($row=mysql_fetch_array($result)) {
						array_push($backoffice['similar_products'], $row);
					}
				}
			} else {
				array_push($backoffice['status'], 'Error with MySQL query: '.mysql_error($link));
			}
		} else {
			array_push($backoffice['status'], 'Error connecting to MySQL');
		}
	}
	
	function update($backoffice) {
		// TODO - Validate data before sending to MySQL
		$link=mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
		if ($link) {
			$query='
UPDATE `is4c_op`.`products` SET
	`advertised`='.((isset($_REQUEST['edit_advertised']) && $_REQUEST['edit_advertised']=='on')?'1':'0').',
	`department`='.$_REQUEST['edit_department'].',
	`deposit`='.$_REQUEST['edit_deposit'].',
	`description`=\''.$_REQUEST['edit_description'].'\',
	`discount`='.((isset($_REQUEST['edit_discount']) && $_REQUEST['edit_discount']=='on')?'1':'0').',
	`foodstamp`='.((isset($_REQUEST['edit_foodstamp']) && $_REQUEST['edit_foodstamp']=='on')?'1':'0').',
	`inUse`='.((isset($_REQUEST['edit_inuse']) && $_REQUEST['edit_inuse']=='on')?'1':'0').',
	`modified`=\''.strftime("%F %T", strtotime("now")).'\',
	`normal_price`='.$_REQUEST['edit_price'].',
	`scale`='.((isset($_REQUEST['edit_scale']) && $_REQUEST['edit_scale']=='on')?'1':'0').',
	`size`=\''.$_REQUEST['edit_size'].'\',
	`subdept`='.$_REQUEST['edit_subdepartment'].',
	`tareweight`='.$_REQUEST['edit_tareweight'].',
	`tax`='.$_REQUEST['edit_tax'].',
	`unitofmeasure`=\''.$_REQUEST['edit_unitofmeasure'].'\',
	`upc`='.$_REQUEST['edit_upc'].',
	`wicable`='.((isset($_REQUEST['edit_wicable']) && $_REQUEST['edit_wicable']=='on')?'1':'0').'
WHERE `id`='.$_REQUEST['edit_id'].' LIMIT 1';
			$result=mysql_query($query, $link);
			if ($result) {
				array_push($backoffice['status'], 'Item updated successfully');
			} else {
				array_push($backoffice['status'], 'Error with MySQL query: '.mysql_error($link));
			}
		} else {
			array_push($backoffice['status'], 'Error connecting to MySQL');
		}
	}
?>
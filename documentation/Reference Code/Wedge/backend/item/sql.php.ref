<?php
	function search($backoffice) {
		// $_REQUEST['q'] == Query string, or group value
		// $_REQUEST['t'] == Query type
		
		switch ($_REQUEST['t']) {
			// TODO - Further check data
			case 'upc_description_sku':
				// TODO - Update when sku/item_number's are added
				$query_where='(`products`.`upc`=\''.$_REQUEST['q'].'\' OR `products`.`description` LIKE \'%'.$_REQUEST['q'].'%\')';
			break;
			case 'upc': 
				$query_where='`products`.`upc`='.$_REQUEST['q'];
			break;
			case 'description':
				// TODO - Add soundex or something slick
				$query_where='`products`.`description` LIKE \'%'.$_REQUEST['q'].'%\'';
			break;
			case 'item number':
			case 'brand':
			case 'vendor':
				// TODO - Can you believe that these aren't included!
				$query_where='';
			break;
			case 'section':
				$query_where='`products`.`subdept`='.$_REQUEST['q'];
			break;
			case 'ask':
				// TODO - Some type of breakout
				$query_where='';
			break;
			default:
				$query_where='';
			break;
		}
		
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
			FROM `is4c_op`.`products` WHERE 1=1 AND '.$query_where;
			$result=mysql_query($query, $link);
			if ($result) {
				if (mysql_num_rows($result)==1) {
					$backoffice['product_detail']=$row=mysql_fetch_array($result);
				} else if (mysql_num_rows($result)==0) {
					array_push($backoffice['status'], 'No search results found for '.$_REQUEST['q']);
					if (is_numeric($_REQUEST['q'])) {
						array_push($backoffice['status'], 'Create product with UPC = <a href="/item/?a=insert&q='.$_REQUEST['q'].'">'.$_REQUEST['q'].'</a>');
						// TODO - At the Wedge, this leads to a new product being added
					}
				} else {
					$backoffice['multiple_results']=array();
					while ($row=mysql_fetch_array($result)) {
						array_push($backoffice['multiple_results'],$row);
					}
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
		// TODO - Update may be a misleading name for the function, since it handles new products as well.
		// TODO - Maybe test for a database connection before determining query type
		
		if ($_REQUEST['edit_id']=='NEW') {
			// TODO - Validate data before sending to MySQL
			$link=mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
			if ($link) {
				$query='INSERT `is4c_op`.`products` (`advertised`,`department`,`deposit`,`description`,`discount`,`foodstamp`,`inUse`,`modified`,`normal_price`,`scale`,`size`,`subdept`,`tareweight`,`tax`,`unitofmeasure`,`upc`,`wicable`) VALUES (
		'.((isset($_REQUEST['edit_advertised']) && $_REQUEST['edit_advertised']=='on')?'1':'0').',
		'.$_REQUEST['edit_department'].',
		'.$_REQUEST['edit_deposit'].',
		\''.$_REQUEST['edit_description'].'\',
		'.((isset($_REQUEST['edit_discount']) && $_REQUEST['edit_discount']=='on')?'1':'0').',
		'.((isset($_REQUEST['edit_foodstamp']) && $_REQUEST['edit_foodstamp']=='on')?'1':'0').',
		'.((isset($_REQUEST['edit_inuse']) && $_REQUEST['edit_inuse']=='on')?'1':'0').',
		\''.strftime("%F %T", strtotime("now")).'\',
		'.$_REQUEST['edit_price'].',
		'.((isset($_REQUEST['edit_scale']) && $_REQUEST['edit_scale']=='on')?'1':'0').',
		\''.$_REQUEST['edit_size'].'\',
		'.$_REQUEST['edit_subdepartment'].',
		'.$_REQUEST['edit_tareweight'].',
		'.$_REQUEST['edit_tax'].',
		\''.$_REQUEST['edit_unitofmeasure'].'\',
		'.$_REQUEST['edit_upc'].',
		'.((isset($_REQUEST['edit_wicable']) && $_REQUEST['edit_wicable']=='on')?'1':'0').')';
				$result=mysql_query($query, $link);
				if ($result) {
					array_push($backoffice['status'], 'Item added successfully');
				} else {
					array_push($backoffice['status'], 'Error with MySQL query: '.mysql_error($link));
				}
			} else {
				array_push($backoffice['status'], 'Error connecting to MySQL');
			}
		} else {
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
	}
?>
<?php
	function search($backoffice) {
		global $dbc,$FANNIE_OP_DB,$FANNIE_URL;
		
		switch ($_REQUEST['t']) {
			// TODO - Further check data
			case 'upc_description_sku':
				// TODO - Update when sku/item_number's are added
				$query_where='(upc=\''.$_REQUEST['q'].'\' OR description LIKE \'%'.$_REQUEST['q'].'%\')';
			break;
			case 'upc': 
				$query_where='upc='.$_REQUEST['q'];
			break;
			case 'description':
				// TODO - Add soundex or something slick
				$query_where='description LIKE \'%'.$_REQUEST['q'].'%\'';
			break;
			case 'item number':
			case 'brand':
			case 'vendor':
				// TODO - Can you believe that these aren't included!
				$query_where='';
			break;
			case 'section':
				$query_where='subdept='.$_REQUEST['q'];
			break;
			case 'ask':
				// TODO - Some type of breakout
				$query_where='';
			break;
			default:
				$query_where='';
			break;
		}
		
		if ($dbc->connections[$FANNIE_OP_DB] != false) {
			$query='SELECT
			advertised, 
			department, 
			deposit,
			description,
			discount,
			foodstamp, 
			id,
			modified, 
			normal_price,
			scale, 
			size, 
			subdept,
			tareweight,
			tax, 
			unitofmeasure, 
			upc,
			wicable,
			inUse 
			FROM products WHERE 1=1 AND '.$query_where;
			$result=$dbc->query($query);
			if ($result) {
				if ($dbc->num_rows($result)==1) {
					$backoffice['product_detail']=$row=$dbc->fetch_array($result);
				} else if ($dbc->num_rows($result)==0) {
					array_push($backoffice['status'], 'No search results found for '.$_REQUEST['q']);
					if (is_numeric($_REQUEST['q'])) {
						array_push($backoffice['status'], 'Create product with UPC = <a href="'.$FANNIE_URL.'item/?a=insert&q='.$_REQUEST['q'].'">'.$_REQUEST['q'].'</a>');
						// TODO - At the Wedge, this leads to a new product being added
					}
				} else {
					$backoffice['multiple_results']=array();
					while ($row=$dbc->fetch_array($result)) {
						array_push($backoffice['multiple_results'],$row);
					}
				}
			} else {
				array_push($backoffice['status'], 'Error with MySQL query: '.$dbc->error());
			}
		} else {
			array_push($backoffice['status'], 'Error connecting to MySQL');
		}
	}
	
	function similarproducts($backoffice) {
		global $dbc,$FANNIE_OP_DB;
		/*
		 * For most products, select all products from brand using the vendor code to match.
		 * For PLUs, use custom defined PLU ranges
		 */
		
		// TODO - Code for PLU ranges
		if ($dbc->connections[$FANNIE_OP_DB] != false) {
			$query='SELECT upc, description, normal_price FROM products WHERE upc LIKE \'00'.substr($backoffice['product_detail']['upc'],2,5).'%\' ORDER BY upc';
			$result=$dbc->query($query);
			if ($result) {
				if ($dbc->num_rows($result)==0) {
					array_push($backoffice['status'], 'No similar products found');
				} else {
					while ($row=$dbc->fetch_array($result)) {
						array_push($backoffice['similar_products'], $row);
					}
				}
			} else {
				array_push($backoffice['status'], 'Error with MySQL query: '.$dbc->error());
			}
		} else {
			array_push($backoffice['status'], 'Error connecting to MySQL');
		}
	}
	
	
	function update($backoffice) {
		global $dbc,$FANNIE_OP_DB;
		// TODO - Update may be a misleading name for the function, since it handles new products as well.
		// TODO - Maybe test for a database connection before determining query type
		$vals = array(
			'advertised'=>((isset($_REQUEST['edit_advertised']) && $_REQUEST['edit_advertised']=='on')?'1':'0'),
			'department'=>$_REQUEST['edit_department'],
			'deposit'=>$_REQUEST['edit_deposit'],
			'description'=>$dbc->escape($_REQUEST['edit_description']),
			'discount'=>((isset($_REQUEST['edit_discount']) && $_REQUEST['edit_discount']=='on')?'1':'0'),
			'foodstamp'=>((isset($_REQUEST['edit_foodstamp']) && $_REQUEST['edit_foodstamp']=='on')?'1':'0'),
			'inUse'=>((isset($_REQUEST['edit_inuse']) && $_REQUEST['edit_inuse']=='on')?'1':'0'),
			'modified'=>$dbc->now(),
			'normal_price'=>$_REQUEST['edit_price'],
			'scale'=>((isset($_REQUEST['edit_scale']) && $_REQUEST['edit_scale']=='on')?'1':'0'),
			'size'=>$dbc->escape($_REQUEST['edit_size']),
			'subdept'=>$_REQUEST['edit_subdepartment'],
			'tareweight'=>$_REQUEST['edit_tareweight'],
			'tax'=>$_REQUEST['edit_tax'],
			'unitofmeasure'=>$dbc->escape($_REQUEST['edit_unitofmeasure']),
			'upc'=>$dbc->escape($_REQUEST['edit_upc']),
			'wicable'=>((isset($_REQUEST['edit_wicable']) && $_REQUEST['edit_wicable']=='on')?'1':'0')
		);
		
		if ($_REQUEST['edit_id']=='NEW') {
			// TODO - Validate data before sending to MySQL
			if ($dbc->connections[$FANNIE_OP_DB] != false) {
				$result=$dbc->smart_insert('products',$vals);
				if ($result) {
					array_push($backoffice['status'], 'Item added successfully');
				} else {
					array_push($backoffice['status'], 'Error with MySQL query: '.$dbc->error());
				}
			} else {
				array_push($backoffice['status'], 'Error connecting to MySQL');
			}
		} else {
			// TODO - Validate data before sending to MySQL
			if ($dbc->connections[$FANNIE_OP_DB] != false) {
				$where = 'id='.$_REQUEST['edit_id'];
				$result=$dbc->smart_update('products',$vals,$where);
				if ($result) {
					array_push($backoffice['status'], 'Item updated successfully');
				} else {
					array_push($backoffice['status'], 'Error with MySQL query: '.$dbc->error());
				}
			} else {
				array_push($backoffice['status'], 'Error connecting to MySQL');
			}
		}
	}
?>

<?php
	// TODO - The searchProduct and addProduct forms should be handled better.
		/* 
		 * The search box should only be visible if previously no upc was 
		 * searched for. Likewise, the addProduct form should not be visible
		 * unless a product was searched for and matches an existing product.
		 * Also, the results box should only appear when needed.
		 * All in all, some things to do 
		 */

	// Test update

	// Back to the main batch page if no id set
	if (!isset($_REQUEST['id'])) {
		header ("Location: /batch");
	}

	require_once($_SERVER["DOCUMENT_ROOT"]."/define.conf");

	$backoffice=array();
	$backoffice['status']=array();

	/*
	 * This page is enough of a beast to break out of index.php
	 * Per batch, view info, actions, add/search/modified products, list products
	 */

	require_once($_SERVER["DOCUMENT_ROOT"].'/src/htmlparts.php');
	
	require_once($_SERVER["DOCUMENT_ROOT"].'/lib/table_batchTypes.php');
		$batchTypes_result=get_batchTypes(&$backoffice);
		
	require_once('sql.php');		

	if (isset($_REQUEST['a']) && $_REQUEST['a']=='editBatch') {
		editBatch(&$backoffice);
	} else if (isset($_REQUEST['a']) && $_REQUEST['a']=='searchProduct') {
		$search_batchProducts_result=search_batchProducts(&$backoffice, $_REQUEST['id'], $_REQUEST['searchProduct_upc']);
		if ($search_batchProducts_result) {
			if (mysql_num_rows($search_batchProducts_result)==1) {
				print_r('one result');
				// Populate Add/Modify/Remove form
			} else if (mysql_num_rows($search_batchProducts_result)>1) {
				print_r('more than one result');
				// Display a list of matches
			} else {
				print_r('no results');
				// Search products table for UPC
				$search_allProducts_result=search_allProducts(&$backoffice, $_REQUEST['searchProduct_upc']);
				if ($search_allProducts_result) {
					if (mysql_num_rows($search_allProducts_result)==1) {
						print_r('one new result');
						// Populate Add/Modify/Remove form
					} else if (mysql_num_rows($search_allProducts_result)>1) {
						print_r('more than new one result');
						// Display a list of matches
					} else {
						print_r('no new results');
					}
				} else {
					// Already covered
				} 
			}
		} else {
			// Already covered
		}
	}
	
	require_once($_SERVER["DOCUMENT_ROOT"].'/lib/materialized_batch.php');
		$batchInfo_result=get_batchInfo(&$backoffice, $_REQUEST['id']);
		$batchInfo_row=mysql_fetch_assoc($batchInfo_result);
		$batchProducts_result=get_batchProducts(&$backoffice, $_REQUEST['id']);
	
	$html='<!DOCTYPE HTML>
<html>
	<head>';
	
	$html.=head();
	
	$html.='
		<link href="batch.css" media="screen" rel="stylesheet" type="text/css"/>
		<script src="batch.js" type="text/javascript"></script>
		<title>IS4C - Sale Batches</title>
	</head>
	<body>';
	
	$html.=body();
	
	$html.='
		<div id="page_panel">
			<h1>'.$batchInfo_row['batchHeaders name'].'</h1>
			<div>
				<form action="./edit.php" method="post" name="editBatch">
					<fieldset>
						<legend>Info</legend>
						<input name="a" type="hidden" value="editBatch"/>
						<input name="id" type="hidden" value="'.$_REQUEST['id'].'"/>
						<label for="editBatch_name"><span class="accesskey">N</span>ame</label>
						<input accesskey="n" id="editBatch_name" name="editBatch_name" onkeyup="valid_name(this)" type="text" value="'.$batchInfo_row['batchHeaders name'].'"/>
						<label for="editBatch_start"><span class="accesskey">S</span>tart</label>
						<input accesskey="s" id="editBatch_start" name="editBatch_start" type="date" value="'.$batchInfo_row['start'].'"/>
						<label for="editBatch_end"><span class="accesskey">E</span>nd</label>
						<input accesskey="e" id="editBatch_end" name="editBatch_end" type="date" value="'.$batchInfo_row['end'].'"/>
						<label for="editBatch_type"><span class="accesskey">T</span>ype</label>
						<select accesskey="t" id="editBatch_type" name="editBatch_type">';
	while ($row=mysql_fetch_array($batchTypes_result)) {
		$html.='
							<option '.($batchInfo_row['batchTypes name']==$row['name']?'selected ':'').'value="'.$row['id'].'">'.$row['name'].'</option>';
	}
	
	$html.='
						</select>
						<input type="submit" value="Update"/>
						<input disabled type="button" value="Merge"/>
					</fieldset>
				</form>
			</div>
			<div>
				<form action="./edit.php" method="post" name="searchProduct">
					<fieldset>
						<legend>Search</legend>
						<input name="a" type="hidden" value="searchProduct"/>
						<input name="id" type="hidden" value="'.$_REQUEST['id'].'"/>
						<label for="searchProduct_upc"><span class="accesskey">U</span>PC</label>
						<input accesskey="u" id="searchProduct_upc" name="searchProduct_upc" type="text"/>
						<input type="submit" value="Search"/>
					</fieldset>
				</form>
			</div>
			<div>
				<form action="./edit.php" method="post" name="addProduct">
					<fieldset>
						<legend>Add/Modify/Remove</legend>
						<div class="edit_row">
							<input name="a" type="hidden" value="addProduct"/>
							<input name="id" type="hidden" value="'.$_REQUEST['id'].'"/>
							<label for="addProduct_upc">UPC</label>
							<input id="addProduct_upc" name="addProduct_upc" readonly type="text"/>
							<label for="addProduct_description">Description</label>
							<input id="addProduct_description" name="addProduct_description" readonly type="text"/>
							<label for="addProduct_normal_price">Normal Price</label>
							<input id="addProduct_normal_price" name="addProduct_normal_price" readonly type="text"/>
							<label for="addProduct_price"><span class="accesskey">P</span>rice</label>
							<input accesskey="p" id="addProduct_price" name="addProduct_price" type="number"/>
						</div>
						<div class="edit_row">
							<input type="submit" value="Update"/>
							<input type="button" value="Remove"/>
						</div>
					</fieldset>
				</form>
			</div>
			<div>
				<form action="./edit.php" method="post" name="listProduct">
					<fieldset>
						<legend>Products</legend>';
	if (mysql_num_rows($batchProducts_result)>0) {
		$html.='
						<table>
							<thead>
								<tr>
									<th>UPC</th>
									<th>Description</th>
									<th>Retail</th>
									<th>Sales Retail</th>
									<th>Action</th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<td colspan=5><input type="button" value="print"/></td>
								</tr>
							</tfoot>
							<tbody>';

		while ($row=mysql_fetch_array($batchProducts_result)) {
			$html.='
								<tr>
									<td>'.$row['upc'].'</td>
									<td>'.$row['description'].'</td>
									<td>'.$row['normal_price'].'</td>
									<td>'.$row['price'].'</td>
									<td>X</td>
								</tr>';
		}
		$html.='
							</tbody>
						</table>';
	} else {
		$html.='
						<h2>No products entered in batch yet</h2>';
	}
	
	$html.='
					</fieldset>
				</form>
			</div>
			<div id="page_panel_statuses">';
	foreach ($backoffice['status'] as $msg) {
		$html.='
				<p class="status">'.$msg.'</p>';
	}
	
	$html.='
			</div>
		</div>';
	
	$html.=foot();
	
	$html.='
	</body>
</html>';
	
	print_r($html);
?>

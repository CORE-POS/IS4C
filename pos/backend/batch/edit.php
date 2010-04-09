<?php
	require_once($_SERVER["DOCUMENT_ROOT"]."/define.conf");

	$backoffice=array();
	$backoffice['status']=array();

	/*
	 * This page is enough of a beast to break out of index.php
	 * Per batch, view info, actions, add/search/modified products, list products
	 */
	
	// Test line!!!

	require_once($_SERVER["DOCUMENT_ROOT"].'/src/htmlparts.php');
	
	require_once($_SERVER["DOCUMENT_ROOT"].'/lib/table_batchTypes.php');
		$batchTypes_result=get_batchTypes(&$backoffice);
		
	require_once('sql.php');		

	// Back to the main batch page if no id set
	if (!isset($_REQUEST['id'])) {
		header ("Location: /batch");
	}

	require_once($_SERVER["DOCUMENT_ROOT"].'/lib/materialized_batch.php');
		$batchInfo_result=get_batchList(&$backoffice);
		$batchInfo_row=mysql_fetch_assoc($batchInfo_result);
	
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
						<input type="button" value="Merge"/>
					</fieldset>
				</form>
			</div>
			<div>
				<form action="./edit.php" method="get" name="searchBatch">
						<legend>Add/Modify/Search</legend>
						<input name="a" type="hidden" value="searchBatch"/>
						<label for="searchBatch_upc"><span class="accesskey">U</span>PC</label>
						<input accesskey="u" id="searchBatch_upc" name="searchBatch_upc" type="text"/>
						<input type="submit" value="Search"/>
					</fieldset>
				</form>
			</div>
			<div>
				<form>
					<fieldset>
						<legend>Products</legend>
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
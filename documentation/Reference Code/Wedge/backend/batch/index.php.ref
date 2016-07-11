<?php
	require_once($_SERVER["DOCUMENT_ROOT"]."/define.conf");

	$backoffice=array();
	$backoffice['status']=array();

	/*
	 * 	<h1>Simple TODO</h1>
			<ul>
			#	<li>Restructure tables. Instead of Batches & BatchList, have BatchHeaders, BatchProducts, BatchTypes in is4c_op, and BatchMerged in is4c_log</li>
				<li>Code this page. Simple, right?</li>
				<li>GET - Search by UPC<li>
				<li>GET - Search by batch (also, link from table displaying all batches</li>
			#	<li>POST - Add new batch</li>
				<li>POST - Merge batch(es)</li>
				<li>POST - Delete batch</li>
				<li>GET - Search by UPC to add to batch</li>
				<li>POST - Delete item from batch</li>
				<li>GET - Edit item in batch</li>
				<li>POST - Add item to batch</li>
				<li>Other actions?</li>
				<li>Add reasonable defaults to forms</li>
			</ul>
		<hr>
	 */

	require_once($_SERVER["DOCUMENT_ROOT"].'/src/htmlparts.php');
	
	require_once($_SERVER["DOCUMENT_ROOT"].'/lib/table_batchTypes.php');
		$batchTypes_result=get_batchTypes(&$backoffice);
	
	require_once('sql.php');		
		
	if (isset($_REQUEST['a']) && $_REQUEST['a']=='addBatch') {
		addBatch(&$backoffice);
	} else if (isset($_REQUEST['a']) && $_REQUEST['a']=='listBatch') {
		listBatch(&$backoffice);
	}

	// This needs to happen after any addBatch, deleteBatch, or editBatch request
	require_once($_SERVER["DOCUMENT_ROOT"].'/lib/materialized_batch.php');
		$batchList_result=get_batchList(&$backoffice);
	
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
			<div>
				<form action="./" method="post" name="addBatch">
					<fieldset>
						<legend>Add Batch</legend>
						<input name="a" type="hidden" value="addBatch"/>
						<label for="addBatch_name"><span class="accesskey">N</span>ame</label>
						<input accesskey="n" id="addBatch_name" name="addBatch_name" onkeyup="valid_name(this)" type="text"/>
						<label for="addBatch_start"><span class="accesskey">S</span>tart</label>
						<input accesskey="s" id="addBatch_start" name="addBatch_start" type="date"/>
						<label for="addBatch_end"><span class="accesskey">E</span>nd</label>
						<input accesskey="e" id="addBatch_end" name="addBatch_end" type="date"/>
						<label for="addBatch_type"><span class="accesskey">T</span>ype</label>
						<select accesskey="t" id="addBatch_type" name="addBatch_type">';
	while ($row=mysql_fetch_array($batchTypes_result)) {
		$html.='
							<option value="'.$row['id'].'">'.$row['name'].'</option>';
	}
	
	$html.='
						</select>
						<input type="submit" value="Add"/>
					</fieldset>
				</form>
			</div>';
	/*
	 * Going to leave searching all batches for the future
			<div>
				<form action="./" method="get" name="searchBatch">
					<fieldset>
						<legend>Search Batches</legend>
						<input name="a" type="hidden" value="searchBatch"/>
						<label for="searchBatch_upc"><span class="accesskey">U</span>PC</label>
						<input accesskey="u" id="searchBatch_upc" name="searchBatch_upc" type="text"/>
						<input type="submit" value="Search"/>
					</fieldset>
				</form>
			</div> */
	$html.='
			<div>
				<form action="./" method="post" name="listBatch">
					<fieldset>
						<legend>List of Active Batches</legend>
						<input name="a" type="hidden" value="listBatch"/>';
	if ($batchList_result && mysql_num_rows($batchList_result)>0) {
		$html.='
						<table>
							<thead>
								<tr>
									<th>Name</th>
									<th>Merge Status</th>
									<th>Type</th>
									<th>Start</th>
									<th>End</th>
									<th>Merge</th>
									<th>Delete</th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<td class="textAlignRight" colspan=7"><input type="submit"/></td>
								</tr>
							</tfoot>
							<tbody>';
		while ($row=mysql_fetch_array($batchList_result)) {
			$html.='
								<tr>
									<td><a href="./edit.php?id='.$row['id'].'">'.$row['batchHeaders name'].'</a></td>
									<td>'.$row['batchMerges modified'].'</td>
									<td>'.$row['batchTypes name'].'</td>
									<td>'.strftime("%F", strtotime($row['start'])).'</td>
									<td>'.strftime("%F", strtotime($row['end'])).'</td>
									<td class="textAlignCenter"><input name="listBatch_mergeFlag[]" type="checkbox" value="'.$row['id'].'" /></td>
									<td class="textAlignCenter"><input name="listBatch_deleteFlag[]" type="checkbox"value="'.$row['id'].'" /></td>
								</tr>';
		}
		$html.='
							</tbody>
						</table>';
	} else {
		$html.='
						<h2>No batches found</h2>';
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
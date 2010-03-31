<?php
	$backoffice=array();
	$backoffice['status']=array();

	/*
	 * 	<h1>Simple TODO</h1>
			<ul>
			#	<li>Restructure tables. Instead of Batches & BatchList, have BatchHeaders, BatchProducts, BatchTypes in is4c_op, and BatchMerged in is4c_log</li>
				<li>Code this page. Simple, right?</li>
				<li>GET - Search by UPC<li>
				<li>GET - Search by batch (also, link from table displaying all batches</li>
				<li>POST - Add new batch</li>
				<li>POST - Merge batch(es)</li>
				<li>POST - Delete batch</li>
				<li>GET - Search by UPC to add to batch</li>
				<li>POST - Delete item from batch</li>
				<li>GET - Edit item in batch</li>
				<li>POST - Add item to batch</li>
				<li>Other actions?</li>
			</ul>
		<hr>
	 */

	require_once($_SERVER["DOCUMENT_ROOT"].'/src/htmlparts.php');
	
	require_once($_SERVER["DOCUMENT_ROOT"]."/lib/table_batchTypes.php");
		$batchTypes_result=get_batchTypes(&$backoffice);
		
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
			</div>
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
			</div>
			<div>
				<p>List of active batches</p>
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
					<tfoot/>
					<tbody>
						<tr>
							<td>Some sale</td>
							<td>Not merged</td>
							<td>Calendar</td>
							<td>2010-04-01</td>
							<td>2010-04-30</td>
							<td><input type="checkbox"/></td>
							<td><input type="checkbox"/></td>
						</tr>
						<tr>
							<td>Some WSL batch</td>
							<td>2010-03-20</td>
							<td>WSL</td>
							<td>2010-03-22</td>
							<td>--</td>
							<td><input type="checkbox"/></td>
							<td><input type="checkbox"/></td>
						</tr>
						<tr>
							<td>Some price change</td>
							<td>Not merged</td>
							<td>Price Change</td>
							<td>--</td>
							<td>--</td>
							<td><input type="checkbox"/></td>
							<td><input type="checkbox"/></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>';
	
	$html.=foot();
	
	$html.='
	</body>
</html>';
	
	print_r($html);
?>
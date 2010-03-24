<?php
	require_once($_SERVER["DOCUMENT_ROOT"].'/src/htmlparts.php');

	$html='<!DOCTYPE HTML>
<html>
	<head>';
	
	$html.=head();
	
	$html.='
		<title>IS4C - Sale Batches</title>
	</head>
	<body>';
	
	$html.=body();
	
	$html.='
		<div id="page_panel">
			<h1>Simple TODO</h1>
			<ul>
				<li>Restructure tables. Instead of Batches & BatchList, have BatchHeaders, BatchProducts, BatchTypes in is4c_op, and BatchMerged in is4c_log</li>
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
			<div>
				<p>Block to add a batch</p>
				<form>
					<label>Name</label>
					<input type="text"/>
					<label>Start</label>
					<input type="date"/>
					<label>End</label>
					<input type="date"/>
					<label>Type</label>
					<select>
						<option>Calendar</option>
					</select>
					<input type="submit"/>
				</form>
			</div>
			<hr>
			<div>
				<p>Block to search for upc in existing batches. Maybe bring in same search function as item maintenance page?</p>
				<form>
					<label>UPC</label>
					<input type="text"/>
					<input type="submit"/>
				</form>
			</div>
			<hr>
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
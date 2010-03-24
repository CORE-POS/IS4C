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
				<li>Restructure tables. Instead of Batches & BatchList, have BatchHeaders, BatchProducts in is4c_op, and BatchMerged in is4c_log</li>
				<li>Code this page. Simple, right?</li>
			</ul>
		</div>';
	
	$html.=foot();
	
	$html.='
	</body>
</html>';
	
	print_r($html);
?>
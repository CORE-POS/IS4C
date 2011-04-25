<?php
	require_once($_SERVER["DOCUMENT_ROOT"].'/src/htmlparts.php');

	$html='<!DOCTYPE HTML>
<html>
	<head>';
	
	$html.=head();
	
	$html.='
		<title>IS4C - Admin</title>
	</head>
	<body>';
	
	$html.=body();
	
	$html.='
		<div id="page_panel">
			<h1>Just ideas for now...</h1>
			<ul>
				<li>Lane Configuration (IPs, nicknames)</li>
				<li>SQL Configuration (users, passwords)</li>
				<li>User Configuration (users, passwords, access levels for maintenance &amp; reporting)</li>
				<li>Error Logs</li>
			</ul>
		</div>';
	
	$html.=foot();
	
	$html.='
	</body>
</html>';
	
	print_r($html);
?>
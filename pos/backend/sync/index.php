<?php
	require_once($_SERVER["DOCUMENT_ROOT"].'/src/htmlparts.php');

	$html='<!DOCTYPE HTML>
<html>
	<head>';
	
	$html.=head();
	
	$html.='
		<title>IS4C - Synchronization</title>
	</head>
	<body>';
	
	$html.=body();
	
	$html.='
		<div id="page_panel">
			<h1>Synchronization</h1>
			<ul>
				<li><a href="reload.php?t=products">Products</a></li>
				<li><a href="reload.php?t=custdata">Membership</a></li>
				<li><a href="reload.php?t=employees">Employees</a></li>
				<li><a href="reload.php?t=departments">Departments</a></li>
				<li><a href="reload.php?t=subdepts">Subdepartments</a></li>
				<li><a href="reload.php?t=tenders">Tenders</a></li>
			</ul>
		</div>';
	
	$html.=foot();
	
	$html.='
	</body>
</html>';
	
	print_r($html);
?>
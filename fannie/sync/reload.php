<?php
	require('../config.php');
	require_once($FANNIE_ROOT.'src/htmlparts.php');
	include($FANNIE_ROOT.'src/trans_connect.php');
	
	if (isset($_REQUEST['t'])) {
		switch ($_REQUEST['t']) {
			case 'products':
				$name='products';
				$type='Products';
			break;
			case 'custdata':
				$name='custdata';
				$type='Membership';
			break;
			case 'employees':
				$name='employees';
				$type='Employees';
			break;
			case 'departments':
				$name='departments';
				$type='Departments';
			break;
			case 'subdepts':
				$name='subdepts';
				$type='Subdepartments';
			break;
			case 'tenders':
				$name='tenders';
				$type='Tenders';
			break;
			
			default:
				header("Location: index.php");
			break;
		}
	} else {
		header("Location: index.php");
	}
	
	$html='<!DOCTYPE HTML>
<html>
	<head>';
	
	$html.=head();
	
	$html.='
		<title>Synchronization - '.$type.'</title>
	</head>
	<body>';
	
	$html.=body();
	
	$html.='
		<div id="page_panel">
			<h1>Synchronize '.$type.'</h1>
			<form action="./synchronize.php" method="post" name="synchronize">';
	
	foreach ($FANNIE_LANES as $lane) {
		$html.='
				<fieldset>
					<label>'.$lane['host'].'</label>
					<input checked name="lanes[]" type="checkbox" value="'.$lane['host'].'"/>
				</fieldset>';
	}
	
	$html.='
				<fieldset>
					<input type="hidden" name="t" value="'.$name.'"/>
					<input type="submit"/>
				</fieldset>
			</form>
		<p class="status">';
	
	if ($dbc->connections[$FANNIE_TRANS_DB] != False) {
		$query='SELECT datetime FROM synchronizationLog WHERE name=\''.$name.'\' AND status=1 ORDER BY datetime DESC';
		$query = $dbc->add_select_limit($query,1);
		$result=$dbc->query($query);
		if ($result && $dbc->num_rows($result)==1) {
			$row=$dbc->fetch_array($result);
			$html.='Last synchronized @ '.$row['datetime'];
			
		} else {
			$html.='Unable to query synchronization log.';
		}
	} else {
		$html.='Unable to connect to main server.';		
	}

	$html.='</p>
	</body>
</html>';

	print_r($html);
?>

<?php
	require_once($_SERVER["DOCUMENT_ROOT"]."/define.conf");
	require_once($_SERVER["DOCUMENT_ROOT"].'/src/htmlparts.php');
	
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
	
// TODO - Auto generate lanes from define.conf, for now, hardcode
	$lanes=array(
		array('Name'=>'Lane 01', 'IP'=>'10.10.10.53')
	);
	
	foreach ($lanes as $lane) {
		$html.='
				<fieldset>
					<label>'.$lane['Name'].'</label>
					<input checked name="lanes[]" type="checkbox" value="'.$lane['IP'].'"/>
				</fieldset>';
	}
	
	$html.='
				<fieldset>
					<input type="hidden" name="t" value="'.$name.'"/>
					<input type="submit"/>
				</fieldset>
			</form>
		<p class="status">';
	
	$link=mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
	if ($link) {
		$query='SELECT `synchronizationLog`.`datetime` FROM `is4c_log`.`synchronizationLog` WHERE `synchronizationLog`.`name`=\''.$name.'\' AND `synchronizationLog`.`status`=1 ORDER BY `synchronizationLog`.`datetime` DESC LIMIT 1';
		$result=mysql_query($query, $link);
		if ($result && mysql_num_rows($result)==1) {
			$row=mysql_fetch_array($result);
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
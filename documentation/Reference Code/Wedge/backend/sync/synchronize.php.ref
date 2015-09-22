<?php
	require_once($_SERVER["DOCUMENT_ROOT"]."/define.conf");
	require_once($_SERVER["DOCUMENT_ROOT"].'/src/htmlparts.php');
	
	// TODO - This could be moved to a table or handled better
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
			<p class="status">';
	
	$link=mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
	if ($link) {
		// TODO - Hanlde errors from make_synchronization_query better
		if ($type=='Products') {
			require_once('synchronizeproducts.php');
			$query=make_synchronization_query();
		}
		
		// TODO - This should happen before trying to connect to the server
		// TODO - Get the lane names involved
		if (isset($_REQUEST['lanes']) && count($_REQUEST['lanes']>0)) {
			$synchronization_success=1;
			
			foreach ($_REQUEST['lanes'] as $lane_ip) {
				$lane_success=1;

				if ($type=='Products') {
					// Will send an E_WARNING on failure
					$link=mysql_connect($lane_ip, $_SESSION["mUser"], $_SESSION["mPass"]);
					if ($link) {
						$result=mysql_query($query, $link);
						if ($result) {
							$html.=$lane_ip.' synchronized</p>
			<p class="status">';
						} else {
							$html.=$lane_ip.' failed ('.mysql_error($link).')</p>
			<p class="status">';
							$synchronization_success=0;
						}
					} else {
						$html.='Unable to connect to '.$lane_ip.'</p>
			<p class="status">';
						$synchronization_success=0;
					}
				} else {
					// TODO - Set path to mysqldump in define.conf?
					// TODO - Find quick ways to test the connectivity of a lane instead of relying on long timeouts
					$sync='mysqldump -u '.$_SESSION['mUser'].' '.($_SESSION['mPass']?' -p'.$_SESSION['mPass']:'').' is4c_op '.$name.' | mysql -u '.$_SESSION['mUser'].' '.($_SESSION['mPass']?' -p'.$_SESSION['mPass']:'').' -h '.$lane_ip.' is4c_op 2>&1';
					exec($sync, $result);
					foreach ($result as $msg) {
						if ($msg && strlen($msg)>0) {
							$html.=$lane_ip.' failed ('.$msg.')</p>
			<p class="status">';
							$lane_success=0;
							$synchronization_success=0;
						}
					}
				
					if ($lane_success) {
						$html.=$lane_ip.' synchronized</p>
			<p class="status">';
					}
				}
			}
			
			$link=mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
			$query='INSERT INTO `is4c_log`.`synchronizationLog` (`id`,`name`,`datetime`,`status`,`ip`) VALUES (NULL,\''.$name.'\',NOW(),'.$synchronization_success.',\''.$_SERVER["REMOTE_ADDR"].'\')';
			$result=mysql_query($query);
			if ($result) {
				// Do we need to know?
			} else {
				// Do we need to know?
			}
		} else {
			$html.='Not sure which lanes to synchronize.';
		}
	} else {
		$html.='Unable to connect to main server.';		
	}
	
	$html.='</p>
		</div>';
	
	$html.=foot();
	
	$html.='
	</body>
</html>';
	
	print_r($html);
?>
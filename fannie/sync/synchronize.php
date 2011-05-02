<?php
	require('../config.php');
	require_once($FANNIE_ROOT.'src/htmlparts.php');
	include($FANNIE_ROOT.'src/trans_connect.php');
	
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
	
	if ($$dbc->connections[$FANNIE_TRANS_DB] != false) {
		// TODO - Hanlde errors from make_synchronization_query better
		if ($type=='Products') {
			require_once('synchronizeproducts.php');
			$query=make_synchronization_query();
		}
		
		// TODO - This should happen before trying to connect to the server
		// TODO - Get the lane names involved
		if (isset($_REQUEST['lanes']) && count($_REQUEST['lanes']>0)) {
			$synchronization_success=1;
			
			// hack: lookup lane in config based on IP
			$ln = array();
			foreach($FANNIE_LANES as $l){
				if ($lane_ip == $l['host']){
					$ln = $l;
					break;
				}
			}

			foreach ($_REQUEST['lanes'] as $lane_ip) {
				$lane_success=1;

				if ($type=='Products') {
					// Will send an E_WARNING on failure
					$link=new SQLManager($ln['host'],$ln['type'],$ln['op'],$ln['user'],$ln['pw']);
					if ($link->connections[$ln['op']] != false) {
						$result=$dbc->query($query);
						if ($result) {
							$html.=$lane_ip.' synchronized</p>
			<p class="status">';
						} else {
							$html.=$lane_ip.' failed ('.$link->error().')</p>
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

					$sync = sprintf('mysqldump -u %s %s -h %s %s %s | mysql -u %s %s -h %s %s 2>&1',
						$FANNIE_SERVER_USER,(!empty($FANNIE_SERVER_PW)?"-p".$FANNIE_SERVER_PW:''),
						$FANNIE_SERVER,$FANNIE_OP_DB,$name,
						$ln['user'],(!empty($ln['pw'])?"-p".$ln['pw']:''),
						$ln['host'],$ln['op']);
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
			
			$query='INSERT INTO synchronizationLog (id,name,datetime,status,ip) VALUES (NULL,\''.$name.'\','.$dbc->now().','.$synchronization_success.',\''.$_SERVER["REMOTE_ADDR"].'\')';

			$result=$dbc->query($query);
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

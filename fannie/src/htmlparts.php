<?php
	function head() {
		global $FANNIE_URL;
		return '
		<link href="'.$FANNIE_URL.'src/screen.css" media="screen" rel="stylesheet" type="text/css"/>';
	}
	
	function body() {
		global $FANNIE_URL;
		return '
		<div id="page_top"><a class="a_unstyled" href="'.$FANNIE_URL.'">IS4C Maintenance &amp; Reporting</a></div>
		<div id="page_nav">
			<ul>
				<li><a href="'.$FANNIE_URL.'item">Item Maintenance</a></li>
				<li><a href="'.$FANNIE_URL.'batch">Sale Batches</a></li>
				<li><a href="'.$FANNIE_URL.'label">Label Maker</a></li>
				<li>Reports</li>
				<li>Dayend Balancing</li>
				<li><a href="'.$FANNIE_URL.'sync">Synchronization</a></li>
				<li><a href="'.$FANNIE_URL.'admin">Admin</a></li>
			</ul>
		</div>';
	}
	
	function foot() {
		return '
		<div id="page_foot">
			<p class="p_status">'.$_SERVER['REMOTE_ADDR'].'</p>
		</div>';
	}
?>

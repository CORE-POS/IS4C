<?php
	function head() {
		return '
		<link href="/src/screen.css" media="screen" rel="stylesheet" type="text/css"/>';
	}
	
	function body() {
		return '
		<div id="page_top"><a class="a_unstyled" href="/">IS4C Maintenance &amp; Reporting</a></div>
		<div id="page_nav">
			<ul>
				<li><a href="/item">Item Maintenance</a></li>
				<li><a href="/batch">Sale Batches</a></li>
				<li><a href="/label">Label Maker</a></li>
				<li>Reports</li>
				<li>Dayend Balancing</li>
				<li><a href="/sync">Synchronization</a></li>
				<li><a href="/admin">Admin</a></li>
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

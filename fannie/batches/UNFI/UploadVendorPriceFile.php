<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/* configuration for your module - Important */
include("../../config.php");
include($FANNIE_ROOT.'classlib2.0/FanniePage.php');
include($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');

class UploadVendorPriceFile extends FanniePage {
	/* html header, including navbar */
	protected $title = "Fannie - Upload Price File";
	protected $header = "Upload Price File";

	function body_content(){
		global $FANNIE_URL, $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);
		$p = $dbc->prepare_statement('SELECT vendorID,vendorName FROM vendors ORDER BY vendorName');
		$r = $dbc->exec_statement($p);
		$ret = '<b>Use the Default import tool</b>:<br /><ul>';
		while($w = $dbc->fetch_row($r)){
			$ret .= sprintf('<li><a href="%sitem/vendors/DefaultUploadPage.php?vid=%d">Upload %s Price File</a>',
				$FANNIE_URL,$w['vendorID'],$w['vendorName']);
		}
		$ret .= '</ul>';
		$ret .= '<hr />';
		$ret .= '<b>Use a Custom import tool</b>:<br /><ul>';
		$files = scandir('load-classes');
		foreach($files as $f){
			if($f[0] == '.') continue;
			if (substr($f,-4) != '.php') continue;
			$ret .= sprintf('<li><a href="load-classes/%s">%s</a></li>',
					$f,substr($f,0,strlen($f)-4));
		}
		$ret .= '</ul>';
		return $ret;
	}

}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)){
	$obj = new UploadVendorPriceFile();
	$obj->draw_page();
}

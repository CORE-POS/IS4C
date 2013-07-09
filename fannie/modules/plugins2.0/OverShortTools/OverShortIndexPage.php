<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class OverShortIndexPage extends FanniePage {

	protected $header = 'Over Short Tools';
	protected $title = 'Over Short Tools';
	protected $auth_classes = array('overshorts');

	function body_content(){
		ob_start();
		?>
		<ul>
		<li><a href="OverShortCashierPage.php">Single Cashier O/S</a></li>
		<li><a href="OverShortDayPage.php">Whole Day O/S</a></li> 
		<li><a href="OverShortSafecountPage.php">Safe Count</a></li> 
		<li><a href="OverShortDepositSlips.php">Deposit Slips</a></li> 
		</ul>
		<?php
		return ob_get_clean();
	}
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])){
	$obj = new OverShortIndexPage();
	$obj->draw_page();
}

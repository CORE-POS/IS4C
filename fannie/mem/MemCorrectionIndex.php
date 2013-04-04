<?php
/*******************************************************************************

    Copyright 2007 Alberta Cooperative Grocery, Portland, Oregon.

    This file is part of Fannie.

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
// A page to search the member base.
include('../config.php');
include($FANNIE_ROOT.'classlib2.0/FanniePage.php');
include($FANNIE_ROOT.'classlib2.0/lib/FormLib.php');

class MemCorrectionIndex extends FanniePage {

	protected $title='Fannie - Member Management Module';
	protected $header='Make Member Corrections';

	private $msgs = '';

	function body_content(){
		ob_start();
		?>
		<ul>
		<li><a href="correction_pages/MemEquityTransferTool.php">Equity Transfer</a></li>
		<li><a href="correction_pages/MemArTransferTool.php">AR Transfer</a></li>
		<li><a href="correction_pages/MemArEquitySwapTool.php">AR/Equity Swap</a></li>
		<li><a href="correction_pages/MemArEquityDumpTool.php">Remove AR/Equity</a></li>
		<li><a href="correction_pages/PatronageTransferTool.php">Transfer Patronage</a></li>
		</ul>
		<?php
		return $this->msgs.ob_get_clean();
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)){
	$obj = new MemCorrectionIndex();
	$obj->draw_page();
}

?>

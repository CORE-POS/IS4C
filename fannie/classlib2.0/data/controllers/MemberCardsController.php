<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

/**
  @class MemberCardsController

*/

if (!class_exists('FannieDB'))
	include(dirname(__FILE__).'/../FannieDB.php');

class MemberCardsController {
	
	/**
	  Update memberCards record for an account
	  @param $card_no the member number
	  @param $upc the barcode
	*/
	public static function update($card_no,$upc){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);
	
		$delP = $dbc->prepare_statement("DELETE FROM memberCards WHERE card_no=?");
		$delR = $dbc->exec_statement($delP,array($card_no));

		/** don't create entry w/o UPC */
		if ($upc != ''){
			$upc = str_pad($upc,13,'0',STR_PAD_LEFT);
			$insP = $dbc->prepare_statement("INSERT INTO memberCards (card_no, upc)
					VALUES (?, ?)");
			$insR = $dbc->exec_statement($insP,array($card_no,$upc));
			return $insR;
		}
		else return $delR;
	}

}

?>

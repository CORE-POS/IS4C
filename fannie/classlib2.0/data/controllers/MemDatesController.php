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
  @class MemDatesController

*/

if (!class_exists('FannieDB'))
	include(dirname(__FILE__).'/../FannieDB.php');

class MemDatesController {
	
	/**
	  Update memDates record for an account
	  @param $card_no the member number
	  @param $start the starting date
	  @param $end the ending date
	*/
	public static function update($card_no,$start,$end){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);
		self::init_record($dbc,$card_no);

		$upP = $dbc->prepare_statement("UPDATE memDates SET start_date=?,
				end_date=? WHERE card_no=?");
		$upR = $dbc->exec_statement($upP, array($start,$end,$card_no));

		return $upR;
	}

	private static function init_record($dbc,$card_no){
		$q = $dbc->prepare_statement("SELECT card_no FROM memDates WHERE card_no=?");
		$r = $dbc->exec_statement($q,array($card_no));

		if ($dbc->num_rows($r) == 0){
			$ins = $dbc->preparse_statement("INSERT INTO memDates (card_no,
				start_date,end_date) VALUES (?, NULL, NULL)");
			$dbc->exec_statement($ins,array($card_no));
		}
	}

}

?>

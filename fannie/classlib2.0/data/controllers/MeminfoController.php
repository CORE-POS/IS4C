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
  @class MeminfoController

*/

if (!class_exists('FannieDB'))
	include(dirname(__FILE__).'/../FannieDB.php');

class MeminfoController {
	
	/**
	  Update meminfo record for an account
	  @param $card_no the member number
	  @param $fields array of column names and values
	*/
	public static function update($card_no,$fields){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);
		self::init_record($dbc,$card_no);

		$upQ = "UPDATE meminfo SET ";
		$args = array();
		foreach($fields as $name=>$value){
			switch($name){
			case 'street':
			case 'city':
			case 'state':
			case 'zip':
			case 'phone':
			case 'email_1':
			case 'email_2':
			case 'ads_OK':
				if ($name === 0 || $name === True)
					break; // switch does loose comparison...
				$upQ .= $name." = ?,";
				$args[] = $value;
				break;
			default:
				break;
			}
		}
		if ($upQ == "UPDATE meminfo SET ") return True; // nothing to update 

		$upQ = rtrim($upQ,",");
		$upQ .= ' WHERE card_no=?';
		$args[] = $card_no;
		$upP = $dbc->prepare_statement($upQ);
		$upR = $dbc->exec_statement($upP, $args);

		return $upR;
	}

	private static function init_record($dbc,$card_no){
		$q = $dbc->prepare_statement("SELECT card_no FROM meminfo WHERE card_no=?");
		$r = $dbc->exec_statement($q,array($card_no));

		if ($dbc->num_rows($r) == 0){
			$ins = $dbc->prepare_statement("INSERT INTO meminfo (card_no,
				last_name,first_name,othlast_name,othfirst_name,street,
				city,state,zip,phone,email_1,email_2,ads_OK) VALUES
				(?,'','','','','','','','','','','',1)");
			$dbc->exec_statement($ins,array($card_no));
		}
	}

}

?>

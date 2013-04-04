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

if (!class_exists('FannieDB'))
	include(dirname(__FILE__).'/../FannieDB.php');
if (!class_exists('ProdUpdateController'))
	include(dirname(__FILE__).'/ProdUpdateController.php');

class ProductsController {

	/**
	  Update product record for a upc
	  New records are created automatically as needed
	  @param $upc the upc
	  @param $fields array of column names and values
	  @param $update_only don't add if the product doesn't exist
	*/
	public static function update($upc,$fields, $update_only=False){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);
		if (!is_numeric($upc))
			return False;
		if (!is_int($upc) && !ctype_digit($upc))
			return False;
		$upc = substr($upc,0,13);
		$upc = str_pad($upc,13,'0',STR_PAD_LEFT);

		$chkP = $dbc->prepare_statement("SELECT upc FROM products WHERE upc=?");
		$chkR = $dbc->exec_statement($chkP, array($upc));
		if ($dbc->num_rows($chkR) == 0)
			return ($update_only===False ? self::add($upc,$fields) : True);

		$valid_columns = $dbc->table_definition('products');

		$updateQ = "UPDATE products SET ";
		$updateArgs = array();
		foreach($fields as $name => $value){
			switch($name){
			case 'description':
			case 'normal_price':
			case 'pricemethod':
			case 'groupprice':
			case 'quantity':
			case 'special_price':
			case 'specialpricemethod':
			case 'specialgroupprice':
			case 'specialquantity':
			case 'start_date':
			case 'end_date':
			case 'department':
			case 'size':
			case 'tax':
			case 'foodstamp':
			case 'scale':
			case 'scaleprice':
			case 'mixmatchcode':
			case 'advertised':
			case 'tareweight':
			case 'discount':
			case 'discounttype':
			case 'unitofmeasure':
			case 'wicable':
			case 'qttyEnforced':
			case 'idEnforced':
			case 'cost':
			case 'inUse':
			case 'numflag':
			case 'subdept':
			case 'deposit':
			case 'local':
			case 'store_id':
				if ($name === 0 || $name === True)
					break; // switch does loose comparison...
				if (!isset($valid_columns[$name]))
					break; // table does not have that column
				$updateQ .= $name." = ?,";
				$updateArgs[] = $value;
				break;
			default:
				break;
			}
		}

		/** if only name fields were provided, there's
		    nothing to do here */
		if ($updateQ != "UPDATE products SET "){
			$updateQ .= 'modified='.$dbc->now();
			$updateQ .= " WHERE upc=?";
			$updateArgs[] = $upc;

			$updateP = $dbc->prepare_statement($updateQ);
			$updateR = $dbc->exec_statement($updateP,$updateArgs);
			if ($updateR === False) return False;
			ProdUpdateController::add($upc, $fields);
		}

		return True;
	}

	/**
	  Delete an item
	  @param $upc the upc
	*/
	public static function delete($upc){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);
		if (!is_numeric($upc))
			return False;
		if (!is_int($upc) && !ctype_digit($upc))
			return False;
		$upc = substr($upc,0,13);
		$upc = str_pad($upc,13,'0',STR_PAD_LEFT);

		$delP = $dbc->prepare_statement('DELETE FROM products WHERE upc=?');
		$delR = $dbc->exec_statement($delP,array($upc));
		if ($delR === False) return False;
		ProdUpdateController::add($upc,array('description'=>'_DELETED'));
		return True;	
	}

	/**
	  Create product record for a upc
	  @param $upc the upc
	  @param $fields array of column names and values
	*/
	private static function add($upc,$fields){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);		
		if (!is_numeric($upc))
			return False;
		if (!is_int($upc) && !ctype_digit($upc))
			return False;
		$upc = substr($upc,0,13);
		$upc = str_pad($upc,13,'0',STR_PAD_LEFT);
		
		$args = array();
		$q = 'INSERT INTO products ';

		$q .= '(upc,';
		$args[] = $upc;

		$q .= 'description,';
		$args[] = isset($fields['description'])?$fields['description']:'';

		$q .= 'normal_price,';
		$args[] = isset($fields['normal_price'])?$fields['normal_price']:0.00;

		$q .= 'pricemethod,';
		$args[] = isset($fields['pricemethod'])?$fields['pricemethod']:0;

		$q .= 'groupprice,';
		$args[] = isset($fields['groupprice'])?$fields['groupprice']:0.00;

		$q .= 'quantity,';
		$args[] = isset($fields['quantity'])?$fields['quantity']:0;

		$q .= 'special_price,';
		$args[] = isset($fields['special_price'])?$fields['special_price']:0.00;

		$q .= 'specialpricemethod,';
		$args[] = isset($fields['specialpricemethod'])?$fields['specialpricemethod']:0;

		$q .= 'specialgroupprice,';
		$args[] = isset($fields['specialgroupprice'])?$fields['specialgroupprice']:0.00;

		$q .= 'specialquantity,';
		$args[] = isset($fields['specialquantity'])?$fields['specialquantity']:0;

		$q .= 'department,';
		$args[] = isset($fields['department'])?$fields['department']:0;

		$q .= 'size,';
		$args[] = isset($fields['size'])?$fields['size']:'';

		$q .= 'tax,';
		$args[] = isset($fields['tax'])?$fields['tax']:0;

		$q .= 'foodstamp,';
		$args[] = isset($fields['foodstamp'])?$fields['foodstamp']:0;

		$q .= 'scale,';
		$args[] = isset($fields['scale'])?$fields['scale']:0;

		$q .= 'scaleprice,';
		$args[] = isset($fields['scaleprice'])?$fields['scaleprice']:0.00;

		$q .= 'mixmatchcode,';
		$args[] = isset($fields['mixmatchcode'])?$fields['mixmatchcode']:'';

		$q .= 'advertised,';
		$args[] = isset($fields['advertised'])?$fields['advertised']:1;

		$q .= 'tareweight,';
		$args[] = isset($fields['tareweight'])?$fields['tareweight']:0.00;

		$q .= 'discount,';
		$args[] = isset($fields['discount'])?$fields['discount']:0;

		$q .= 'discounttype,';
		$args[] = isset($fields['discounttype'])?$fields['discounttype']:0;

		$q .= 'unitofmeasure,';
		$args[] = isset($fields['unitofmeasure'])?$fields['unitofmeasure']:'';

		$q .= 'wicable,';
		$args[] = isset($fields['wicable'])?$fields['wicable']:0;

		$q .= 'qttyEnforced,';
		$args[] = isset($fields['qttyEnforced'])?$fields['qttyEnforced']:0;

		$q .= 'idEnforced,';
		$args[] = isset($fields['idEnforced'])?$fields['idEnforced']:0;

		$q .= 'cost,';
		$args[] = isset($fields['cost'])?$fields['cost']:0.00;

		$q .= 'inUse,';
		$args[] = isset($fields['inUse'])?$fields['inUse']:1;

		$q .= 'numflag,';
		$args[] = isset($fields['numflag'])?$fields['numflag']:0;

		$q .= 'subdept,';
		$args[] = isset($fields['subdept'])?$fields['subdept']:0;

		$q .= 'deposit,';
		$args[] = isset($fields['deposit'])?$fields['deposit']:0;

		$q .= 'local,';
		$args[] = isset($fields['local'])?$fields['local']:0;

		$q .= 'store_id,';
		$args[] = isset($fields['store_id'])?$fields['store_id']:0;

		$q .= 'modified) VALUES (';
		foreach($args as $a) $q .= '?,';
		$q .= $dbc->now().')';

		$insP = $dbc->prepare_statement($q);
		$insR = $dbc->exec_statement($insP, $args);
		if ($insR === False) return False;

		ProdUpdateController::add($upc, $fields);
		return True;
	}
}

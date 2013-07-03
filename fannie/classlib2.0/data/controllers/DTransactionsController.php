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

class DTransactionsController extends BasicController {

	protected $name = 'dtransactions';

	protected $columns = array(
	'datetime'	=> array('type'=>'DATETIME','index'=>True),
	'register_no'	=> array('type'=>'SMALLINT'),
	'emp_no'	=> array('type'=>'SMALLINT'),
	'trans_no'	=> array('type'=>'INT'),
	'upc'		=> array('type'=>'VARCHAR(13)','index'=>True),
	'description'	=> array('type'=>'VARCHAR(30)'),
	'trans_type'	=> array('type'=>'VARCHAR(1)','index'=>True),
	'trans_subtype'	=> array('type'=>'VARCHAR(2)'),
	'trans_status'	=> array('type'=>'VARCHAR(1)'),
	'department'	=> array('type'=>'SMALLINT','index'=>True),
	'quantity'	=> array('type'=>'DOUBLE'),
	'scale'		=> array('type'=>'TINYINT','default'=>0.00),
	'cost'		=> array('type'=>'MONEY'),
	'unitPrice'	=> array('type'=>'MONEY'),
	'total'		=> array('type'=>'MONEY'),
	'regPrice'	=> array('type'=>'MONEY'),
	'tax'		=> array('type'=>'SMALLINT'),
	'foodstamp'	=> array('type'=>'TINYINT'),
	'discount'	=> array('type'=>'MONEY'),
	'memDiscount'	=> array('type'=>'MONEY'),
	'discountable'	=> array('type'=>'TINYINT'),
	'discounttype'	=> array('type'=>'TINYINT'),
	'voided'	=> array('type'=>'TINYINT'),
	'percentDiscount'=> array('type'=>'TINYINT'),
	'ItemQtty'	=> array('type'=>'DOUBLE'),
	'volDiscType'	=> array('type'=>'TINYINT'),
	'volume'	=> array('type'=>'TINYINT'),
	'VolSpecial'	=> array('type'=>'MONEY'),
	'mixMatch'	=> array('type'=>'VARCHAR(13)'),
	'matched'	=> array('type'=>'SMALLINT'),
	'memType'	=> array('type'=>'TINYINT'),
	'staff'		=> array('type'=>'TINYINT'),
	'numflag'	=> array('type'=>'INT','default'=>0),
	'charflag'	=> array('type'=>'INT','default'=>''),
	'card_no'	=> array('type'=>'INT','index'=>True),
	'trans_id'	=> array('type'=>'TINYINT')
	);

	/**
	  Overriden to check multiple tables that should
	  all have identical or similar structure
	*/
	public function normalize($db_name){
		global $FANNIE_ARCHIVE_DB, $FANNIE_ARCHIVE_METHOD;
		$trans_adds = 0;
		$log_adds = 0;
		// check self first
		$chk = parent::normalize($db_name);
		if ($chk !== False) $trans_adds += $chk;
		
		$this->name = 'transarchive';
		$chk = parent::normalize($db_name);
		if ($chk !== False) $trans_adds += $chk;

		$this->name = 'suspended';
		$chk = parent::normalize($db_name);
		if ($chk !== False) $trans_adds += $chk;

		// if columns were added to any dtrans tables, go ahead and
		// verify the archive table(s) too.
		if ($trans_adds > 0){
			echo "Archive Issue: dtransactions structure changed\n";
			$this->connection = FannieDB::get($FANNIE_ARCHIVE_DB);
			if ($FANNIE_ARCHIVE_METHOD == 'partitions'){
				$this->name = 'bigArchive';
				parent::normalize($FANNIE_ARCHIVE_DB);
			}
			else {
				$pattern = '/^transArchive\d\d\d\d\d\d$/';
				$tables = $this->connection->get_tables($FANNIE_ARCHIVE_DB);
				foreach($tables as $t){
					if (preg_match($pattern,$t)){
						$this->name = $t;
						parent::normalize($FANNIE_ARCHIVE_DB);
					}
				}
			}
		}
	
		// move on to dlog views.
		// dlog_15 is used for detection since it's the only
		// actual table. datetime is swapped out for tdate
		// and trans_num is tacked on the end
		$this->name = 'dlog_15';
		unset($this->columns['datetime']);
		$tdate = array('tdate'=>array('type'=>'datetime','index'=>True));
		$trans_num = array('trans_num'=>array('type'=>'VARCHAR(25)'));
		$this->columns = $tdate + $this->columns + $trans_num;
		$chk = parent::normalize($db_name);
		if ($chk !== False) $log_adds += $chk;

		// rebuild views
		if ($log_adds > 0){
			echo "Archive Issue: dlog structure changed\n";
			echo "Recreate view: dlog\n";
			$this->normalize_log('dlog','dtransactions');
			echo "Recreate view: dlog_90_view\n";
			$this->normalize_log('dlog_90_view','transarchive');
			$this->connection = FannieDB::get($FANNIE_ARCHIVE_DB);
			if ($FANNIE_ARCHIVE_METHOD == 'partitions'){
				$this->normalize_log('dlogBig','bigArchive');
			}
			else {
				$pattern = '/^dlog\d\d\d\d\d\d$/';
				$tables = $this->connection->get_tables($FANNIE_ARCHIVE_DB);
				foreach($tables as $t){
					if (preg_match($pattern,$t)){
						echo "Recreate view: $t\n";
						$this->normalize_log($t, 'transArchive'.substr($t,4));
					}
				}
			}
		}
	}

	/**
	  Rebuild dlog style views
	  @param $view_name name of the view
	  @param $table_name underlying table

	  The view changes the column "datetime" to "tdate" and
	  adds a "trans_num" column. Otherwise it includes all
	  the columns from dtransactions. Columns "trans_type"
	  and "trans_subtype" still have translations to fix
	  older records but everyting else passes through as-is.
	*/
	private function normalize_log($view_name, $table_name){
		if ($this->connection->table_exists($view_name)){
			$sql = 'DROP VIEW '.$this->connection->identifier_escape($view_name);
			echo $sql."\n";
		}

		$sql = 'CREATE VIEW '.$this->connection->identifier_escape($view_name).' AS '
			.'SELECT '
			.$this->connection->identifier_escape('datetime').' AS '
			.$this->connection->identifier_escape('tdate').',';
		$c = $this->connection; // for more concise code below
		foreach($this->columns as $name => $definition){
			if ($name == 'datetime') continue;
			elseif ($name == 'tdate') continue;
			elseif ($name == 'trans_num'){
				// create trans_num field
				$sql .= $c->concat(
				$c->convert($c->identifier_escape('emp_no'),'char'),
				"'-'",
				$c->convert($c->identifier_escape('register_no'),'char'),
				"'-'",
				$c->convert($c->identifier_escape('trans_no'),'char'),
				''
				).' as trans_num';
			}
			elseif($name == 'trans_type'){
				// type conversion for old records. Newer coupon & discount
				// records should have correct trans_type when initially created
				$sql .= "CASE WHEN (trans_subtype IN ('CP','IC') OR upc like('%000000052')) then 'T' 
					WHEN upc = 'DISCOUNT' then 'S' else trans_type end as trans_type,\n";
			}
			elseif($name == 'trans_subtype'){
				// type conversion for old records. Probably WFC quirk that can
				// eventually go away entirely
				$sql .= "CASE WHEN upc = 'MAD Coupon' THEN 'MA' 
				   WHEN upc like('%00000000052') THEN 'RR' ELSE trans_subtype END as trans_subtype,\n";
			}
			else {
				$sql .= $c->identifier_escape($name).",\n";
			}
		}
		$sql .= ' FROM '.$c->identifier_escape($table_name)
			.' WHERE '.$c->identifier_escape('trans_status')
			." NOT IN ('D','X','Z') AND emp_no <> 9999
			AND register_no <> 99";
		echo $sql."\n";
	}

	/* START ACCESSOR FUNCTIONS */

	public function datetime(){
		if(func_num_args() == 0){
			if(isset($this->instance["datetime"]))
				return $this->instance["datetime"];
			elseif(isset($this->columns["datetime"]["default"]))
				return $this->columns["datetime"]["default"];
			else return null;
		}
		else{
			$this->instance["datetime"] = func_get_arg(0);
		}
	}

	public function register_no(){
		if(func_num_args() == 0){
			if(isset($this->instance["register_no"]))
				return $this->instance["register_no"];
			elseif(isset($this->columns["register_no"]["default"]))
				return $this->columns["register_no"]["default"];
			else return null;
		}
		else{
			$this->instance["register_no"] = func_get_arg(0);
		}
	}

	public function emp_no(){
		if(func_num_args() == 0){
			if(isset($this->instance["emp_no"]))
				return $this->instance["emp_no"];
			elseif(isset($this->columns["emp_no"]["default"]))
				return $this->columns["emp_no"]["default"];
			else return null;
		}
		else{
			$this->instance["emp_no"] = func_get_arg(0);
		}
	}

	public function trans_no(){
		if(func_num_args() == 0){
			if(isset($this->instance["trans_no"]))
				return $this->instance["trans_no"];
			elseif(isset($this->columns["trans_no"]["default"]))
				return $this->columns["trans_no"]["default"];
			else return null;
		}
		else{
			$this->instance["trans_no"] = func_get_arg(0);
		}
	}

	public function upc(){
		if(func_num_args() == 0){
			if(isset($this->instance["upc"]))
				return $this->instance["upc"];
			elseif(isset($this->columns["upc"]["default"]))
				return $this->columns["upc"]["default"];
			else return null;
		}
		else{
			$this->instance["upc"] = func_get_arg(0);
		}
	}

	public function description(){
		if(func_num_args() == 0){
			if(isset($this->instance["description"]))
				return $this->instance["description"];
			elseif(isset($this->columns["description"]["default"]))
				return $this->columns["description"]["default"];
			else return null;
		}
		else{
			$this->instance["description"] = func_get_arg(0);
		}
	}

	public function trans_type(){
		if(func_num_args() == 0){
			if(isset($this->instance["trans_type"]))
				return $this->instance["trans_type"];
			elseif(isset($this->columns["trans_type"]["default"]))
				return $this->columns["trans_type"]["default"];
			else return null;
		}
		else{
			$this->instance["trans_type"] = func_get_arg(0);
		}
	}

	public function trans_subtype(){
		if(func_num_args() == 0){
			if(isset($this->instance["trans_subtype"]))
				return $this->instance["trans_subtype"];
			elseif(isset($this->columns["trans_subtype"]["default"]))
				return $this->columns["trans_subtype"]["default"];
			else return null;
		}
		else{
			$this->instance["trans_subtype"] = func_get_arg(0);
		}
	}

	public function trans_status(){
		if(func_num_args() == 0){
			if(isset($this->instance["trans_status"]))
				return $this->instance["trans_status"];
			elseif(isset($this->columns["trans_status"]["default"]))
				return $this->columns["trans_status"]["default"];
			else return null;
		}
		else{
			$this->instance["trans_status"] = func_get_arg(0);
		}
	}

	public function department(){
		if(func_num_args() == 0){
			if(isset($this->instance["department"]))
				return $this->instance["department"];
			elseif(isset($this->columns["department"]["default"]))
				return $this->columns["department"]["default"];
			else return null;
		}
		else{
			$this->instance["department"] = func_get_arg(0);
		}
	}

	public function quantity(){
		if(func_num_args() == 0){
			if(isset($this->instance["quantity"]))
				return $this->instance["quantity"];
			elseif(isset($this->columns["quantity"]["default"]))
				return $this->columns["quantity"]["default"];
			else return null;
		}
		else{
			$this->instance["quantity"] = func_get_arg(0);
		}
	}

	public function scale(){
		if(func_num_args() == 0){
			if(isset($this->instance["scale"]))
				return $this->instance["scale"];
			elseif(isset($this->columns["scale"]["default"]))
				return $this->columns["scale"]["default"];
			else return null;
		}
		else{
			$this->instance["scale"] = func_get_arg(0);
		}
	}

	public function cost(){
		if(func_num_args() == 0){
			if(isset($this->instance["cost"]))
				return $this->instance["cost"];
			elseif(isset($this->columns["cost"]["default"]))
				return $this->columns["cost"]["default"];
			else return null;
		}
		else{
			$this->instance["cost"] = func_get_arg(0);
		}
	}

	public function unitPrice(){
		if(func_num_args() == 0){
			if(isset($this->instance["unitPrice"]))
				return $this->instance["unitPrice"];
			elseif(isset($this->columns["unitPrice"]["default"]))
				return $this->columns["unitPrice"]["default"];
			else return null;
		}
		else{
			$this->instance["unitPrice"] = func_get_arg(0);
		}
	}

	public function total(){
		if(func_num_args() == 0){
			if(isset($this->instance["total"]))
				return $this->instance["total"];
			elseif(isset($this->columns["total"]["default"]))
				return $this->columns["total"]["default"];
			else return null;
		}
		else{
			$this->instance["total"] = func_get_arg(0);
		}
	}

	public function regPrice(){
		if(func_num_args() == 0){
			if(isset($this->instance["regPrice"]))
				return $this->instance["regPrice"];
			elseif(isset($this->columns["regPrice"]["default"]))
				return $this->columns["regPrice"]["default"];
			else return null;
		}
		else{
			$this->instance["regPrice"] = func_get_arg(0);
		}
	}

	public function tax(){
		if(func_num_args() == 0){
			if(isset($this->instance["tax"]))
				return $this->instance["tax"];
			elseif(isset($this->columns["tax"]["default"]))
				return $this->columns["tax"]["default"];
			else return null;
		}
		else{
			$this->instance["tax"] = func_get_arg(0);
		}
	}

	public function foodstamp(){
		if(func_num_args() == 0){
			if(isset($this->instance["foodstamp"]))
				return $this->instance["foodstamp"];
			elseif(isset($this->columns["foodstamp"]["default"]))
				return $this->columns["foodstamp"]["default"];
			else return null;
		}
		else{
			$this->instance["foodstamp"] = func_get_arg(0);
		}
	}

	public function discount(){
		if(func_num_args() == 0){
			if(isset($this->instance["discount"]))
				return $this->instance["discount"];
			elseif(isset($this->columns["discount"]["default"]))
				return $this->columns["discount"]["default"];
			else return null;
		}
		else{
			$this->instance["discount"] = func_get_arg(0);
		}
	}

	public function memDiscount(){
		if(func_num_args() == 0){
			if(isset($this->instance["memDiscount"]))
				return $this->instance["memDiscount"];
			elseif(isset($this->columns["memDiscount"]["default"]))
				return $this->columns["memDiscount"]["default"];
			else return null;
		}
		else{
			$this->instance["memDiscount"] = func_get_arg(0);
		}
	}

	public function discountable(){
		if(func_num_args() == 0){
			if(isset($this->instance["discountable"]))
				return $this->instance["discountable"];
			elseif(isset($this->columns["discountable"]["default"]))
				return $this->columns["discountable"]["default"];
			else return null;
		}
		else{
			$this->instance["discountable"] = func_get_arg(0);
		}
	}

	public function discounttype(){
		if(func_num_args() == 0){
			if(isset($this->instance["discounttype"]))
				return $this->instance["discounttype"];
			elseif(isset($this->columns["discounttype"]["default"]))
				return $this->columns["discounttype"]["default"];
			else return null;
		}
		else{
			$this->instance["discounttype"] = func_get_arg(0);
		}
	}

	public function voided(){
		if(func_num_args() == 0){
			if(isset($this->instance["voided"]))
				return $this->instance["voided"];
			elseif(isset($this->columns["voided"]["default"]))
				return $this->columns["voided"]["default"];
			else return null;
		}
		else{
			$this->instance["voided"] = func_get_arg(0);
		}
	}

	public function percentDiscount(){
		if(func_num_args() == 0){
			if(isset($this->instance["percentDiscount"]))
				return $this->instance["percentDiscount"];
			elseif(isset($this->columns["percentDiscount"]["default"]))
				return $this->columns["percentDiscount"]["default"];
			else return null;
		}
		else{
			$this->instance["percentDiscount"] = func_get_arg(0);
		}
	}

	public function ItemQtty(){
		if(func_num_args() == 0){
			if(isset($this->instance["ItemQtty"]))
				return $this->instance["ItemQtty"];
			elseif(isset($this->columns["ItemQtty"]["default"]))
				return $this->columns["ItemQtty"]["default"];
			else return null;
		}
		else{
			$this->instance["ItemQtty"] = func_get_arg(0);
		}
	}

	public function volDiscType(){
		if(func_num_args() == 0){
			if(isset($this->instance["volDiscType"]))
				return $this->instance["volDiscType"];
			elseif(isset($this->columns["volDiscType"]["default"]))
				return $this->columns["volDiscType"]["default"];
			else return null;
		}
		else{
			$this->instance["volDiscType"] = func_get_arg(0);
		}
	}

	public function volume(){
		if(func_num_args() == 0){
			if(isset($this->instance["volume"]))
				return $this->instance["volume"];
			elseif(isset($this->columns["volume"]["default"]))
				return $this->columns["volume"]["default"];
			else return null;
		}
		else{
			$this->instance["volume"] = func_get_arg(0);
		}
	}

	public function VolSpecial(){
		if(func_num_args() == 0){
			if(isset($this->instance["VolSpecial"]))
				return $this->instance["VolSpecial"];
			elseif(isset($this->columns["VolSpecial"]["default"]))
				return $this->columns["VolSpecial"]["default"];
			else return null;
		}
		else{
			$this->instance["VolSpecial"] = func_get_arg(0);
		}
	}

	public function mixMatch(){
		if(func_num_args() == 0){
			if(isset($this->instance["mixMatch"]))
				return $this->instance["mixMatch"];
			elseif(isset($this->columns["mixMatch"]["default"]))
				return $this->columns["mixMatch"]["default"];
			else return null;
		}
		else{
			$this->instance["mixMatch"] = func_get_arg(0);
		}
	}

	public function matched(){
		if(func_num_args() == 0){
			if(isset($this->instance["matched"]))
				return $this->instance["matched"];
			elseif(isset($this->columns["matched"]["default"]))
				return $this->columns["matched"]["default"];
			else return null;
		}
		else{
			$this->instance["matched"] = func_get_arg(0);
		}
	}

	public function memType(){
		if(func_num_args() == 0){
			if(isset($this->instance["memType"]))
				return $this->instance["memType"];
			elseif(isset($this->columns["memType"]["default"]))
				return $this->columns["memType"]["default"];
			else return null;
		}
		else{
			$this->instance["memType"] = func_get_arg(0);
		}
	}

	public function staff(){
		if(func_num_args() == 0){
			if(isset($this->instance["staff"]))
				return $this->instance["staff"];
			elseif(isset($this->columns["staff"]["default"]))
				return $this->columns["staff"]["default"];
			else return null;
		}
		else{
			$this->instance["staff"] = func_get_arg(0);
		}
	}

	public function numflag(){
		if(func_num_args() == 0){
			if(isset($this->instance["numflag"]))
				return $this->instance["numflag"];
			elseif(isset($this->columns["numflag"]["default"]))
				return $this->columns["numflag"]["default"];
			else return null;
		}
		else{
			$this->instance["numflag"] = func_get_arg(0);
		}
	}

	public function charflag(){
		if(func_num_args() == 0){
			if(isset($this->instance["charflag"]))
				return $this->instance["charflag"];
			elseif(isset($this->columns["charflag"]["default"]))
				return $this->columns["charflag"]["default"];
			else return null;
		}
		else{
			$this->instance["charflag"] = func_get_arg(0);
		}
	}

	public function card_no(){
		if(func_num_args() == 0){
			if(isset($this->instance["card_no"]))
				return $this->instance["card_no"];
			elseif(isset($this->columns["card_no"]["default"]))
				return $this->columns["card_no"]["default"];
			else return null;
		}
		else{
			$this->instance["card_no"] = func_get_arg(0);
		}
	}

	public function trans_id(){
		if(func_num_args() == 0){
			if(isset($this->instance["trans_id"]))
				return $this->instance["trans_id"];
			elseif(isset($this->columns["trans_id"]["default"]))
				return $this->columns["trans_id"]["default"];
			else return null;
		}
		else{
			$this->instance["trans_id"] = func_get_arg(0);
		}
	}
	/* END ACCESSOR FUNCTIONS */
}

?>

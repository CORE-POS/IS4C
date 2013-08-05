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
  @class BasicModel
*/

class BasicModel {

	/**
	  Name of the table
	*/
	protected $name;

	/**
	  Definition of columns. Keyed by column name.
	  Values should be arrays with keys for:
	  - type (required)
	  - default (null if omitted)
	  - primary_key (optional, boolean)
	  - index (optional, boolean)
	  - increment (optional, boolean)
	*/
	protected $columns = array();

	/**
	  List of columns that should be unique
	  per record. Only necessary if the
	  table has no primary key.
	*/
	protected $unique = array();

	/**
	  Type transalations for different DB
	  backends.
	*/
	protected $meta_types = array(
		'MONEY' => array('default'=>'DECIMAL(10,2)','mssql'=>'MONEY')
	);

	/**
	  Database connection
	*/
	protected $connection = False;
	public function db(){ return $this->connection; }

	/**
	  List of column names => values
	*/
	protected $instance = array();

	/**
	  Name of preferred database
	*/
	protected $preferred_db = '';
	public function preferred_db(){ return $this->preferred_db; }

	/**
	  Constructor
	  @param $con a SQLManager object
	*/
	public function __construct($con){
		$this->connection = $con;
		if (empty($this->unique)){
			foreach($this->columns as $name=>$definition){
				if (isset($definition['primary_key']) && $definition['primary_key'])
					$this->unique[] = $name;
			}
		}
	}

	/**
	  Create the table
	  @return boolean
	*/
	public function create(){
		if ($this->connection->table_exists($this->name))
			return True;

		$dbms = $this->connection->dbms_name();
		$pkey = array();
		$indexes = array();
		$inc = False;
		$sql = 'CREATE TABLE '.$this->name.' (';
		foreach($this->columns as $cname => $definition){
			if (!isset($definition['type'])) return False;

			$sql .= $this->connection->identifier_escape($cname);	
			$sql .= ' ';

			$type = $definition['type'];
			if (isset($this->meta_types[strtoupper($type)]))
				$type = $this->get_meta($type, $dbms);
			$sql .= $type;

			if (isset($definition['increment']) && $definition['increment']){
				if ($dbms == 'mssql')
					$sql .= ' IDENTITY (1, 1) NOT NULL';
				else
					$sql .= ' NOT NULL AUTO_INCREMENT';
				$inc = True;
			}
			elseif (isset($definition['default']) && $definition['default']){
				if ($dbms == 'mssql')
					$sql .= ' '.$definition['default'];
				else
					$sql .= ' DEFAULT '.$definition['default'];
			}

			$sql .= ',';

			if (isset($definition['primary_key']) && $definition['primary_key'])
				$pkey[] = $cname;
			elseif (isset($definition['index']) && $definition['index'])
				$indexes[] = $cname;
		}

		if (!empty($pkey)){
			$sql .= ' PRIMARY KEY (';
			foreach($pkey as $col){
				$sql .= $this->connection->identifier_escape($col).',';
			}
			$sql = substr($sql,0,strlen($sql)-1).'),';
		}
		if (!empty($indexes)){
			foreach($indexes as $index){
				$sql .= ' INDEX (';
				$sql .= $this->connection->identifier_escape($index);
				$sql .= '),';
			}
		}

		$sql = rtrim($sql,',');
		$sql .= ')';
		if ($inc && $dbms == 'mssql')
			$sql .= ' ON [PRIMARY]';

		$prep = $this->connection->prepare_statement($sql);
		$result = $this->connection->exec_statement($prep);
		return ($result === False) ? False : True;
	}

	/**
	  Populate instance with database values
	  Requires a uniqueness constraint. Assign
	  those columns before calling load().
	  @return boolean
	*/
	public function load(){
		if (empty($this->unique)) return False;
		foreach($this->unique as $column){
			if (!isset($this->instance[$column]))
				return False;
		}

		$sql = 'SELECT ';
		foreach($this->columns as $name => $definition){
			$sql .= $this->connection->identifier_escape($name).',';
		}
		$sql = substr($sql,0,strlen($sql)-1);
		
		$sql .= ' FROM '.$this->name.' WHERE 1=1';
		$args = array();
		foreach($this->unique as $name){
			$sql .= ' AND '.$this->connection->identifier_escape($name).' = ?';
			$args[] = $this->instance[$name];
		}

		$prep = $this->connection->prepare_statement($sql);
		$result = $this->connection->exec_statement($prep, $args);

		if ($this->connection->num_rows($result) > 0){
			$row = $this->connection->fetch_row($result);
			foreach($this->columns as $name => $definition){
				if (!isset($row[$name])) continue;
				$this->instance[$name] = $row[$name];
			}
			return True;
		}
		else
			return False;
	}

	/**
	  Clear object values.
	*/
	public function reset(){
		$this->instance = array();
	}

	public function get_columns(){
		return $this->columns;
	}

	public function get_name(){
		return $this->name;
	}

	/**
	  Find records that match this instance
	  @param $sort array of columns to sort by
	  @return an array of controller objects
	*/
	public function find($sort=''){
		if (!is_array($sort)) $sort = array($sort);

		$sql = 'SELECT ';
		foreach($this->columns as $name => $definition){
			$sql .= $this->connection->identifier_escape($name).',';
		}
		$sql = substr($sql,0,strlen($sql)-1);
		
		$sql .= ' FROM '.$this->name.' WHERE 1=1';
		
		$args = array();
		foreach($this->instance as $name => $value){
			$sql .= ' AND '.$this->connection->identifier_escape($name).' = ?';
			$args[] = $value;
		}

		$order_by = '';
		foreach($sort as $name){
			if (!isset($this->columns[$name])) continue;
			$order_by .= $this->connection->identifier_escape($name).',';
		}
		if ($order_by !== ''){
			$order_by = substr($order_by,0,strlen($order_by)-1);
			$sql .= ' ORDER BY '.$order_by;
		}

		$prep = $this->connection->prepare_statement($sql);
		$result = $this->connection->exec_statement($prep, $args);

		$ret = array();
		$my_type = get_class($this);
		while($row = $this->connection->fetch_row($result)){
			$obj = new $my_type($this->connection);
			foreach($this->columns as $name => $definition){
				if (!isset($row[$name])) continue;
				$obj->$name($row[$name]);
			}
			$ret[] = $obj;
		}
		return $ret;
	}

	/**
	  Delete record from the database.
	  Requires a uniqueness constraint. Assign
	  those columns before calling delete().
	  @return boolean
	*/
	public function delete(){
		if (empty($this->unique)) return False;
		foreach($this->unique as $column){
			if (!isset($this->instance[$column]))
				return False;
		}

		$sql = 'DELETE FROM '.$this->name.' WHERE 1=1';
		$args = array();
		foreach($this->unique as $name){
			$sql .= ' AND '.$this->connection->identifier_escape($name).' = ?';
			$args[] = $this->instance[$name];
		}

		$prep = $this->connection->prepare_statement($sql);
		$result = $this->connection->exec_statement($prep, $args);
		return ($result === False) ? False : True;
	}

	/**
	  Get database-specific type
	  @param $type a "meta-type" with different underlying type
	    depending on the DB
	  @param $dbms string DB name
	  @return string
	*/
	protected function get_meta($type, $dbms){
		if (!isset($this->meta_types[strtoupper($type)])) return $type;
		$meta = $this->meta_types[strtoupper($type)];
		return isset($meta[$dbms]) ? $meta[$dbms] : $meta['default'];
	}

	/**
	  Save current record. If a uniqueness constraint
	  is defined it will INSERT or UPDATE appropriately.
	  @return SQL result object or boolean false
	*/
	public function save(){
		$new_record = False;
		// do we have values to look up?
		foreach($this->unique as $column){
			if (!isset($this->instance[$column]))
				$new_record = True;
		}
		if (count($this->unique) == 0)
			$new_record = True;

		if (!$new_record){
			// see if matching record exists
			$check = 'SELECT * FROM '.$this->connection->identifier_escape($this->name)
				.' WHERE 1=1';
			$args = array();
			foreach($this->unique as $column){
				$check .= ' AND '.$this->connection->identifier_escape($column).' = ?';
				$args[] = $this->instance[$column];
			}
			$prep = $this->connection->prepare_statement($check);
			$result = $this->connection->exec_statement($prep, $args);
			if ($this->connection->num_rows($result)==0)
				$new_record = True;
		}

		if ($new_record)
			return $this->insert_record();
		else
			return $this->update_record();
	}

	/**
	  Helper. Build & execute insert query
	  @return SQL result object or boolean false
	*/
	protected function insert_record(){
		$sql = 'INSERT INTO '.$this->connection->identifier_escape($this->name);
		$cols = '(';
		$vals = '(';
		$args = array();
		foreach($this->instance as $column => $value){
			if (isset($this->columns[$column]['increment']) && $this->columns[$column]['increment'])
				continue;
			$cols .= $this->connection->identifier_escape($column).',';
			$vals .= '?,';	
			$args[] = $value;
		}
		$cols = substr($cols,0,strlen($cols)-1).')';
		$vals = substr($vals,0,strlen($vals)-1).')';
		$sql .= ' '.$cols.' VALUES '.$vals;

		$prep = $this->connection->prepare_statement($sql);
		$result = $this->connection->exec_statement($prep, $args);
		return $result;
	}

	/**
	  Helper. Build & execute update query
	  @return SQL result object or boolean false
	*/
	protected function update_record(){
		$sql = 'UPDATE '.$this->connection->identifier_escape($this->name);
		$sets = '';
		$where = '1=1';
		$set_args = array();
		$where_args = array();
		foreach($this->instance as $column => $value){
			if (in_array($column, $this->unique)){
				$where .= ' AND '.$this->connection->identifier_escape($column).' = ?';
				$where_args[] = $value;
			}
			else {
				if (isset($this->columns[$column]['increment']) && $this->columns[$column]['increment'])
					continue;
				$sets .= ' '.$this->connection->identifier_escape($column).' = ?,';
				$set_args[] = $value;
			}
		}
		$sets = substr($sets,0,strlen($sets)-1);

		$sql .= ' SET '.$sets.' WHERE '.$where;
		$all_args = $set_args;
		foreach($where_args as $arg) $all_args[] = $arg;
		$prep = $this->connection->prepare_statement($sql);
		$result = $this->connection->exec_statement($prep, $all_args);
		return $result;
	}

	public function push_to_lanes(){
		global $FANNIE_LANES;
		// load complete record
		if (!$this->load()) return False;

		$current = $this->connection;
		// save to each lane
		foreach($FANNIE_LANES as $lane){
			$sql = new SQLManager($lane['host'],$lane['type'],$lane['op'],
						$lane['user'],$lane['pw']);	
			if (!is_object($sql) || $sql->connections[$lane['op']] === False)
				continue;
			$this->connection = $sql;
			$this->save();
		}
		$this->connection = $current;
		return True;
	}

	public function delete_from_lanes(){
		global $FANNIE_LANES;
		// load complete record
		if (!$this->load()) return False;

		$current = $this->connection;
		// save to each lane
		foreach($FANNIE_LANES as $lane){
			$sql = new SQLManager($lane['host'],$lane['type'],$lane['op'],
						$lane['user'],$lane['pw']);	
			if (!is_object($sql) || $sql->connections[$lane['op']] === False)
				continue;
			$this->connection = $sql;
			$this->delete();
		}
		$this->connection = $current;
		return True;
	}

	/**
	  Compare existing table to definition
	  Add any columns that are missing from the table structure
	  Extra columns that are present in the table but not in the
	  controlelr class are left as-is.
	  @param $db_name name of the database containing the table 
	  @param $preview_only boolean [default True] do not
	         make any changes
	  @return number of columns added or False on failure
	*/
	public function normalize($db_name, $preview_only=True){
		echo "==========================================\n";
		echo "Checking table $db_name.".$this->name."\n";
		echo "==========================================\n";
		$this->connection = FannieDB::get($db_name);
		if (!$this->connection->table_exists($this->name)){
			echo "No table found!\n";
			return False;
		}
		$current = $this->connection->table_definition($this->name);

		$new = array();
		$unknown = array();
		foreach ($this->columns as $col_name => $defintion){
			if (!in_array($col_name,array_keys($current))){
				$new[] = $col_name;
			}
		}
		foreach($current as $col_name => $type){
			if (!in_array($col_name,array_keys($this->columns))){
				$unknown[] = $col_name;
				echo "Ignoring unkown column: $col_name (delete manually if desired)\n";
			}
		}
		$our_columns = array_keys($this->columns);
		$their_columns = array_keys($current);
		for($i=0;$i<count($our_columns);$i++){
			if (!in_array($our_columns[$i],$new))
				continue; // column already exists
			echo "Adding column: {$our_columns[$i]}\n";
			$sql = '';
			foreach($their_columns as $their_col){
				if (isset($our_columns[$i-1]) && $our_columns[$i-1] == $their_col){
					$sql = 'ALTER TABLE '.$this->name.' ADD COLUMN '
						.$this->connection->identifier_escape($our_columns[$i]).' '
						.$this->get_meta($this->columns[$our_columns[$i]]['type'],
							$this->connection->dbms_name())
						.' AFTER '.$this->connection->identifier_escape($their_col);
					break;
				}
				elseif (isset($our_columns[$i+1]) && $our_columns[$i+1] == $their_col){
					$sql = 'ALTER TABLE '.$this->name.' ADD COLUMN '
						.$this->connection->identifier_escape($our_columns[$i]).' '
						.$this->get_meta($this->columns[$our_columns[$i]]['type'],
							$this->connection->dbms_name())
						.' BEFORE '.$this->connection->identifier_escape($their_col);
					break;
				}
				if (isset($our_columns[$i-1]) && in_array($our_columns[$i-1],$new)){
					$sql = 'ALTER TABLE '.$this->name.' ADD COLUMN '
						.$this->connection->identifier_escape($our_columns[$i]).' '
						.$this->get_meta($this->columns[$our_columns[$i]]['type'],
							$this->connection->dbms_name())
						.' AFTER '.$this->connection->identifier_escape($our_columns[$i-1]);
					break;
				}
			}
			if ($sql !== '') {
				if ($preview_only){
					echo "\tSQL Details: $sql\n";
				}
				else {
					$this->connection->query($sql);
				}
			}

			if ($sql === ''){
				echo "\tError: could not find context for {$our_columns[$i]}\n";
			}

			if ($sql !== '' && isset($this->columns[$our_columns[$i]]['index'])
				&& $this->columns[$our_columns[$i]]['index']){
				$index_sql = 'ALTER TABLE '.$this->name.' ADD INDEX '
						.$this->connections->identifier_escape($our_columns[$i])
						.' ('.$this->connections->identifier_escape($our_columns[$i]).')';
				if ($preview_only){
					echo "Adding index to column: {$our_columns[$i]}\n";
					echo "\tSQL Details: $index_sql\n";
				}
				else {
					$this->connection->query($index_sql);
				}
			}
		}
		echo "==========================================\n";
		echo "Check complete\n";
		echo "==========================================\n\n";

		return count($new) + count($unknown);
	}

	/**
	  Rewrite the given file to create accessor
	  functions for all of its columns
	*/
	public function generate($filename){
		$start_marker = '/* START ACCESSOR FUNCTIONS */';
		$end_marker = '/* END ACCESSOR FUNCTIONS */';
		$before = '';
		$after = '';
		$foundStart = False;
		$foundEnd = False;
		$fp = fopen($filename,'r');
		while(!feof($fp)){
			$line = fgets($fp);
			if (!$foundStart){
				$before .= $line;
				if (strstr($line,$start_marker))
					$foundStart = True;
			}
			elseif($foundStart && !$foundEnd){
				if (strstr($line, $end_marker)){
					$foundEnd = True;
					$after .= $line;
				}
			}
			elseif($foundStart && $foundEnd)
				$after .= $line;
		}
		fclose($fp);

		if (!$foundStart || !$foundEnd){
			echo "Error: could not locate code block\n";
			if (!$foundStart) echo "Missing start\n";
			if (!$foundEnd) echo "Missing end\n";
			return False;
		}

		$fp = fopen($filename,'w');
		fwrite($fp,$before);
		foreach($this->columns as $name => $definition){
			fwrite($fp,"\n");
			fwrite($fp,"\tpublic function ".$name."(){\n");
			fwrite($fp,"\t\tif(func_num_args() == 0){\n");
			fwrite($fp,"\t\t\t".'if(isset($this->instance["'.$name.'"]))'."\n");
			fwrite($fp,"\t\t\t\t".'return $this->instance["'.$name.'"];'."\n");
			fwrite($fp,"\t\t\t".'elseif(isset($this->columns["'.$name.'"]["default"]))'."\n");
			fwrite($fp,"\t\t\t\t".'return $this->columns["'.$name.'"]["default"];'."\n");
			fwrite($fp,"\t\t\telse return null;\n");
			fwrite($fp,"\t\t}\n");
			fwrite($fp,"\t\telse{\n");
			fwrite($fp,"\t\t\t".'$this->instance["'.$name.'"] = func_get_arg(0);'."\n");
			fwrite($fp,"\t\t}\n");
			fwrite($fp,"\t}\n");
		}
		fwrite($fp,$after);
		fclose($fp);

		return True;
	}

	function new_model($name){
		$fp = fopen($name.'.php','w');
		fwrite($fp,"<?php
/*******************************************************************************

    Copyright ".date("Y")." Whole Foods Co-op

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
  @class $name
*/
class $name extends BasicModel {\n");
		fwrite($fp,"\n");
		fwrite($fp,"\tprotected \$name = \"".substr($name,0,strlen($name)-5)."\";\n");
		fwrite($fp,"\n");
		fwrite($fp,"\tprotected \$columns = array(\n\t);\n");
		fwrite($fp,"\n");
		fwrite($fp,"\t/* START ACCESSOR FUNCTIONS */\n");
		fwrite($fp,"\t/* END ACCESSOR FUNCTIONS */\n");
		fwrite($fp,"}\n?>\n");
		fclose($fp);
	}

	function get_models(){
		/**
		  Experiment using lambdas. I was curious
		  if I could do recursion without having
		  a named function.
		*/
		$search = function($path) use (&$search){
			if (is_file($path) && substr($path,'-4')=='.php'){
				include_once($path);
				$class = basename(substr($path,0,strlen($path)-4));
				if (class_exists($class) && is_subclass_of($class, 'BasicModel'))
					return array($class);
			}
			elseif(is_dir($path)){
				$dh = opendir($path);
				$ret = array();	
				while( ($file=readdir($dh)) !== False){
					if ($file == '.' || $file == '..')
						continue;
					$ret = array_merge($ret, $search($path.'/'.$file));
				}
				return $ret;
			}
			return array();
		};
		$models = $search(dirname(__FILE__));
		return $models;
	}
}

if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)){

	$obj = new BasicModel(null);
	var_dump($obj->get_models());

	if (($argc < 2 || $argc > 4) || ($argc == 3 && $argv[1] != "--new") || ($argc == 4 && $argv[1] != '--update')){
		echo "Generate Accessor Functions: php BasicModel.php <Subclass Filename>\n";
		echo "Create new Model: php BasicModel.php --new <Model Name>\n";
		echo "Update Table Structure: php BasicModel.php --update <Database name> <Subclass Filename>\n";
		exit;
	}

	include(dirname(__FILE__).'/../../../config.php');
	include(dirname(__FILE__).'/../../FannieAPI.php');

	if ($argc == 3){
		$modelname = $argv[2];
		if (substr($modelname,-4) == '.php')
			$modelname = substr($modelname,0,strlen($modelname)-4);
		if (substr($modelname,-5) != 'Model')
			$modelname .= 'Model';
		echo "Generating Model '$modelname'\n";
		$obj = new BasicModel(null);
		$obj->new_model($modelname);
		exit;
	}

	$classfile = $argv[1];
	if ($argc == 4) $classfile = $argv[3];
	if (!file_exists($classfile)){
		echo "Error: file '$classfile' does not exist\n";
		exit;
	}

	$class = pathinfo($classfile, PATHINFO_FILENAME);
	include($classfile);
	if (!class_exists($class)){
		echo "Error: class '$class' does not exist\n";
		exit;
	}

	$obj = new $class(null);
	if (!is_a($obj, 'BasicModel')){
		echo "Error: invalid class. Must be BasicModel\n";
		exit;
	}

	if ($argc == 2){
		$try = $obj->generate($classfile);
		if ($try) echo "Generated accessor functions\n";
		else echo "Failed to generate functions\n";
	}
	else if ($argc == 4){
		$try = $obj->normalize($argv[2],True);
		if ($try !== False && $try > 0){
			while(True){
				echo 'Apply Changes [Y/n]: ';
				$in = rtrim(fgets(STDIN));
				if ($in === 'n' || $in === False || $in === '')
					break;
				elseif($in ==='Y'){
					// THIS WILL APPLY PROPOSED CHANGES!
					$obj->normalize($argv[2],False);
					break;
				}
			}
		}
	}
	exit;
}

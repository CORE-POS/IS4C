<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

/*
    con     => SQLManager object
    dbms        => type: mysql or mssql probably
    db_name     => user-defined database name
    table_name  => table/view to create
    stddb       => standardized database name for
               the sake of file paths
               'op', 'trans', or 'arch'
*/
function create_if_needed($con,$dbms,$db_name,$table_name,$stddb){
    $ret = array('db'=>$db_name,'struct'=>$table_name,'error'=>0,'error_msg'=>'');
    if ($con->table_exists($table_name,$db_name)) return $ret;
    
    $fn = dirname(__FILE__)."/sql/$stddb/$table_name.php";
    if (!file_exists($fn)){
        $ret['error_msg'] = "<i>Error: no create file for $stddb.$table_name.
            File should be: $fn</i><br />";
        $ret['error'] = 1;
        return $ret;
    }

    include($fn);
    if (!isset($CREATE["$stddb.$table_name"])){
        $ret['error_msg'] = "<i>Error: file $fn doesn't have a valid \$CREATE</i><br />";
        $ret['error'] = 2;
        return $ret;
    }

    $prep = $con->prepare_statement($CREATE["$stddb.$table_name"],$db_name);
    $result = $con->exec_statement($prep,array(),$db_name);
    if ($result === False){
        $ret['error_msg'] = $con->error($db_name);
        $ret['error'] = 3;
    }
    return $ret;
}

/* query to create another table with the same
    columns
*/
function duplicate_structure($dbms,$table1,$table2){
    if (strstr($dbms,"MYSQL")){
        return "CREATE TABLE `$table2` LIKE `$table1`";
    }
    elseif ($dbms == "MSSQL"){
        return "SELECT * INTO [$table2] FROM [$table1] WHERE 1=0";
    }
}

/**
  Remove a deprecated table/view
  @param $con SQLManager object
  @param $db_name string database name
  @param $table_name string table/view name
  @param $is_view boolean default true
  @return keyed array with any error info
*/
function dropDeprecatedStructure($con, $db_name, $table_name, $is_view=true)
{
    $ret = array('db'=>$db_name,'struct'=>$table_name,'error'=>0,'error_msg'=>'');

    if ($con->table_exists($table_name, $db_name)) {
        $dropQ = 'DROP '.($is_view ? 'VIEW' : 'TABLE').' '
                .$con->identifier_escape($table_name, $db_name);
        $result = $con->query($dropQ, $db_name);
        if ($result === false) {
            $ret['error_msg'] = $con->error($db_name);
            $ret['error'] = 3;
        }
    }

    return $ret;
}

function ar_departments(){
    global $FANNIE_AR_DEPARTMENTS;
    $ret = preg_match_all("/[0-9]+/",$FANNIE_AR_DEPARTMENTS,$depts);
    if ($ret != 0){
        /* AR departments exist */
        $depts = array_pop($depts);
        $dlist = "(";
        foreach ($depts as $d){
            $dlist .= $d.",";   
        }
        $dlist = substr($dlist,0,strlen($dlist)-1).")";
        return $dlist;
    }
    return "";
}

function equity_departments(){
    global $FANNIE_EQUITY_DEPARTMENTS;
    $ret = preg_match_all("/[0-9]+/",$FANNIE_EQUITY_DEPARTMENTS,$depts);
    if ($ret != 0){
        /* equity departments exist */
        $depts = array_pop($depts);
        $dlist = "(";
        foreach ($depts as $d){
            $dlist .= $d.",";   
        }
        $dlist = substr($dlist,0,strlen($dlist)-1).")";
        return $dlist;
    }
    return "";
}

function qualified_names(){
    global $FANNIE_SERVER_DBMS,$FANNIE_OP_DB,$FANNIE_TRANS_DB;

    $ret = array("op"=>$FANNIE_OP_DB,"trans"=>$FANNIE_TRANS_DB);
    if ($FANNIE_SERVER_DBMS == "MSSQL"){
        $ret["op"] .= ".dbo";
        $ret["trans"] .= ".dbo";
    }
    return $ret;
}

function loaddata($sql, $table) 
{
    global $FANNIE_ROOT, $FANNIE_SERVER;
    $loaded = 0;
    $success = true;
    if (file_exists("{$FANNIE_ROOT}install/sample_data/$table.sql")){
        echo " from $table.sql<br>\n";
        $fp = fopen("{$FANNIE_ROOT}install/sample_data/$table.sql","r");
        while($line = fgets($fp)){
            $prep = $sql->prepare_statement("INSERT INTO $table VALUES $line");
            $try = $sql->exec_statement($prep);
            if ($try === false) {
                $error = $sql->error();
                $success = false;
                echo "<br><small style='color:red;'>"
                    . (strlen($error)? $error : 'Unknown error')
                    . " executing:<br><code>{$query}</code></small><br>\n";
            } else {
                if (++$loaded % 100 === 0) {
                    echo "<br>\n";
                    flush();
                }
                echo ".";
            }
        }
        fclose($fp);
    } else if (file_exists("{$FANNIE_ROOT}install/sample_data/$table.csv")) {
        echo " from $table.csv ";
        $LOCAL = 'LOCAL';
        if ($FANNIE_SERVER == '127.0.0.1' || $FANNIE_SERVER == 'localhost')
            $LOCAL = '';
        $prep = $sql->prepare_statement("LOAD DATA $LOCAL INFILE
            '{$FANNIE_ROOT}install/sample_data/$table.csv'
            INTO TABLE $table
            FIELDS TERMINATED BY ','
            ESCAPED BY '\\\\'
            OPTIONALLY ENCLOSED BY '\"'
            LINES TERMINATED BY '\\r\\n'");
        $try = $sql->exec_statement($prep);
        if ($try === false) {
            $error = $sql->error();
            echo "<br><span style='color:red;'>"
                . (strlen($error)? $error : 'Unknown error')
                . " executing:<br><code>{$query}</code><br></span><br>\n";
        }
        /** alternate implementation
            for non-mysql and/or LOAD DATA LOCAL
            not allowed */
        if ($try !== false) {
            echo " succeeded!<br>\n";
            $loaded = 'All';
        } else {
            echo " line-by-line<br>\n";
            $fp = fopen("{$FANNIE_ROOT}install/sample_data/$table.csv",'r');
            $stmt = false;
            while(!feof($fp)){
                $line = fgetcsv($fp);
                if (!is_array($line)) continue;
                if ($stmt === False){
                    $query = 'INSERT INTO '.$table.' VALUES (';
                    foreach($line as $field)
                        $query .= '?,';
                    $query = substr($query,0,strlen($query)-1).')';
                    $stmt = $sql->prepare_statement($query);
                }
                $try = $sql->exec_statement($stmt, $line);
                if ($try === false) {
                    $error = $sql->error();
                    $success = false;
                    echo "<br><span style='color:red;'>"
                        . (strlen($error)? $error : 'Unknown error')
                        . " executing:<br><code>{$query}</code><br>("
                        . "'" . join("', '", $line) . "')"
                        . ' [' . count($line) . ' operands]'
                        . "</span><br>\n";
                } else {
                    if (++$loaded % 100 === 0) {
                        echo "<br>\n";
                        flush();
                    }
                    echo ".";
                }
            }
            fclose($fp);
        }
    } else {
        echo "<br><span style='color:red;'>Table data not found in either {$table}.sql or {$table}.csv</span><br>\n";
    }

    echo ($success? ' success!' : "<br>\n'$table' load " . ($loaded? 'partial success;' : 'failed;'))
        . " $loaded " . ($loaded == 1? 'record was' : 'records were') . " loaded.<br>\n";

    return $success;
}

?>

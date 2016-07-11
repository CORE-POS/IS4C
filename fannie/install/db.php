<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/* query to create another table with the same
    columns
*/
function duplicate_structure($dbms,$table1,$table2){
    if (strstr($dbms,"MYSQL") || $dbms == 'PDO'){
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
    $ret = array(
        'db'=>$db_name,
        'struct'=>$table_name,
        'error'=>0,
        'error_msg'=>'',
        'deprecated'=>true,
    );

    // SQLManager can actually check this now
    $is_view = $con->isView($table_name, $db_name);

    if ($con->table_exists($table_name, $db_name)) {
        $dropQ = 'DROP '.($is_view ? 'VIEW' : 'TABLE').' '
                .$con->identifierEscape($table_name, $db_name);
        $result = $con->query($dropQ, $db_name);
        if ($result === false) {
            $ret['error_msg'] = $con->error($db_name);
            $ret['error'] = 3;
        }
    }

    return $ret;
}


<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

if (!chdir("CompressProdUpdate")){
    echo "Error: Can't find directory (archive prod update)";
    exit;
}

include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');

/* HELP

   This script dumps prodUpdate into an archive
   table and truncates it. Keeping prodUpdate
   small makes scanning it for interesting changes
   a faster process.

   This should be called *after* any other compress 
   scripts.
*/

set_time_limit(0);
ini_set('memory_limit','256M');

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$matching = $sql->matchingColumns('prodUpdate', 'prodUpdateArchive');
$col_list = '';
foreach($matching as $column) {
    if ($column == 'prodUpdateID') {
        continue;
    }
    $col_list .= $column . ',';
}
$col_list = substr($col_list, 0, strlen($col_list)-1);

$worked = $sql->query("INSERT INTO prodUpdateArchive ($col_list) SELECT $col_list FROM prodUpdate");
if ($worked){
    $sql->query("TRUNCATE TABLE prodUpdate");
}
else {
    echo "There was an archiving error on prodUpdate\n";
    flush();
}

?>

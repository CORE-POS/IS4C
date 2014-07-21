<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

require('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

$page_title = 'Fannie - Sale Signs';
$header = 'Sale Signs';
include($FANNIE_ROOT.'src/header.html');

if (!isset($_REQUEST['signtype'])){
    echo '<ul>';
    $dh = opendir('enabled');
    while(($file=readdir($dh)) !== False){
        if ($file[0] == ".") continue;
        if (substr($file,-4) != ".php") continue;
        printf('<li><a href="index.php?action=start&signtype=%s">%s</a></li>',
            substr($file,0,strlen($file)-4),
            substr($file,0,strlen($file)-4)
        );
    }
    echo '</ul>';
}
else {
    $class = $_REQUEST['signtype'];
    include('enabled/'.$class.'.php');
    $obj = new $class();
}


include($FANNIE_ROOT.'src/footer.html');
?>

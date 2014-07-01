<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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

include('../../../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/Credentials/OutsideDB.is4c.php');

$thisMonth = date('n');
while(($thisMonth-1)%3 != 0) $thisMonth--;
$qStart = sprintf("%d-%02d-01 00:00:00",date("Y"),$thisMonth);

$thisMonth = date('n');
while(($thisMonth)%3 != 0) $thisMonth++;
$qEnd = sprintf("%d-%02d-%d 23:59:59",date("Y"),$thisMonth,date('j',mktime(0,0,0,$thisMonth+1,0,2000)));

if (isset($_REQUEST['upc'])){
    $q = $dbc->prepare_statement("SELECT d.datetime,d.upc,p.description,
        u.name,u.real_name,d.quantity 
        FROM dtransactions AS d
        LEFT JOIN productUser AS p ON d.upc=p.upc 
        LEFT JOIN Users AS u ON d.emp_no=u.uid
        WHERE trans_type='I' AND datetime BETWEEN ? AND ?
        AND d.upc=?");
    $r = $dbc->exec_statement($q,array($qStart,$qEnd,$_REQUEST['upc']));
    $rc = 0;
    while($w = $dbc->fetch_row($r)){
        if ($rc==0){
            printf('Sales for %s (%s) this quarter (%s to %s)',
                $w['description'],$w['upc'],$qStart,$qEnd);
            echo '<table cellspacing="0" cellpadding="4" border="1">
                <tr><th>Email</th><th>Name</th><th>Qty Sold</th></tr>';
        }
        printf('<tr><td>%s</td><td>%s</td><td>%d</td></tr>',
            $w['name'],$w['real_name'],$w['quantity']);
        $rc++;
    }
    echo '</table>';
}
else {
    echo 'Classes sold this quarter ('.$qStart.' to '.$qEnd.')';

    $q = $dbc->prepare_statement("SELECT d.upc,p.description,sum(d.quantity) FROM dtransactions AS d
        LEFT JOIN productUser AS p ON d.upc=p.upc 
        WHERE trans_type='I' AND datetime BETWEEN ? AND ?
        GROUP BY d.upc,p.description ORDER BY p.description");
    $r = $dbc->exec_statement($q,array($qStart,$qEnd));
    echo '<table cellspacing="0" cellpadding="4" border="1">
        <tr><th>UPC</th><th>Class</th><th>Qty Sold</th></tr>';
    while($w = $dbc->fetch_row($r)){
        printf('<tr><td><a href="?upc=%s">%s</a></td><td>%s</td><td>%d</td></tr>',
            $w[0],$w[0],$w[1],$w[2]);
    }
    echo '</table>';
}
?>

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

include('../../../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

if (isset($_REQUEST['submit'])){
    if (isset($_REQUEST['excel'])){
        header("Content-Disposition: inline; filename=voterlist_".date("Y-m-d").".xls");
        header("Content-type: application/vnd.ms-excel; name='excel'");
        ob_start();
    }
    else{
        printf("<a href=index.php?submit=yes&excel=yes>Save to Excel</a>");
    }

    $q = $dbc->prepare_statement("SELECT c.CardNo,c.FirstName,c.LastName,
        m.street,m.city,m.state,m.zip FROM
        custdata AS c LEFT JOIN meminfo AS m
        ON c.CardNo=m.card_no
        WHERE personNum=1 AND Type='PC'
        AND memType IN (1,3)
        AND c.LastName <> 'NEW MEMBER'
        ORDER BY c.CardNo");
    $r = $dbc->exec_statement($q);

    echo '<table cellspacing="0" cellpadding="4" border="1">';
    //echo '<tr><th>Username</th><th>Password</th>';
    echo '<tr><th>First Line</th><th>FN</th>';
    echo '<th>LN</th><th>Addr1</th><th>Addr2</th>';
    echo '<th>City</th><th>State</th><th>Zip</th></tr>';
    while($row = $dbc->fetch_row($r)){
        echo '<tr>';
        $row['LastName'] = preg_replace('/[^A-Za-z ]/','',$row['LastName']);
        $row['LastName'] = str_replace(" ","-",$row['LastName']);
        /*
        echo '<td>'.strtolower($row['FirstName'][0].$row['LastName']).'</td>';  
        echo '<td>'.str_pad($row['CardNo'],5,'0',STR_PAD_LEFT).'</td>';
        echo '<td>'.$row['FirstName'].' '.$row['LastName'].'</td>';
        */
        echo '<td>';
        echo strtolower($row['FirstName'][0].$row['LastName']);
        echo ' '.str_pad($row['CardNo'],5,'0',STR_PAD_LEFT);
        echo '<td>'.$row['FirstName'].'</td><td>'.$row['LastName'].'</td>';
        if (strstr($row['street'],"\n")){
            list($one,$two) = explode("\n",$row['street']);
            echo '<td>'.$one.'</td><td>'.$two.'</td>';
        }
        else
            echo '<td>'.$row['street'].'</td><td></td>';
        echo '<td>'.$row['city'].'</td>';
        echo '<td>'.$row['state'].'</td>';
        echo '<td>'.$row['zip'].'</td>';
        echo '</tr>';
    }
    echo '</table>';

    if (isset($_REQUEST['excel'])){
        include($FANNIE_ROOT.'src/ReportConvert/HtmlToArray.php');
        include($FANNIE_ROOT.'src/ReportConvert/ArrayToXls.php');
        $output = ob_get_contents();
        ob_end_clean();
        $array = HtmlToArray($output);
        $xls = ArrayToXls($array);
        echo $xls;
    }
            
}
else {

$page_title = "Fannie : Voter List";
$header = "Voter List";
include($FANNIE_ROOT.'src/header.html');
?>
<form action=index.php method=get>
Generate list of current active owners for board election
<p />
Excel <input type=checkbox name=excel />
<p />
<input type=submit name=submit value="Submit" />
</form>
<?php
include($FANNIE_ROOT.'src/footer.html');
}
?>

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
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

if (isset($_REQUEST['submit'])){
    $d1 = $_REQUEST['date1'];
    $d2 = $_REQUEST['date2'];

    $dlog = DTransactionsModel::selectDlog($d1,$d2);

    if (isset($_REQUEST['excel'])){
        header("Content-Disposition: inline; filename=local_{$d1}_{$d2}.xls");
        header("Content-type: application/vnd.ms-excel; name='excel'");
        ob_start();
    }
    else{
        printf("<a href=index.php?date1=%s&date2=%s&submit=yes&excel=yes>Save to Excel</a>",
            $d1,$d2);
    }

    $sales = $dbc->prepare_statement("SELECT t.department,d.dept_name,s.superID,n.super_name,
            sum(case when numflag = 2 then total else 0 end) as localSales,
            sum(case when numflag = 1 then total else 0 end) as scSales,
            sum(total) as allSales
            FROM $dlog as t inner join departments as d
            ON t.department=d.dept_no LEFT JOIN 
            MasterSuperDepts AS s ON s.dept_ID=t.department
            LEFT JOIN superDeptNames AS n ON s.superID=n.superID
            WHERE 
            tdate BETWEEN ? AND ?
            and trans_type = 'I'
            and s.superID > 0
            AND upc Not IN ('RRR','DISCOUNT')
            group by t.department,d.dept_name,s.superID,n.super_name
            order by s.superID,t.department");
    $result = $dbc->exec_statement($sales,array($d1.' 00:00:00',$d2.' 23:59:59'));
    $sID = -1;
    $sname = "";
    $sttl = 0;
    $slocal = 0;
    $sc = 0;
    $master_totals = array(0,0,0);
    echo '<table cellspacing="0" cellpadding="4" border="1">';
    while($row = $dbc->fetch_row($result)){
        if ($sID != $row['superID']){
            if ($sID != -1){
                printf('<tr><th>Ttl</th><th>%s</th>
                    <th>$%.2f</th><th>%.2f%%</th>
                    <th>$%.2f</th><th>%.2f%%</th>
                    <th>$%.2f</th></tr>',
                    $sname,$slocal,100*($slocal/$sttl),
                    $sc,100*($sc/$sttl),$sttl);
            }
            printf('<tr><th colspan=2>%s</th><th>300mi</th><th>%%</th>
                <th>SC</th><th>%%</th><th>Dept TTL</th></tr>',
                (isset($_REQUEST['excel'])?'':'&nbsp;'));
            $sID = $row['superID'];
            $sname = $row['super_name'];
            $sttl = 0;
            $slocal = 0;
            $sc = 0;
        }
        if ($row['allSales'] == 0) $row['allSales']=1; // no div by zero
        printf('<tr><td>%d</td><td>%s</td><td>$%.2f</td>
            <td>%.2f%%</td><td>$%.2f</td>
            <td>%.2f%%</td><td>$%.2f</td></tr>',
            $row['department'],$row['dept_name'],
            $row['localSales'],
            100*($row['localSales']/$row['allSales']),
            $row['scSales'],
            100*($row['scSales']/$row['allSales']),
            $row['allSales']
        );
        $slocal += $row['localSales'];
        $sc += $row['scSales'];
        $sttl += $row['allSales'];
        $master_totals[0] += $row['localSales'];
        $master_totals[1] += $row['scSales'];
        $master_totals[2] += $row['allSales'];
    }
    printf('<tr><th>Ttl</th><th>%s</th>
        <th>$%.2f</th><th>%.2f%%</th>
        <th>$%.2f</th><th>%.2f%%</th>
        <th>$%.2f</th></tr>',
        $sname,$slocal,100*($slocal/$sttl),
        $sc,100*($sc/$sttl),$sttl);

    printf('<tr><td colspan=7>&nbsp;</td></tr>
        <tr><th>Ttl</th><th>Store</th>
        <th>$%.2f</th><th>%.2f%%</th>
        <th>$%.2f</th><th>%.2f%%</th>
        <th>$%.2f</th></tr>',
        $master_totals[0],100*($master_totals[0]/$master_totals[2]),
        $master_totals[1],100*($master_totals[1]/$master_totals[2]),
        $master_totals[2]);

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

$page_title = "Fannie : Local Sales Report";
$header = "Local Sales Report";
include($FANNIE_ROOT.'src/header.html');
$lastMonday = "";
$lastSunday = "";

$ts = mktime(0,0,0,date("n"),date("j")-1,date("Y"));
while($lastMonday == "" || $lastSunday == ""){
    if (date("w",$ts) == 1 && $lastSunday != "")
        $lastMonday = date("Y-m-d",$ts);
    elseif(date("w",$ts) == 0)
        $lastSunday = date("Y-m-d",$ts);
    $ts = mktime(0,0,0,date("n",$ts),date("j",$ts)-1,date("Y",$ts));    
}
?>
<form action=index.php method=get>
<table cellspacing=4 cellpadding=4>
<tr>
<th>Start Date</th>
<td><input type=text name=date1 id="date1" /></td>
</tr><tr>
<th>End Date</th>
<td><input type=text name=date2 id="date2" /></td>
</tr><tr>
<td>Excel <input type=checkbox name=excel /></td>
<td><input type=submit name=submit value="Submit" /></td>
</tr>
</table>
</form>
<script type="text/javascript">
$(document).ready(function(){
    $('#date1').datepicker({dateFormat:'yy-mm-dd'});
    $('#date2').datepicker({dateFormat:'yy-mm-dd'});
});
</script>
<?php
include($FANNIE_ROOT.'src/footer.html');
}
?>

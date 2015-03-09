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


/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 27Nov13 Andy Theuninck revised to use FannieDB
    * 17Mar13 Andy Theuninck removed variable name for datestamp filed
    * + (not necessary, not compatible with all databases)
    * 25Jan13 EL Add today, yesterday, this week, last week, this month, last month options.
    *  2Jan13 Eric Lee Report of Costs, based on GeneralSales/index.php
    * + Base on a dtrans table
    * + Use variable for name of datestamp field.
    * + Exclude what the dlog view excludes
    * + For trans_type D approximate cost as (total / dept markup).
    * + Page heading.
    * + Format report as a single table. Other HTML adjustments.
    * I'm not sure the dept==1 flavour gets markup right when department has changed
    *  since the transaction.
*/

include(dirname(__FILE__) . '/../../../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

/* This program has two modes:
 * 1. Display the form for specifying the report.
 *    Test: the submit button has not been clicked so $_REQUEST['submit'] is not set.
 * 2. Prepare and display the report.
 *    Test: the submit button has been clicked so $_REQUEST['submit'] is set.
*/
if (isset($_REQUEST['submit'])){

    $d1 = $_REQUEST['date1'];
    $d2 = $_REQUEST['date2'];
    $dept = $_REQUEST['dept'];

    if ( isset($_REQUEST['other_dates']) ) {
        switch ($_REQUEST['other_dates']) {
            case 'today':
                $d1 = date("Y-m-d");
                $d2 = $d1;
                break;
            case 'yesterday':
                $d1 = date("Y-m-d", strtotime('yesterday'));
                $d2 = $d1;
                break;
            case 'this_week':
                $d1 = date("Y-m-d", strtotime('last monday'));
                $d2 = date("Y-m-d");
                break;
            case 'last_week':
                $d1 = date("Y-m-d", strtotime('last monday - 7 days'));
                $d2 = date("Y-m-d", strtotime('last sunday'));
                break;
            case 'this_month':
                $d1 = date("Y-m-d", strtotime('first day of this month'));
                $d2 = date("Y-m-d");
                break;
            case 'last_month':
                $d1 = date("Y-m-d", strtotime('first day of last month'));
                $d2 = date("Y-m-d", strtotime('last day of last month'));
                break;
        }
    }

    $dlog = DTransactionsModel::selectDtrans($d1,$d2);

    if (isset($_REQUEST['excel'])){
        header("Content-Disposition: inline; filename=costs_{$d1}_{$d2}.xls");
        header("Content-type: application/vnd.ms-excel; name='excel'");
    }
    else{
        printf("<H3>General Costs: %s </H3>\n", ($d1 == $d2) ? "For $d1" : "From $d1 to $d2");
        printf("<a href=index.php?date1=%s&date2=%s&dept=%s&submit=yes&excel=yes>Save to Excel</a>",
            $d1,$d2,$dept);
    }


    /* Using department settings at the time of sale.
   * I.e. The department# from the transaction.
   *  If that department# no longer exists or is different then the report will be wrong.
   *  This does not use a departments table contemporary with the transactions.
    */
    if ($dept == 0){
        $costs = "SELECT
                    d.Dept_name,
                    sum(CASE WHEN t.trans_type = 'I' THEN t.cost WHEN t.trans_type = 'D' AND m.margin > 1.00 THEN t.total / m.margin ELSE 0.00 END),
                    sum(t.quantity),
                    s.superID,
                    s.super_name
                FROM
                    $dlog AS t LEFT JOIN
                    departments AS d ON d.dept_no=t.department LEFT JOIN
                    MasterSuperDepts AS s ON t.department=s.dept_ID LEFT JOIN
                    deptMargin AS m ON t.department=m.dept_id
                WHERE 
                    (datetime BETWEEN ? AND ?)
                    AND (s.superID > 0 OR s.superID IS NULL) 
                    AND (t.trans_type in ('I','D'))
                    AND ((t.trans_status not in ('D','X','Z')) and (t.emp_no not in (7000, 9999)) and (t.register_no <> 99))
                GROUP BY
                    s.superID,s.super_name,d.dept_name,t.department
                ORDER BY
                    s.superID,t.department";

    }
    /* Using current department settings.
     * I.e. The department for the upc from the current products table.
   *  This does not use a departments table contemporary with the transactions.
    */
    elseif ($dept == 1){
        $costs = "SELECT
                CASE WHEN e.dept_name IS NULL THEN d.dept_name ELSE e.dept_name end,
                sum(CASE WHEN t.trans_type = 'I' THEN t.cost WHEN t.trans_type = 'D' AND m.margin > 1.00 THEN t.total / m.margin ELSE 0.00 END),
                sum(t.quantity),
                CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end,
                CASE WHEN s.super_name IS NULL THEN r.super_name ELSE s.super_name END
            FROM
                $dlog AS t LEFT JOIN
                products AS p ON t.upc=p.upc LEFT JOIN
                departments AS d ON d.dept_no=t.department LEFT JOIN
                departments AS e ON p.department=e.dept_no LEFT JOIN
                MasterSuperDepts AS s ON s.dept_ID=p.department LEFT JOIN
                MasterSuperDepts AS r ON r.dept_ID=t.department LEFT JOIN
                deptMargin AS m ON p.department=m.dept_id
            WHERE
                (datetime BETWEEN ? AND ?)
                AND (t.trans_type = 'I' or t.trans_type = 'D')
                AND (s.superID > 0 OR (s.superID IS NULL AND r.superID > 0)
                    OR (s.superID IS NULL AND r.superID IS NULL))
                AND ((t.trans_status not in ('D','X','Z')) and (t.emp_no not in (7000, 9999)) and (t.register_no <> 99))
            GROUP BY
                CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end,
                CASE WHEN s.super_name IS NULL THEN r.super_name ELSE s.super_name END,
                CASE WHEN e.dept_name IS NULL THEN d.dept_name ELSE e.dept_name end,
                CASE WHEN e.dept_no IS NULL THEN d.dept_no ELSE e.dept_no end
            ORDER BY
                CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end,
                CASE WHEN e.dept_no IS NULL THEN d.dept_no ELSE e.dept_no end";
    }
    else {
        print "<br />Form variable 'dept' value >{$dept}< is unknown.";
        exit;
    }

    // Array in which totals used in the report are accumulated.
    $supers = array();

    $prep = $dbc->prepare_statement($costs);
    $costsR = $dbc->exec_statement($prep,array($d1.' 00:00:00',$d2.' 23:59:59'));
    
    $curSuper = 0;
    $grandTotal = 0;
    while($row = $dbc->fetch_row($costsR)){
        if ($curSuper != $row[3]){
            $curSuper = $row[3];
        }
        if (!isset($supers[$curSuper]))
            $supers[$curSuper] = array('costs'=>0.0,'qty'=>0.0,'name'=>$row[4],'depts'=>array());
        $supers[$curSuper]['costs'] += $row[1];
        $supers[$curSuper]['qty'] += $row[2];
        $supers[$curSuper]['depts'][] = array('name'=>$row[0],'costs'=>$row[1],'qty'=>$row[2]);
        $grandTotal += $row[1];
    }

    $superCount = 0;
    foreach($supers as $s){
        if ($s['costs']==0) continue;
        $superCount++;
        if ( $superCount == 1) {
            echo "<table border=1>\n";
            echo "<tr align=center bgcolor='FFFF99'><th>&nbsp;</th><th>Cost</th><th>Qty</th><th>% Cost</th><th>Dept %</th></tr>\n";
        } else {
            echo "<tr><th colspan='99' style='color:white;'>Foo</th></tr>\n";
            echo "<tr align=center bgcolor='FFFF99'><th>&nbsp;</th><th>Cost</th><th>Qty</th><th>% Cost</th><th>Dept %</th></tr>\n";
        }
        $superSum = $s['costs'];
        foreach($s['depts'] as $d){
            printf("<tr align=right><td >%s</td><td >\$%.2f</td><td >%.2f</td>
                <td >%.2f %%</td><td >%.2f %%</td></tr>\n",
                $d['name'],$d['costs'],$d['qty'],
                $d['costs'] / $grandTotal * 100,
                $d['costs'] / $superSum * 100);
        }

        printf("<tr border = 1 align=right bgcolor=#ffff99><th>%s</th><th>\$%.2f</th><th>%.2f</th>
            <th>%.2f %%</th><td>&nbsp;</td></tr>\n",
            $s['name'],$s['costs'],$s['qty'],$s['costs']/$grandTotal * 100);
            
    }
    echo "</table><br />";

    printf("<b>Total Costs: </b>\$%.2f",$grandTotal);
}
// Form for specifying the report.
else {

$page_title = "Fannie : General Costs Report";
$header = "General Costs Report";
include($FANNIE_ROOT.'src/header.html');
$lastMonday = "";
$lastSunday = "";

/* Default date range is the most recent complete Mon-Sun week,
 *  with calculation beginning yesterday.
 *  If today is Friday the 25th the range is 14th to 20th.
 *  If today is Monday the 28th the range is 21st to 27th.
*/
$ts = mktime(0,0,0,date("n"),date("j")-1,date("Y"));
while($lastMonday == "" || $lastSunday == ""){
    if (date("w",$ts) == 1 && $lastSunday != "")
        $lastMonday = date("Y-m-d",$ts);
    elseif(date("w",$ts) == 0)
        $lastSunday = date("Y-m-d",$ts);
    $ts = mktime(0,0,0,date("n",$ts),date("j",$ts)-1,date("Y",$ts));    
}
?>
<script type="text/javascript">
$(document).ready(function(){
    $('#date1').datepicker({dateFormat:'yy-mm-dd'});
    $('#date2').datepicker({dateFormat:'yy-mm-dd'});
});
</script>
<form action=index.php method=get>
<style type="text/css">
/* This makes the input and label look like they have the same baseline. */
input[type="radio"] ,
input[type="checkbox"] {
    height: 8px;
}
</style>
<table cellspacing=4 cellpadding=4 border='0'>
<tr style='vertical-align:top;'>
<td>
<table cellspacing='4' cellpadding='4' border='0'>
<tr>
<th>Start Date</th>
<td><input type=text name='date1' id="date1" /></td>
<tr>
<th>End Date</th>
<td>
<input type=text name='date2' id="date2" /></td>
</td>
<tr>
<th></th>
<td>
<input type='radio' name='other_dates' value='' checked='1' > Start - End
</td>
</table>
</td>
<td rowspan='1'>
<fieldset style='border:330066;'>
<legend>Other dates</legend>
<table style='margin: 0em 0em 0em 0em;'>
<tr style='vertical-align:top;'><td style='margin: 0em 1.0em 0em 0em;'>
<input type='radio' name='other_dates' value='today'> Today</br >
<input type='radio' name='other_dates' value='this_week'> This week</br >
<input type='radio' name='other_dates' value='this_month'> This month</br >
</td>
<td rowspan='1'>
<input type='radio' name='other_dates' value='yesterday'> Yesterday</br >
<input type='radio' name='other_dates' value='last_week'> Last week</br >
<input type='radio' name='other_dates' value='last_month'> Last month</br >
</td>
</tr>
</table>
</fieldset>
</td>
</tr>
<tr><td colspan='99'><select name='dept'>
<option value='0'>Use the department# the upc was assigned to at time of sale</option>
<option value='1'>Use the department# the upc is assigned to now</option>
</select></td>
</tr>
<tr><td>Excel <input type='checkbox' name='excel' /></td>
<td colspan='99'><input type='submit' name='submit' value="Submit" /></td>
</tr>
</table>
</form>
<?php
include($FANNIE_ROOT.'src/footer.html');
}
?>

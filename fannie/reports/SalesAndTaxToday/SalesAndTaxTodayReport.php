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

/* --FUNCTIONALITY- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * Show total sales by hour for today from dlog.
 * Show total of each type of tax for the day.
 * Offer dropdown of superdepartments and, on-select, display the same report for
 *  that superdept only
 *  showing the proportion of sales for the hour and day its sales represent.
 *  For each tax show the proportion of total tax of that type
 *   coming from the superdept.
 * This page extends FanniePage rather than FannieReportPage because it is
 *  is simpler than most reports and would be encumbered by the FannieReportPage
 *  structure.
*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * 28Nov13 EL card_no=0 are apparently all members, so OK as is.
 * 23Jul13 EL Commented code for lane2 times-in-future problem.
 *  2Jul13 EL Contains some development comments and apparatus.
*/

include(dirname(__FILE__) . '/../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class SalesAndTaxTodayReport extends FannieReportTool 
{

    //protected $auth_classes = array('salesbyhour');
    public $description = '[Today\'s Sales and Tax] shows current day totals by hour and tax totals for the day.';
    public $report_set = 'Sales Reports';

    protected $selected;
    protected $name = "";
    protected $supers;

    public function __construct(){
        global $FANNIE_AUTH_DEFAULT;
        parent::__construct();
        //if ( isset($FANNIE_AUTH_DEFAULT) )
        //  $this->must_authenticate = $FANNIE_AUTH_DEFAULT;

        // Should let fanadmin, cashier in but keep lydia out.
        $this->auth_classes[] = 'salesbyhour';
    }

    function preprocess()
    {
        global $FANNIE_OP_DB, $FANNIE_WINDOW_DRESSING;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        // Should let fanadmin, cashier in but keep lydia out.
        // But it doesn't.
        //$this->auth_classes[] = 'salesbyhour';

        /* Populate an array of superdepartments from which to
         *  select for filtering this report in the next run
         *  and if a superdepartment was chosen for this run
         *  get its name.
        */
        $this->selected = (isset($_GET['super']))?$_GET['super']:-1;
        $superP = $dbc->prepare_statement("SELECT superID,super_name FROM MasterSuperDepts ORDER BY super_name");
        $superR = $dbc->exec_statement($superP);
        $this->supers = array();
        $this->supers[-1] = "All";
        while($row = $dbc->fetch_row($superR)){
            $this->supers[$row[0]] = $row[1];
            if ($this->selected == $row[0])
                $this->name = $row[1];
        }

        $this->title = "Fannie : Today's $this->name Sales and Taxes";
        $this->header = "Today's $this->name Sales and Taxes";

        if ( isset($FANNIE_WINDOW_DRESSING) )
            $this->has_menus($FANNIE_WINDOW_DRESSING);
        else
            $this->has_menus(True);

        return True;

    // preprocess()
    }

    function body_content()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_COOP_ID;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $today = date("Y-m-d");
        $table = 'dlog';    // i.e. dlog. dlog_15 if $today is before today.
        $ddiff = 0; // i.e. 0. -n if $today is before today.
/*
$table = 'dlog_15'; // i.e. dlog. dlog_15 if $today is before today.
$today = "2013-11-27";
$ddiff = -1;    // i.e. 0. -n if $today is before today.
echo "<br />Jiggered date, table= $table, datediff= $ddiff for $today";
//
$key = 'APACHE_RUN_USER';
if (isset($_ENV["$key"]))
    echo "<br />$key is {$_ENV["$key"]}";
else
    echo "<br />$key doesn't exist";
$val = getenv("$key");
//$val = apache_getenv("$key");
echo "<br />val of key $key is $val";
$id=exec("/usr/bin/id");
echo "<br />id $id";
*/

        $hourNames = array();
        for ($n=0;$n<24;++$n) {
            if ($n==0)
                $hourNames["$n"] = "Midnight";
            elseif ($n<12) {
                $hIndex = sprintf("%02d",$n);
                $hourNames["$hIndex"] = "{$n}am";
            }
            elseif ($n==12)
                $hourNames["$n"] = "Noon";
            else {
                $p = ($n-12);
                $hourNames["$n"] = "{$p}pm";
            }
        }

        $queryArgs = array();
        if ( $this->selected != -1 ) {
            $queryArgs[]=$this->selected;
            $queryArgs[]=$this->selected;
        }
        // Array pointer to the last column before taxes. Not used.
        //$colIndex = ($this->selected == -1)?1:2;
        // taxNames1 is all the taxes, with key/index agreeing with taxrates.id
        // I'm trying to not use it and to replace its use in StoreSummary with
        //  and array like taxNames.
        //x$taxNames1 = array(0 => '');
        // Treated as hash. Value is array.
        $taxNames = array();
        //x$taxCol = array(0 => '');
        $taxColName = '';
        $taxColNameSuper = '';
        $taxQuery = '';
        $tQ = $dbc->prepare_statement("SELECT id, rate, description
            FROM taxrates
            WHERE id > 0 ORDER BY id");
        $tR = $dbc->exec_statement($tQ);
        while ( $trow = $dbc->fetch_array($tR) ) {
            $taxId = $trow['id'];
            //x$taxNames1[$taxId] = $trow['description'];
            if ( $trow['rate'] > 0 ) {
                $taxNames[$taxId] = array();
                $taxNames[$taxId]['name'] = $trow['description'];
                $taxNames[$taxId]['sum'] = 0;
                $taxColName = 'taxes'.$taxId;
                $taxNames[$taxId]['colName'] = $taxColName;
                // $taxNames[$taxId]['index'] = ++$colIndex; // prefer to index by name; not used.
                // [queryAll] is not used outside this loop.  Needed?
                $taxNames[$taxId]['queryAll'] = 
                    ", sum(CASE WHEN d.tax = $taxId THEN d.total * x.rate ELSE 0 END) $taxColName";
                $taxQuery .= $taxNames[$taxId]['queryAll'];
                if ( $this->selected != -1 ) {
                    $taxNames[$taxId]['sum2'] = 0;
                    $taxColNameSuper = 'taxes'.$taxId.'s';
                    $taxNames[$taxId]['colNameSuper'] = $taxColNameSuper;
                    // [querySuper] is not used outside this loop.  Needed?
                    $taxNames[$taxId]['querySuper'] = 
                        ", sum(CASE WHEN d.tax = $taxId AND t.superID=? THEN d.total * x.rate ELSE 0 END) $taxColNameSuper";
                    $taxQuery .= $taxNames[$taxId]['querySuper'];
                    // For the tax query, which isn't using subs.
                    $queryArgs[] = $this->selected;
                }
            }
        }
//xecho "<br />"; print_r($taxNames);

        if ( isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == 'WEFC_Toronto' )
            $shrinkageUsers = " AND d.card_no not between 99900 and 99998";
        else
            $shrinkageUsers = "";

        // Plain, no superdepartment.
        /*
                    AND tdate < '2013-07-23 23:00:00'
                    AND tdate like '2013-07-23 %'
                    AND tdate >" . $dbc->now() . "
                WHERE ".$dbc->datediff('tdate',$dbc->now())."=$ddiff
        */
        if ($this->selected == -1){
            $query1="SELECT ".$dbc->hour('tdate')." as Hour, 
                    sum(total)as Sales,
                    sum(case when d.card_no = 99999 then total else 0 end) as nms
                    $taxQuery
                FROM ".$FANNIE_TRANS_DB.$dbc->sep()."$table AS d
                    LEFT JOIN MasterSuperDepts AS t ON d.department = t.dept_ID
                    LEFT JOIN taxrates AS x ON d.tax=x.id
                WHERE ".$dbc->datediff('tdate',$dbc->now())."=$ddiff
                    AND (trans_type ='I' OR trans_type = 'D' or trans_type='M')
                    AND (t.superID > 0 or t.superID IS NULL){$shrinkageUsers}
                GROUP BY ".$dbc->hour('tdate')."
                ORDER BY ".$dbc->hour('tdate');
        }
        // For a superdepartment.
        else {
            $query1="SELECT ".$dbc->hour('tdate')." as Hour, 
                    sum(total)as Sales,
                    sum(case when t.superID=? then total else 0 end) as superdeptSales,
                    sum(case when t.superID=? AND card_no = 99999 then total else 0 end) as nms
                    $taxQuery
                FROM ".$FANNIE_TRANS_DB.$dbc->sep()."$table AS d
                    LEFT JOIN MasterSuperDepts AS t ON d.department = t.dept_ID
                    LEFT JOIN taxrates AS x ON d.tax=x.id
                WHERE ".$dbc->datediff('tdate',$dbc->now())."=$ddiff
                    AND (trans_type ='I' OR trans_type = 'D' or trans_type='M')
                    AND t.superID > 0{$shrinkageUsers}
                GROUP BY ".$dbc->hour('tdate')."
                ORDER BY ".$dbc->hour('tdate');
        }
//echo "<br /> $query1 <br />"; print_r($queryArgs);

        $prep = $dbc->prepare_statement($query1);
        $result = $dbc->exec_statement($query1,$queryArgs);

        echo "<div align=\"center\"><h1>Today's <span style=\"color:green;\">$this->name</span> Sales and Taxes!</h1>";
        $today_time = date("l F j, Y g:i A");
        echo "<h3 style='font-family:arial; color:#444444;'>$today_time</h3>";
        echo "<table cellpadding=4 cellspacing=2 border=0>";
        printf("<tr><td style='font-size:1.3em;'><b>Hour</b></td>
        <td style='text-align:right;font-size:1.3em;'><b>Sales</b></td>
        <td style='text-align:right;font-size:1.3em;' title='%% of Sales to Members'><b>Member%%</b></td>
        <td style='text-align:right;font-size:1.3em;' title='%% of Sales to Non-Members'><b>Non-Member%%</b></td>
        <td style='font-size:1.3em;'>%s</td></tr>",
            ($this->selected==-1)?"":"<b>Proportion</b>");
        $sum = 0;
        $sum2 = 0;  // If report by SuperDept.
        $member_sum = 0;
        $member_sum2 = 0;
        $non_member_sum = 0;
        $non_member_sum2 = 0;
        $pcell="<td style='text-align:right;'>%.2f%%</td>";
        $dcell = "<td style='text-align:right;'>\$ %s</td>";
        while($row=$dbc->fetch_row($result)){
            printf("<tr><td>%s</td>{$dcell}{$pcell}{$pcell}<td style='%s'>%.2f%%</td></tr>",
                $hourNames["{$row['Hour']}"],
                ($this->selected==-1)?number_format($row['Sales'],2):number_format($row['superdeptSales'],2),
                (($row['Sales']-$row['nms'])==0)?0.00:($row['Sales']-$row['nms'])/$row['Sales']*100,
                ($row['nms']==0)?0.00:$row['nms']/$row['Sales']*100,
                ($this->selected==-1)?'display:none;':'text-align:right;',  
                ($this->selected==-1)?0.00:$row['superdeptSales']/$row['Sales']*100
                );
            $sum += $row['Sales'];
            $non_member_sum += $row['nms'];
            $member_sum += ($row['Sales']-$row['nms']);
            if($this->selected != -1) {
                $sum2 += $row['superdeptSales'];
                $non_member_sum2 += $row['nms'];
                $member_sum2 += ($row['superdeptSales']-$row['nms']);
            }
            foreach($taxNames as $k=>$tx) {
                $taxNames[$k]['sum'] += $row[$tx['colName']];
                if($this->selected != -1)
                    $taxNames[$k]['sum2'] += $row[$tx['colNameSuper']];
            }
        }

        // Total Sales
        $grandMemberSalesProp = ($this->selected==-1)?
            (($sum==0)?0.00:($member_sum/$sum)):
            (($sum2==0)?0.00:($member_sum2/$sum2));
        $grandNonMemberSalesProp = ($this->selected==-1)?
            (($sum==0)?0.00:($non_member_sum/$sum)):
            (($sum2==0)?0.00:($non_member_sum2/$sum2));
        printf("<tr><td width=60px style='text-align:left; font-size:1.5em;font-weight:700;'>%s</td>
        {$dcell}{$pcell}{$pcell}<td style='%s'>%.2f%%</td></tr>",
                'Total',
                ($this->selected==-1)?number_format($sum,2):number_format($sum2,2),
                number_format(($grandMemberSalesProp*100),2),
                number_format(($grandNonMemberSalesProp*100),2),
                ($this->selected==-1)?'display:none;':'text-align:right;',  
                ($this->selected==-1)?0.00:($sum==0)?0.00:round($sum2/$sum*100,2)
                );

        // Total of each Tax
        foreach($taxNames as $k=>$tx) {
            printf("<tr><td width=60px style='text-align:left;font-weight:700;'>%s</td>
            <td style='text-align:right;'>\$ %s</td><td style='%s'>%.2f%%</td></tr>",
                $tx['name'],
                ($this->selected==-1)?number_format($tx['sum'],2):number_format($tx['sum2'],2),
                ($this->selected==-1)?'display:none;':'text-align:right;',  
                ($this->selected==-1)?0.00:$this->taxProportion($this->selected,$tx['sum'],$tx['sum2'])
                );
                // Nested test doesn't work.
                //($this->selected==-1)?0.00:($tx['sum']==0)?0.00:round($tx['sum2']/$tx['sum']*100,2)
        }

        echo "</table>";

        echo "<p>Also available: <select onchange=\"top.location='"
            .basename($_SERVER['PHP_SELF'])
            ."?super='+this.value;\">";
        foreach($this->supers as $k=>$v){
            echo "<option value=$k";
            if ($k == $this->selected)
                echo " selected";
            echo ">$v</option>";
        }
        echo "</select></p></div>";

    // body_content()
    }

    function taxProportion ($selected, $sum, $sum2) {
        $retVal = 0.00;
        if ($selected==-1)
            $retVal = 0.00;
        else
            if ($sum==0)
                $retVal = 0.00;
            else 
                $retVal = round($sum2/$sum*100,2);

        return($retVal);
    }

// SalesAndTaxTodayReport
}

FannieDispatch::conditionalExec(false);

?>

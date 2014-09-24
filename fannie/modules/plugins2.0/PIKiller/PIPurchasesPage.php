<?php 
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI'))
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
if (!class_exists('PIKillerPage')) {
    include('lib/PIKillerPage.php');
}

class PIPurchasesPage extends PIKillerPage {

    protected $title = 'Member Purchase History';

    protected function get_id_handler(){
        $this->card_no = $this->id;

        $my = FormLib::get_form_value('my',date('Ym'));
        $start = date("Y-m-d",mktime(0,0,0,substr($my,4),1,substr($my,0,4)));
        $end = date("Y-m-t",mktime(0,0,0,substr($my,4),1,substr($my,0,4)));
        $table = DTransactionsModel::selectDlog($start,$end);

        $this->__models['start'] = $start;
        $this->__models['end'] = $end;
        
        return True;
    }

    protected function get_id_view(){
        global $FANNIE_TRANS_DB,$FANNIE_URL;
        $table = DTransactionsModel::selectDlog($this->__models['start'],$this->__models['end']);
        $my = date('Ym',strtotime($this->__models['start']));

        $dbc = FannieDB::get($FANNIE_TRANS_DB);
        $query = "SELECT month(tdate) as mt,day(tdate) as dt,year(tdate) as yt,trans_num,
            sum(case when trans_type='T' then -total else 0 end) as tenderTotal,
            sum(case when department=990 then total else 0 end) as payment,
            sum(case when trans_subtype='MI' then total else 0 end) as charges,
            sum(case when department in (991,992) then total else 0 end) as stock,
            sum(case when trans_subtype='MA' then total else 0 end) as madcoupon,
            sum(case when upc='DISCOUNT' then total else 0 end) as discountTTL,
            sum(case when upc like '00499999%' then total else 0 end) as wfcoupon
            FROM $table as t
            WHERE card_no=?
            AND tdate BETWEEN ? AND ?
            GROUP BY year(tdate),month(tdate),day(tdate),trans_num
            ORDER BY yt DESC, mt DESC,
            dt DESC";
        $args = array($this->id, $this->__models['start'].' 00:00:00', $this->__models['end'].' 23:59:59');
        if ($my == date('Ym') && substr($table, -5) != '.dlog') {
            // current month. tack on today's transactions
            $today = "SELECT month(tdate) as mt,day(tdate) as dt,year(tdate) as yt,trans_num,
                sum(case when trans_type='T' then -total else 0 end) as tenderTotal,
                sum(case when department=990 then total else 0 end) as payment,
                sum(case when trans_subtype='MI' then total else 0 end) as charges,
                sum(case when department in (991,992) then total else 0 end) as stock,
                sum(case when trans_subtype='MA' then total else 0 end) as madcoupon,
                sum(case when upc='DISCOUNT' then total else 0 end) as discountTTL,
                sum(case when upc like '00499999%' then total else 0 end) as wfcoupon
                FROM dlog as t
                WHERE card_no=?
                GROUP BY year(tdate),month(tdate),day(tdate),trans_num ";
            $query = $today . ' UNION ALL ' . $query;
            array_unshift($args, $this->id);
        }
        $prep = $dbc->prepare_statement($query);
        $result = $dbc->exec_statement($prep, $args);

        ob_start();
        echo '<tr><td>';

        echo "<form action=\"PIPurchasesPage.php\" id=myform method=get>";
        echo "<input type=hidden name=id value=\"".$this->id."\" />";
        $ts = time();
        echo "<select name=my onchange=\"\$('#myform').submit();\">";
        while(True){
            $val = date("Ym",$ts);
            printf("<option value=\"%d\" %s>%s %d</option>",
                $val,($val==$my?"selected":""),
                date("F",$ts),date("Y",$ts));

            $ts = mktime(0,0,0,date("n",$ts)-1,1,date("Y",$ts));

            if (date("Y",$ts) == 2004 && date("n",$ts) == 9)
                break;  
        }
        echo "</select>";

        $visits = 0;
        $spending = 0.0;
        echo "<table cellspacing=0 cellpadding=4 border=1 style=\"font-weight:bold;\">";
        while($row = $dbc->fetch_row($result)){
            echo "<tr>";
            printf("<td>%d/%d/%d</td>",$row[0],$row[1],$row[2]);
            printf("<td><a href=\"{$FANNIE_URL}admin/LookupReceipt/RenderReceiptPage.php?receipt=%s&month=%d&day=%d&year=%d\">%s</a></td>",
                $row[3],$row[0],$row[1],$row[2],$row[3]);
            printf("<td>\$%.2f</td>",$row[4]);
            echo "<td>";
            if ($row[5] != 0) echo "<span style=\"color:#bb44bb;\" title=\"A/R Payment\">P</span>";
            if ($row[6] != 0) echo "<span style=\"color:#0055aa;\" title=\"A/R Charge\">C</span>";
            if ($row[7] != 0) echo "<span style=\"color:#ff3300;\" title=\"Equity Purchase\">S</span>";
            if ($row[8] != 0) echo "<span style=\"color:#003311;\" title=\"Quarterly Coupon\">MC</span>";
            if ($row[9] != 0) echo "<span style=\"color:#003333;\" title=\"Percent Discount\">%</span>";
            if ($row[10] != 0) echo "<span style=\"color:#660033;\" title=\"In Store Coupon\">IC</span>";
            echo "&nbsp;</td>";
            echo "</tr>";
            $spending += $row[4];
            $visits += 1;
        }
        echo "</table>";
        printf("<b>Visits</b>: %d<br /><b>Spending</b>: \$%.2f
            <br /><b>Avg</b>: \$%.2f",
            $visits,$spending,
            ($visits > 0 ? $spending/$visits : 0));

        echo '</td></tr>';
        return ob_get_clean();
    }

}

FannieDispatch::conditionalExec();

?>

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

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class MemPurchasesPage extends FannieRESTfulPage 
{

    protected $title = 'Member Purchase History';
    protected $header = 'Member Purchase History';

    public $description = '[Member Purchases] lists all of a given member\'s transactions.';

    protected function get_id_handler()
    {
        $this->card_no = $this->id;

        $my = FormLib::get_form_value('my',date('Ym'));
        $start = date("Y-m-d",mktime(0,0,0,substr($my,4),1,substr($my,0,4)));
        $end = date("Y-m-t",mktime(0,0,0,substr($my,4),1,substr($my,0,4)));

        $this->__models['start'] = $start;
        $this->__models['end'] = $end;
        
        return true;
    }

    protected function get_id_view()
    {
        global $FANNIE_TRANS_DB,$FANNIE_URL;
        $table = DTransactionsModel::selectDlog($this->__models['start'],$this->__models['end']);
        $my = date('Ym',strtotime($this->__models['start']));

        $dbc = FannieDB::get($FANNIE_TRANS_DB);
        $query = "SELECT month(tdate),day(tdate),year(tdate),trans_num,
            sum(case when trans_type='T' then -total else 0 end) as tenderTotal
            FROM $table as t
            WHERE card_no=?
            AND tdate BETWEEN ? AND ?
            GROUP BY year(tdate),month(tdate),day(tdate),trans_num
            ORDER BY year(tdate) DESC, month(tdate) DESC,
            day(tdate) DESC";
        $prep = $dbc->prepare_statement($query);
        $result = $dbc->exec_statement($prep, 
            array($this->id, $this->__models['start'].' 00:00:00', $this->__models['end'].' 23:59:59'));

        ob_start();

        echo "<form action=\"MemPurchasesPage.php\" id=myform method=get>";
        echo "<input type=hidden name=id value=\"".$this->id."\" />";
        $ts = time();
        echo "<select name=my onchange=\"\$('#myform').submit();\">";
        $count = 0;
        while(true) {
            $val = date("Ym",$ts);
            printf("<option value=\"%d\" %s>%s %d</option>",
                $val,($val==$my?"selected":""),
                date("F",$ts),date("Y",$ts));

            $ts = mktime(0,0,0,date("n",$ts)-1,1,date("Y",$ts));

            // cuts off at 5 years
            if ($count++ > 60) {
                break;  
            }
        }
        echo "</select>";

        $visits = 0;
        $spending = 0.0;
        echo "<table cellspacing=0 cellpadding=4 border=1 style=\"font-weight:bold;\">";
        while($row = $dbc->fetch_row($result)) {
            echo "<tr>";
            printf("<td>%d/%d/%d</td>",$row[0],$row[1],$row[2]);
            printf("<td><a href=\"{$FANNIE_URL}admin/LookupReceipt/RenderReceiptPage.php?receipt=%s&month=%d&day=%d&year=%d\">%s</a></td>",
                $row[3],$row[0],$row[1],$row[2],$row[3]);
            printf("<td>\$%.2f</td>",$row[4]);
            echo "</tr>";
            $spending += $row[4];
            $visits += 1;
        }
        echo "</table>";
        printf("<b>Visits</b>: %d<br /><b>Spending</b>: \$%.2f
            <br /><b>Avg</b>: \$%.2f",
            $visits,$spending,
            ($visits > 0 ? $spending/$visits : 0));

        return ob_get_clean();
    }

}

FannieDispatch::conditionalExec();


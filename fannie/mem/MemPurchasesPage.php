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
    public $themed = true;

    protected function get_id_handler()
    {
        $this->card_no = $this->id;

        try {
            $monthYear = $this->form->my;
        } catch (Exception $ex) {
            $monthYear = date('Ym');
        }
        $start = date("Y-m-d",mktime(0,0,0,substr($monthYear,4),1,substr($monthYear,0,4)));
        $end = date("Y-m-t",mktime(0,0,0,substr($monthYear,4),1,substr($monthYear,0,4)));

        $this->__models['start'] = $start;
        $this->__models['end'] = $end;
        
        return true;
    }

    protected function get_id_view()
    {
        $URL = $this->config->get('URL');
        $table = DTransactionsModel::selectDlog($this->__models['start'],$this->__models['end']);
        $monthYear = date('Ym',strtotime($this->__models['start']));
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));

        $query = "SELECT month(tdate),day(tdate),year(tdate),trans_num,
            sum(case when trans_type='T' then -total else 0 end) as tenderTotal
            FROM $table as t
            WHERE card_no=?
            AND tdate BETWEEN ? AND ?
            GROUP BY year(tdate),month(tdate),day(tdate),trans_num
            ORDER BY year(tdate) DESC, month(tdate) DESC,
            day(tdate) DESC";
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, 
            array($this->id, $this->__models['start'].' 00:00:00', $this->__models['end'].' 23:59:59'));

        ob_start();

        echo "<form action=\"MemPurchasesPage.php\" id=myform method=get>";
        echo "<input type=hidden name=id value=\"".$this->id."\" />";
        $tstamp = time();
        echo '<div class="form-group">';
        echo "<select class=\"form-control\" name=my onchange=\"\$('#myform').submit();\">";
        $count = 0;
        while(true) {
            $val = date("Ym",$tstamp);
            printf("<option value=\"%d\" %s>%s %d</option>",
                $val,($val==$monthYear?"selected":""),
                date("F",$tstamp),date("Y",$tstamp));

            $tstamp = mktime(0,0,0,date("n",$tstamp)-1,1,date("Y",$tstamp));

            // cuts off at 5 years
            if ($count++ > 60) {
                break;  
            }
        }
        echo "</select>";
        echo '</div>';

        $visits = 0;
        $spending = 0.0;
        echo "<table class=\"table table-bordered\">";
        while($row = $dbc->fetch_row($result)) {
            echo "<tr>";
            printf("<td>%d/%d/%d</td>",$row[0],$row[1],$row[2]);
            printf("<td><a href=\"{$URL}admin/LookupReceipt/RenderReceiptPage.php?receipt=%s&month=%d&day=%d&year=%d\">%s</a></td>",
                $row[3],$row[0],$row[1],$row[2],$row[3]);
            printf("<td>\$%.2f</td>",$row[4]);
            echo "</tr>";
            $spending += $row[4];
            $visits += 1;
        }
        echo "</table>";
        echo '<p>';
        printf("<b>Visits</b>: %d<br /><b>Spending</b>: \$%.2f
            <br /><b>Avg</b>: \$%.2f",
            $visits,$spending,
            ($visits > 0 ? $spending/$visits : 0));
        echo '</p>';

        return ob_get_clean();
    }

    public function unitTest($phpunit)
    {
        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->_method = 'get';
        $form->id = 1;
        $this->setForm($form);
        $this->readRoutes();

        $phpunit->assertEquals(true, $this->get_id_handler());
        $page = $this->get_id_view();
        $phpunit->assertNotEquals(0, strlen($page));
    }

    public function helpContent()
    {
        return '<p>
            This report displays purchases for the given member
            in a given month. The default is the current month.
            Use the drop down menu to select other months.
            </p>
            <p>
            Click receipt number links to see detail from
            individual transactions.
            </p>';
    }

}

FannieDispatch::conditionalExec();


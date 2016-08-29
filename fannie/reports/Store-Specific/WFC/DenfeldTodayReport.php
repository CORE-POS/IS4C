<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/* --FUNCTIONALITY- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * Show total sales by hour for today from dlog.
 * Offer dropdown of superdepartments and, on-select, display the same report for
 *  that superdept only.
 * This page extends FanniePage because it is simpler than most reports
 *  and would be encumbered by the FannieReportPage structure.
*/

include(dirname(__FILE__) . '/../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class DenfeldTodayReport extends \COREPOS\Fannie\API\FannieReportTool 
{
    public $description = '[Today\'s Sales] shows current day totals by hour.';
    public $report_set = 'Sales Reports';

    protected $selected = -1;
    protected $name = "";
    protected $supers = array();

    public function preprocess()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $this->form = new COREPOS\common\mvc\FormValueContainer();
        try {
            $this->selected = $this->form->super;
        } catch (Exception $ex) { }


        /* Populate an array of superdepartments from which to
         *  select for filtering this report in the next run
         *  and if a superdepartment was chosen for this run
         *  get its name.
        */
        $superP = $dbc->prepare("SELECT superID,super_name FROM MasterSuperDepts ORDER BY super_name");
        $superR = $dbc->execute($superP);
        $this->supers = array();
        $this->supers[-1] = "All Departments";
        while ($row = $dbc->fetchRow($superR)) {
            $this->supers[$row[0]] = $row[1];
            if ($this->selected == $row[0]) {
                $this->name = $row[1];
            }
        }

        $this->title = "Fannie : Today's $this->name Sales [Denfeld, Real-Time]";
        $this->header = '';

        $this->addScript($this->config->get('URL').'src/javascript/d3.js/d3.v3.min.js');
        $this->addScript('../../../src/javascript/d3.js/charts/singleline/singleline.js');
        $this->addCssFile('../../../src/javascript/d3.js/charts/singleline/singleline.css');
        $this->addScript('../../SalesToday/salesToday.js');

        return True;

    // preprocess()
    }

    private function salesQuery($dbc)
    {
        $args = array();
        $query1 ="
            SELECT ".$dbc->hour('tdate').", 
                SUM(total) AS Sales ";
        if ($this->selected != -1) {
            $query1 .= ', SUM(CASE WHEN t.superID=? THEN total ELSE 0 END) AS prodSales ';
            $args[] = $this->selected; 
        }
        $query1 .= '
            FROM denfeld_only.dlog AS d 
                LEFT JOIN MasterSuperDepts AS t ON d.department = t.dept_ID
            WHERE d.tdate >= ' . $dbc->curdate() . "
                AND (trans_type ='I' OR trans_type = 'D' or trans_type='M')
                AND (t.superID > 0 or t.superID IS NULL) ";

        $query1 .= ' GROUP BY ' . $dbc->hour('tdate')
                .  ' ORDER BY ' . $dbc->hour('tdate');

        return array($query1, $args);
    }

    public function body_content()
    {
        global $FANNIE_TRANS_DB;
        include(dirname(__FILE__) . '/../../../src/Credentials/Denfeld.wfc.php');

        list($query1, $args) = $this->salesQuery($dbc);
        $prep = $dbc->prepare($query1);
        $result = $dbc->execute($query1,$args);

        ob_start();
        echo "<div class=\"text-center container\"><h1>Today's <span style=\"color:green;\">$this->name</span> Sales!</h1>";
        echo '<em>Denfeld-only, real-time</em>';
        echo "<table class=\"table table-bordered no-bs-table\">"; 
        echo "<tr><td><b>Hour</b></td><td><b>Sales</b></td></tr>";
        $sum = 0;
        $sum2 = 0;
        while ($row=$dbc->fetchRow($result)){
            printf("<tr class=\"datarow\"><td class=\"x-data\">%d</td><td class=\"y-data text-right\">%.2f</td><td class='%s'>%.2f%%</td></tr>",
                $row[0],
                ($this->selected==-1)?$row[1]:$row[2],
                ($this->selected==-1)?'collapse':'text-right',  
                ($this->selected==-1)?0.00:$row[2]/$row[1]*100);
            $sum += $row[1];
            if($this->selected != -1) {
                $sum2 += $row[2];
            }
        }
        echo "<tr><th width=60px class='text-left'>Total</th><td class='text-right'>";
        if ($this->selected != -1) {
            echo number_format($sum2,2)."</td><td>"
                . ($sum == 0 ? 0: round($sum2/$sum*100,2)) . "%";
        } else {
            echo number_format($sum,2);
        }
        echo "</td></tr></table>";

        echo '<div class="form-group form-inline">
            <select name="dept" class="form-control">';
        foreach ($this->supers as $k=>$v) {
            echo "<option value=$k";
            if ($k == $this->selected) {
                echo " selected";
            }
            echo ">$v</option>";
        }
        echo "</select></div>";

        echo '<div id="chartDiv"></div>';

        $this->addOnloadCommand('salesToday.graphData();');
        $this->addOnloadCommand("\$('select').change(salesToday.reloadGraph);\n");

        echo '</div>';

        return ob_get_clean();
    // body_content()
    }

    public function css_content()
    {
        return <<<CSS
.no-bs-table {
    width: auto !important;
    margin-left: auto;
    margin-right: auto;
}
CSS;
    }

    public function helpContent()
    {
        return '<p>Hourly Sales for the current day. The drop down menu
            can switch the report to a single super department.</p>';
    }

// SalesTodayReport
}

FannieDispatch::conditionalExec();


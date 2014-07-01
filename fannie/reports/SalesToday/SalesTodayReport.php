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
 * Offer dropdown of superdepartments and, on-select, display the same report for
 *  that superdept only.
 * This page extends FanniePage because it is simpler than most reports
 *  and would be encumbered by the FannieReportPage structure.
*/

include(dirname(__FILE__) . '/../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class SalesTodayReport extends FannieReportTool 
{
    public $description = '[Today\'s Sales] shows current day totals by hour.';
    public $report_set = 'Sales Reports';

    protected $selected;
    protected $name = "";
    protected $supers;

    public function preprocess()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $this->selected = (isset($_GET['super']))?$_GET['super']:-1;

        /* Populate an array of superdepartments from which to
         *  select for filtering this report in the next run
         *  and if a superdepartment was chosen for this run
         *  get its name.
        */
        $superP = $dbc->prepare_statement("SELECT superID,super_name FROM MasterSuperDepts ORDER BY super_name");
        $superR = $dbc->exec_statement($superP);
        $this->supers = array();
        $this->supers[-1] = "All";
        while($row = $dbc->fetch_row($superR)) {
            $this->supers[$row[0]] = $row[1];
            if ($this->selected == $row[0]) {
                $this->name = $row[1];
            }
        }

        $this->title = "Fannie : Today's $this->name Sales";
        $this->header = "Today's $this->name Sales";

        $this->has_menus(True);
        $this->add_script($FANNIE_URL.'src/javascript/d3.js/d3.v3.min.js');

        return True;

    // preprocess()
    }

    public function body_content()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $today = date("Y-m-d");

        $query1="SELECT ".$dbc->hour('tdate').", 
                sum(total)as Sales
            FROM ".$FANNIE_TRANS_DB.$dbc->sep()."dlog AS d left join MasterSuperDepts AS t
                ON d.department = t.dept_ID
            WHERE ".$dbc->datediff('tdate',$dbc->now())."=0
                AND (trans_type ='I' OR trans_type = 'D' or trans_type='M')
                AND (t.superID > 0 or t.superID IS NULL)
            GROUP BY ".$dbc->hour('tdate')."
            ORDER BY ".$dbc->hour('tdate');
        $args = array();
        if ($this->selected != -1) {
            $query1="SELECT ".$dbc->hour('tdate').", 
                    sum(total)as Sales,
                    sum(case when t.superID=? then total else 0 end) as prodSales
                FROM ".$FANNIE_TRANS_DB.$dbc->sep()."dlog AS d left join MasterSuperDepts AS t
                    ON d.department = t.dept_ID
                WHERE ".$dbc->datediff('tdate',$dbc->now())."=0
                    AND (trans_type ='I' OR trans_type = 'D' or trans_type='M')
                    AND t.superID > 0
                GROUP BY ".$dbc->hour('tdate')."
                ORDER BY ".$dbc->hour('tdate');
            $args = array($this->selected);
        }

        $prep = $dbc->prepare_statement($query1);
        $result = $dbc->exec_statement($query1,$args);

        ob_start();
        echo "<div align=\"center\"><h1>Today's <span style=\"color:green;\">$this->name</span> Sales!</h1>";
        echo "<table cellpadding=4 cellspacing=2 border=0>";
        echo "<tr><td><b>Hour</b></td><td><b>Sales</b></td></tr>";
        $sum = 0;
        $sum2 = 0;
        while($row=$dbc->fetch_row($result)){
            printf("<tr class=\"datarow\"><td class=\"x-data\">%d</td><td class=\"y-data\" style='text-align:right;'>%.2f</td><td style='%s'>%.2f%%</td></tr>",
                $row[0],
                ($this->selected==-1)?$row[1]:$row[2],
                ($this->selected==-1)?'display:none;':'text-align:right;',  
                ($this->selected==-1)?0.00:$row[2]/$row[1]*100);
            $sum += $row[1];
            if($this->selected != -1) {
                $sum2 += $row[2];
            }
        }
        echo "<tr><th width=60px style='text-align:left;'>Total</th><td style='text-align:right;'>";
        if ($this->selected != -1) {
            echo number_format($sum2,2)."</td><td>".round($sum2/$sum*100,2)."%";
        } else {
            echo number_format($sum,2);
        }
        echo "</td></tr></table>";

        echo "<p>Also available: <select onchange=\"top.location='SalesTodayReport.php?super='+this.value;\">";
        foreach($this->supers as $k=>$v) {
            echo "<option value=$k";
            if ($k == $this->selected) {
                echo " selected";
            }
            echo ">$v</option>";
        }
        echo "</select></p></div>";

        echo '<div id="chartDiv"></div>';

        $this->add_onload_command('graphData();');

        return ob_get_clean();
    // body_content()
    }

    public function css_content()
    {
        ob_start();
        ?>
/* tell the SVG path to be a thin blue line without any area fill */
path {
    stroke: steelblue;
    stroke-width: 2;
    fill: none;
}
                                                                                        
.axis {
    shape-rendering: crispEdges;
}

.x.axis line {
    stroke: lightgrey;
}

.x.axis .minor {
    stroke-opacity: .5;
}

.x.axis path {
    display: none;
}

.y.axis line, .y.axis path {
    fill: none;
    stroke: #000;
    stroke-width: 1;
}
        <?php
        return ob_get_clean();
    }

    public function javascript_content()
    {
        ob_start();
        ?>
function graphData()
{
    var data = Array();
    var xmin = 24;
    var xmax = 0;
    var ymin = 999999999;
    var ymax = 0;

    $('.datarow').each(function(){
        var x = Number($(this).find('.x-data').html());
        var y = Number($(this).find('.y-data').html());
        if (x < xmin) {
            xmin = x;
        }
        if (x > xmax) {
            xmax = x;
        }
        if (y < ymin) {
            ymin = y;
        }
        if (y > ymax) {
            ymax = y;
        }
        data.push(Array(x, y));
    });

    drawLineGraph(data, Array(xmin, xmax), Array(ymin, ymax));
}

function drawLineGraph(data, xrange, yrange) 
{
    /* implementation heavily influenced by http://bl.ocks.org/1166403 */
                
    // define dimensions of graph
    var m = [80, 80, 80, 80]; // margins
    var w = 650 - m[1] - m[3]; // width
    var h = 400 - m[0] - m[2]; // height
                                                        
    // X scale will fit all values from data[] within pixels 0-w
    var x = d3.scale.linear().domain(xrange).range([0, w]);
    // Y scale will fit values from 0-10 within pixels h-0 (Note the inverted domain for the y-scale: bigger is up!)
    var y = d3.scale.linear().domain(yrange).range([h, 0]);

    // create a line function that can convert data[] into x and y points
    var line = d3.svg.line()
        .x(function(d,i) { 
            // verbose logging to show what's actually being done
            //console.log('Plotting X value for data point: ' + d + ' using index: ' + i + ' to be at: ' + x(d[0]) + ' using our xScale.');
            // return the X coordinate where we want to plot this datapoint
            return x(d[0]); 
        })
        .y(function(d) { 
            // verbose logging to show what's actually being done
            //console.log('Plotting Y value for data point: ' + d + ' to be at: ' + y(d) + " using our yScale.");
            // return the Y coordinate where we want to plot this datapoint
            return y(d[1]); 
        })

        // Add an SVG element with the desired dimensions and margin.
        var graph = d3.select("#chartDiv").append("svg:svg")
            .attr("width", w + m[1] + m[3])
            .attr("height", h + m[0] + m[2])
            .append("svg:g")
            .attr("transform", "translate(" + m[3] + "," + m[0] + ")");

        // create yAxis
        var xAxis = d3.svg.axis().scale(x).tickSize(-h).tickSubdivide(true);
        // Add the x-axis.
        graph.append("svg:g")
            .attr("class", "x axis")
            .attr("transform", "translate(0," + h + ")")
            .call(xAxis);

        // create left yAxis
        var yAxisLeft = d3.svg.axis().scale(y).ticks(4).orient("left");
        // Add the y-axis to the left
        graph.append("svg:g")
            .attr("class", "y axis")
            .attr("transform", "translate(-25,0)")
            .call(yAxisLeft);

        // Add the line by appending an svg:path element with the data line we created above
        // do this AFTER the axes above so that the line is above the tick-lines
        graph.append("svg:path").attr("d", line(data));
}
        <?php
        return ob_get_clean();
    }

// SalesTodayReport
}

FannieDispatch::conditionalExec(false);

?>

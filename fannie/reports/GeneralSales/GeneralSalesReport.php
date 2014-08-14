<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class GeneralSalesReport extends FannieReportPage 
{

    public $description = '[General Sales Report] shows total sales per department for a given date range in dollars as well as a percentage of store-wide sales.';
    public $report_set = 'Sales Reports';

    private $grandTTL = 1;
    protected $title = "Fannie : General Sales Report";
    protected $header = "General Sales Report";
    protected $report_cache = 'none';
    protected $multi_report_mode = false;
    protected $sortable = false;
    protected $no_sort_but_style = true;
    protected $chart_data_columns = array(1);
    protected $report_headers = array('','Sales','Quantity','% Sales','Dept %');
    protected $required_fields = array('date1', 'date2');

    public function preprocess()
    {
        parent::preprocess();
        if ($this->content_function == 'report_content' && $this->report_format == 'html') {
            $this->add_script('../../src/javascript/d3.js/d3.v3.min.js');
        }

        return true;
    }

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_ARCHIVE_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $d1 = FormLib::get_form_value('date1',date('Y-m-d'));
        $d2 = FormLib::get_form_value('date2',date('Y-m-d'));
        $dept = $_REQUEST['dept'];

        $dlog = DTransactionsModel::selectDlog($d1,$d2);

        $superR = $dbc->query('SELECT dept_ID FROM MasterSuperDepts WHERE superID=0');
        $omitDepts = array();
        $omitVals = '';
        while ($superW = $dbc->fetch_row($superR)) {
            $omitDepts[] = $superW['dept_ID'];
            $omitVals .= '?,';
        }
        $omitVals = substr($omitVals, 0, strlen($omitVals)-1);

        $sales = "SELECT d.Dept_name,sum(t.total),
                sum(case when unitPrice=0.01 THEN 1 else t.quantity END),
                s.superID,s.super_name
                FROM $dlog AS t 
                    LEFT JOIN departments AS d ON d.dept_no=t.department 
                    LEFT JOIN MasterSuperDepts AS s ON t.department=s.dept_ID
                WHERE 
                    t.department NOT IN ($omitVals)
                    AND t.trans_type IN ('I', 'D')
                    AND (tdate BETWEEN ? AND ?)
                GROUP BY s.superID,s.super_name,d.dept_name,t.department
                ORDER BY s.superID,t.department";
        if ($dept == 1){
            $sales = "SELECT CASE WHEN e.dept_name IS NULL THEN d.dept_name ELSE e.dept_name end,
                sum(t.total),sum(CASE WHEN unitPrice=0.01 then 1 else t.quantity END),
                CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end,
                CASE WHEN s.super_name IS NULL THEN r.super_name ELSE s.super_name END
                FROM $dlog AS t LEFT JOIN
                products AS p ON t.upc=p.upc LEFT JOIN
                departments AS d ON d.dept_no=t.department LEFT JOIN
                departments AS e ON p.department=e.dept_no LEFT JOIN
                MasterSuperDepts AS s ON s.dept_ID=p.department LEFT JOIN
                MasterSuperDepts AS r ON r.dept_ID=t.department
                WHERE
                    t.trans_type IN ('I', 'D')
                    AND (s.superID > 0 OR (s.superID IS NULL AND r.superID > 0)
                    OR (s.superID IS NULL AND r.superID IS NULL))
                    AND (tdate BETWEEN ? AND ?)
                GROUP BY
                CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end,
                CASE WHEN s.super_name IS NULL THEN r.super_name ELSE s.super_name END,
                CASE WHEN e.dept_name IS NULL THEN d.dept_name ELSE e.dept_name end,
                CASE WHEN e.dept_no IS NULL THEN d.dept_no ELSE e.dept_no end
                ORDER BY
                CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end,
                CASE WHEN e.dept_no IS NULL THEN d.dept_no ELSE e.dept_no end";
                $omitDepts = array();
        }
        $supers = array();
        $prep = $dbc->prepare_statement($sales);
        $args = $omitDepts;
        $args[] = $d1 . ' 00:00:00';
        $args[] = $d2 . ' 23:59:59';
        $salesR = $dbc->exec_statement($prep, $args);
    
        $curSuper = 0;
        $grandTotal = 0;
        while($row = $dbc->fetch_row($salesR)){
            if ($curSuper != $row[3]){
                $curSuper = $row[3];
            }
            if (!isset($supers[$curSuper]))
                $supers[$curSuper] = array('sales'=>0.0,'qty'=>0.0,'name'=>$row[4],'depts'=>array());
            $supers[$curSuper]['sales'] += $row[1];
            $supers[$curSuper]['qty'] += $row[2];
            $supers[$curSuper]['depts'][] = array('name'=>$row[0],'sales'=>$row[1],'qty'=>$row[2]);
            $grandTotal += $row[1];
        }

        $data = array();
        $i = 1;
        foreach($supers as $s) {
            if ($s['sales']==0) {
                $i++;
                continue;
            }

            $superSum = $s['sales'];
            foreach($s['depts'] as $d) {
                $record = array(
                    $d['name'],
                    sprintf('%.2f',$d['sales']),
                    sprintf('%.2f',$d['qty']),
                    sprintf('%.2f',($d['sales'] / $grandTotal) * 100),
                    sprintf('%.2f',($d['sales'] / $superSum) * 100)
                );
                $data[] = $record;
            }

            $record = array(
                $s['name'],
                sprintf('%.2f', $s['sales']),
                sprintf('%.2f', $s['qty']),
                '',
                sprintf('%.2f%%', ($s['sales'] / $grandTotal) * 100),
            );
            $record['meta'] = FannieReportPage::META_BOLD | FannieReportPage::META_CHART_DATA;

            $data[] = $record;

            $data[] = array('meta'=>FannieReportPage::META_BLANK);

            if ($i < count($supers)) {
                $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);
            }
            $i++;
        }

        $this->grandTTL = $grandTotal;

        return $data;
    }

    public function calculate_footers($data)
    {
        $sumQty = 0.0;
        $sumSales = 0.0;
        foreach($data as $row) {
            if (isset($row['meta'])) {
                continue;
            }
            $sumQty += $row[2];
            $sumSales += $row[1];
        }
        return array('Total',$sumSales,$sumQty, '', null);
    }

    public function javascriptContent()
    {
        global $FANNIE_URL;
        if ($this->report_format != 'html') {
            return '';
        }

        ob_start();
        ?>
function drawPieChart()
{
    var w = 900,                        //width
    h = 900,                            //height
    r = 300,                            //radius
    color = d3.scale.category20c();     //builtin range of colors

    var total_sales = 0.00;
    $('.d3Data').each(function(){
        total_sales += Number($(this).html());
    });

    var data = new Array();
    $('.d3ChartData').each(function(){
        var percentage = (Number($(this).find('.d3Data').html()) / total_sales) * 100;
        percentage = Math.round(percentage * 100) / 100;
        var label = $(this).find('.d3Label').html()+"\n"+percentage+"%";
        if (percentage < 5) label = '';
        var row = {
            'label' : label,
            'value' : percentage
        };
        data.push(row);
    });
                                                     
    var vis = d3.select("body")
        .append("svg:svg")              //create the SVG element inside the <body>
        .data([data])                   //associate our data with the document
        .attr("width", w)           //set the width and height of our visualization (these will be attributes of the <svg> tag
        .attr("height", h)
        .append("svg:g")                //make a group to hold our pie chart
        .attr("transform", "translate(" + r + "," + r + ")")    //move the center of the pie chart from 0, 0 to radius, radius

    var arc = d3.svg.arc()              //this will create <path> elements for us using arc data
        .outerRadius(r);

    var pie = d3.layout.pie()           //this will create arc data for us given a list of values
        .value(function(d) { return d.value; });    //we must tell it out to access the value of each element in our data array

    var arcs = vis.selectAll("g.slice")     //this selects all <g> elements with class slice (there aren't any yet)
        .data(pie)                          //associate the generated pie data (an array of arcs, each having startAngle, endAngle and value properties) 
        .enter()                            //this will create <g> elements for every "extra" data element that should be associated with a selection. 
                                            //The result is creating a <g> for every object in the data array
        .append("svg:g")                //create a group to hold each slice (we will have a <path> and a <text> element associated with each slice)
        .attr("class", "slice");    //allow us to style things in the slices (like text)

    arcs.append("svg:path")
        .attr("fill", function(d, i) { return color(i); } ) //set the color for each slice to be chosen from the color function defined above
        .attr("d", arc);                                    //this creates the actual SVG path using the associated data (pie) with the arc drawing function

    arcs.append("svg:text")               //add a label to each slice
        .attr("transform", function(d) {      //set the label's origin to the center of the arc
            //we have to make sure to set these before calling arc.centroid
            d.innerRadius = 0;
            d.outerRadius = r;
            return "translate(" + arc.centroid(d) + ")";        //this gives us a pair of coordinates like [50, 50]
        })
        .attr("text-anchor", "middle")                          //center the text on it's origin
        .text(function(d, i) { return data[i].label; });        //get the label from our original data array
}
        <?php
        $this->add_onload_command('drawPieChart();');
        return ob_get_clean();
    }

    public function form_content()
    {
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
        <form action=GeneralSalesReport.php method=get>
        <table cellspacing=4 cellpadding=4>
        <tr>
        <th>Start Date</th>
        <td><input type=text id=date1 name=date1 value="<?php echo $lastMonday; ?>" /></td>
        <td rowspan="2">
        <?php echo FormLib::date_range_picker(); ?>
        </td>
        </tr><tr>
        <th>End Date</th>
        <td><input type=text id=date2 name=date2 value="<?php echo $lastSunday; ?>" /></td>
        </tr><tr>
        <td colspan=2><select name=dept>
        <option value=0>Use department settings at time of sale</option>
        <option value=1>Use current department settings</option>
        </select></td>
        </tr><tr>
        <td>Excel <input type=checkbox name=excel /></td>
        <td><input type=submit name=submit value="Submit" /></td>
        </tr>
        </table>
        </form>
        <?php
        $this->add_onload_command('$(\'#date1\').datepicker();');
        $this->add_onload_command('$(\'#date2\').datepicker();');
    }

}

FannieDispatch::conditionalExec(false);

?>

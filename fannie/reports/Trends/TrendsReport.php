<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class TrendsReport extends FannieReportPage
{
    protected $title = "Fannie : Trends";
    protected $header = "Trend Report";

    protected $required_fields = array('date1', 'date2');

    public $description = '[Trends] shows daily sales totals for items over a given date range. Items can be included by UPC, department, or manufacturer.';
    public $report_set = 'Movement Reports';
    public $themed = true;

    public function report_description_content()
    {
        if ($this->report_format != 'html') {
            return array();
        }

        $url = $this->config->get('URL');
        $this->add_script($url . 'src/javascript/jquery.js');
        $this->add_script($url . 'src/javascript/jquery-ui.js');
        $this->add_css_file($url . 'src/javascript/jquery-ui.css');

        $dates_form = '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
        foreach ($_GET as $key => $value) {
            if ($key != 'date1' && $key != 'date2' && $key != 'store') {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $dates_form .= sprintf('<input type="hidden" name="%s[]" value="%s" />', $key, $v);
                    }
                } else {
                    $dates_form .= sprintf('<input type="hidden" name="%s" value="%s" />', $key, $value);
                }
            }
        }
        foreach ($_POST as $key => $value) {
            if ($key != 'date1' && $key != 'date2' && $key != 'store') {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $dates_form .= sprintf('<input type="hidden" name="%s[]" value="%s" />', $key, $v);
                    }
                } else {
                    $dates_form .= sprintf('<input type="hidden" name="%s" value="%s" />', $key, $value);
                }
            }
        }
        $stores = FormLib::storePicker();
        $dates_form .= '
            <label>Start Date</label>
            <input class="date-field" type="text" name="date1" value="' . FormLib::get('date1') . '" /> 
            <label>End Date</label>
            <input class="date-field" type="text" name="date2" value="' . FormLib::get('date2') . '" /> 
            <input type="hidden" name="excel" value="" id="excel" />
            ' . $stores['html'] . '
            <button type="submit" onclick="$(\'#excel\').val(\'\');return true;">Change Dates</button>
            <button type="submit" onclick="$(\'#excel\').val(\'csv\');return true;">Download</button>
            </form>';

        $this->add_onload_command("\$('.date-field').datepicker({dateFormat:'yy-mm-dd'});");
        
        return array($dates_form);
 
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $dlog = DTransactionsModel::selectDlog($date1,$date2);
        $store = FormLib::get('store', 0);

        $from_where = FormLib::standardItemFromWhere();
        
        $select_cols = '
            t.upc AS prodID, 
            CASE WHEN p.brand IS NULL THEN \'\' ELSE p.brand END AS brand, 
            CASE WHEN p.description IS NULL THEN t.description ELSE p.description END AS description';
        $group_cols = '
            t.upc, 
            p.brand, 
            CASE WHEN p.description IS NULL THEN t.description ELSE p.description END';
        if (FormLib::get('lookup-type') == 'likecode') {
            $select_cols = '
                u.likeCode AS prodID,
                \'\' AS brand,
                u.likeCodeDesc AS description';
            $group_cols = '
                u.likeCode,
                u.likeCodeDesc';
        }

        $query = "
            SELECT 
                YEAR(t.tdate) AS year,
                MONTH(t.tdate) AS month,
                DAY(t.tdate) AS day,
                $select_cols, "
                . DTrans::sumQuantity('t') . " AS total
            " . $from_where['query'] . "
                AND trans_status <> 'M'
                AND trans_type = 'I'
                AND " . DTrans::isStoreID($store, 't') . "
            GROUP BY YEAR(t.tdate),
                MONTH(t.tdate),
                DAY(t.tdate),
                $group_cols
            ORDER BY prodID,
                YEAR(t.tdate),
                MONTH(t.tdate),
                DAY(t.tdate)";
        $prep = $dbc->prepare($query);
        $from_where['args'][] = $store;
        $result = $dbc->execute($prep,$from_where['args']);
    
        // variable columns. one per dates
        $dates = array();
        while($date1 != $date2) {
            $dates[] =  $date1;
            $parts = explode("-",$date1);
            if (count($parts) != 3) break;
            $date1 = date("Y-m-d",mktime(0,0,0,$parts[1],$parts[2]+1,$parts[0]));
        } 
        $dates[] = $date2;
        
        $this->report_headers = array('UPC', 'Brand', 'Description');
        foreach ($dates as $i) {
            $this->report_headers[] = $i;
        }
        $this->report_headers[] = 'Total';
    
        $current = array('upc'=>'', 'brand'=> '', 'description'=>'');
        $data = array();
        // track upc while going through the rows, storing 
        // all data about a given upc before printing
        while ($row = $dbc->fetchRow($result)){  
            if ($current['upc'] != $row['prodID']){
                if ($current['upc'] != ""){
                    $record = array(
                        $current['upc'], 
                        $current['brand'],
                        $current['description']
                    );
                    $sum = 0.0;
                    foreach ($dates as $i){
                        if (isset($current[$i])){
                            $record[] = sprintf('%.2f', $current[$i]);
                            $sum += $current[$i];
                        } else {
                            $record[] = 0.0;
                        }
                    }
                    $record[] = sprintf('%.2f', $sum);
                    $data[] = $record;
                }
                // update 'current' values and clear data
                // brand may be missing in the case of like codes
                $current = array(
                    'upc'=>$row['prodID'], 
                    'brand' => $row['brand'],
                    'description'=>$row['description']
                );
            }
            // get a yyyy-mm-dd format date from sql results
            $year = $row['year'];
            $month = str_pad($row['month'],2,'0',STR_PAD_LEFT);
            $day = str_pad($row['day'],2,'0',STR_PAD_LEFT);
            $datestr = $year."-".$month."-".$day;
            
            // index result into data based on date string
            // this is to properly place data in the output table
            // even when there are 'missing' days for a given upc
            $current[$datestr] = $row['total'];
        }

        // add the last data set
        $record = array($current['upc'], $current['brand'], $current['description']);
        $sum = 0.0;
        foreach ($dates as $i){
            if (isset($current[$i])){
                $record[] = sprintf('%.2f', $current[$i]);
                $sum += $current[$i];
            } else {
                $record[] = 0.0;
            }
        }
        $record[] = sprintf('%.2f', $sum);
        $data[] = $record;

        return $data;
    }

    public function form_content()
    {
        ob_start();
        $stores = FormLib::storePicker();
        ?>
<form method=get action=TrendsReport.php class="form">
<div class="row">
    <?php echo FormLib::standardItemFields(); ?>
    <?php echo FormLib::standardDateFields(); ?>
</div>
<p>
    <div class="form-inline">
    <label>Store</label>
    <?php echo $stores['html']; ?>
    <button type="submit" class="btn btn-default">Submit</button>
    </div>
</p>
</form>
        <?php
        $this->add_script($this->config->URL . 'item/autocomplete.js');
        $ws = $this->config->URL . 'ws/';
        $this->add_onload_command("bindAutoComplete('#brand-field', '$ws', 'brand');\n");
        $this->add_onload_command("bindAutoComplete('#upc-field', '$ws', 'item');\n");

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Trends shows per-item, per-day sales. Rows are
            items, columns are dates. The department range or brand
            or UPC or like code range controls which set of items
            appear in the report.</p>
            <p>Note this report purposely excludes open rings both
            for performance reasons and to avoid piling on
            extraneous rows.</p>';
    }
}

FannieDispatch::conditionalExec();


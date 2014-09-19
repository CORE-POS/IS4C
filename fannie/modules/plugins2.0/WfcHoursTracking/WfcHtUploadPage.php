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
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!class_exists('WfcHtLib')) {
    require(dirname(__FILE__).'/WfcHtLib.php');
}
if (!function_exists('sys_get_temp_dir')) {
    require($FANNIE_ROOT.'src/tmp_dir.php');
}

class WfcHtUploadPage extends FanniePage
{
    protected $must_authenticate = true;
    protected $auth_classes = array('upload_hours_data');
    protected $header = 'Upload';
    protected $title = 'Upload';
    
    public $page_set = 'Plugin :: WFC Hours Tracking';
    public $description = '[Hours Upload] imports data for hourly employees.';

    private $mode = 'form';

    public function preprocess()
    {
        if (isset($_POST["MAX_FILE_SIZE"])){
            $this->mode = 'upload';
        } elseif (isset($_POST["data"])) {
            $this->mode = 'import';
        }

        return true;
    }

    public function css_content()
    {
        return '
.one {
    background: #ffffff;
}
.one td {
    text-align: right;
}
.two {
    background: #ffffcc;
}
.two td {
    text-align: right;
}
        ';
    }

    private function upload_content()
    {
        $db = WfcHtLib::hours_dbconnect();

        $ADP_COL = 3;
        $HOURS_COL = 6;
        $TYPE_COL = 5;
        $ALT_COL = 4;
        $HEADERS = true;

        $colors = array("one","two");
        $filename = md5(time());
        $tmp = sys_get_temp_dir();
        move_uploaded_file($_FILES['upload']['tmp_name'],"$tmp/$filename");
    
        $start = FormLib::get('start');
        $end = FormLib::get('end');

        $fp = fopen("$tmp/$filename","r");
        $c = 1;
        $ret = "<form action=\"{$_SERVER['PHP_SELF']}\" method=post>";
        $ret .= "<b>Pay Period</b>: $start - $end<br />";
        $ret .= "<input type=hidden name=start value=\"$start\" />";
        $ret .= "<input type=hidden name=end value=\"$end\" />";
        $ret .= "<table cellpadding=4 cellspacing=0 border=1>";
        $ret .= "<tr class=one><th>ADP ID</th><th>Reg. Hours</th><th>OT Hours</th>";
        $ret .= "<th>PTO</th><th>UTO</th><th>Alt. Rate</th><th>Holiday</th></tr>";

        $rows = array();
        $checkQ = $db->prepare_statement("select empID from employees where adpID=?");
        while (!feof($fp)){
            $fields = fgetcsv($fp);
            if ($HEADERS){
                $HEADERS = false;
                continue;
            }
            if (count($fields) == 0) {
                continue;
            }
            if (!isset($fields[$ADP_COL])) {
                continue;
            }

            $adpID = ltrim($fields[$ADP_COL],"U8U");
            if (!isset($rows[$adpID])){
                $rows[$adpID] = array(
                    "regular"=>0.0,
                    "overtime"=>0.0,
                    "pto"=>0.0,
                    "uto"=>0.0,
                    "alt"=>0.0,
                    "holiday"=>0.0
                );
            }

            $checkR = $db->exec_statement($checkQ, array($adpID));
            if ($db->num_rows($checkR) < 1){
                $ret .= "Notice: ADP ID #$adpID doesn't match any current employee.";
                $ret .= "Data for this ID is being omitted.<br />";
                foreach($fields as $f) {
                    $ret .= $f.' ';
                }
                $ret .= '<hr />';
                continue;
            }

            $hours = 0;
            if (is_numeric($fields[$HOURS_COL])) {
                $hours = $fields[$HOURS_COL];
            }

            switch(strtoupper($fields[$TYPE_COL])){
                case 'REGLAR':
                    if (substr($fields[$ALT_COL],-1)=="0")
                        $rows[$adpID]['regular'] += $hours; 
                    else
                        $rows[$adpID]['alt'] += $hours;
                    break;
                case 'REGRT2':
                    $rows[$adpID]['alt'] += $hours;
                    break;
                case 'OVTIME':
                    $rows[$adpID]['overtime'] += $hours;
                    break;
                case 'PERSNL':
                    $rows[$adpID]['pto'] += $hours;
                    break;
                case 'UTO':
                    $rows[$adpID]['uto'] += $hours;
                    break;
                case 'WRKHOL':
                    $rows[$adpID]['regular'] += $hours;
                    break;
                case 'HOLDAY':
                    $rows[$adpID]['holiday'] += $hours;
                    break;
                default:
                    $ret .= "Unknown type: ".$fields[$TYPE_COL]."<br />";
            }   
        }

        foreach($rows as $adpID => $row){
            $ret .= "<tr class=$colors[$c]>";
            $ret .= "<td>$adpID</td><td>{$row['regular']}</td><td>{$row['overtime']}</td>";
            $ret .= "<td>{$row['pto']}</td><td>{$row['uto']}</td><td>{$row['alt']}</td>";
            $ret .= "<td>{$row['holiday']}</td>";
            $ret .= "</tr>";

            $ret .= sprintf("<input type=hidden name=data[] value=\"%d,%f,%f,%f,%f,%f,%f\" />",
                $adpID,$row['regular'],$row['overtime'],$row['pto'],
                $row['uto'],$row['alt'],$row['holiday']
            );
        
            $c = ($c+1)%2;
        }
        $ret .= "</table>";
        $ret .= "<input type=submit value=\"Import Data\">";
    
        fclose($fp);
        unlink("$tmp/$filename");

        return $ret;    
    }

    private function import_content()
    {
        $db = WfcHtLib::hours_dbconnect();

        $datalines = FormLib::get('data');
        $start = FormLib::get('start');
        $end = FormLib::get('end');

        $dateStr = date('n/j/Y', strtotime($start)).' - '.date('n/j/Y', strtotime($end));
        $year = date('Y', strtotime($start));
    
        $ppIDQ = "select max(periodID)+1 from PayPeriods";
        $ppIDR = $db->query($ppIDQ);
        $ppIDW = $db->fetch_row($ppIDR);
        $ppID = $ppIDW[0];

        $ppQ = $db->prepare_statement("INSERT INTO PayPeriods (periodID, dateStr, year, startDate, endDate) 
                                    VALUES (?,?,?,?,?)");
        $ppR = $db->exec_statement($ppQ, array($ppID, $dateStr, $year, $start, $end));

        $eIDQ = $db->prepare_statement("select empID from employees where adpID=?");
        $insQ = $db->prepare_statement("INSERT INTO ImportedHoursData 
                    VALUES (?,?,?,?,?,?,0,?,?,?)");
        foreach ($datalines as $line) {
            $fields = explode(",",$line);
            $eIDR = $db->exec_statement($eIDQ, array($fields[0]));
            if ($db->num_rows($eIDR) < 1) {
                continue;
            }
            $eIDW = $db->fetch_row($eIDR);
            $empID = $eIDW['empID'];

            $insR = $db->exec_statement($insQ, array($empID, $ppID, $year, $fields[1],
                                $fields[2], $fields[3],
                                $fields[5], $fields[6],
                                $fields[4]));
        }

        $cuspQ = $db->prepare_statement("UPDATE cusping as c 
            left join employees as e
            on c.empID = e.empID
            SET e.PTOLevel=e.PTOLevel+1, e.PTOCutoff=?
            where c.cusp = '!!!'");
        $cuspR = $db->exec_statement($cuspQ, array($ppID));

        $ret = "ADP data import complete!<br />";
        $ret .= "<a href=WfcHtListPage.php>View Employees</a><br />";
        $ret .= "<a href=WfcHtPayPeriodsPage.php>View Pay Periods</a>";
    
        return $ret;
    }

    private function form_content()
    {
        global $FANNIE_URL;
        echo '
<form enctype="multipart/form-data" action="'.$_SERVER['PHP_SELF'].'" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
Pay Period: <input type=text name=start id="start" />
<input type=text name=end id="end" /><p />
Holiday Hours: <select name=asHoliday><option value=1>As Holiday</option><option value=0>As Hours Worked</option>
</select><p />
Filename: <input type="file" id="file" name="upload" />
<input type="submit" value="Upload File" />
</form>
        ';
        $this->add_onload_command("\$('#start').datepicker();\n");
        $this->add_onload_command("\$('#end').datepicker();\n");
    }

    public function body_content()
    {
        switch($this->mode) {
            case 'upload':
                return $this->upload_content();
            case 'import':
                return $this->import_content();
            case 'form':
            default:
                return $this->form_content();
        }
    }
}

FannieDispatch::conditionalExec();


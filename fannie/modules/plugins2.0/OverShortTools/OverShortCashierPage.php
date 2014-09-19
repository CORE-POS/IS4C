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
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class OverShortCashierPage extends FanniePage {
    
    // 10Nov13 EL Added title and header
    protected $title = 'Over/Short Single Cashier';
    protected $header = 'Over/Short Single Cashier';
    protected $window_dressing = False;
    protected $auth_classes = array('overshorts');
    public $page_set = 'Plugin :: Over/Shorts';
    public $description = '[Single Cashier] allows viewing and entering tender amounts by cashier.';

    // 10Nov13 EL Added constructor
    public function __construct() {
        global $FANNIE_WINDOW_DRESSING;
        // To set authentication.
        parent::__construct();
        if (isset($FANNIE_WINDOW_DRESSING))
            $this->has_menus($FANNIE_WINDOW_DRESSING);
    }

    function preprocess(){
        $action = FormLib::get_form_value('action',False);
        if ($action !== False){
            $this->ajaxRequest($action);
            return False;
        }
        return True;
    }

    function ajaxRequest($action){
        global $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['OverShortDatabase']);
        $date = FormLib::get_form_value('date');
        $empno = FormLib::get_form_value('empno');
        switch($action){
        case 'loadCashier':
            echo $this->displayCashier($date,$empno);
            break;
        case 'save':
            $tenders = FormLib::get_form_value('tenders');
            $notes = FormLib::get_form_value('notes');
            $checks = FormLib::get_form_value('checks');
            echo $this->save($empno,$date,$tenders,$checks,$notes);
            break;
        }
    }

    function displayCashier($date,$empno){
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['OverShortDatabase']);

        $dlog = DTransactionsModel::selectDlog($date);

        $totals = array();
        $counts = array();
        $names = array();
        $counts["SCA"] = 0.00;

        $totalsQ = "SELECT 
            CASE WHEN trans_subtype IN ('CC','AX') THEN 'CC' ELSE trans_subtype END
            as trans_subtype,MAX(TenderName) as TenderName,
            -1*SUM(total) FROM $dlog as d LEFT JOIN "
            .$FANNIE_OP_DB.$dbc->sep()."tenders as t
            ON d.trans_subtype=t.TenderCode
            WHERE emp_no = ?
            AND tdate BETWEEN ? AND ?
            AND trans_type='T'
            AND d.upc NOT IN ('0049999900001', '0049999900002')
            GROUP BY 
            CASE WHEN trans_subtype IN ('CC','AX') THEN 'CC' ELSE trans_subtype END
            ORDER BY TenderID";
        $totalsP = $dbc->prepare_statement($totalsQ);
        $totalsR = $dbc->exec_statement($totalsP, array($empno,$date.' 00:00:00',$date.' 23:59:59'));
        while($totalsW = $dbc->fetch_row($totalsR)){
            if (in_array($totalsW['trans_subtype'], OverShortTools::$EXCLUDE_TENDERS)) {
                continue;
            }
            $totals[$totalsW[0]] = $totalsW[2];
            $names[$totalsW[0]] = $totalsW[1];
            $counts[$totalsW[0]] = 0.00;
        }

        $model = new DailyCountsModel($dbc);
        $model->date($date);
        $model->emp_no($empno);
        foreach($model->find() as $obj)
            $counts[$obj->tender_type()] = $obj->amt();

        $posTotal = 0;
        $countTotal = 0;
        $osTotal = 0;

        // cash + checks required
        if (!isset($totals['CA'])) $totals['CA'] = 0.00;
        if (!isset($counts['CA'])) $counts['CA'] = 0.00;
        if (!isset($totals['CK'])) $totals['CK'] = 0.00;
        if (!isset($counts['CK'])) $counts['CK'] = 0.00;
        
        $ret = "";
        $ret .= "<b>$date</b> - Emp. #$empno</b><br />";    
        $ret .= "<i>Starting cash</i>: <input type=text onchange=\"resumSheet();\"  id=countSCA size=5 value=\"".$counts['SCA']."\" /><br />";
        $posTotal += $counts['SCA'];
        $ret .= "<input type=\"hidden\" class=\"tenderCode\" value=\"CA\" />";
        $ret .= "<input type=\"hidden\" class=\"tenderCode\" value=\"CK\" />";
        $ret .= "<form onsubmit=\"save(); return false;\">";
        $ret .= "<table cellpadding=4 cellspacing=0 border=1>";
        $ret .= "<tr class=color><th>Cash</th><td>POS</td><td>Count</td><td>O/S</td>";
        $ret .= "<td>&nbsp;</td>";
        $ret .= "<th>Checks</th><td>POS</td><td>Count</td><td>O/S</td><td>List checks</td></tr>";

        $ret .= "<tr>";
        $ret .= "<td>&nbsp;</td>";
        $ret .= "<td id=posCA>".$totals['CA']."</td>";
        $ret .= "<td><input type=text onchange=\"resumSheet();\"  size=5 id=countCA value=\"".$counts['CA']."\" /></td>";
        $os = round($counts['CA'] - $totals['CA'] - $counts['SCA'],2);
        $ret .= "<td id=osCA>$os</td>";

        $posTotal += $totals['CA'];
        $countTotal += $counts['CA'];
        $osTotal += $os;

        $ret .= "<td>&nbsp;</td>";

        $ret .= "<td>&nbsp;</td>";
        $ret .= "<td id=posCK>".$totals['CK']."</td>";
        $ret .= "<td id=countCK>".$counts['CK']."</td>";
        $os = round($counts['CK'] - $totals['CK'],2);
        $ret .= "<td id=osCK>$os</td>";
        $checks = "";
        $model = new DailyChecksModel($dbc);
        $model->date($date);
        $model->emp_no($empno);
        $model->load();
        $checks = "";
        foreach( explode(",",$model->checks()) as $c){
            if (is_numeric($c))
                $checks .= "$c\n";
        }
        $checks = substr($checks,0,strlen($checks)-1);
        $ret .= "<td rowspan=7><textarea rows=11 cols=7 id=checklisting onchange=\"resumChecks();\">$checks</textarea></td>";
        $ret .= "</tr>";

        $posTotal += $totals['CK'];
        $countTotal += $counts['CK'];
        $osTotal += $os;

        $codes = array_keys($totals);
        for($i=0;$i<count($codes);$i++){
            $code = $codes[$i];
            if ($code == 'CA') continue;
            if ($code == 'CK') continue;
            $next = False;
            for($j=$i+1;$j<count($codes);$j++){
                if($codes[$j] == 'CA') continue;
                if($codes[$j] == 'CK') continue;
                $next = $codes[$j];
                $i += 1; // we're consuming two entries this iteration
                break;
            }

            $ret .= "<tr><td colspan=9 height=4><span style=\"font-size:1%;\">&nbsp;</span></td></tr>";

            $ret .= "<tr class=color><th>".$names[$code]."</th><td>POS</td><td>Count</td><td>O/S</td>";
            $ret .= "<input type=\"hidden\" class=\"tenderCode\" value=\"$code\" />";
            $ret .= "<td>&nbsp;</td>";
            if ($next){
                $ret .= "<th>".$names[$next]."</th><td>POS</td><td>Count</td><td>O/S</td></tr>";
                $ret .= "<input type=\"hidden\" class=\"tenderCode\" value=\"$next\" />";
            }
            else
                $ret .= "<th colspan=\"4\">&nbsp;</th></tr>";

            $ret .= "<tr>";
            $ret .= "<td>&nbsp;</td>";
            $ret .= "<td id=pos$code>".$totals[$code]."</td>";
            $ret .= "<td><input type=text onchange=\"resumSheet();\"  size=5 id=count$code value=\"".$counts[$code]."\" /></td>";
            if (!isset($counts[$code])) $counts[$code] = 0.00;
            $os = round($counts[$code] - $totals[$code],2);
            $ret .= "<td id=osCC>$os</td>";

            $posTotal += $totals[$code];
            $countTotal += $counts[$code];
            $osTotal += $os;
        
            $ret .= "<td>&nbsp;</td>";

            if ($next){
                $ret .= "<td>&nbsp;</td>";
                $ret .= "<td id=pos$next>".$totals[$next]."</td>";
                $ret .= "<td><input type=text onchange=\"resumSheet();\"  size=5 id=count$next value=\"".$counts[$next]."\" /></td>";
                if (!isset($counts[$next])) $counts[$next] = 0.00;
                $os = round($counts[$next] - $totals[$next],2);
                $ret .= "<td id=os$next>$os</td>";

                $posTotal += $totals[$next];
                $countTotal += $counts[$next];
                $osTotal += $os;
            }
            else
                $ret .= "<td colspan=\"4\">&nbsp;</td>";
            $ret .= "</tr>";
        }

        $ret .= "<tr><td colspan=10 height=4><span style=\"font-size:1%;\">&nbsp;</span></td></tr>";

        $ret .= "<tr class=color>";
        $ret .= "<th>Totals</th><td>POS</td><td>Count</td><td>O/S</td><td>&nbsp;</td>";
        $model = new DailyNotesModel($dbc);
        $model->date($date);
        $model->emp_no($empno);
        $model->load();
        $note = str_replace("''","'",$model->note());
        $ret .= "<td colspan=5 rowspan=2><textarea id=notes rows=4 cols=40>$note</textarea></td></tr>";
        $ret .= "<tr>";
        $ret .= "<td>&nbsp;</td>";
        $ret .= "<td id=posT>$posTotal</td>";
        $ret .= "<td id=countT>$countTotal</td>";
        $ret .= "<td id=osT>$osTotal</td>";
        $ret .= "<td>&nbsp;</td>";
        $ret .= "</tr>";

        $ret .= "</table>";
        $ret .= "<input type=submit value=Save />";
        $ret .= "</form>";

        $ret .= "<input type=hidden id=current_empno value=\"$empno\" />";
        $ret .= "<input type=hidden id=current_date value=\"$date\" />";

        return $ret;
    }

    function save($empno,$date,$tenders,$checks,$notes){
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['OverShortDatabase']);
    
        $model = new DailyNotesModel($dbc);
        $model->date($date);
        $model->emp_no($empno);
        $notes = str_replace("'","''",urldecode($notes));
        $model->note($notes);
        $model->save();

        $model = new DailyChecksModel($dbc);
        $model->date($date);
        $model->emp_no($empno);
        $model->checks($checks);
        $model->save();

        $model = new DailyCountsModel($dbc);
        $model->date($date);
        $model->emp_no($empno);
        $tarray = explode("|",$tenders);
        foreach($tarray as $t){
            $temp = explode(":",$t);
            if (count($temp) != 2) continue;
            if (!is_numeric($temp[1])) continue;

            $tender = $temp[0];
            $amt = $temp[1];

            $model->tender_type($tender);
            $model->amt($amt);
            $model->save();
        }

        return "Saved";
    }
    
    function css_content(){
        return '
            tr.color {
                background: #ffffcc;
            }
            body, table, td, th {
              color: #000;
            }
        ';  
    }

    function body_content(){
        global $FANNIE_URL;
        ob_start();
        $this->add_script('js/cashier.js'); 
        $this->add_script($FANNIE_URL.'src/javascript/jquery.js');
        $this->add_script($FANNIE_URL.'src/javascript/jquery-ui.js');
        $this->add_css_file($FANNIE_URL.'src/style.css');
        $this->add_css_file($FANNIE_URL.'src/javascript/jquery-ui.css');
        $this->add_onload_command("\$('#date').datepicker();");
        if (!$this->window_dressing) {
            echo "<html>";
            echo "<head><title>{$this->title}</title>";
            echo "</head>";
            echo "<body>";
        }
        ?>
        <div id=input>
        <form style='margin-top:1.0em;' onsubmit="loadCashier(); return false;">
        <b>Date</b>:<input type=text  id=date size=10 />
        <b>Cashier</b>:<input type=text  id=empno size=5 /> 
        <input type=submit value="Load Cashier" />
        </form>
        </div>

        <div id=display>
        </div>
        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

?>

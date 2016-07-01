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

class OverShortCashierPage extends FanniePage {
    
    // 10Nov13 EL Added title and header
    protected $title = 'Over/Short Single Cashier';
    protected $header = 'Over/Short Single Cashier';
    protected $auth_classes = array('overshorts');
    public $page_set = 'Plugin :: Over/Shorts';
    public $description = '[Single Cashier] allows viewing and entering tender amounts by cashier.';
    public $themed = true;

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
        $date = FormLib::get('date');
        $empno = FormLib::get('empno');
        $mode = FormLib::get('mode');
        switch($action){
        case 'loadCashier':
            echo $this->displayCashier($date,$empno,$mode);
            break;
        case 'save':
            $tenders = FormLib::get_form_value('tenders');
            $notes = FormLib::get_form_value('notes');
            $checks = FormLib::get_form_value('checks');
            $store = FormLib::get('store');
            echo $this->save($empno,$date,$store,$tenders,$checks,$notes);
            break;
        }
    }

    function displayCashier($date,$empno,$mode)
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['OverShortDatabase']);
        $store = FormLib::get('store', 1);

        $dlog = DTransactionsModel::selectDlog($date);

        $totals = array();
        $counts = array();
        $names = array();
        $counts["SCA"] = 0.00;
        $filter_field = (strtolower($mode)==='cashier') ? 'emp_no' : 'register_no';
        $filter_label = (strtolower($mode)==='cashier') ? 'Emp.' : 'Lane';

        $args = array($empno, $date.' 00:00:00',$date.' 23:59:59');
        $totalsQ = "SELECT 
            CASE WHEN trans_subtype IN ('CC','AX') THEN 'CC' ELSE trans_subtype END
            as trans_subtype,MAX(TenderName) as TenderName,
            -1*SUM(total) FROM $dlog as d LEFT JOIN "
            .$FANNIE_OP_DB.$dbc->sep()."tenders as t
            ON d.trans_subtype=t.TenderCode
            WHERE $filter_field = ?
            AND tdate BETWEEN ? AND ?
            AND trans_type='T' ";
        if ($this->config->get('COOP_ID') == 'WFC_Duluth') {
            $totalsQ .= " AND d.upc NOT IN ('0049999900001', '0049999900002') ";
        }
        if ($store != 0) {
            $totalsQ .= ' AND d.store_id = ? ';
            $args[] = $store;
        }
        $totalsQ .= " GROUP BY 
            CASE WHEN trans_subtype IN ('CC','AX') THEN 'CC' ELSE trans_subtype END
            ORDER BY TenderID";
        $totalsP = $dbc->prepare($totalsQ);
        $totalsR = $dbc->execute($totalsP, $args);
        while ($totalsW = $dbc->fetchRow($totalsR)){
            if (in_array($totalsW['trans_subtype'], OverShortTools::$EXCLUDE_TENDERS)) {
                continue;
            }
            $totals[$totalsW[0]] = $totalsW[2];
            $names[$totalsW[0]] = $totalsW[1];
            $code = $totalsW[0];
            if ($code !== 'CA' && $code !== 'CK' && $code !== 'WT') {
                $counts[$code] = $totals[$code];
            } else {
                $counts[$totalsW[0]] = 0.00;
            }
        }

        $model = new DailyCountsModel($dbc);
        $model->date($date);
        $model->emp_no($empno);
        $model->storeID($store);
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
        $ret .= "<b>$date</b> - $filter_label. #$empno<br />";    
        $ret .= '<div class="form-group form-inline">';
        $ret .= "<label>Starting cash</label>: 
            <div class=\"input-group\">
            <span class=\"input-group-addon\">\$</span>
            <input class=\"form-control form-control-sm\" type=text onchange=\"resumSheet();\" 
                id=countSCA value=\"".$counts['SCA']."\" />
            </div>
            </div>";
        $posTotal += $counts['SCA'];
        $ret .= "<input type=\"hidden\" class=\"tenderCode\" value=\"CA\" />";
        $ret .= "<input type=\"hidden\" class=\"tenderCode\" value=\"CK\" />";
        $ret .= "<form onsubmit=\"save(); return false;\">";
        $ret .= "<table class=\"table\">";
        $ret .= "<tr class=color><th>Cash</th><td>POS</td><td>Count</td><td>O/S</td><td>Cash Counter</td>";
        $ret .= "<td>&nbsp;</td>";
        $ret .= "<th>Checks</th><td>POS</td><td>Count</td><td>O/S</td><td>List checks</td></tr>";

        $ret .= "<tr>";
        $ret .= "<td>&nbsp;</td>";
        $ret .= "<td id=posCA>".$totals['CA']."</td>";
        $ret .= "<td><div class=\"input-group\">
            <span class=\"input-group-addon\">\$</span>
            <input type=text onchange=\"resumSheet();\" id=countCA 
                class=\"form-control form-control-sm\" value=\"".$counts['CA']."\" />
            </div></td>";
        $os = round($counts['CA'] - $totals['CA'] - $counts['SCA'],2);
        $ret .= "<td id=osCA>$os</td>";

        $posTotal += $totals['CA'];
        $countTotal += $counts['CA'];
        $osTotal += $os;

        $ret .= "<td rowspan=7><textarea rows=11 cols=7 id=cash-counter 
            class=\"form-control\" onchange=\"sumCashCounter();\"></textarea></td>";
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
        $model->storeID($store);
        $model->load();
        $checks = "";
        foreach( explode(",",$model->checks()) as $c){
            if (is_numeric($c))
                $checks .= "$c\n";
        }
        $checks = substr($checks,0,strlen($checks)-1);
        $ret .= "<td rowspan=7><textarea rows=11 cols=7 id=checklisting 
            class=\"form-control\" onchange=\"resumChecks();\">$checks</textarea></td>";
        $ret .= "</tr>";

        $posTotal += $totals['CK'];
        $countTotal += $counts['CK'];
        $osTotal += $os;

        $codes = array_keys($totals);
        for ($i=0;$i<count($codes);$i++) {
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

            //$ret .= "<tr><td colspan=9 height=4><span style=\"font-size:1%;\">&nbsp;</span></td></tr>";

            $ret .= "<tr class=color><th class=\"small\">".$names[$code]."</th><td>POS</td><td>Count</td><td>O/S</td>";
            $ret .= "<input type=\"hidden\" class=\"tenderCode\" value=\"$code\" />";
            $ret .= "<td>&nbsp;</td>";
            if ($next) {
                $ret .= "<th class=\"small\">".$names[$next]."</th><td>POS</td><td>Count</td><td>O/S</td></tr>";
                $ret .= "<input type=\"hidden\" class=\"tenderCode\" value=\"$next\" />";
            } else {
                $ret .= "<th colspan=\"4\">&nbsp;</th></tr>";
            }

            $ret .= "<tr>";
            $ret .= "<td>&nbsp;</td>";
            $ret .= "<td id=pos$code>".$totals[$code]."</td>";
            $ret .= "<td><div class=\"input-group\">
                <span class=\"input-group-addon\">\$</span>
                <input type=text onchange=\"resumSheet();\" id=count$code 
                    class=\"form-control form-control-sm\" value=\"".$counts[$code]."\" />
                </div></td>";
            $os = round($counts[$code] - $totals[$code],2);
            $ret .= "<td id=osCC>$os</td>";

            $posTotal += $totals[$code];
            $countTotal += $counts[$code];
            $osTotal += $os;
        
            $ret .= "<td>&nbsp;</td>";

            if ($next) {
                $ret .= "<td>&nbsp;</td>";
                $ret .= "<td id=pos$next>".$totals[$next]."</td>";
                $ret .= "<td><div class=\"input-group\">
                    <span class=\"input-group-addon\">\$</span>
                    <input type=text onchange=\"resumSheet();\" id=count$next 
                        class=\"form-control form-control-sm\" value=\"".$counts[$next]."\" />
                    </div></td>";
                if (!isset($counts[$next])) $counts[$next] = 0.00;
                $os = round($counts[$next] - $totals[$next],2);
                $ret .= "<td id=os$next>$os</td>";

                $posTotal += $totals[$next];
                $countTotal += $counts[$next];
                $osTotal += $os;
            } else {
                $ret .= "<td colspan=\"4\">&nbsp;</td>";
            }
            $ret .= "</tr>";
        }

        $ret .= "<tr><td colspan=10 height=4><span style=\"font-size:1%;\">&nbsp;</span></td></tr>";

        $ret .= "<tr class=color>";
        $ret .= "<th>Totals</th><td>POS</td><td>Count</td><td>O/S</td><td>&nbsp;</td>";
        $model = new DailyNotesModel($dbc);
        $model->date($date);
        $model->emp_no($empno);
        $model->storeID($store);
        $model->load();
        $note = str_replace("''","'",$model->note());
        $ret .= "<td colspan=5 rowspan=2><textarea id=notes class=\"form-control\">$note</textarea></td></tr>";
        $ret .= "<tr>";
        $ret .= "<td>&nbsp;</td>";
        $ret .= "<td id=posT>$posTotal</td>";
        $ret .= "<td id=countT>$countTotal</td>";
        $ret .= "<td id=osT>$osTotal</td>";
        $ret .= "<td>&nbsp;</td>";
        $ret .= "</tr>";

        $ret .= "</table>";
        $ret .= "<p><button type=submit class=\"btn btn-default\">Save</button></p>";
        $ret .= "</form>";

        $ret .= "<input type=hidden id=current_empno value=\"$empno\" />";
        $ret .= "<input type=hidden id=current_date value=\"$date\" />";
        $ret .= "<input type=hidden id=current_store value=\"$store\" />";

        return $ret;
    }

    function save($empno,$date,$store,$tenders,$checks,$notes){
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['OverShortDatabase']);
    
        $model = new DailyNotesModel($dbc);
        $model->date($date);
        $model->emp_no($empno);
        $model->storeID($store);
        $notes = str_replace("'","''",urldecode($notes));
        $model->note($notes);
        $model->save();

        $model = new DailyChecksModel($dbc);
        $model->date($date);
        $model->emp_no($empno);
        $model->storeID($store);
        $model->checks($checks);
        $model->save();

        $model = new DailyCountsModel($dbc);
        $model->date($date);
        $model->emp_no($empno);
        $model->storeID($store);
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
        /**
          jQuery-UI datepicker pops *under* other
          elements for some reason. Issue seems isolated
          to this particular page.
        */
        return '
            tr.color {
                background: #ffffcc;
            }
            body, table, td, th {
              color: #000;
            }
            .ui-datepicker {
                z-index: 100 !important;
            }
        ';  
    }

    function body_content()
    {
        ob_start();
        $this->add_script('js/cashier.js'); 
        if (!$this->window_dressing) {
            echo "<html>";
            echo "<head><title>{$this->title}</title>";
            echo "</head>";
            echo "<body>";
        }
        ?>
        <div id=input class="form-inline form-group">
        <form id="osForm" style='margin-top:1.0em;' onsubmit="loadCashier(); return false;">
        <label>Date</label>:<input class="form-control date-field" type=text name=date id=date required />
        <select name="mode" class="form-control"><option>Drawer</option><option>Cashier</option></select>
        :<input type=text name=empno id=empno class="form-control" placeholder="Lane or Employee #" required />
        <?php
        $sp = FormLib::storePicker('store', false);
        echo $sp['html'];
        ?>
        <button type=submit class="btn btn-default">Load</button>
        </form>
        </div>

        <div id=display>
        </div>
        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);


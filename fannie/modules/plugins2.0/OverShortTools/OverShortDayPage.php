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

class OverShortDayPage extends FanniePage 
{
    // 10Nov13 EL Added title and header
    protected $title = 'Over/Short Whole Day';
    protected $header = 'Over/Short Whole Day';
    protected $auth_classes = array('overshorts');
    public $page_set = 'Plugin :: Over/Shorts';
    public $description = '[Single Day] allows viewing and entering tender amounts for all
    cashiers on a given day.';
    public $themed = true;

    function preprocess()
    {
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
        switch($action){
        case 'save':
            $date = FormLib::get_form_value('curDate');
            $data = FormLib::get_form_value('data');
            $user = FormLib::get_form_value('user');
            $resolved = FormLib::get_form_value('resolved');
            $notes = FormLib::get_form_value('notes');
            $store = FormLib::get('store');
    
            $model = new OverShortsLogModel($dbc);
            $model->date($date);
            $model->username($user);
            $model->resolved($resolved);
            $model->storeID($store);
            $model->save();
            
            $this->save($date,$store,$data);
            $this->saveNotes($date,$store,$notes);
            echo "saved";
            break;

        case 'date':
            $date = FormLib::get_form_value('arg');
            if (empty($date)) {
                $date = date('Y-m-d');
            }
            $store = FormLib::get('store', 1);
            $dlog = DTransactionsModel::selectDlog($date);
            $args = array($date . ' 00:00:00', $date . ' 23:59:59', $store);

            /**
              Mode toggles how totals are calculated. Cashier mode lists totals for
              each cashier. Drawer mode lists a total for each register. Some of the code that
              follows (and underlying database) uses "emp_no" when it really means "whichever 
              identifier we're grouping by".
            */
            $mode = FormLib::get('mode', 'cashier');
            $name_field = $mode == 'cashier' ? 'e.firstname' : $dbc->concat("'Register '", 'd.register_no', '');
            $id_field = $mode == 'cashier' ? 'd.emp_no' : 'd.register_no';
            $id_field_name = $mode == 'cashier' ? 'emp_no' : 'register_no';
            
            $empsR = null;
            if (FormLib::get_form_value('emp_no') !== ''){
                /* get info for single employee */
                $empsQ = "SELECT e.firstname, e.emp_no FROM "
                    .$FANNIE_OP_DB.$dbc->sep()."employees AS e
                    WHERE emp_no=?";
                $empsP = $dbc->prepare($empsQ);
                $empsR = $dbc->execute($empsP,array(FormLib::get_form_value('emp_no')));
            } else {
                /* determine who worked that day (and their first names) */
                $empsQ = "
                    SELECT $name_field,
                        $id_field 
                    FROM $dlog AS d
                        LEFT JOIN $FANNIE_OP_DB".$dbc->sep()."employees AS e ON d.emp_no=e.emp_no
                    WHERE d.tdate BETWEEN ? AND ? 
                        AND trans_type='T' 
                        AND d.store_id = ? ";
                if ($this->config->get('COOP_ID') == 'WFC_Duluth') {
                    $empsQ .= " AND d.upc NOT IN ('0049999900001', '0049999900002') ";
                }
                $empsQ .= " 
                    GROUP BY $id_field,
                        $name_field 
                    ORDER BY $name_field";
                $empsP = $dbc->prepare($empsQ);
                $empsR=$dbc->execute($empsP,$args);
            }
            $output = "<h3 id=currentdate>$date</h3>";
            $output .= '<input type="hidden" id="currentstore" value="' . $store . '" />';

            $output .= "<form onsubmit=\"save(); return false;\">";
            $output .= "<table class=\"table table-striped\"><tr>";
            $output .= "<th>Name</th><th>&nbsp;</th><th>Total</th><th>Counted Amt</th><th>Over/Short</th></tr>";

            $tQ = "SELECT d.trans_subtype,t.TenderName FROM $dlog as d, "
                .$FANNIE_OP_DB.$dbc->sep()."tenders AS t 
                WHERE d.tdate BETWEEN ? AND ? AND trans_type='T'
                    AND d.trans_subtype = t.TenderCode 
                    AND d.store_id=? ";
            if ($this->config->get('COOP_ID') == 'WFC_Duluth') {
                $tQ .= " AND d.upc NOT IN ('0049999900001', '0049999900002') ";
            }
            $tQ .= " GROUP BY d.trans_subtype, t.TenderName, t.tenderID
                ORDER BY t.TenderID";
            $tP = $dbc->prepare($tQ);
            $tR=$dbc->execute($tP,$args);

            $tender_info = array();
            while($tW = $dbc->fetch_row($tR)){
                if ($tW['trans_subtype'] == 'AX') {
                    continue; // group AMEX w/ other credit
                } else if (in_array($tW['trans_subtype'], OverShortTools::$EXCLUDE_TENDERS)) {
                    continue;
                }
                $record = array(
                    'name' => $tW['TenderName'],
                    'posTtl' => 0.0,
                    'countTtl' => 0.0,
                    'osTtl' => 0.0,
                    'perEmp' => array()
                );
                $tender_info[$tW['trans_subtype']] = $record;
            }
            if ($this->config->get('COOP_ID') == 'WFC_Duluth' && !isset($tender_info['CK'])) {
                $tender_info['CK'] = array(
                    'name' => 'Check',
                    'posTtl' => 0.0,
                    'countTtl' => 0.0,
                    'osTtl' => 0.0,
                    'perEmp' => array(),
                );
            }
    
            $overallTotal = 0;
            $overallCountTotal = 0;
            $overallOSTotal = 0;    

            /* get cash, check, and credit totals for each employee
            print them in a table along with input boxes for over/short */
            $q = "SELECT -1*sum(total) AS total,$id_field,
                CASE WHEN trans_subtype IN ('CC','AX') THEN 'CC' ELSE trans_subtype END
                AS trans_subtype
                FROM $dlog AS d
                WHERE tdate BETWEEN ? AND ? 
                AND trans_type='T' 
                AND d.store_id=? ";
            if ($this->config->get('COOP_ID') == 'WFC_Duluth') {
                $q .= " AND d.upc NOT IN ('0049999900001', '0049999900002') ";
            }
            if (FormLib::get_form_value('emp_no') !== ''){
                $q .= ' AND ' . $id_field . '=? ';
                $args[] = FormLib::get_form_value('emp_no');
            }
            $q .= "GROUP BY $id_field,
                CASE WHEN trans_subtype IN ('CC','AX') THEN 'CC' ELSE trans_subtype END";
            $p = $dbc->prepare($q);
            $r = $dbc->execute($p, $args);
            $posttl = array();
            while($w = $dbc->fetch_row($r)){
                if (in_array($w['trans_subtype'], OverShortTools::$EXCLUDE_TENDERS)) {
                    continue;
                }
                $tender_info[$w['trans_subtype']]['perEmp'][$w[1]] = $w['total'];
            }

            $noteP = $dbc->prepare('SELECT note FROM dailyNotes WHERE emp_no=? AND date=? AND storeID=?');
            $scaP = $dbc->prepare('SELECT amt FROM dailyCounts WHERE date=? AND emp_no=? AND storeID=?
                            AND tender_type=\'SCA\'');
            $countP = $dbc->prepare("select amt from dailyCounts where date=? and emp_no=? and tender_type=? AND storeID=?");

            while ($row = $dbc->fetchRow($empsR)){
                $emp_no = $row[1];
                $perCashierTotal = 0;
                $perCashierCountTotal = 0;
                $perCashierOSTotal = 0;

                $noteR = $dbc->execute($noteP, array($emp_no, $date, $store));
                $noteW = $dbc->fetchRow($noteR);
                $note = stripslashes($noteW[0]);

                $output .= "<input type=hidden class=\"cashier\" value=\"$row[1]\" />";
      
                $output .= "<tr><td><a href=OverShortDayPage.php?action=date&arg=$date&emp_no=$row[1] target={$date}_{$row[1]}>$row[0]</a></td>";
                $output .= "<td>Starting cash</td><td>n/a</td>";
                $fetchR = $dbc->execute($scaP, array($date, $emp_no, $store));
                $startcash = 0;
                if ($dbc->num_rows($fetchR) != 0) {
                    $fetchW = $dbc->fetch_row($fetchR);
                    $startcash = $fetchW[0];
                }
                $output .= "<td><div class=\"input-group\">
                    <span class=\"input-group-addon\">\$</span>
                    <input type=text id=startingCash$row[1] class=\"form-control form-control-sm startingCash\" value=\"";
                $output .= $startcash;
                $output .= "\" onchange=\"calcOS('Cash',$row[1]);\" />
                    </div></td><td>n/a</td></tr>";
                $perCashierCountTotal -= $startcash;
                $tender_info['CA']['countTtl'] -= $startcash;

                foreach ($tender_info as $code => $info) {
                    $posAmt = 0;    
                    if (isset($info['perEmp'][$emp_no]))
                        $posAmt = $info['perEmp'][$emp_no];
                    $output .= "<tr><td>&nbsp;</td><td>".$info['name']."</td>
                        <td id=dlog$code$row[1]>$posAmt</td>";
                    $output .= "<input type=\"hidden\" class=\"tcode$emp_no\" value=\"$code\" />";

                    $fetchR = $dbc->execute($countP, array($date, $emp_no, $code, $store));
                    $value = '';
                    if ($dbc->num_rows($fetchR) != 0) {
                        $fetchW = $dbc->fetch_row($fetchR);
                        $value = $fetchW[0];
                    } elseif ($code !== 'CA' && $code !== 'CK' && $code !== 'WT') {
                        $value = $posAmt;
                    }
                    $output .= "<td><div class=\"input-group\">
                        <span class=\"input-group-addon\">\$</span>
                        <input type=text id=count$code$row[1] 
                        class=\"countT$code countEmp$emp_no form-control form-control-sm\"
                        onchange=\"calcOS('$code',$row[1]);\" value=\"$value\"/>
                        </div></td>";
                    if ($value === '') {
                        $value = 0;
                    }
                    $os = round($value - $posAmt,2);
                    if ($code == 'CA') {
                        $os = round($value - $posAmt - $startcash,2);
                    }
                    $output .= "<td id=os$code$row[1]>$os</td></tr>";
                    $output .= "<input type=hidden class=\"osT$code osEmp$emp_no\" 
                        id=os$code$row[1]Hidden value=\"$os\" />";

                    $tender_info[$code]['countTtl'] += $value;
                    $tender_info[$code]['osTtl'] += $os;

                    $perCashierCountTotal += $value;
                    $perCashierOSTotal += $os;

                    $tender_info[$code]['posTtl'] += $posAmt;
                    $perCashierTotal += $posAmt;
                }

                $perCashierTotal = round($perCashierTotal,2);
                $perCashierCountTotal = round($perCashierCountTotal,2);
                $perCashierOSTotal = round($perCashierOSTotal,2);

                $output .= "<tr><td>&nbsp;</td><td>Cashier totals</td>";
                $output .= "<td>$perCashierTotal</td>";
                $output .= "<td id=countTotal$row[1]>$perCashierCountTotal</td>";
                $output .= "<td id=osTotal$row[1]>$perCashierOSTotal</td>";
                $output .= "<tr><td>&nbsp;</td><td>Notes</td><td colspan=3</td>";
                $output .= "<textarea class=\"form-control\" rows=5 cols=35 id=note$row[1]>$note</textarea></td></tr>";
          
                $output .= "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
            }
            if (FormLib::get_form_value('emp_no') !== ''){
                // single employee view. grand totals are redundant
                echo $output;
                return False;
            }
            /* add overall totals */
            $output .= "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
    
            $overallTotal = 0.0;
            $overallCountTotal = 0.0;
            $overallOSTotal = 0.0;
            foreach($tender_info as $code => $info){
                $caTotal = round($info['posTtl'],2);
                $countCATotal = round($info['countTtl'],2);
                $osCATotal = round($info['osTtl'],2);
                $output .= "<tr><td><b>Totals</b></td><td>".$info['name']."</td><td id={$code}total>$caTotal</td>";
                $output .= "<td id=count{$code}Total>$countCATotal</td>";
                $output .= "<td id=os{$code}Total>$osCATotal</td></tr>";
                $overallTotal += $caTotal;
                $overallCountTotal += $countCATotal;
                $overallOSTotal += $osCATotal;
            }
    
            $overallTotal = round($overallTotal,2);
            $overallCountTotal = round($overallCountTotal,2);
            $overallOSTotal = round($overallOSTotal,2);
            $output .= "<tr><td><b>Grand totals</td><td>&nbsp;</td>";
            $output .= "<td id=overallTotal>$overallTotal</td>";
            $output .= "<td id=overallCountTotal>$overallCountTotal</td>";
            $output .= "<td id=overallOSTotal>$overallOSTotal</td></tr>";

            $noteR = $dbc->execute($noteP, array(-1, $date));
            $noteW = $dbc->fetchRow($noteR);
            $note = $noteW[0];
            $output .= "<tr><td>&nbsp;</td><td>Notes</td><td colspan=3</td>";
            $output .= "<textarea class=\"form-control\" rows=5 cols=35 id=totalsnote>$note</textarea></td></tr>";

            $output .= "</table>";
    
            $model = new OverShortsLogModel($dbc);
            $model->date($date);
            $model->load();
            $output .= "<p>This date last edited by: <span id=lastEditedBy><b>".$model->username()."</b></span><br />";
            $output .= "<button type=submit class=\"btn btn-default\">Save</button>";
            $output .= " <label><input type=checkbox id=resolved ";
            if ($model->resolved() == 1)
                $output .= "checked";
            $output .= " /> Resolved</label></p>";
            $output .= "</form>";

            /* "send" output back */
            echo $output;
            break;
        }
    }

    function save($date,$store,$data){
        global $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['OverShortDatabase']);
        $bycashier = explode(',',$data);

        $model = new DailyCountsModel($dbc);
        $model->date($date);
        $model->storeID($store);
        foreach ($bycashier as $c){
            $temp = explode(':',$c);
            if (count($temp) != 2) continue;
            $cashier = $temp[0];
            $tenders = explode(';',$temp[1]);
            $model->emp_no($cashier);
            foreach($tenders as $t){
                $temp = explode('|',$t);
                $tender_type = $temp[0];
                $amt = isset($temp[1]) ? rtrim($temp[1]) : '';
                if ($amt != ''){
                    $model->tender_type($tender_type);
                    $model->amt($amt);
                    $model->save();
                }
            }
        }
    }

    function saveNotes($date,$store,$notes){
        global $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['OverShortDatabase']);
        $noteIDs = explode('`',$notes);
        $model = new DailyNotesModel($dbc);
        $model->date($date);
        $model->storeID($store);
        foreach ($noteIDs as $n){
            $temp = explode('|',$n);
            $emp = $temp[0];
            $note = str_replace("'","''",urldecode($temp[1]));
            $model->emp_no($emp);
            $model->note($note);
            $model->save();
        }
    }

    function javascript_content(){
        ob_start();
    ?>

function setdate()
{
    var dataStr = $('#osForm').serialize();
    dataStr += '&action=date';
    $('#date').val('');
    $('#forms').html('');
    $('#loading-bar').show();
    $.ajax({
        url: 'OverShortDayPage.php',
        data: dataStr,
        success: function(data){
            $('#loading-bar').hide();
            $('#forms').html(data);
        }
    });
}

function calcOS(type,empID){
    var dlogAmt = $('#dlog'+type+empID).html();
    var countAmt = $('#count'+type+empID).val();
    
    if (countAmt.indexOf('+') != -1){
        var temp = countAmt.split('+');
        var countAmt = 0;
        for (var i = 0; i < temp.length; i++){
            countAmt += Number(temp[i]);
        }
        $('#count'+type+empID).val(Math.round(countAmt*100)/100);
    }
    
    var extraAmt = 0;
    if (type == 'CA'){
        extraAmt = $('#startingCash'+empID).val();

        if (extraAmt.indexOf('+') != -1){
            var temp = extraAmt.split('+');
            var extraAmt = 0;
            for (var i = 0; i < temp.length; i++){
                extraAmt += Number(temp[i]);
            }
            $('#startingCash'+empID).val(Math.round(extraAmt*100)/100);
        }
    }
    
    var diff = Math.round((countAmt - dlogAmt - extraAmt)*100)/100;
    
    $('#os'+type+empID).html(diff);
    $('#os'+type+empID+'Hidden').val(diff);
    
    resum(type);
    cashierResum(empID);
}

function resum(type){
    var countSum = 0;
    $('.countT'+type).each(function(){
        countSum += Number($(this).val());
    });

    if (type == 'CA'){
        $('.startingCash').each(function(){
            countSum -= Number($(this).val());
        });
    }
    
    var osSum = 0;
    $('.osT'+type).each(function(){
        osSum += Number($(this).val());
    });
        
    var oldcount = Number($('#count'+type+'Total').html());
    var oldOS = Number($('#os'+type+'Total').html());
    var newcount = Math.round(countSum*100)/100;
    var newOS = Math.round(osSum*100)/100;

    $('#count'+type+'Total').html(newcount);
    $('#os'+type+'Total').html(newOS);

    var overallCount = Number($('#overallCountTotal').html());
    var overallOS = Number($('#overallOSTotal').html());

    var newOverallCount = overallCount + (newcount - oldcount);
    var newOverallOS = overallOS + (newOS - oldOS);

    $('#overallCountTotal').html(Math.round(newOverallCount*100)/100);
    $('#overallOSTotal').html(Math.round(newOverallOS*100)/100);
}

function cashierResum(empID){
    var countSum = 0;
    countSum -= Number($('#startingCash'+empID).val());
    $('.countEmp'+empID).each(function(){
        countSum += Number($(this).val());
    });
    var osSum = 0;
    $('.osEmp'+empID).each(function(){
        osSum += Number($(this).val());
    });
    $('#countTotal'+empID).html(Math.round(countSum*100)/100);
    $('#osTotal'+empID).html(Math.round(osSum*100)/100);
}

function save(){
    var outstr = '';
    var notes = '';
    var emp_nos = document.getElementsByName('cashier');
    $('.cashier').each(function(){
        var emp_no = $(this).val();
        outstr += emp_no+":";
        if ($('#startingCash'+emp_no).length != 0)
            outstr += "SCA|"+$('#startingCash'+emp_no).val()+";";

        $('.tcode'+emp_no).each(function(){
            var code = $(this).val();
            if ($('#count'+code+emp_no).length != 0)
                outstr += code+"|"+$('#count'+code+emp_no).val()+";";
        });
        
        var note = $('#note'+emp_no).val();
        
        notes += emp_no + "|" + escape(note);
        outstr += ",";
        notes += "`";
    });
    var note = $('#totalsnote').val();
    notes += "-1|"+escape(note);
    
    var curDate = $('#currentdate').html();
    var store = $('#currentstore').val();
    var user = $('#user').val();
    var resolved = 0;
    if (document.getElementById('resolved').checked)
        resolved = 1;

    $('#lastEditedBy').html("<b>"+user+"</b>");

    $.ajax({
        url: 'OverShortDayPage.php',
        type: 'post',
        data: 'action=save&curDate='+curDate+'&data='+outstr+'&user='+user+'&resolved='+resolved+'&notes='+notes+'&store='+store,
        success: function(data){
            if (data == "saved")
                alert('Data saved successfully');
            else
                alert(data);
        }
    }); 
}

    <?php
        return ob_get_clean();
    }


    function css_content(){
        ob_start();
        ?>
#forms {

}

body, table, td, th {
  color: #000;
}
    <?php
        return ob_get_clean();
    }

    function body_content(){
        global $FANNIE_URL;
        $user = FannieAuth::checkLogin();
        ob_start();
        ?>
        <form style='margin-top:1.0em;' id="osForm" onsubmit="setdate(); return false;" >
        <div class="form-group form-inline">
        <label>Date</label>:<input class="form-control date-field" type=text id=date name=arg />
        <select class="form-control" name="mode">
            <option value="drawer">Drawer</option>
            <option value="cashier">Cashier</option>
        </select>
        <?php
        $sp = FormLib::storePicker('store', false);
        echo $sp['html'];
        ?>
        <button type=submit class="btn btn-default">Set</button>
        <input type=hidden id=user value="<?php if(isset($user)) echo $user ?>" />
        </div>
        </form>

        <div id="loading-bar" class="collapse">
            <?php echo \COREPOS\Fannie\API\lib\FannieUI::loadingBar(); ?>
        </div>
        <div id="forms"></div>
        <?php
        return ob_get_clean();
    }

}

FannieDispatch::conditionalExec(false);


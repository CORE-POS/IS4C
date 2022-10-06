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
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class OverShortSafecountV3 extends FanniePage {

    protected $window_dressing = False;
    protected $auth_classes = array('overshorts');

    public $page_set = 'Plugin :: Over/Shorts';
    public $description = '[Safe Count] stores information about cash on hand and change buys.';

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
        $d1 = FormLib::get_form_value('date1');
        $d2 = FormLib::get_form_value('date2');
        $store = FormLib::get('store');
        $type = FormLib::get('type', 'Regular');
    
        $dateStr = $d1." ".$d2;
        if ($d1 == $d2) $dateStr = $d1;
        switch($action){
        case 'loader':
            echo $this->displayUI($dateStr, $store, $type);
            break;
        case 'save':
            echo $this->save($dateStr, $store, $type,
                FormLib::get_form_value('changeOrder'),
                FormLib::get_form_value('openSafeCount'),
                FormLib::get_form_value('buyAmount'),
                FormLib::get_form_value('atmAmount'),
                FormLib::get_form_value('atmAmount2'),
                FormLib::get_form_value('tillCount'),
                FormLib::get_form_value('notes')
                );
            break;  
        }
    }

    function save($dateStr,$store,$type,$changeOrder,$openSafeCount,$buyAmount,$atmAmount,$atmAmount2,$tillCount,$notes){
        $this->saveInputs($dateStr,$store,$type,'changeOrder',$changeOrder);
        $this->saveInputs($dateStr,$store,$type,'openSafeCount',$openSafeCount);
        $this->saveInputs($dateStr,$store,$type,'buyAmount',$buyAmount);
        $this->saveInputs($dateStr,$store,$type,'atm',$atmAmount);
        $this->saveInputs($dateStr,$store,$type,'atm2',$atmAmount2);
        $this->saveTillCounts($dateStr, $store, $type, $tillCount);
        $this->saveNotes($dateStr, $store, $type, $notes);
    
        return 'Saved';
    }

    function saveInputs($dateStr,$store,$type,$row,$data){
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['OverShortDatabase']);

        $model = new DailyDepositModel($dbc);
        $model->dateStr($dateStr);
        $model->storeID($store);
        $model->countType($type);
        $model->rowName($row);
        $model->countFormat(2);

        $temp = explode('|',$data);
        foreach($temp as $t){
            $temp2 = explode(':',$t);
            if (count($temp2) < 2) continue;
            $denom = $temp2[0];
            $amt = $temp2[1];
        
            if ($amt == '') continue;

            $model->denomination($denom);
            $model->amt($amt);
            $model->save();
        }
    }

    function saveTillCounts($dateStr, $store, $type, $data)
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['OverShortDatabase']);

        $model = new DailyDepositModel($dbc);
        $model->dateStr($dateStr);
        $model->storeID($store);
        $model->countType($type);
        $model->countFormat(2);
        $temp = explode('|',$data);
        foreach($temp as $t){
            $temp2 = explode(':',$t);
            if (count($temp2) < 2) continue;
            $rowID = $temp2[0];
            $amt = $temp2[1];
        
            if ($amt == '') continue;

            $model->rowName($rowID);
            $model->denomination('ttl');
            $model->amt($amt);
            $model->save();
        }
    }

    function saveNotes($dateStr, $store, $type, $data)
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['OverShortDatabase']);
        $model = new DailyDepositNotesModel($dbc);
        $model->dateStr($dateStr);
        $model->storeID($store);
        $model->countType($type);
        $temp = explode('|',$data);
        foreach($temp as $t){
            $temp2 = explode(':',$t, 2);
            if (count($temp2) < 2) continue;
            $model->noteID($temp2[0]);
            $model->note($temp2[1]);
            $model->save();
        }
    }

    function displayUI($dateStr, $store, $type){
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['OverShortDatabase']);

        $startDate = $dateStr;
        $endDate = $dateStr;
        if (strstr($dateStr," ")){
            $temp = explode(" ",$dateStr);
            $startDate = $temp[0];
            $endDate = $temp[1];
        }
        if (trim($startDate) === '' || trim($endDate) === '') {
            return "Invalid Dates";
        }

        $holding = array('changeOrder'=>array(),
                'openSafeCount'=>array(),
                'buyAmount' => array(),
                'atm'=>array('count'=>0),
                'atm2'=>array('count'=>0),
                );

        $denoms = array('0.01','0.05','0.10','0.25','Junk','1.00','5.00','10.00','20.00','50.00','100.00','Checks');
        foreach($denoms as $d){
            foreach($holding as $k=>$v){
                $holding[$k]["$d"] = 0;
            }
        }

        $model = new DailyDepositModel($dbc);
        $model->dateStr($dateStr);
        $model->storeID($store);
        $model->countType($type);
        foreach($model->find() as $obj){
            if (!isset($holding[$obj->rowName()])) {
                $holding[$obj->rowName()] = array();
            }
            $holding[$obj->rowName()][$obj->denomination()] = $obj->amt();
        }

        $actualTotal = 0;
        $accountableTotal = 0;
        $buyAmountTotal = 0;
    
        $ret = "<h3>$dateStr</h3>";
        $ret .= "<table cellspacing=0 border=1 cellpadding=4><tr><td>&nbsp;</td>";
        foreach ($denoms as $d) $ret .= "<th>$d</th>";
        $ret .= "<th>Total</th></tr>";

        $ret .= "<tr class=color><th title=\"Currency ordered from the bank. This should match Buy Amount from the previous count.\">Change Order</th>";
        $sum = 0;
        foreach($denoms as $d){ 
            if ($d == 'Checks' || $d == "100.00" || $d == "50.00" || $d == "Junk") 
                $ret .= "<td>&nbsp;</td>";
            else{
                $ret .= "<td><input size=4 type=text id=\"changeOrder$d\" value=".$holding['changeOrder'][$d];
                $ret .= " onchange=\"updateChangeOrder('$d');\" /></td>";
                $sum += $holding['changeOrder'][$d];
            }
        }
        $ret .= "<td id=changeOrderTotal>$sum</td></tr>";

        $ret .= "<tr><th title=\"Money in the safe at the start of this cont.\">Open Safe Count</th>";
        $sum = 0;
        foreach($denoms as $d){
            if ($d == 'Checks') 
                $ret .= "<td>&nbsp;</td>";
            else{
                $ret .= "<td><input size=4 type=text id=\"safeCount1$d\" value=".$holding['openSafeCount'][$d];
                $ret .= " onchange=\"updateOpenSafeCount('$d');\" /></td>";
                $sum += $holding['openSafeCount'][$d];
            }
        }
        $ret .= "<td id=safeCount1Total>$sum</td></tr>";

        $dateClause = ' date = ?';
        $dateArgs = array($dateStr);
        if (strstr($dateStr," ")){
            $dates = explode(" ",$dateStr);
            $dateClause = ' date BETWEEN ? AND ? AND storeID = ? ';
            $dateArgs = array($dates[0],$dates[1], $store);
        }
        $countQ = "SELECT tender_type,sum(amt) from dailyCounts where tender_type in ('CA','CK','SCA') and $dateClause GROUP BY tender_type";
        $countP = $dbc->prepare($countQ);
        $countR = $dbc->execute($countP, $dateArgs);
        $osCounts = array('CA'=>0,'CK'=>0,'SCA'=>0);
        while($countW = $dbc->fetch_row($countR))
            $osCounts[$countW[0]] = $countW[1];


        $ret .= "<tr class=color><th title=\"This is the Open Safe Count plus the Change Order.\">Total Change Fund</th>";
        $sum = 0;
        foreach($denoms as $d){
            if ($d == "Checks"){
                $ret .= "<td>&nbsp;</td>";
            }
            else {
                $val = $holding['changeOrder'][$d] + $holding['openSafeCount'][$d];
                $ret .= "<td id=cashInTills$d>$val</td>";
                $sum += $val;
            }
        }
        $ret .= "<td id=cashInTillsTotal>$sum</td></tr>";
        $accountableTotal += $sum;

        $ret .= "<tr><th title=\"Cash on-hand in the ATM\">ATM</th>";
        $ret .= "<td colspan=\"7\">&nbsp;</td>";
        $ret .= "<td>Count:</td>";
        $ret .= "<td><input size=4 type=text id=atmCount value=\"".$holding['atm']['count']."\"
                onchange=\"updateAtmAmounts();\" /></td>";
        $ret .= "<td>Secondary:</td>";
        $ret .= "<td><input size=4 type=text id=atmCount2 value=\"".$holding['atm2']['count']."\"
                onchange=\"updateAtmAmounts();\" /></td>";
        $ret .= "<td colspan=\"2\">&nbsp;</td>";
        $ret .= "</tr>";

        if ($store == 1) {
            $pars = array("0.01"=>60,"0.05"=>120,"0.10"=>320,"0.25"=>1200,"1.00"=>2600,"5.00"=>1000,"10.00"=>1300);
        } else {
            $pars = array("0.01"=>35,"0.05"=>80,"0.10"=>250,"0.25"=>800,"1.00"=>1755,"5.00"=>750,"10.00"=>830);
        }
        if (file_exists(__DIR__ . '/pars.json')) {
            $json = json_decode(file_get_contents(__DIR__ . '/pars.json'), true);
            if (isset($json[$store])) {
                $pars = $json[$store];
            }
        }

        $parTTL = 0; foreach($pars as $k=>$v) $parTTL += $v;
        $ret .= "<tr class=\"color\"><th title=\"Pars are the amounts we want to keep on hand of each denomination. Click here to adjust the current pars for the store.\"><a href=\"OverShortParsPage.php\">Par Amounts</a></th>";
        $ret .= "<td id=par0.01>".$pars['0.01']."</td>";
        $ret .= "<td id=par0.05>".$pars['0.05']."</td>";
        $ret .= "<td id=par0.10>".$pars['0.10']."</td>";
        $ret .= "<td id=par0.25>".$pars['0.25']."</td>";
        $ret .= "<td>&nbsp;</td>";
        $ret .= "<td id=par1.00>".$pars['1.00']."</td>";
        $ret .= "<td id=par5.00>".$pars['5.00']."</td>";
        $ret .= "<td id=par10.00>".$pars['10.00']."</td>";
        $ret .= "<td id=par20.00>".$pars['20.00']."</td>";
        $ret .= "<td colspan=3>&nbsp;</td>";
        $ret .= sprintf("<td>%.2f</td></tr>",$parTTL);

        $ret .= "<tr><th title=\"This is what we need to order from the bank to reach pars. It's Par minus Total Change Fund except for 20s which are also reduced by the ATM on-hand count.\">Buy Amount</th>";
        foreach ($denoms as $d){
            if (isset($holding['buyAmount'][$d]))
                $ret .= "<td id=buyAmount$d>".$holding['buyAmount'][$d]."</td>";
            else
                $ret .= "<td id=buyAmount$d></td>";
        }
        $ret .= "<td id=buyAmountTotal>".
            (isset($holding['buyAmount']) ? array_sum($holding['buyAmount']) : 0)
            ."</td></tr>";

        $dlog = DTransactionsModel::selectDlog($startDate,$endDate);
        $dlogClause = str_replace(' date ', ' d.tdate ', $dateClause);
        $dlogClause = str_replace(' storeID ', ' d.store_id ', $dlogClause);
        $posTotalQ = "SELECT -1*sum(d.total) FROM $dlog as d WHERE ". $dlogClause . " AND d.trans_subtype IN ('CA','CK')";
        $posTotalP = $dbc->prepare($posTotalQ);   
        $dateArgs[1] .= ' 23:59:59';
        $posTotalR = $dbc->execute($posTotalP, $dateArgs);
        $posTotalW = $dbc->fetch_row($posTotalR);
        $posTotal = sprintf('%.2f', $posTotalW[0]);

        $ret .= "<tr class=\"color\"><th>Over/Shorts</th>";
        $ret .= "<td><i>Count total</i></td><td>".round(($osCounts['CA']+$osCounts['CK'] - $osCounts['SCA'] ),2)."</td>";
        $ret .= "<td><i>POS total</i></td><td>".$posTotal."</td>";
        $ret .= "<td><i>Variance</i></td><td>".round(($osCounts['CA']+$osCounts['CK']) - $osCounts['SCA'] -$posTotal,2)."</td>";
        $ret .= "<td><i>Actual</i></td><td id=actualTotal>$actualTotal</td>";
        $ret .= "<td><i>Accountable</i></td><td id=accountableTotal>".round($accountableTotal,2)."</td>";
        $ret .= "<td><i>Variance</i></td><td id=aaVariance>".round($actualTotal - $accountableTotal,2)."</td>";
        $ret .= "<td>&nbsp;</td></tr>"; 


        $dailies = array();
        $countQ = "SELECT date,
                SUM(CASE WHEN tender_type IN ('CA','CK') THEN amt
                    WHEN tender_type = 'SCA' THEN -amt
                ELSE 0 end) AS total
                FROM dailyCounts WHERE date BETWEEN ? AND ?
                    AND storeID=?
                GROUP BY date";
        $countP = $dbc->prepare($countQ);
        $countR = $dbc->execute($countP, array($startDate,$endDate,$store));
        while($row = $dbc->fetch_row($countR)){
            $d = $row['date'];
            if (!isset($dailies[$d])) $dailies[$d] = array(0,0);
            $dailies[$d][0] = $row['total'];
        }
        $posQ = "SELECT YEAR(tdate),MONTH(tdate),DAY(tdate),
                SUM(case when trans_subtype in ('CA','CK') then -total ELSE 0 END) as total
                FROM $dlog AS d WHERE tdate BETWEEN ? AND ?
                    AND d.store_id=?
                GROUP BY YEAR(tdate),MONTH(tdate),DAY(tdate)";
        $posP = $dbc->prepare($posQ);
        $posR = $dbc->execute($posP, array($startDate.' 00:00:00',$endDate.' 23:59:59',$store));
        while($row = $dbc->fetch_row($posR)){
            $d = $row[0]."-".str_pad($row[1],2,'0',STR_PAD_LEFT)."-".str_pad($row[2],2,'0',STR_PAD_LEFT);
            if (!isset($dailies[$d])) $dailies[$d] = array(0,0);
            $dailies[$d][1] = $row[3];
        }
        $num = 0;
        foreach($dailies as $k=>$v){
            if ($num % 2 == 0){
                if ($num != 0) $ret .= "</tr>";
                if ($num % 4 == 0) $ret .= "<tr>";
                else $ret .= "<tr class=\"color\">";
            }
            $ret .= sprintf("<th>%s</th><td><i>Count</i></td><td>%.2f</td>
                    <td><i>POS</i></td><td>%.2f</td><td><i>Variance
                    </i></td><td>%.2f</td>",$k,$v[0],$v[1],($v[0]-$v[1]));
            $num++;
        }
        if ($num % 2 != 0)
            $ret .= "<td colspan=7>&nbsp;</td>";
        $ret .= "</tr>";
        $ret .= "</table>";

        $ret .= '<br /><br />';

        $ret .= "<table cellspacing=0 border=1 cellpadding=4>";
        $ret .= '<tr><th>Date</th><th>Cashier Count</th><th>Drop</th><th>POS</th><th>Var.</th><th>Notes</th><th>Checks</th></tr>';

        $model = new DailyDepositNotesModel($dbc);
        $model->dateStr($dateStr);
        $model->storeID($store);
        $model->countType($type);
        $notes = array();
        foreach ($model->find() as $obj) {
            $notes[$obj->noteID()] = $obj->note();
        }

        $startTS = strtotime($startDate);
        $endTS = strtotime($endDate);
        $dropP = $dbc->prepare("SELECT dropAmount, registerNo, FirstName 
            FROM DailyTillCounts AS c
                LEFT JOIN " . FannieDB::fqn('employees', 'op') . " as e on c.registerNo=e.emp_no
                WHERE dateID=? AND storeID=? AND registerNo=?");
        $count = 0;
        $dropTTL = 0;
        $dropVar = 0;
        while ($startTS <= $endTS) {
            $date = date('Y-m-d', $startTS);
            $dateID = date('Ymd', $startTS);

            $dlog = DTransactionsModel::selectDlog($date);
            $caP = $dbc->prepare("SELECT d.emp_no, e.FirstName, -1 * SUM(total) AS ttl
                FROM {$dlog} AS d
                    LEFT JOIN " . FannieDB::fqn('employees', 'op') . " AS e ON d.emp_no=e.emp_no
                WHERE tdate BETWEEN ? AND ? AND trans_type='T' AND store_id=?
                AND (trans_subtype='CA' OR (trans_subtype='CK' AND description='Check'))
                GROUP BY d.emp_no, e.FirstName
                HAVING ABS(SUM(total)) > 0.005");

            $caR  = $dbc->execute($caP, array($date, $date . ' 23:59:59', $store));
            $first = true;
            $dayTTL = 0;
            while ($row = $dbc->fetchRow($caR)) {
                $class = $count % 2 == 0 ? 'color' : '';
                $ret .= '<tr class="' . $class . '">';
                $ret .= '<td>' . $date . ' ' . $row['FirstName'] . '</td>';
                $drop = $dbc->getRow($dropP, array($dateID, $store, $row['emp_no']));
                $ret .= '<td>' . $drop['dropAmount'] . '</td>';
                $cur = 0;
                $key = $dateID . '-' . $row['emp_no'];
                if (isset($holding['drop' . $key]) && isset($holding['drop' . $key]['ttl'])) {
                    $cur = $holding['drop' . $key]['ttl'];
                }
                $ret .= '<td><input type="text" size="4" class="drop" id="drop' . $key . '" value="' . $cur . '" 
                    onchange="recalcDropVariance(event);" /></td>';
                $cash = $row['ttl'];
                $ret .= '<td class="pos">' . sprintf('%.2f', $cash) . '</td>';
                $ret .= '<td class="var">' . sprintf('%.2f', $cur - $cash) . '</td>';
                $note = isset($notes['note' . $key]) ? $notes['note' . $key] : '';
                $ret .= '<td class="day-notes"><input type="text" class="day-notes" id="note' . $key . '" value="' . $note . '" /></td>';
                if ($first) {
                    $ret .= '<td rowspan="4" valign="top">
                        <textarea class="day-notes" id="noteChecks' . $dateID . '" rows="7">';
                    $ret .= isset($notes['noteChecks' . $dateID]) ? $notes['noteChecks' . $dateID] : '';
                    $ret .= '</textarea></td>';
                }
                $ret .= '</tr>';
                $dropTTL += $cur;
                $dropVar += ($cur - $cash);
                $dayTTL += $cur;
                $count++;
                $first = false;
            }
            $class = $count % 2 == 0 ? 'color' : '';
            $ret .= '<tr class="' . $class . '"><th>Total</th><td>&nbsp;</td><td>' . $dayTTL . '</td><td>&nbsp;</td><td>&nbsp;</td></tr>';
            $count++;
            $startTS = mktime(0, 0, 0, date('n', $startTS), date('j', $startTS) + 1, date('Y', $startTS));
        }
        $class = $count % 2 == 0 ? 'color' : '';
        $ret .= '<tr class="' . $class . '"><td>Open Safe</td><td>n/a</td>';
        $cur = 0;
        if (isset($holding['dropExtra']) && isset($holding['dropExtra']['ttl'])) {
            $cur = $holding['dropExtra']['ttl'];
        }
        $ret .= '<td><input type="text" size="4" class="drop" id="dropExtra" value="' . $cur . '" 
            onchange="recalcDropVariance(event);" /></td>';
        $cash = $holding['openSafeCount']['20.00'] + $holding['openSafeCount']['50.00'] + $holding['openSafeCount']['100.00'] + $holding['openSafeCount']['Junk'];
        $ret .= '<td class="pos" id="extraPos">' . sprintf('%.2f', $cash) . '</td>';
        $ret .= '<td class="var">' . sprintf('%.2f', $cur - $cash) . '</td>';
            $note = isset($notes['noteExtra']) ? $notes['noteExtra'] : '';
        $ret .= '<td class="day-notes"><input type="text" class="day-notes" id="noteExtra" value="' . $note . '" /></td>';
        $ret .= '</tr>';
        $count++;
        $dropTTL += $cur;
        $dropVar += ($cur - $cash);
        $class = $count % 2 == 0 ? 'color' : '';
        $ret .= '<tr class="' . $class . '"><td>Total</td><td></td>';
        $ret .= '<td id="dropTTL">' . sprintf('%.2f', $dropTTL) . '</td>';
        $ret .= '<td></td>';
        $ret .= '<td id="dropVar">' . sprintf('%.2f', $dropVar) . '</td>';
        $ret .= '<td></td>';
        $ret .= '</tr>';

        $ret .= "</table>";
        $ret .= '<br /><br />';
        $ret .= "<input type=hidden id=savedDate1 value=\"$startDate\" />";
        $ret .= "<input type=hidden id=savedDate2 value=\"$endDate\" />";
        $ret .= "<input type=hidden id=savedStore value=\"$store\" />";
        $ret .= "<input type=hidden id=savedType value=\"$type\" />";
        foreach($denoms as $d){
            $ret .= "<input type=\"hidden\" class=\"denom\" value=\"$d\" />";
        }
        $note = isset($notes['allNotes']) ? $notes['allNotes'] : '';
        $ret .= '<p><b>Notes</b>:<br /><textarea class="day-notes" rows="5" cols="45" id="allNotes">' . $note . '</textarea></p>';
        $ret .= "<input type=submit value=Save onclick=\"save();\" />";
    
        return $ret;
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
        global $FANNIE_URL, $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['OverShortDatabase']);
        $this->addScript('js/countV3.js?date=20220927');
        $this->addScript($FANNIE_URL.'src/javascript/jquery.js');
        $this->addScript($FANNIE_URL.'src/javascript/jquery-ui.js');
        $this->addCssFile($FANNIE_URL.'src/style.css');
        $this->addCssFile($FANNIE_URL.'src/javascript/jquery-ui.css');
        $this->addOnloadCommand("\$('#startDate').datepicker({dateFormat:'yy-mm-dd'});");
        $this->addOnloadCommand("\$('#endDate').datepicker({dateFormat:'yy-mm-dd'});");
        ob_start();
        ?>
        <html>
        <head>
            <title>Count</title>
        </head>
        <body>

        <div id=input>
        <table>
        <tr>
            <th colspan="5">July 2020 &amp; newer version (<a href="OverShortSafecountPage.php">Switch</a>)</th>
        </tr>
        <tr>
            <th>Start Date</th><td><input type=text id=startDate autocomplete=off /></td>
            <td>
            <input type=submit Value=Load onclick="loader();" />
            </td>
            <td >
            Recent Counts: <select onchange="existingDates(this.value);">
            <option value=''>Select one...</option>
            <?php
            $res = $dbc->query('SELECT dateStr FROM dailyDeposit WHERE countFormat=2 GROUP BY dateStr ORDER BY dateStr DESC');
            $count = 0;
            while($row = $dbc->fetch_row($res)) {
                if ($count++ > 100) {
                    break;
                }
                echo '<option>'.$row['dateStr'].'</option>';
            }
            ?>
            </select>
            </td>
        </tr>
        <tr>
            <th>End Date</th><td><input type=text id=endDate autocomplete=off /></td>
            <td>Store</td>
            <td>
            <?php
            $stores = FormLib::storePicker('store', false);
            echo $stores['html'];
            ?>
            </td>
            <td>Type</td>
            <td>
            <select name="type">
                <option>Regular</option>
                <option>Month End</option>
            </select>
            </td>
        </tr>
        </table>
        </div>

        <hr />

        <div id=display></div>
        <?php
        return ob_get_clean();
    }

}

FannieDispatch::conditionalExec();


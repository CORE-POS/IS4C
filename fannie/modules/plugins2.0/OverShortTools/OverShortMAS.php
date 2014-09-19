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

class OverShortMAS extends FannieRESTfulPage {
    protected $header = 'MAS Export';
    protected $title = 'MAS Export';

    public $page_set = 'Plugin :: Over/Shorts';
    public $description = '[MAS Export] formats over/short info for MAS90 journal entry.';

    function preprocess(){
        $this->__routes[] = 'get<startDate><endDate>';
        return parent::preprocess();
    }

    function get_data(){
        global $FANNIE_OP_DB;
        $dlog = DTransactionsModel::selectDlog($this->startDate, $this->endDate);
        $dtrans = DTransactionsModel::selectDtrans($this->startDate, $this->endDate);

        $records = array();
        $dateID = date('ymd', strtotime($this->endDate));
        $dateStr = date('m/d/y', strtotime($this->endDate));
        $names = array(
        'CA' => 'Deposit',
        'EF' => 'EBT Food/Cash',
        '41201' => 'DELI PREPARED FOODS',
        '41205' => 'DELI CHEESE',
        '41300' => 'PRODUCE',
        '41305' => 'SEEDS',
        '41310' => 'TRANSPLANTS',
        '41315' => 'GEN MERC/FLOWERS',
        '41400' => 'GROCERY',
        '41405' => 'GROCERY CLEANING, PAPER',
        '41407' => 'GROCERY BULK WATER',
        '41410' => 'BULK A',
        '41415' => 'BULK B',
        '41420' => 'COOL',
        '41425' => 'COOL BUTTER',
        '41430' => 'COOL MILK',
        '41435' => 'COOL FROZEN',
        '41500' => 'HABA BULK/SPICES & HERBS',
        '41505' => 'HABA BULK/PKG COFFEE',
        '41510' => 'HABA BODY CARE',
        '41515' => 'HABA VIT/MN/HRB/HOMEOPA',
        '41520' => 'GEN MERC/BOOKS',
        '41600' => 'GROCERY BAKERY FROM VEN',
        '41605' => 'GEN MERC/HOUSEWARES',
        '41610' => 'MARKETING',
        '41640' => 'GEN MERC/CARDS',
        '41645' => 'GEN MERC/MAGAZINES',
        '41700' => 'MEAT/POULTRY/SEAFOOD FR',
        '41705' => 'MEAT/POULTRY/SEAFOOD FZ',
        );
        $codes = array(
        'CP' => 10740,
        'GD' => 21205,
        'SC' => 21200,
        'MI' => 10710,
        'IC' => 67710,
        'MA' => 66600,
        'RR' => 63380,  
        'OB' => 66600,
        'AD' => 66600,
        'NCGA' => 66600,
        'Member Discounts' => 66600,
        'Staff Discounts' => 61170,
        );

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $args = array($this->startDate.' 00:00:00', $this->endDate.' 23:59:59');

        $tenderQ = "SELECT SUM(total) AS amount,
                CASE WHEN trans_subtype IN ('CA','CK') THEN 'CA'
                WHEN trans_subtype IN ('CC','AX') THEN 'CC'
                WHEN trans_subtype IN ('EF','EC') THEN 'EF'
                WHEN trans_subtype = 'IC' AND upc='0049999900001' THEN 'OB'
                WHEN trans_subtype = 'IC' AND upc='0049999900002' THEN 'AD'
                ELSE trans_subtype END as type,
                MAX(CASE WHEN d.upc IN ('0049999900001','0049999900002') THEN d.description ELSE TenderName END) as name
                FROM $dlog AS d LEFT JOIN
                tenders AS t ON d.trans_subtype=t.TenderCode
                WHERE trans_type='T'
                AND tdate BETWEEN ? AND ?
                AND department <> 703
                GROUP BY type HAVING SUM(total) <> 0 ORDER BY type";
        $tenderP = $dbc->prepare_statement($tenderQ);
        $tenderR = $dbc->exec_statement($tenderP, $args);
        while($w = $dbc->fetch_row($tenderR)){
            $coding = isset($codes[$w['type']]) ? $codes[$w['type']] : 10120;
            $name = isset($names[$w['type']]) ? $names[$w['type']] : $w['name'];
            $credit = $w['amount'] < 0 ? -1*$w['amount'] : 0;
            $debit = $w['amount'] > 0 ? $w['amount'] : 0;
            $row = array($dateID, $dateStr,
                str_pad($coding,9,'0',STR_PAD_RIGHT),
                $credit, $debit, $name);    
            $records[] = $row;
        }

        $discountQ = "SELECT SUM(total) as amount,
            CASE WHEN staff=1 OR memType IN (1,3) THEN 'Staff Discounts'
            ELSE 'Member Discounts' END as name
            FROM $dlog WHERE upc='DISCOUNT'
            AND total <> 0 AND tdate BETWEEN ? AND ?
            GROUP BY name ORDER BY name";
        $discountP = $dbc->prepare_statement($discountQ);
        $discountR = $dbc->exec_statement($discountP, $args);
        while($w = $dbc->fetch_row($discountR)){
            $coding = isset($codes[$w['name']]) ? $codes[$w['name']] : 66600;
            $name = $w['name'];
            $credit = $w['amount'] < 0 ? -1*$w['amount'] : 0;
            $debit = $w['amount'] > 0 ? $w['amount'] : 0;
            $row = array($dateID, $dateStr,
                str_pad($coding,9,'0',STR_PAD_RIGHT),
                $credit, $debit, $name);    
            $records[] = $row;
        }

        $salesQ = "SELECT sum(total) as amount, salesCode,
            MIN(dept_name) as name
            FROM $dlog AS d 
            INNER JOIN departments as t
            ON d.department = t.dept_no
            INNER JOIN MasterSuperDepts AS m
            ON d.department=m.dept_ID
            WHERE d.trans_type IN ('I','D')
            AND tdate BETWEEN ? AND ?
            AND m.superID > 0
            AND register_no <> 20
            GROUP BY salesCode HAVING sum(total) <> 0 
            ORDER BY salesCode";
        $salesP = $dbc->prepare_statement($salesQ);
        $salesR = $dbc->exec_statement($salesP, $args);
        while($w = $dbc->fetch_row($salesR)){
            $coding = isset($codes[$w['salesCode']]) ? $codes[$w['salesCode']] : $w['salesCode'];
            $name = isset($names[$w['salesCode']]) ? $names[$w['salesCode']] : $w['name'];
            $credit = $w['amount'] < 0 ? -1*$w['amount'] : 0;
            $debit = $w['amount'] > 0 ? $w['amount'] : 0;
            $row = array($dateID, $dateStr,
                str_pad($coding,9,'0',STR_PAD_RIGHT),
                $credit, $debit, $name);    
            $records[] = $row;
        }

        $taxQ = "SELECT SUM(total) FROM $dlog WHERE tdate BETWEEN ? AND ?
            AND upc='TAX'";
        $taxP = $dbc->prepare_statement($taxQ);
        $taxR = $dbc->exec_statement($taxP, $args);
        $taxes = 0.00;
        if ($dbc->num_rows($taxR) > 0){
            $taxW = $dbc->fetch_row($taxR);
            $taxes = $taxW[0];
        }
        $records[] = array($dateID, $dateStr, 211800000, 0, $taxes, 'Sales Tax Collected');

        $salesQ = "SELECT sum(total) as amount, salesCode,
            MIN(dept_name) as name
            FROM $dlog AS d 
            INNER JOIN departments as t
            ON d.department = t.dept_no
            INNER JOIN MasterSuperDepts AS m
            ON d.department=m.dept_ID
            WHERE d.trans_type IN ('I','D')
            AND tdate BETWEEN ? AND ?
            AND m.superID = 0
            AND d.department <> 703
            AND register_no <> 20
            GROUP BY salesCode HAVING sum(total) <> 0 
            ORDER BY salesCode";
        $salesP = $dbc->prepare_statement($salesQ);
        $salesR = $dbc->exec_statement($salesP, $args);
        while($w = $dbc->fetch_row($salesR)){
            $coding = isset($codes[$w['salesCode']]) ? $codes[$w['salesCode']] : $w['salesCode'];
            $name = isset($names[$w['salesCode']]) ? $names[$w['salesCode']] : $w['name'];
            $credit = $w['amount'] < 0 ? -1*$w['amount'] : 0;
            $debit = $w['amount'] > 0 ? $w['amount'] : 0;
            $row = array($dateID, $dateStr,
                str_pad($coding,9,'0',STR_PAD_RIGHT),
                $credit, $debit, $name);    
            $records[] = $row;
        }

        $miscQ = "SELECT total as amount, description as name,
            trans_num, tdate FROM $dlog WHERE department=703
            AND trans_subtype <> 'IC'
            AND tdate BETWEEN ? AND ? ORDER BY tdate";
        $miscP = $dbc->prepare_statement($miscQ);
        $miscR = $dbc->exec_statement($miscP, $args);
        $detailP = $dbc->prepare_statement("SELECT description 
            FROM $dtrans WHERE trans_type='C'
            AND trans_subtype='CM' AND datetime BETWEEN ? AND ?
            AND emp_no=? and register_no=? and trans_no=? ORDER BY trans_id");
        while($w = $dbc->fetch_row($miscR)){
            $coding = 63350;
            list($date,$time) = explode(' ',$w['tdate']);
            list($e,$r,$t) = explode('-',$w['trans_num']);
            // lookup comments on the transaction
            $detailR = $dbc->exec_statement($detailP, array(
                $date.' 00:00:00', $date.' 23:59:59',
                $e, $r, $t
            ));
            if ($dbc->num_rows($detailR) > 0){
                $w['name'] = '';
                while($detail = $dbc->fetch_row($detailR))
                    $w['name'] .= $detail['description'];
                if (is_numeric($w['name'])) $coding=trim($w['name']);
            }
            $name = $w['name'].' ('.$date.' '.$w['trans_num'].')';
            $credit = $w['amount'] < 0 ? -1*$w['amount'] : 0;
            $debit = $w['amount'] > 0 ? $w['amount'] : 0;
            $row = array($dateID, $dateStr,
                str_pad($coding,9,'0',STR_PAD_RIGHT),
                $credit, $debit, $name);    
            $records[] = $row;
        }

        $miscQ = "SELECT SUM(-total) as amount
            FROM $dlog WHERE department=703
            AND trans_subtype = 'IC'
            AND tdate BETWEEN ? AND ? ORDER BY tdate";
        $miscP = $dbc->prepare_statement($miscQ);
        $miscR = $dbc->exec_statement($miscP, $args);
        while($w = $dbc->fetch_row($miscR)) {
            $record = array(
                $dateID, $dateStr,
                str_pad(66600,9,'0',STR_PAD_RIGHT),
                sprintf('%.2f', $w['amount']),
                0.00, 'PAT REBATE DISCOUNT',
            );
            $records[] = $record; 
        }

        return $records;
    }

    function get_startDate_endDate_handler(){
        if (FormLib::get_form_value('excel','') !== ''){
            $this->window_dressing = False;
            $_SERVER['REQUEST_URI'] = str_replace('&excel=yes','',$_SERVER['REQUEST_URI']);
            header("Content-type: text/csv");
            header("Content-Disposition: attachment; filename=MAS-export.{$this->endDate}.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
        }
        return True;
    }

    function get_startDate_endDate_view(){
        global $FANNIE_URL, $FANNIE_ROOT;
        $records = DataCache::getFile('daily');
        if ($records !== False)
            $records = unserialize($records);
        if (!is_array($records)){
            $records = $this->get_data();
            DataCache::putFile('daily', serialize($records));
        }

        $ret = '';
        $debit = $credit = 0.0;
        if (FormLib::get_form_value('excel','') === ''){
            $ret .= sprintf('<a href="OverShortMAS.php?startDate=%s&endDate=%s&excel=yes">Download</a>',
                    $this->startDate, $this->endDate);
            $ret .= '<table cellpadding="4" cellspacing="0" border="1">';
            foreach($records as $r){
                if (preg_match('/\(\d+-\d+-\d+ \d+-\d+-\d+\)/',$r[5])){
                    $tmp = explode(' ',$r[5]);
                    $date = trim($tmp[count($tmp)-2],'()'); 
                    $trans = trim($tmp[count($tmp)-1],'()');    
                    $r[5] = sprintf('<a href="%sadmin/LookupReceipt/RenderReceiptPage.php?receipt=%s&date=%s">%s</a>',
                            $FANNIE_URL, $trans, $date, $r[5]);
                }
                $ret .= sprintf('<tr><td>%d</td><td>%s</td><td>%s</td>
                        <td>%.2f</td><td>%.2f</td><td>%s</td></tr>',
                        $r[0],$r[1],$r[2],$r[3],$r[4],$r[5]);
                $debit += $r[3];
                $credit += $r[4];
            }
            $ret .= sprintf('<tr><td colspan="3">Sum</td><td>%.2f</td><td>%.2f</td><td>&nbsp;</td></tr>',
                    $debit, $credit);
            $ret .= '</table>';
        }
        else {
            foreach($records as $row){
                $line = '';
                foreach($row as $val){
                    $line .= strstr($val,',') ? '"'.$val.'"' : $val;
                    $line .= ',';
                }
                $line = substr($line,0,strlen($line)-1)."\r\n";
                $ret .= $line;
            }
        }
        return $ret;
    }

    function get_view(){
        global $FANNIE_URL;
        $this->add_onload_command("\$('#startDate').datepicker();");
        $this->add_onload_command("\$('#endDate').datepicker();");
        $ret = '<form action="OverShortMAS.php" method="get">
            <table>
            <tr><th>Start</th>
            <td><input name="startDate" size="10" id="startDate" /></td>
            </tr><tr><th>End</th>
            <td><input name="endDate" size="10" id="endDate" /></td>
            </tr></table>
            <input type="submit" value="Get Data" />
            </form>';
        return $ret;
    }

}

FannieDispatch::conditionalExec();

?>

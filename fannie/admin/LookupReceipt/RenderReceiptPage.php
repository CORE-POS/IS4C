<?php
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class RenderReceiptPage extends FanniePage {

    protected $window_dressing = False;

    public $description = '[Reprint Receipt] show a POS transaction receipt.';

    function body_content(){
        ob_start();
        ?>
        <form action=RenderReceiptPage.php method=post>
        Date: <input type=text name=date><br>
        Receipt Num: <input type=text name=receipt><br>
        <input type=submit name=submit>
        <?php
        $ret = ob_get_clean();
        $transNum = FormLib::get_form_value('receipt');
        $month = FormLib::get_form_value('month');
        $day = FormLib::get_form_value('day');
        $year = FormLib::get_form_value('year');
        $date = FormLib::get_form_value('date');
        $date1 = "";
        if ($year !== '' && $month !== '' && $day !== ''){
            $date1 = $year."-".str_pad($month,2,'0',STR_PAD_LEFT)
                ."-".str_pad($day,2,'0',STR_PAD_LEFT);
        }
        else if ($date !== ''){
            $tmp = explode("-",$date);
            if (is_array($tmp) && count($tmp)==3){
                $year = strlen($tmp[0]==2)?'20'.$tmp[0]:$tmp[0];
                $month = str_pad($tmp[1],2,'0',STR_PAD_LEFT);
                $day = str_pad($tmp[2],2,'0',STR_PAD_LEFT);
                $date1 = $year."-".$month."-".$day;
            }
            else {
                $tmp = explode("/",$date);
                if (is_array($tmp) && count($tmp)==3){
                    $year = strlen($tmp[2]==2)?'20'.$tmp[2]:$tmp[2];
                    $month = str_pad($tmp[0],2,'0',STR_PAD_LEFT);
                    $day = str_pad($tmp[1],2,'0',STR_PAD_LEFT);
                    $date1 = $year."-".$month."-".$day;
                }
                else $date1 = $date;
            }
        }

        if ($date1 !== '' && $transNum !== ''){
            $ret .= $this->receiptHeader($date1,$transNum);
            $ret .= $this->ccInfo($date1, $transNum);
            $ret .= $this->signatures($date1, $transNum);
        }
        return $ret;
    }

    function receiptHeader($date,$trans) {
        global $FANNIE_ARCHIVE_DB, $FANNIE_TRANS_DB, $FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_METHOD;
        $dbconn = ($FANNIE_SERVER_DBMS=='MSSQL')?'.dbo.':'.';

        $totime = strtotime($date);
        $month = date('m',$totime);
        $year = date('Y',$totime);
        $day = date('j',$totime);
        $transact = explode('-',$trans);
        $emp_no = $transact[0];
        $trans_no = $transact[2];
        $reg_no = $transact[1];

        $table = DTransactionsModel::selectDtrans(date('Y-m-d',$totime));
        $query1 = "SELECT 
            description,
            case 
                when voided = 5 
                    then 'Discount'
                when trans_status = 'M'
                    then 'Mbr special'
                when scale <> 0 and quantity <> 0 
                    then concat(convert(quantity,char), ' @ ', convert(unitPrice,char))
                when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
                    then concat(convert(volume,char), ' /', convert(unitPrice,char))
                when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
                    then concat(convert(Quantity,char), ' @ ', convert(Volume,char), ' /', convert(unitPrice,char))
                when abs(itemQtty) > 1 and discounttype = 3
                    then concat(convert(ItemQtty,char), ' /', convert(UnitPrice,char))
                when abs(itemQtty) > 1
                    then concat(convert(quantity,char), ' @ ', convert(unitPrice,char)) 
                when matched > 0
                    then '1 w/ vol adj'
                else ''
                    
            end
            as comment,
            total,
            case 
                when trans_status = 'V' 
                    then 'VD'
                when trans_status = 'R'
                    then 'RF'
                when tax <> 0 and foodstamp <> 0
                    then 'TF'
                when tax <> 0 and foodstamp = 0
                    then 'T' 
                when tax = 0 and foodstamp <> 0
                    then 'F'
                when tax = 0 and foodstamp = 0
                    then '' 
            end
            as Status,
            datetime, register_no, emp_no, trans_no, card_no as memberID
            FROM $table 
            WHERE datetime BETWEEN ? AND ? 
            AND register_no=? AND emp_no=? and trans_no=?
            AND voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
            ORDER BY trans_id";
        $args = array("$year-$month-$day 00:00:00", "$year-$month-$day 23:59:59", 
                $reg_no, $emp_no, $trans_no);
        return $this->receipt_to_table($query1,$args,0,'FFFFFF');
    }

    function receipt_to_table($query,$args,$border,$bgcolor){
        global $FANNIE_TRANS_DB, $FANNIE_COOP_ID;

        $dbc = FannieDB::get($FANNIE_TRANS_DB);
        $prep = $dbc->prepare_statement($query); 
        $results = $dbc->exec_statement($prep,$args);
        $number_cols = $dbc->num_fields($results);
        $rows = array();
        while($row = $dbc->fetch_row($results))
            $rows[] = $row;
        $row2 = $rows[0];
        $emp_no = $row2['emp_no'];  
        $trans_num = $row2['emp_no']."-".$row2['register_no']."-".$row2['trans_no'];

        /* 20Jan13 EL The way I would like to do this.
         * Or perhaps get from core_trans.lane_config
        if ( $CORE_LOCAL->get("receiptHeaderCount") > 0 ) {
            $receiptHeader = "";
            $c = $CORE_LOCAL->get("receiptHeaderCount");
            for ( $i=1; $i <= $c; $i++ ) {
                $h = "receiptHeader$i";
                $receiptHeader .= ("<tr><td align=center colspan=4>" . $CORE_LOCAL->get("$h") . "</td></tr>\n");
            }
        }
        */

        $receiptHeader = "";
        if ( isset($FANNIE_COOP_ID) ) {
            switch ($FANNIE_COOP_ID) {

            case "WEFC_Toronto":
                $receiptHeader .= ("<tr><td align=center colspan=4>" . "W E S T &nbsp; E N D &nbsp; F O O D &nbsp; C O - O P" . "</td></tr>\n");
                $receiptHeader .= ("<tr><td align=center colspan=4>" . "416-533-6363" . "</td></tr>\n");
                $receiptHeader .= ("<tr><td align=center colspan=4>" . "Local food for local tastes" . "</td></tr>\n");
                break;

            case "WFC_Duluth":
                $receiptHeader .= ("<tr><td align=center colspan=4>" . "W H O L E &nbsp; F O O D S &nbsp; C O - O P" . "</td></tr>\n");
                $receiptHeader .= ("<tr><td align=center colspan=4>" . "218-728-0884" . "</td></tr>\n");
                $receiptHeader .= ("<tr><td align=center colspan=4>" . "MEMBER OWNED SINCE 1970" . "</td></tr>\n");
                break;

            default:
                $receiptHeader .= ("<tr><td align=center colspan=4>" . "FANNIE_COOP_ID >{$FANNIE_COOP_ID}<" . "</td></tr>\n");

            }
        }

        $ret = "<table border = $border bgcolor=$bgcolor>\n";
        $ret .= "{$receiptHeader}\n";
        $ret .= "<tr><td align=center colspan=4>{$row2['datetime']} &nbsp; &nbsp; $trans_num</td></tr>";
        $ret .= "<tr><td align=center colspan=4>Cashier:&nbsp;$emp_no</td></tr>";
        $ret .= "<tr><td colspan=4>&nbsp;</td></tr>";
        $ret .= "<tr align left>\n";
        foreach($rows as $row){
            $ret .= "<tr><td align=left>";
            $ret .= $row["description"]; 
            $ret .= "</td>";
            $ret .= "<td align=right>";
            $ret .= $row["comment"];
            $ret .= "</td><td align=right>";
            $ret .= $row["total"];
            $ret .= "</td><td align=right>";
            $ret .= $row["Status"];
            $ret .= "</td></tr>";   
        } 
        
        $ret .= "<tr><td colspan=4>&nbsp;</td></tr>";
        $ret .= "<tr><td colspan=4 align=center>--------------------------------------------------------</td></tr>";
        $ret .= "<tr><td colspan=4 align=center>Reprinted Transaction</td></tr>";
        $ret .= "<tr><td colspan=4 align=center>--------------------------------------------------------</td></tr>";
        $ret .= "<tr><td colspan=4 align=center>" . _('Owner') . "#: {$row2['memberID']}</td</tr>";
        $ret .= "</table>\n";

        return $ret;
    }

    function ccInfo($date1, $transNum){
        global $FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB;
        $dbconn = ($FANNIE_SERVER_DBMS=='MSSQL')?'.dbo.':'.';
        $dbc = FannieDB::get($FANNIE_TRANS_DB);

        $dateInt = str_replace("-","",$date1);
        list($emp,$reg,$trans) = explode("-",$transNum);

        $query = $dbc->prepare_statement("SELECT mode, amount, PAN, 
            CASE WHEN manual=1 THEN 'keyed' ELSE 'swiped' END AS entryMethod, 
            issuer, xResultMessage, xApprovalNumber, xTransactionID, name,
            q.refNum
            FROM {$FANNIE_TRANS_DB}{$dbconn}efsnetRequest AS q LEFT JOIN 
            {$FANNIE_TRANS_DB}{$dbconn}efsnetResponse AS r
            ON q.refNum=r.refNum  WHERE q.date=? AND
            q.cashierNo=? AND q.laneNo=? AND q.transNo=?
            and commErr=0
            UNION ALL 
            SELECT m.mode, amount, PAN, 
            CASE WHEN manual=1 THEN 'keyed' ELSE 'swiped' END AS entryMethod, 
            issuer, r.xResultMessage, r.xApprovalNumber, r.xTransactionID, name,
            q.refNum
            from {$FANNIE_TRANS_DB}{$dbconn}efsnetRequestMod m
            join {$FANNIE_TRANS_DB}{$dbconn}efsnetRequest q
              on q.date=m.date
              and q.cashierNo=m.cashierNo
              and q.laneNo=m.laneNo
              and q.transNo=m.transNo
              and q.transID=m.transID
            join {$FANNIE_TRANS_DB}{$dbconn}efsnetResponse AS r
            ON q.refNum=r.refNum  WHERE q.date=? AND
            q.cashierNo=? AND q.laneNo=? AND q.transNo=?
            and m.validResponse=1 and 
            (m.xResponseCode=0 or m.xResultMessage like '%APPROVE%')
            and m.commErr=0 AND r.commErr=0");
        $result = $dbc->exec_statement($query,array(
                        $dateInt,$emp,$reg,$trans,
                        $dateInt,$emp,$reg,$trans
                        ));
        $ret = '';
        $pRef = '';
        while ($row = $dbc->fetch_row($result)){
            if ($pRef == $row['refNum'] && $row['mode'] != 'VOID') continue;
            $ret .= "<hr />";
            $ret .= 'Mode: '.$row['mode'].'<br />';
            $ret .= "Card: ".$row['issuer'].' '.$row['PAN'].'<br />';
            $ret .= "Name: ".$row['name'].'<br />';
            $ret .= "Entry Method: ".$row['entryMethod'].'<br />';
            $ret .= "Sequence Number: ".$row['xTransactionID'].'<br />';
            $ret .= "Authorization: ".$row['xResultMessage'].'<br />';
            $ret .= '<b>Amount</b>: '.sprintf('$%.2f',$row['amount']).'<br />';
            if ($row['mode'] == 'VOID'){}
            elseif(strstr($row['mode'],'retail_'))
                $ret .= 'FAPS<br />';
            else
                $ret .= 'MERCURY<br />';
            $pRef = $row['refNum'];
        }
        return $ret;
    }

    private function signatures($tdate, $transNum)
    {
        global $FANNIE_TRANS_DB;
        if (strstr($tdate, ' ')) {
            list($tdate, $time) = explode(' ', $tdate, 2);
        }
        list($emp,$reg,$trans) = explode('-', $transNum);

        $dbc = FannieDB::get($FANNIE_TRANS_DB);
        $lookupQ = 'SELECT capturedSignatureID 
                    FROM CapturedSignature
                    WHERE tdate BETWEEN ? AND ?
                        AND emp_no=?
                        AND register_no=?
                        AND trans_no=?';
        $lookupP = $dbc->prepare($lookupQ);
        $args = array(
            $tdate . ' 00:00:00',
            $tdate . ' 23:59:59',
            $emp,
            $reg,
            $trans,
        );
        $lookupR = $dbc->execute($lookupP, $args);
        $ret = '';
        while($row = $dbc->fetch_row($lookupR)) {
            $ret .= sprintf('<img style="border: solid 1px black; padding: 5px;"
                                alt="Signature Image"
                                src="SigImage.php?id=%d" />',
                                $row['capturedSignatureID']
            );
        }

        return $ret;
    }

}

FannieDispatch::conditionalExec(false);


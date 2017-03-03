<?php
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class RenderReceiptPage extends \COREPOS\Fannie\API\FannieReadOnlyPage 
{
    protected $window_dressing = true;

    public $description = '[Reprint Receipt] show a POS transaction receipt.';
    public $themed = true;

    /**
      Uses parent method to setup all javascript and css includes
      but returns an ultra simple header. Receipt page needs
      to be printable on paper
    */
    public function getHeader()
    {
        parent::getHeader();
        return '
            <!doctype html>
            <html>
            <head>
                <title>Reprint Receipt</title>
            </head>
            <body>
            <div class="container-fluid">';
    }

    /**
      Simple footer matches simple header
    */
    public function getFooter()
    {
        return '</div></body></html>';
    }

    private function getReceiptDate($form)
    {
        // see if date was passed in
        try {
            $stamp = strtotime($form->date);
            if ($stamp === false) {
                return false;
            } else {
                return date('Y-m-d', $stamp);
            }
        } catch (Exception $ex) {}

        // see if year, month, and day were passed in
        try {
            $stamp = mktime(0, 0, 0, $form->month, $form->day, $form->year);
            return date('Y-m-d', $stamp);
        } catch (Exception $ex) {
            return false;
        }
    }

    function get_view()
    {
        ob_start();
        ?>
        <form action=RenderReceiptPage.php method=get
            class="hidden-print">
        <p>
        <div class="form-group form-inline">
            <label>Date</label>:
            <input type=text name=date id="date-field"
                class="form-control" />
            <label>Receipt Num</label>:
            <input type=text name=receipt id="trans-field"
                class="form-control" />
            <button type=submit class="btn btn-default">Find Receipt</button>
        </div>
        </p>
        </form>
        <hr class="hidden-print" />
        <?php
        $ret = ob_get_clean();
        $date1 = $this->getReceiptDate($this->form);
        try {
            $transNum = $this->form->receipt;
        } catch (Exception $ex) {
            $transNum = '';
        }

        if ($date1 !== false && $transNum !== '') {
            $ret .= '<p>';
            $ret .= $this->receiptHeader($date1,$transNum);
            $voided = $this->wasVoided($date1, $transNum);
            if ($voided !== false) {
                $ret .= sprintf('<hr>This transaction was
                    voided by <a href="?date=%s&receipt=%s">%s</a>',
                    $date1, $voided, $voided);
            }
            $is_void = $this->isVoid($date1, $transNum);
            if ($is_void !== false) {
                $is_void = substr($is_void, 20);
                $ret .= sprintf('<hr>This transaction voided
                    the previous <a href="?date=%s&receipt=%s">%s</a>',
                    $date1, $is_void, $is_void);
            }
            $ret .= $this->ccInfo($date1, $transNum);
            $ret .= $this->signatures($date1, $transNum);
            $ret .= '</p>';
            $this->add_onload_command("\$('#date-field').val('$date1');\n");
            $this->add_onload_command("\$('#trans-field').val('$transNum');\n");

            $ret .= '<p class="hidden-print">';
            $ret .= '<a href="RawReceipt.php?date=' . $date1 . '&trans=' . $transNum . '">Database Details</a>';
            $ret .= '</p>';
        }
        $this->add_onload_command("\$('#date-field').datepicker({dateFormat:'yy-mm-dd'});\n");

        return $ret;
    }

    private function wasVoided($date, $trans_num)
    {
        $prep = $this->connection->prepare("
            SELECT trans_num
            FROM " . $this->config->get('TRANS_DB') . $this->connection->sep() . "voidTransHistory
            WHERE description = ?
                AND tdate BETWEEN ? AND ?
        ");
        $args = array(
            'VOIDING TRANSACTION ' . $trans_num,
            $date . ' 00:00:00',
            $date . ' 23:59:59',
        );

        return $this->connection->getValue($prep, $args);
    }

    private function isVoid($date, $trans_num)
    {
        $prep = $this->connection->prepare("
            SELECT description
            FROM " . $this->config->get('TRANS_DB') . $this->connection->sep() . "voidTransHistory
            WHERE trans_num = ?
                AND tdate BETWEEN ? AND ?
        ");
        $args = array(
            $trans_num,
            $date . ' 00:00:00',
            $date . ' 23:59:59',
        );

        return $this->connection->getValue($prep, $args);
    }

    function receiptHeader($date,$trans) 
    {
        $totime = strtotime($date);
        $month = date('m',$totime);
        $year = date('Y',$totime);
        $day = date('j',$totime);
        $transact = explode('-',$trans);
        if (count($transact) != 3) {
            return '';
        }
        $emp_no = $transact[0];
        $trans_no = $transact[2];
        $reg_no = $transact[1];

        $table = DTransactionsModel::selectDtrans(date('Y-m-d',$totime));
        $query1 = "SELECT 
            description,
            case 
                when voided = 5 
                    then unitPrice
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
            CASE
                WHEN voided in (3) THEN unitPrice
                WHEN voided IN (5) THEN NULL 
                ELSE total
            END AS total,
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
            datetime, register_no, emp_no, trans_no, card_no as memberID,
            upc
            FROM $table 
            WHERE datetime BETWEEN ? AND ? 
                AND register_no=? AND emp_no=? and trans_no=?
                AND voided <> 4 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
                AND trans_type <> 'L'
            ORDER BY trans_id";
        $args = array("$year-$month-$day 00:00:00", "$year-$month-$day 23:59:59", 
                $reg_no, $emp_no, $trans_no);
        return $this->receipt_to_table($query1,$args,0,'FFFFFF');
    }

    private function receiptHeaderLines()
    {
        $receiptHeader = "";
        if ($this->config->get('COOP_ID')) { 
            switch ($this->config->get('COOP_ID')) {

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
                break;
            }
        }
        return $receiptHeader;
    }

    function receipt_to_table($query,$args,$border,$bgcolor)
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
        $prep = $dbc->prepare($query); 
        $results = $dbc->execute($prep,$args);
        $number_cols = $dbc->numFields($results);
        $rows = array();
        while ($row = $dbc->fetch_row($results)) {
            $rows[] = $row;
        } 
        if (isset($rows[0])) {
            $row2 = $rows[0];
        } else {
            $row2 = array('emp_no'=>'','register_no'=>'','trans_no'=>'','datetime'=>'','memberID'=>'');
        }
        $emp_no = $row2['emp_no'];  
        $trans_num = $row2['emp_no']."-".$row2['register_no']."-".$row2['trans_no'];

        $receiptHeader = $this->receiptHeaderLines();

        $ret = "<table border = $border bgcolor=$bgcolor>\n";
        $ret .= "{$receiptHeader}\n";
        $ret .= "<tr><td align=center colspan=4>{$row2['datetime']} &nbsp; &nbsp; $trans_num</td></tr>";
        $ret .= "<tr><td align=center colspan=4>Cashier:&nbsp;$emp_no</td></tr>";
        $ret .= "<tr><td colspan=4>&nbsp;</td></tr>";
        $ret .= "<tr align left>\n";
        foreach ($rows as $row) {
            $ret .= "<tr><td align=left>";
            if ($row['description'] == 'BADSCAN') {
                $row['description'] .= ' (' . $row['upc'] . ')';
            }
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
        $ret .= "<tr><td colspan=4 align=center>" . _('Owner') . "#: {$row2['memberID']}</td></tr>";
        $ret .= "</table>\n";

        return $ret;
    }

    function ccInfo($date1, $transNum)
    {
        global $FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB;
        $dbconn = ($FANNIE_SERVER_DBMS=='MSSQL')?'.dbo.':'.';
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));

        $dateInt = str_replace("-","",$date1);
        list($emp,$reg,$trans) = explode("-",$transNum);

        $query = $dbc->prepare("SELECT transType AS mode, amount, PAN, 
            CASE WHEN manual=1 THEN 'keyed' ELSE 'swiped' END AS entryMethod, 
            issuer, xResultMessage, xApprovalNumber, xTransactionID, name,
            refNum
            FROM {$FANNIE_TRANS_DB}{$dbconn}PaycardTransactions
            WHERE dateID=? AND
                empNo=? AND registerNo=? AND transNo=?
                AND commErr=0");
        $result = $dbc->execute($query,array($dateInt,$emp,$reg,$trans));
        $ret = '';
        $pRef = '';
        while ($row = $dbc->fetchRow($result)) {
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
        if (strstr($tdate, ' ')) {
            list($tdate, $time) = explode(' ', $tdate, 2);
        }
        list($emp,$reg,$trans) = explode('-', $transNum);

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
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

    public function unitTest($phpunit)
    {
        $form = new COREPOS\common\mvc\ValueContainer();
        $form->receipt = '1-1-1';
        $form->year = date('Y');
        $form->month = date('n');
        $form->day = date('j');
        $this->setForm($form);
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $form->date = date('Y-m-d');
        $this->setForm($form);
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $phpunit->assertNotEquals(0, strlen($this->getHeader()));
        $phpunit->assertNotEquals(0, strlen($this->getFooter()));
    }

}

FannieDispatch::conditionalExec();


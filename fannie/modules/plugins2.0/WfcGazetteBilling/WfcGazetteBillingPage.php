<?php
include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class WfcGazetteBillingPage extends \COREPOS\Fannie\API\FannieUploadPage {

    public $page_set = 'Plugin :: WfcGazetteBilling';
    public $description = '[Import Billing Data] to generate AR transactions with appropriate balances.';
    public $themed = true;

    protected $preview_opts = array(
        'phone' => array(
            'display_name' => 'Phone',
            'default' => 2,
            'required' => true
        ),
        'card_no' => array(
            'display_name' => 'Mem#',
            'default' => 3,
            'required' => true
        ),
        'size' => array(
            'display_name' => 'Ad Size/Color',
            'default' => 4,
            'required' => true
        ),
        'amount' => array(
            'display_name' => 'Amount',
            'default' => 6,
            'required' => true
        ),
        'name' => array(
            'display_name' => 'Name',
            'default' => 0,
            'required' => true
        )
    );

    protected $header = 'Gazette Billing';
    protected $title = 'Gazette Billing';

    function preprocess(){
        $posted_info = FormLib::get_form_value('cardnos');
        if (is_array($posted_info)){
            $this->content_function = 'post_charges';
            return True;
        }
        return parent::preprocess();
    }

    function post_charges(){
        global $FANNIE_TRANS_DB;
        $EMP_NO = $this->config->get('EMP_NO');
        $LANE_NO = $this->config->get('REGISTER_NO');
        $ret = "<b>Date</b>: ".date("m/d/Y")."<br />
            <i>Summary of charges</i><br />
            <table class=\"table\">
            <tr><th>Account</th><th>Charge</th><th>Receipt #</th></tr>";
        $sql = FannieDB::get($FANNIE_TRANS_DB); 
        $dRecord = DTrans::defaults();
        $dRecord['emp_no'] = $EMP_NO;
        $dRecord['register_no'] = $LANE_NO;
        $dRecord['trans_type'] = 'D';
        $dRecord['department'] = 703;
        $dRecord['quantity'] = 1;
        $dRecord['ItemQtty'] = 1;
        $dRecord['trans_id'] = 1;

        $dParam = DTrans::parameterize($dRecord, 'datetime', $sql->now());
        $insD = $sql->prepare("INSERT INTO dtransactions
                ({$dParam['columnString']}) VALUES ({$dParam['valueString']})");

        $tRecord = DTrans::defaults();
        $tRecord['emp_no'] = $EMP_NO;
        $tRecord['register_no'] = $LANE_NO;
        $tRecord['trans_type'] = 'D';
        $tRecord['department'] = 994;
        $tRecord['quantity'] = 1;
        $tRecord['ItemQtty'] = 1;
        //$tRecord['upc'] = '0';
        //$tRecord['description'] = 'InStore Charges';
        //$tRecord['trans_type'] = 'T';
        //$tRecord['trans_subtype'] = 'MI';
        //$tRecord['quantity'] = 0;
        //$tRecord['ItemQtty'] = 0;
        $tRecord['trans_id'] = 2;

        $tParam = DTrans::parameterize($tRecord, 'datetime', $sql->now());
        $insT = $sql->prepare("INSERT INTO dtransactions
                ({$tParam['columnString']}) VALUES ({$tParam['valueString']})");

        $invP = $sql->prepare("INSERT INTO B2BInvoices 
            (cardNo, createdDate, createdTransNum, amount, description, isPaid, coding, createdBy, lastModifiedBy, customerNotes, uuid, emailSubject)
            VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, 'Garbanzo Gazette')");
        $invCoding = trim(FormLib::get('coding'));
        $invIssue = trim(FormLib::get('issueName'));
        $custNotes = trim(FormLib::get('customerNotes'));
        $uid = FannieAuth::getUID($this->current_user);
        $flagP = $sql->prepare('UPDATE dtransactions SET numflag=?, charflag=\'B2\' WHERE emp_no=? AND register_no=? AND trans_no=?');
        $amtP = $sql->prepare("UPDATE B2BInvoices SET description=CONCAT(description, ' (\$', amount, ')') WHERE b2bInvoiceID=?");
        $addP = $sql->prepare("UPDATE B2BInvoices SET amount=amount+?, description=CONCAT(description, '\\n', ?) WHERE b2bInvoiceID=?");
        
        $transQ = $sql->prepare("SELECT MAX(trans_no) FROM dtransactions
            WHERE emp_no=? AND register_no=?");
        $sql->startTransaction();
        $invoices = array();
        $multi = array();
        $cards = FormLib::get('cardnos', array());
        $amts = FormLib::get('billable', array());
        $types = FormLib::get('desc', array());
        for ($i=0; $i<count($cards); $i++) {
            $cardno = $cards[$i];
            $amt = $amts[$i];
            $desc = $types[$i];
            $desc = substr($desc,0,24);

            /*
            $transR = $sql->execute($transQ, array($EMP_NO, $LANE_NO));
            $t_no = '';
            if ($sql->num_rows($transR) > 0){
                $row = $sql->fetch_row($transR);
                $t_no = $row[0];
            }
            if ($t_no == "") $t_no = 1;
            else $t_no++;
            $dRecord['trans_no'] = $t_no;
            $dRecord['upc'] = $amt.'DP703';
            $dRecord['description'] = 'Gazette Ad '.$desc;
            $dRecord['unitPrice'] = $amt;
            $dRecord['total'] = $amt;
            $dRecord['regPrice'] = $amt;
            $dRecord['card_no'] = $cardno;

            $dParam = DTrans::parameterize($dRecord);
            $ins = $sql->execute($insD, $dParam['arguments']);

            $tRecord['trans_no'] = $t_no;
            $tRecord['upc'] = $amt.'DP994';
            $tRecord['description'] = 'B2B INVOICING';
            $tRecord['unitPrice'] = -1*$amt;
            $tRecord['total'] = -1*$amt;
            $tRecord['regPrice'] = -1*$amt;
            $tRecord['card_no'] = $cardno;

            $tParam = DTrans::parameterize($tRecord);
            $sql->execute($insT, $tParam['arguments']);

            $ret .= sprintf("<tr><td>%d</td><td>$%.2f</td><td>%s</td></tr>",
                $cardno,$amt,$EMP_NO."-".$LANE_NO."-".$t_no);
            */

            if (isset($invoices[$cardno])) {
                $b2bID = $invoices[$cardno];
                if (!isset($multi[$b2bID])) {
                    $sql->execute($amtP, array($b2bID));
                    $multi[$b2bID] = true;
                }
                $sql->execute($addP, array($amt, 'Winter 2017/18 ' . $desc . sprintf(' ($%.2f)', $amt), $b2bID));
            } else {
                $uuid = '';
                if (class_exists('Ramsey\\Uuid\\Uuid')) {
                    $uuid = Ramsey\Uuid\Uuid::uuid4();
                    $uuid = str_replace('-', '', $uuid->toString());
                }
                $invArgs = array(
                    $cardno,
                    date('Y-m-d H:i:s'),
                    'n/a',
                    $amt,
                    $invIssue . ' ' . $desc,
                    $invCoding,
                    $uid,
                    $uid,
                    $custNotes,
                    $uuid,
                );
                $sql->execute($invP, $invArgs);
                $invID = $sql->insertID();
                $invoices[$cardno] = $invID;
                $ret .= "<tr><td>Invoice</td><td>$invID</td></tr>";
            }
        }
        $sql->commitTransaction();

        $ret .= '</table>';

        return $ret;
    }

    private $output_html = '';
    public function process_file($linedata, $indexes)
    {
        global $FANNIE_OP_DB;
        $PHONE = $this->get_column_index('phone');
        $CONTACT = $this->get_column_index('name');
        $AD_TYPE = $this->get_column_index('size');
        $MEMBER = $this->get_column_index('card_no');
        $AMOUNT = $this->get_column_index('amount');

        $ret = "<b>Gazette Billing Preview</b><br />
            <table class=\"table\"><tr>
            <th>#</th><th>Name</th><th>Type</th><th>Cost</th>
            </tr>
            <form action=WfcGazetteBillingPage.php method=post>";
        $sql = FannieDB::get($FANNIE_OP_DB);
        $searchQ = $sql->prepare("SELECT m.card_no,c.lastname FROM
            meminfo as m left join custdata as c
            on m.card_no=c.cardno and c.personnum=1
            left join suspensions as s on
            m.card_no = s.cardno
            WHERE (c.memtype = 2 or s.memtype1 = 2)
            and (m.phone=? OR m.email_1=? OR m.email_2=?
            or c.lastname=?)");
        $altSearchQ = $sql->prepare("SELECT m.card_no,c.lastname FROM
            meminfo as m left join custdata as c
            on m.card_no=c.cardno and c.personnum=1
            WHERE c.memtype = 2
            AND c.lastname like ? and
            (m.phone=? OR m.email_1=? OR m.email_2=?)");
        $greydoffin=0;
        $warnings = '';
        foreach($linedata as $data){

            if (!isset($data[$PHONE])) continue;

            $ph = $data[$PHONE];
            if (strstr($ph," OR "))
                $ph = array_pop(explode(" OR ",$ph));
            $ph = str_replace(" ","",$ph);
            $cn = $data[$CONTACT];
            $amount = trim($data[$AMOUNT]);
            $amount = trim($amount, '$');
            $data[$MEMBER] = trim(strtoupper($data[$MEMBER])); // match on YES

            if (strstr($cn,'STAR CREATIVE')){
                if (strstr($cn,'TYCOONS'))
                    $ph = '218-623-1889';
                elseif(strstr($cn,'BURRITO'))
                    $ph = '218-348-4557';
                elseif(strstr($cn,'BREWHOUSE'))
                    $ph = '218-726-1392';
                elseif (strstr($cn, 'ENDION')) {
                    $ph = '218-623-1872';
                } elseif (strstr($cn, 'GIFT CARDS')) {
                    $ph = '218.623.1872';
                }
            }

            $desc = $data[$AD_TYPE];
            $desc .= ((substr($data[$MEMBER],0,3)=="YES") ? ', owner' : '');

            $searchR = $sql->execute($searchQ, array($ph, $ph, $ph, $cn));

            if ($sql->num_rows($searchR) > 1){
                $tmp = explode(" ",$data[$CONTACT]);
                $searchR = $sql->execute($altSearchQ, array($tmp[0].'%', $ph, $ph, $ph));
            }

            if (strstr($cn, 'GREY DOFFIN') && strstr(strtoupper($cn),'BUYING')) {
                $searchP = $sql->prepare('SELECT CardNo as card_no, LastName
                        FROM custdata WHERE CardNo=? AND personNum=1');
                $searchR = $sql->execute($searchP, array(6880));
            } elseif (strstr($cn, 'GREY DOFFIN')) {
                $searchP = $sql->prepare('SELECT CardNo as card_no, LastName
                        FROM custdata WHERE CardNo=? AND personNum=1');
                $searchR = $sql->execute($searchP, array(13366));
            }
            
            if ($sql->num_rows($searchR) == 0){
                $warnings .= sprintf("<i>Warning: no membership found for %s (%s)</i><br />",
                    $data[$CONTACT],$ph);
                //$warnings .= '<pre>' . print_r($data, true) . '</pre>';
            }
            elseif ($sql->num_rows($searchR) > 1){
                $warnings .= sprintf("<i>Warning: multiple memberships found for %s (%s)</i><br />",
                    $data[$CONTACT],$ph);
            }
            else {
                    $row = $sql->fetch_row($searchR);
                    $ret .= sprintf("<tr><td>%d</td><td>%s</td>
                    <td>%s (%s)</td>
                    <td><div class=\"input-group\">
                        <span class=\"input-group-addon\">\$</span>
                        <input type=text class=\"form-control\" name=billable[] 
                            required value=%.2f />
                    </div></td></tr>
                    <input type=hidden name=desc[] value=\"%s\" />
                    <input type=hidden name=cardnos[] value=%d />",
                    $row[0],$row[1],$data[$AD_TYPE],
                    (substr($data[$MEMBER],0,3)=="YES")? 'MEMBER':'NON-MEMBER',
                    $amount,
                    $desc,$row[0]);
            }
        }
        $ret .= "</table>";
        $ret .= '<div class="form-group">
                <label>For Issue</label>
                <input type="text" required name="issueName" class="form-control" />
                </div>
                <div class="form-group">
                <label>Coding</label>
                <input type="text" required name="coding" class="form-control" value="65520" />
                </div>
                <div class="form-group">
                <label>Notes for Customers</label>
                <textarea name="customerNotes" class="form-control" rows="5"></textarea>
                </div>';
        $ret .= '<p><button type=submit class="btn btn-default">Charge Accounts</button></p>';
        $ret .= "</form>";
        $this->output_html = $ret;

        if (!empty($warnings)) {
            $this->output_html = '<div class="alert alert-warning">' . $warnings . '</div>' . $this->output_html;
        }

        return true;
    }

    function results_content(){
        return $this->output_html;
    }

    function form_content(){
        return '<p>Upload billing spreadsheet</p>';
    }
}

FannieDispatch::conditionalExec();


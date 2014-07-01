<?php
include(dirname(__FILE__).'/../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

$EMP_NO = 1001;
$LANE_NO = 30;

$BILLING_MEMBER = array(
    "1/20B/W" => 45.00, 
    "1/15B/W" => 60.00,
    "1/10B/W" => 90.00,
    "1/5B/W" => 187.50,
    "1/ 5B/W" => 187.50,
    "1/2B/W" => 412.50,
    "1/ 2B/W" => 412.50,
    "1/20FULL" => 63.75,
    "1/15FULL" => 75.00,
    "1/10FULL"  => 112.50,
    "0.1FULL"  => 112.50,
    "1/5FULL" => 225,
    "1/ 5FULL" => 225,
    "1/2FULL" => 562.50,
    "1/ 2FULL" => 562.50
);

$BILLING_NONMEMBER = array(
    "1/20B/W" => 60,
    "1/20B/W" => 60,
    "1/15B/W" => 80,
    "1/10B/W" => 120,
    "1/5B/W" => 250,
    "1/ 5B/W" => 250,
    "1/2B/W" => 550,
    "1/ 2B/W" => 550,
    "1/20FULL" => 85,
    "1/15FULL" => 100,
    "1/10FULL"  => 150,
    "1/5FULL" => 300,
    "1/ 5FULL" => 300,
    "1/2FULL" => 750,
    "1/ 2FULL" => 750
);

class WfcGazetteBillingPage extends FannieUploadPage {

    protected $preview_opts = array(
        'phone' => array(
            'name' => 'phone',
            'display_name' => 'Phone',
            'default' => 2,
            'required' => True
        ),
        'card_no' => array(
            'name' => 'card_no',
            'display_name' => 'Mem#',
            'default' => 3,
            'required' => True
        ),
        'size' => array(
            'name' => 'size',
            'display_name' => 'Ad Size#',
            'default' => 4,
            'required' => True
        ),
        'color' => array(
            'name' => 'color',
            'display_name' => 'Color/B&W',
            'default' => 5,
            'required' => True
        ),
        'name' => array(
            'name' => 'name',
            'display_name' => 'Name',
            'default' => 0,
            'required' => True
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
        global $FANNIE_TRANS_DB, $EMP_NO, $LANE_NO;
        $ret = "<b>Date</b>: ".date("m/d/Y")."<br />
            <i>Summary of charges</i><br />
            <table cellspacing=0 cellpadding=3 border=1>
            <tr><th>Account</th><th>Charge</th><th>Receipt #</th></tr>";
        $sql = FannieDB::get($FANNIE_TRANS_DB); 
        $dRecord = DTrans::$DEFAULTS;
        $dRecord['emp_no'] = $EMP_NO;
        $dRecord['register_no'] = $LANE_NO;
        $dRecord['trans_type'] = 'D';
        $dRecord['department'] = 703;
        $dRecord['quantity'] = 1;
        $dRecord['ItemQtty'] = 1;
        $dRecord['trans_id'] = 1;

        $dParam = DTrans::parameterize($dRecord, 'datetime', $sql->now());
        $insD = $sql->prepare_statement("INSERT INTO dtransactions
                ({$dParam['columnString']}) VALUES ({$dParam['valueString']})");

        $tRecord = DTrans::$DEFAULTS;
        $tRecord['emp_no'] = $EMP_NO;
        $tRecord['register_no'] = $LANE_NO;
        $tRecord['upc'] = '0';
        $tRecord['description'] = 'InStore Charges';
        $tRecord['trans_type'] = 'T';
        $tRecord['trans_subtype'] = 'MI';
        $tRecord['quantity'] = 0;
        $tRecord['ItemQtty'] = 0;
        $tRecord['trans_id'] = 2;

        $tParam = DTrans::parameterize($tRecord, 'datetime', $sql->now());
        $insT = $sql->prepare_statement("INSERT INTO dtransactions
                ({$tParam['columnString']}) VALUES ({$tParam['valueString']})");
        
        $transQ = $sql->prepare_statement("SELECT MAX(trans_no) FROM dtransactions
            WHERE emp_no=? AND register_no=?");
        foreach(FormLib::get_form_value('cardnos',array()) as $cardno){
            $amt = FormLib::get_form_value('billable'.$cardno);
            $transR = $sql->exec_statement($transQ, array($EMP_NO, $LANE_NO));
            $t_no = '';
            if ($sql->num_rows($transR) > 0){
                $row = $sql->fetch_row($transR);
                $t_no = $row[0];
            }
            if ($t_no == "") $t_no = 1;
            else $t_no++;
            $desc = FormLib::get_form_value('desc'.$cardno);
            $desc = substr($desc,0,24);

            $dRecord['trans_no'] = $t_no;
            $dRecord['upc'] = $amt.'DP703';
            $dRecord['description'] = 'Gazette Ad '.$desc;
            $dRecord['unitPrice'] = $amt;
            $dRecord['total'] = $amt;
            $dRecord['regPrice'] = $amt;
            $dRecord['card_no'] = $cardno;

            $dParam = DTrans::parameterize($dRecord);
            $ins = $sql->exec_statement($insD, $dParam['arguments']);

            $tRecord['trans_no'] = $t_no;
            $tRecord['total'] = -1*$amt;
            $tRecord['card_no'] = $cardno;

            $tParam = DTrans::parameterize($tRecord);
            $sql->exec_statement($insT, $tParam['arguments']);

            $ret .= sprintf("<tr><td>%d</td><td>$%.2f</td><td>%s</td></tr>",
                $cardno,$amt,$EMP_NO."-".$LANE_NO."-".$t_no);
        }
        return $ret;
    }

    private $output_html = '';
    function process_file($linedata){
        global $BILLING_MEMBER, $BILLING_NONMEMBER, $FANNIE_OP_DB;
        $PHONE = $this->get_column_index('phone');
        $CONTACT = $this->get_column_index('name');
        $SIZE = $this->get_column_index('size');
        $COLOR = $this->get_column_index('color');
        $MEMBER = $this->get_column_index('card_no');

        $ret = "<b>Gazette Billing Preview</b><br />
            <table cellspacing=0 cellpadding=3 border=1><tr>
            <th>#</th><th>Name</th><th>Type</th><th>Cost</th>
            </tr>
            <form action=WfcGazetteBillingPage.php method=post>";
        $sql = FannieDB::get($FANNIE_OP_DB);
        $searchQ = $sql->prepare_statement("SELECT m.card_no,c.lastname FROM
            meminfo as m left join custdata as c
            on m.card_no=c.cardno and c.personnum=1
            left join suspensions as s on
            m.card_no = s.cardno
            WHERE (c.memtype = 2 or s.memtype1 = 2)
            and (m.phone=? OR m.email_1=? OR m.email_2=?
            or c.lastname=?)");
        $altSearchQ = $sql->prepare_statement("SELECT m.card_no,c.lastname FROM
            meminfo as m left join custdata as c
            on m.card_no=c.cardno and c.personnum=1
            WHERE c.memtype = 2
            AND c.lastname like ? and
            (m.phone=? OR m.email_1=? OR m.email_2=?)");
        $greydoffin=0;
        foreach($linedata as $data){

            if (!isset($data[$PHONE])) continue;

            $ph = $data[$PHONE];
            if (strstr($ph," OR "))
                $ph = array_pop(explode(" OR ",$ph));
            $ph = str_replace(" ","",$ph);
            $cn = $data[$CONTACT];
            $sz = trim(strtoupper($data[$SIZE]));
            $clr = trim(strtoupper($data[$COLOR]));
            if (isset($clr[0]) && $clr[0] == "B") $clr = "B/W";
            elseif($clr == "COLOR") $clr = "FULL";
            if (!strstr($sz, '/')) 
                $sz = $this->decimal_to_fraction($sz);

            if (strstr($cn,'STAR CREATIVE')){
                if (strstr($cn,'TYCOONS'))
                    $ph = '218-623-1889';
                elseif(strstr($cn,'BURRITO'))
                    $ph = '218-348-4557';
                elseif(strstr($cn,'BREWHOUSE'))
                    $ph = '218-726-1392';
            }

            $desc = "($sz, ".($clr=="FULL" ? "color" : "b&w");
            $desc .= ((substr($data[$MEMBER],0,3)=="YES") ? ', owner' : '').")";

            $searchR = $sql->exec_statement($searchQ, array($ph, $ph, $ph, $cn));

            if ($sql->num_rows($searchR) > 1){
                $tmp = explode(" ",$data[$CONTACT]);
                $searchR = $sql->exec_statement($altSearchQ, array($tmp[0].'%', $ph, $ph, $ph));
            }

            if (strstr($cn, 'GREY DOFFIN')){
                $searchP = $sql->prepare_statement('SELECT CardNo as card_no, LastName
                        FROM custdata WHERE CardNo=? AND personNum=1');
                $args = array( ($greydoffin==0) ? 6880 : 13366 );
                $searchR = $sql->exec_statement($searchP, $args);
                $greydoffin++;
            }
            
            if ($sql->num_rows($searchR) == 0){
                $ret .= sprintf("<i>Warning: no membership found for %s (%s)<br />",
                    $data[$CONTACT],$ph);
            }
            elseif ($sql->num_rows($searchR) > 1){
                $ret .= sprintf("<i>Warning: multiple memberships found for %s (%s)<br />",
                    $data[$CONTACT],$ph);
            }
            elseif (!isset($BILLING_NONMEMBER[$sz.$clr])){
                $ret .= sprintf('<i>Warning: size/color "%s" unknown<br />',
                        $sz.$clr);
            }
            else {
                    $row = $sql->fetch_row($searchR);
                    $ret .= sprintf("<tr><td>%d</td><td>%s</td>
                    <td>%s %s (%s)</td><td><input type=text 
                    size=5 name=billable%d value=%.2f /></td></tr>
                    <input type=hidden name=desc%d value=\"%s\" />
                    <input type=hidden name=cardnos[] value=%d />",
                    $row[0],$row[1],$sz,
                    $data[$COLOR],
                    (substr($data[$MEMBER],0,3)=="YES")?
                    'MEMBER':'NON-MEMBER',
                    $row[0],
                    (substr($data[$MEMBER],0,3)=="YES")?
                    $BILLING_NONMEMBER[$sz.$clr]*0.75:
                    $BILLING_NONMEMBER[$sz.$clr],
                    $row[0],$desc,$row[0]);
            }
        }
        $ret .= "</table>";
        $ret .= "<input type=submit value=\"Charge Accounts\" />";
        $ret .= "</form>";
        $this->output_html = $ret;
        return True;
    }

    function decimal_to_fraction($num){
        $vals = array(
                '1/20' => 0.05,
                '1/15' => 1.0/15.0,
                "1/10" => 0.1,
                "1/5" => 0.2,
                "1/2" => 0.5
        );
        foreach($vals as $frac => $dec){
            if (abs($num - $dec) < 0.001)
                return $frac;
        }
        return $num;
    }

    function results_content(){
        return $this->output_html;
    }

    function form_content(){
        return 'Upload billing spreadsheet';
    }
}

FannieDispatch::conditionalExec();
?>

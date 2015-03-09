<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class GenericBillingPage extends FannieRESTfulPage 
{
    protected $title = "Fannie : Generic Biling";
    protected $header = "Generic Billing";

    public $description = '[Generic Billing] adds a specified amount and memo to a member\'s
    accounts receivable (AR) balance.';
    public $themed = true;

    private $LANE_NO=30;
    private $EMP_NO=1001;
    private $DEPT=703;

    function javascript_content(){
        ob_start();
        ?>
function getMemInfo(){
    $.ajax({
        url: 'GenericBillingPage.php?id='+$('#memnum').val(),
        type: 'get',
        success: function(resp){
            $('#contentArea').html(resp);
            $('#resultArea').html('');
        }
    });
}
function postBilling(){
    var data = 'id='+$('#form_memnum').val();
    data += '&amount='+$('#amount').val();
    data += '&desc='+$('#desc').val();
    $.ajax({
        url: 'GenericBillingPage.php',
        type: 'post',
        data: data,
        dataType: 'json',
        success: function(resp) {
            if (resp.billed) {
                $('#contentArea').html('');
                showBootstrapAlert('#resultArea', 'success', resp.msg);
            } else {
                showBootstrapAlert('#resultArea', 'danger', resp.msg);
            }
        }
    });
}
        <?php
        return ob_get_clean();
    }

    function get_view()
    {
        global $FANNIE_OP_DB;
        $sql = FannieDB::get($FANNIE_OP_DB);
        $value = FormLib::get_form_value('id');
        $this->add_onload_command('$(\'#memnum\').val($(\'#sel\').val());');
        $ret = "<form onsubmit=\"getMemInfo(); return false;\">
            <div class=\"form-group form-inline\">
            <label>Member #</label>:
            <input type=text id=memnum name=id 
                class=\"form-control\" value=\"$value\" />
            <select id=sel class=\"form-control\"
                onchange=\"\$('#memnum').val(this.value);\">";
        $numsQ = "SELECT cardno,lastname FROM custdata WHERE
            memtype = 2
            AND personnum=1
            ORDER BY cardno";
        $numsR = $sql->query($numsQ);
        while($numsW = $sql->fetch_row($numsR)){
            if ($value == trim($numsW[0]))
                $ret .= sprintf("<option value=%d selected>%d %s</option>",$numsW[0],$numsW[0],$numsW[1]);  
            else
                $ret .= sprintf("<option value=%d>%d %s</option>",$numsW[0],$numsW[0],$numsW[1]);   
        }
        $ret .= "</select>
            <button type=submit class=\"btn btn-default\">Submit</button>
            </div>
            </form><hr /><div id=\"contentArea\"></div>
            <div id=\"resultArea\"></div>";
        return $ret;
    }

    function get_id_handler(){
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $sql = FannieDB::get($FANNIE_OP_DB);

        $query = "SELECT c.CardNo,c.LastName,n.balance
            FROM custdata AS c LEFT JOIN
            ".$FANNIE_TRANS_DB.$sql->sep()."ar_live_balance AS n 
            ON c.CardNo=n.card_no
            WHERE c.CardNo=? AND c.personNum=1";
        $prep = $sql->prepare_statement($query);
        $result = $sql->exec_statement($prep, array($this->id));
        $row = $sql->fetch_row($result);

        printf("<form onsubmit=\"postBilling();return false;\">
            <div class=\"col-sm-6\">
            <table class=\"table\">
            <tr>
                <th>Member</th>
                <td>%d<input type=hidden id=form_memnum value=%d /></td>
                <th>Name</th>
                <td>%s</td>
            </tr>
            <tr>
                <th>Current Balance</th>
                <td>%.2f</td>
                <th>Bill</th>
                <td>
                    <div class=\"input-group\">
                        <span class=\"input-group-addon\">$</span>
                        <input type=text class=\"form-control\" id=amount required />
                    </div>
                </td>
            </tr>
            <tr>
                <th>For</th>
                <td colspan=3><input type=text maxlength=35 id=desc 
                    class=\"form-control\" required /></td>
            </tr>
            </table>
            <p>
            <button type=submit class=\"btn btn-default\">Bill Account</button>
            </p>
            </div>
            </form>",
            $row[0],$row[0],$row[1],$row[2]);

        return False;
    }

    function post_id_handler(){
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $sql = FannieDB::get($FANNIE_TRANS_DB);

        $amount = FormLib::get_form_value('amount');
        $desc = FormLib::get_form_value('desc');
        $json = array('msg' => '', 'billed' => 0);
        if ($amount === '') {
            $json['msg'] = "Amount is required";
            echo json_encode($json);
            return false;
        } elseif ($desc === '') {
            $json['msg'] =  "Description is required";
            echo json_encode($json);
            return false;
        }

        $desc = str_replace("'","''",$desc);

        $transQ = $sql->prepare_statement("SELECT MAX(trans_no) 
            FROM dtransactions
            WHERE emp_no=? AND register_no=?");
        $transR = $sql->exec_statement($transQ, array($this->EMP_NO, $this->LANE_NO));
        $t_no = '';
        if ($sql->num_rows($transR) > 0){
            $row = $sql->fetch_array($transR);
            $t_no = $row[0];
        }
        if ($t_no == "") $t_no = 1;
        else $t_no++;

        $record = DTrans::$DEFAULTS;
        $record['register_no'] = $this->LANE_NO;
        $record['emp_no'] = $this->EMP_NO;    
        $record['trans_no'] = $t_no;
        $record['upc'] = $amount.'DP'.$this->DEPT;
        $record['description'] = $desc;
        $record['trans_type'] = 'D';
        $record['department'] = $this->DEPT;
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = $amount;
        $record['total'] = $amount;
        $record['regPrice'] = $amount;
        $record['card_no'] = $this->id;
        $record['trans_id'] = 1;

        $param = DTrans::parameterize($record, 'datetime', $sql->now());
        $prep = $sql->prepare_statement("INSERT INTO dtransactions
                ({$param['columnString']}) VALUES ({$param['valueString']})");
        $sql->exec_statement($prep, $param['arguments']);

        $record['upc'] = '0';
        $record['description'] = 'InStore Charges';
        $record['trans_type'] = 'T';
        $record['trans_subtype'] = 'MI';
        $record['quantity'] = 0;
        $record['ItemQtty'] = 0;
        $record['unitPrice'] = 0;
        $record['regPrice'] = 0;
        $record['total'] = -1*$amount;
        $record['trans_id'] = 2;

        $param = DTrans::parameterize($record, 'datetime', $sql->now());
        $prep = $sql->prepare_statement("INSERT INTO dtransactions
                ({$param['columnString']}) VALUES ({$param['valueString']})");
        $sql->exec_statement($prep, $param['arguments']);

        $json['msg'] = sprintf("Member <b>%d</b> billed <b>$%.2f</b>.<br />
                Receipt is %d-%d-%d.",$this->id,$amount,
                $this->EMP_NO,$this->LANE_NO,$t_no);
        $json['billed'] = 1;
        echo json_encode($json);

        return false;
    }
}

FannieDispatch::conditionalExec();

?>

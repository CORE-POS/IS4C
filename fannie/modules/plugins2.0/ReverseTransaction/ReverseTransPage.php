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

class ReverseTransPage extends FannieRESTfulPage {

    protected $must_authenticate = True;
    protected $auth_classes = array('backvoids');

    protected $header = 'Transaction adjustment tool';
    protected $title = 'Transaction adjustment tool';

    public $page_set = 'Plugin :: Reverse Transaction';
    public $description = '[Reverse Transaction] generates a new transaction that exactly negates
    a previous transaction. The net effect should be zero but with a clear audit trail.';

    function preprocess(){
        $this->__routes[] = 'get<date><trans>';
        $this->__routes[] = 'post<date><trans>';
        $this->__routes[] = 'get<d><t>';
        return parent::preprocess();
    }

    function get_date_trans_handler(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $dlog = DTransactionsModel::selectDlog($this->date);

        $query = "select d.upc,d.trans_type,d.trans_subtype,d.trans_status,
              d.total,d.card_no,p.description,t.TenderName 
              from $dlog as d left join
              products as p on d.upc = p.upc 
              left join tenders AS t ON d.trans_subtype=t.TenderCode
              where tdate BETWEEN ? AND ?
              and trans_num=?
              order by d.trans_id";
        $prep = $dbc->prepare_statement($query);
        $args = array($this->date.' 00:00:00', $this->date.' 23:59:59', $this->trans);
        $result = $dbc->exec_statement($prep, $args);

        if ($dbc->num_rows($result) == 0){
            echo "Error: Transaction {$this->trans} not found on date {$this->date}";
            return False;
        }

        $ret = "<table cellspacing=0 cellpadding=3 border=1><tr>";
        $ret .= "<th>Type</th><th>Status</th><th>UPC</th><th>Description</th><th>Total</th>";
        $ret .= "<tr>";
        $cardno = "";
        while ($row = $dbc->fetch_array($result)){
            $cardno = $row['card_no'];
            $ret .= "<tr>";
            $ret .= "<td>";
            switch($row['trans_type']){
            case 'I':
                $ret .= "Item"; break;
            case 'T':
                $ret .= "Tender"; break;
            case 'S':
                $ret .= "Discount"; break;
            case 'A':
                $ret .= "Tax"; break;
            default:
                $ret .= $row['trans_type']; break;
            }
            $ret .= "</td>";
            if ($row['trans_type'] != 'T')
                $ret .= "<td></td>";
            else if ($row['trans_type'] == 'T'){
                $ret .=  "<td>";
                $ret .= $row['TenderName'];
                if ($ret['total'] < 0)
                    $ret .= ' (as change)';
                $ret .= "</td>";
            }
            $ret .= "<td>".$row[0]."</td>";
            $ret .= "<td>".$row['description']."</td>";
            $ret .= "<td>".$row['total']."</td>";
            $ret .= "</tr>";
        }
        $ret .= "</table><br />";
        $ret .= "<b>Date</b>: ".$this->date." <b>Trans #</b>: ".$this->trans."<br />";
        $ret .= "<b>Member number</b>: ".$cardno."<br /><br />";

        $ret .= "<a href=\"\" onclick=\"doVoid('{$this->date}','{$this->trans}'); return false;\">";
        $ret .= "Void this receipt</a><br />";
        echo $ret;
        return False;
    }

    function get_d_t_handler(){
        $this->date = $this->d;
        $this->trans = $this->t;
        return $this->post_date_trans_handler();
    }

    function post_date_trans_handler(){
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $dlog = DTransactionsModel::selectDtrans($this->date);

        $emp_no = $FANNIE_PLUGIN_SETTINGS['ReversalEmployee'];
        $register_no = $FANNIE_PLUGIN_SETTINGS['ReversalLane'];
        $trans_no = 1;

        $transP = $dbc->prepare_statement('SELECT MAX(trans_no) FROM
            '.$FANNIE_TRANS_DB.$dbc->sep().'dlog WHERE
            emp_no=? AND register_no=?');
        $transR = $dbc->exec_statement($transP, array($emp_no, $register_no));
        while($transW = $dbc->fetch_row($transR))
            $trans_no = $transW[0] + 1;
        
        list($old_emp,$old_reg,$old_trans) = explode("-",$this->trans);
        $query = "select upc, description, trans_type, trans_subtype,
            trans_status, department, quantity, Scale, unitPrice,
            total, regPrice, tax, foodstamp, discount, memDiscount,
            discountable, discounttype, voided, PercentDiscount,
            ItemQtty, volDiscType, volume, volSpecial, mixMatch,
            matched, memType, staff, card_no, numflag, charflag, 
            trans_id 
            from $dlog where register_no = ?
            and emp_no = ? and trans_no = ?
            and datetime BETWEEN ? AND ?
            and trans_status <> 'X'
            order by trans_id";
        $args = array($old_reg, $old_emp, $old_trans,
                $this->date.' 00:00:00', $this->date.' 23:59:59');
        $prep = $dbc->prepare_statement($query);
        $result = $dbc->exec_statement($prep, $args);

        $trans_id = 1;
        $record = DTrans::$DEFAULTS;
        $record['emp_no'] = $emp_no;
        $record['register_no'] = $register_no;
        $record['trans_no'] = $trans_no;
        $record['trans_id'] = $trans_id;

        $comment = $record;
        $comment['description'] = 'VOIDING TRANSACTION '.$this->trans;
        $comment['trans_type'] = 'C';
        $comment['trans_subtype'] = 'CM';
        $comment['trans_status'] = 'D'; 

        $params = DTrans::parameterize($comment, 'datetime', $dbc->now());
        $table = $FANNIE_TRANS_DB.$dbc->sep().'dtransactions';
        $prep = $dbc->prepare_statement("INSERT INTO $table ({$params['columnString']})
                    VALUES ({$params['valueString']})");
        $dbc->exec_statement($prep, $params['arguments']);
        $record['trans_id'] += 1;

        while($w = $dbc->fetch_row($result)){
            $next = $record; // copy base record

            $next['upc'] = $w['upc'];
            $next['description'] = $w['description'];
            $next['trans_type'] = $w['trans_type'];
            $next['trans_subtype'] = $w['trans_subtype'];
            $next['trans_status'] = $w['trans_status'];
            $next['department'] = $w['department'];
            $next['quantity'] = -1*$w['quantity'];
            $next['Scale'] = $w['Scale'];
            $next['unitPrice'] = -1*$w['unitPrice'];
            $next['total'] = -1*$w['total'];
            $next['regPrice'] = -1*$w['regPrice'];
            $next['tax'] = $w['tax'];
            $next['foodstamp'] = $w['foodstamp'];
            $next['discount'] = -1*$w['discount'];
            $next['memDiscount'] = -1*$w['memDiscount'];
            $next['discountable'] = $w['discountable'];
            $next['discounttype'] = $w['discounttype'];
            $next['voided'] = $w['voided'];
            $next['PercentDiscount'] = $w['PercentDiscount'];
            $next['ItemQtty'] = -1*$w['ItemQtty'];
            $next['volDiscType'] = $w['volDiscType'];
            $next['volume'] = -1*$w['volume'];
            $next['volSpecial'] = -1*$w['volSpecial'];
            $next['mixMatch'] = $w['mixMatch'];
            $next['matched'] = $w['matched'];
            $next['memType'] = $w['memType'];
            $next['staff'] = $w['staff'];
            $next['numflag'] = $w['numflag'];
            $next['charflag'] = $w['charflag'];
            $next['card_no'] = $w['card_no'];

            $params = DTrans::parameterize($next, 'datetime', $dbc->now());
            $prep = $dbc->prepare_statement("INSERT INTO $table ({$params['columnString']})
                        VALUES ({$params['valueString']})");
            $dbc->exec_statement($prep, $params['arguments']);
            $record['trans_id'] += 1;
        }

        // return a listing of the new, reversal transaction
        $this->trans = $emp_no.'-'.$register_no.'-'.$trans_no;
        $this->date = date('Y-m-d');
        return $this->get_date_trans_handler();
    }

    function get_view(){
        global $FANNIE_URL;
        $this->add_script('js/reverse.js');
        ob_start();
        ?>
        <form onsubmit="loadReceipt(); return false;">
        <table>
        <tr><td>Date</td><td> <input type=text id=rdate /></td></tr>
        <tr><td>Trans #</td><td> <input type=text id=rtrans_num /></td></tr>
        <tr><td colspan="2"><input type=submit value=Submit /></td></tr>
        </table>
        </form>
        <div id=contentarea>
        </div>
        <?php
        $this->add_onload_command("\$('#rdate').datepicker();\n");

        return ob_get_clean();
    }

}

FannieDispatch::conditionalExec();

?>

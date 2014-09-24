<?php
/*******************************************************************************

    Copyright 2010,2013 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class PatronageTransferTool extends FanniePage {

    protected $title='Fannie - Member Management Module';
    protected $header='Transfer Patronage';

    public $description = '[Transfer Patronage] shifts an entire transaction from one member
    to another.';

    private $errors = '';
    private $mode = 'init';
    private $depts = array();

    private $CORRECTION_CASHIER = 1001;
    private $CORRECTION_LANE = 30;
    private $CORRECTION_DEPT = 800;

    private $cn2;
    private $tn;
    private $date;
    private $name2;
    private $cn1;
    private $amt;

    function preprocess(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        global $FANNIE_EMP_NO, $FANNIE_REGISTER_NO;
        global $FANNIE_CORRECTION_DEPT;
        /**
          Use fannie settings if properly configured
        */
        if (is_numeric($FANNIE_EMP_NO)) {
            $this->CORRECTION_CASHIER = $FANNIE_EMP_NO;
        }
        if (is_numeric($FANNIE_REGISTER_NO)) {
            $this->CORRECTION_LANE = $FANNIE_REGISTER_NO;
        }
        if (is_numeric($FANNIE_CORRECTION_DEPT)) {
            $this->CORRECTION_DEPT = $FANNIE_CORRECTION_DEPT;
        }

        if (FormLib::get_form_value('submit1',False) !== False)
            $this->mode = 'confirm';
        elseif (FormLib::get_form_value('submit2',False) !== False)
            $this->mode = 'finish';

        // error check inputs
        if ($this->mode != 'init'){

            $this->date = FormLib::get_form_value('date');
            $this->tn = FormLib::get_form_value('trans_num');
            $this->cn2 = FormLib::get_form_value('memTo');

            if (!is_numeric($this->cn2)){
                $this->errors .= "<em>Error: member given (".$this->cn2.") isn't a number</em>"
                    ."<br /><br />"
                    ."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
                return True;
            }

            $q = $dbc->prepare_statement("SELECT FirstName,LastName FROM custdata WHERE CardNo=? AND personNum=1");
            $r = $dbc->exec_statement($q,array($this->cn2));
            if ($dbc->num_rows($r) == 0){
                $this->errors .= "<em>Error: no such member: ".$this->cn2."</em>"
                    ."<br /><br />"
                    ."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
                return True;
            }
            $row = $dbc->fetch_row($r);
            $this->name2 = $row[0].' '.$row[1];

            $dlog = DTransactionsModel::selectDlog($this->date);
            $q = $dbc->prepare_statement("SELECT card_no FROM $dlog WHERE trans_num=? AND
                tdate BETWEEN ? AND ?
                ORDER BY card_no DESC");
            $r = $dbc->exec_statement($q,array($this->tn,$this->date.' 00:00:00',$this->date.' 23:59:59'));
            if ($dbc->num_rows($r) == 0){
                $this->errors .= "<em>Error: receipt not found: $tn</em>"
                    ."<br /><br />"
                    ."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
                return True;
            }
            $this->cn1 = array_pop($dbc->fetch_row($r));

            $q = $dbc->prepare_statement("SELECT SUM(CASE WHEN trans_type in ('I','M','D') then total else 0 END)
                FROM $dlog WHERE trans_num=? AND tdate BETWEEN ? AND ?");
            $r = $dbc->exec_statement($q,array($this->tn,$this->date.' 00:00:00',$this->date.' 23:59:59'));
            $this->amt = array_pop($dbc->fetch_row($r));
        }

        return True;
    }
    
    function body_content(){
        if ($this->mode == 'init')
            return $this->form_content();
        elseif($this->mode == 'confirm')
            return $this->confirm_content();
        elseif($this->mode == 'finish')
            return $this->finish_content();
    }

    function confirm_content(){

        if (!empty($this->errors)) return $this->errors;

        $ret = "<form action=\"PatronageTransferTool.php\" method=\"post\">";
        $ret .= "<b>Confirm transfer</b>";
        $ret .= "<p style=\"font-size:120%\">";
        $ret .= sprintf("\$%.2f will be moved from %d to %d (%s)",
            $this->amt,$this->cn1,$this->cn2,$this->name2);
        $ret .= "</p><p>";
        $ret .= "<input type=\"hidden\" name=\"date\" value=\"{$this->date}\" />";
        $ret .= "<input type=\"hidden\" name=\"trans_num\" value=\"{$this->tn}\" />";
        $ret .= "<input type=\"hidden\" name=\"memTo\" value=\"{$this->cn2}\" />";
        $ret .= "<input type=\"submit\" name=\"submit2\" value=\"Confirm\" />";
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $ret .= "<input type=\"submit\" value=\"Back\" onclick=\"back(); return false;\" />";
        $ret .= "</form>";
        
        return $ret;
    }

    function finish_content(){

        if (!empty($this->errors)) return $this->errors;

        $ret = '';
        
        $dtrans = array();
        $dtrans['trans_no'] = $this->getTransNo($this->CORRECTION_CASHIER,$this->CORRECTION_LANE);
        $dtrans['trans_id'] = 1;
        $this->doInsert($dtrans,-1*$this->amt,$this->CORRECTION_DEPT,$this->cn1);

        $ret .= sprintf("Receipt #1: %s",$this->CORRECTION_CASHIER.'-'.$this->CORRECTION_LANE.'-'.$dtrans['trans_no']);

        $dtrans['trans_no'] = $this->getTransNo($this->CORRECTION_CASHIER,$this->CORRECTION_LANE);
        $dtrans['trans_id'] = 1;
        $this->doInsert($dtrans,$this->amt,$this->CORRECTION_DEPT,$this->cn2);

        $ret .= "<br /><br />";
        $ret .= sprintf("Receipt #2: %s",$this->CORRECTION_CASHIER.'-'.$this->CORRECTION_LANE.'-'.$dtrans['trans_no']);

        $ret .= '<hr />';

        $ret .= '<a href="../MemCorrectionIndex.php">Home</a>';

        return $ret;
    }

    function form_content(){

        if (!empty($this->errors)) return $this->errors;

        $ret = "<form action=\"PatronageTransferTool.php\" method=\"post\">";
        $ret .= "<p style=\"font-size:120%\">";
        $ret .= "Date <input type=\"text\" id=\"date\" name=\"date\" size=\"10\" /> ";
        $ret .= '<br />';
        $ret .= "Receipt <input type=\"text\" name=\"trans_num\" size=\"10\" /> ";
        $ret .= "</p><p style=\"font-size:120%;\">";
        $memNum = FormLib::get_form_value('memIN');
        $ret .= "To member #<input type=\"text\" name=\"memTo\" size=\"5\" />";
        $ret .= "</p><p>";
        $ret .= "<input type=\"hidden\" name=\"type\" value=\"equity_transfer\" />";
        $ret .= "<input type=\"submit\" name=\"submit1\" value=\"Submit\" />";
        $ret .= "</p>";
        $ret .= "</form>";

        $this->add_onload_command('$(\'#date\').datepicker();');

        return $ret;
    }

    function getTransNo($emp,$register){
        global $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_TRANS_DB);
        $q = $dbc->prepare_statement("SELECT max(trans_no) FROM dtransactions WHERE register_no=? AND emp_no=?");
        $r = $dbc->exec_statement($q,array($register,$emp));
        $n = array_pop($dbc->fetch_row($r));
        return (empty($n)?1:$n+1);  
    }

    function doInsert($dtrans,$amount,$department,$cardno){
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_TRANS_DB);
        $OP = $FANNIE_OP_DB.$dbc->sep();

        $defaults = array(
            'register_no'=>$this->CORRECTION_LANE,
            'emp_no'=>$this->CORRECTION_CASHIER,
            'trans_no'=>$dtrans['trans_no'],
            'upc'=>'',
            'description'=>'',
            'trans_type'=>'D',
            'trans_subtype'=>'',
            'trans_status'=>'',
            'department'=>'',
            'quantity'=>1,
            'scale'=>0,
            'cost'=>0,
            'unitPrice'=>'',
            'total'=>'',
            'regPrice'=>'',
            'tax'=>0,
            'foodstamp'=>0,
            'discount'=>0,
            'memDiscount'=>0,
            'discountable'=>0,
            'discounttype'=>0,
            'voided'=>0,
            'percentDiscount'=>0,
            'ItemQtty'=>1,
            'volDiscType'=>0,
            'volume'=>0,
            'volSpecial'=>0,
            'mixMatch'=>'',
            'matched'=>0,
            'memType'=>'',
            'staff'=>'',
            'numflag'=>0,
            'charflag'=>'',
            'card_no'=>'',
            'trans_id'=>$dtrans['trans_id']
        );

        $defaults['department'] = $department;
        $defaults['card_no'] = $cardno;
        $defaults['unitPrice'] = $amount;
        $defaults['regPrice'] = $amount;
        $defaults['total'] = $amount;
        if ($amount < 0){
            $defaults['trans_status'] = 'R';
            $defaults['quantity'] = -1;
        }
        $defaults['upc'] = abs($amount).'DP'.$department;

        if (isset($this->depts[$department]))
            $defaults['description'] = $this->depts[$department];
        else {
            $nameP = $dbc->prepare_statement("SELECT dept_name FROM {$OP}departments WHERE dept_no=?");
            $nameR = $dbc->exec_statement($nameP,$department);
            if ($dbc->num_rows($nameR) == 0) {
                $defaults['description'] = 'CORRECTIONS';
            } else {
                $nameW = $dbc->fetch_row($nameR);
                $defaults['description'] = $nameW['dept_name'];
            }
        }

        $q = $dbc->prepare_statement("SELECT memType,Staff FROM {$OP}custdata WHERE CardNo=?");
        $r = $dbc->exec_statement($q,array($cardno));
        $w = $dbc->fetch_row($r);
        $defaults['memType'] = $w[0];
        $defaults['staff'] = $w[1];

        $columns = 'datetime,';
        $values = $dbc->now().',';
        $args = array();
        foreach($defaults as $k=>$v){
            $columns .= $k.',';
            $values .= '?,';
            $args[] = $v;
        }
        $columns = substr($columns,0,strlen($columns)-1);
        $values = substr($values,0,strlen($values)-1);
        $prep = $dbc->prepare_statement("INSERT INTO dtransactions ($columns) VALUES ($values)");
        $dbc->exec_statement($prep, $args);
    }
}

FannieDispatch::conditionalExec(false);

?>

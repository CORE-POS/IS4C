<?php
/*******************************************************************************

    Copyright 2010,2013 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

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

class MemArEquityDumpTool extends FanniePage 
{
    protected $title='Fannie - Member Management Module';
    protected $header='Dump Member Equity/AR';

    public $description = '[Remove Equity/AR] simply removes amounts from a member\'s account.';
    public $themed = true;

    private $errors = '';
    private $mode = 'init';
    private $depts = array();

    private $CORRECTION_CASHIER = 1001;
    private $CORRECTION_LANE = 30;
    private $DEFAULT_DEPT = 703;

    private $dept1;
    private $dept2;
    private $amount;
    private $cn;
    private $name1;

    function preprocess(){
        global $FANNIE_AR_DEPARTMENTS;
        global $FANNIE_EQUITY_DEPARTMENTS;
        global $FANNIE_OP_DB;
        global $FANNIE_EMP_NO, $FANNIE_REGISTER_NO;
        global $FANNIE_MISC_DEPT;
        /**
          Use fannie settings if properly configured
        */
        if (is_numeric($FANNIE_EMP_NO)) {
            $this->CORRECTION_CASHIER = $FANNIE_EMP_NO;
        }
        if (is_numeric($FANNIE_REGISTER_NO)) {
            $this->CORRECTION_LANE = $FANNIE_REGISTER_NO;
        }
        if (is_numeric($FANNIE_MISC_DEPT)) {
            $this->DEFAULT_DEPT = $FANNIE_MISC_DEPT;
        }

        if (empty($FANNIE_AR_DEPARTMENTS)){
            $this->errors .= '<div class="alert alert-danger">Error: no AR departments found</div>';
            return True;
        }

        if (empty($FANNIE_EQUITY_DEPARTMENTS)){
            $this->errors .= '<div class="alert alert-danger">Error: no Equity departments found</div>';
            return True;
        }

        $ret = preg_match_all("/[0-9]+/",$FANNIE_AR_DEPARTMENTS,$depts);
        if ($ret == 0){
            $this->errors .= '<div class="alert alert-danger">Error: can\'t read AR department definitions</div>';
            return True;
        }
        $temp_depts = array_pop($depts);

        $ret = preg_match_all("/[0-9]+/",$FANNIE_EQUITY_DEPARTMENTS,$depts);
        if ($ret == 0){
            $this->errors .= '<div class="alert alert-danger">Error: can\'t read Equity department definitions</div>';
            return True;
        }
        $temp_depts2 = array_pop($depts);
        foreach($temp_depts2 as $num)
            $temp_depts[] = $num;

        $dlist = "(";
        $dArgs = array();
        foreach ($temp_depts as $d){
            $dlist .= "?,"; 
            $dArgs[] = $d;
        }
        $dlist = substr($dlist,0,strlen($dlist)-1).")";

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $q = $dbc->prepare_statement("SELECT dept_no,dept_name FROM departments WHERE dept_no IN $dlist");
        $r = $dbc->exec_statement($q,$dArgs);
        if ($dbc->num_rows($r) == 0){
            $this->errors .= '<div class="alert alert-danger">Error: department(s) don\'t exist.</div>';
            return true;
        }

        $this->depts = array();
        while($row = $dbc->fetch_row($r)){
            $this->depts[$row[0]] = $row[1];
        }

        if (FormLib::get_form_value('submit1',False) !== False)
            $this->mode = 'confirm';
        elseif (FormLib::get_form_value('submit2',False) !== False)
            $this->mode = 'finish';

        // error check inputs
        if ($this->mode != 'init'){

            $this->dept1 = FormLib::get_form_value('deptFrom');
            $this->dept2 = FormLib::get_form_value('deptTo');
            $this->amount = FormLib::get_form_value('amount');
            $this->cn = FormLib::get_form_value('card_no');

            if (!is_numeric($this->amount)){
                $this->errors .= "<div class=\"alert alert-danger\">Error: amount given (".$this->amount.") isn't a number</div>"
                    ."<br /><br />"
                    ."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
                return True;
            }
            if (!is_numeric($this->cn)){
                $this->errors .= "<div class=\"alert alert-danger\">Error: member given (".$this->cn1.") isn't a number</div>"
                    ."<br /><br />"
                    ."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
                return True;
            }
            if ($this->dept1 == $this->dept2){
                $this->errors .= "<div class=\"alert alert-danger\">Error: departments are the same; nothing to convert</div>"
                    ."<br /><br />"
                    ."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
                return True;
            }

            $q = $dbc->prepare_statement("SELECT FirstName,LastName FROM custdata WHERE CardNo=? AND personNum=1");
            $r = $dbc->exec_statement($q,array($this->cn));
            if ($dbc->num_rows($r) == 0){
                $this->errors .= "<div class=\"alert alert-success\">Error: no such member: ".$this->cn."</div>"
                    ."<br /><br />"
                    ."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
                return True;
            }
            $row = $dbc->fetch_row($r);
            $this->name1 = $row[0].' '.$row[1];
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

    function confirm_content()
    {
        if (!empty($this->errors)) return $this->errors;

        $ret = "<form action=\"MemArEquityDumpTool.php\" method=\"post\">";
        $ret .= "<b>Confirm transactions</b>";
        $ret .= "<div class=\"alert alert-info\">";
        $ret .= sprintf("\$%.2f will be moved from %s to %s for Member #%d (%s)",
            $this->amount,$this->depts[$this->dept1],
            $this->dept2,$this->cn,$this->name1);
        $ret .= "</div><p>";
        $ret .= "<input type=\"hidden\" name=\"deptFrom\" value=\"{$this->dept1}\" />";
        $ret .= "<input type=\"hidden\" name=\"deptTo\" value=\"{$this->dept2}\" />";
        $ret .= "<input type=\"hidden\" name=\"amount\" value=\"{$this->amount}\" />";
        $ret .= "<input type=\"hidden\" name=\"card_no\" value=\"{$this->cn}\" />";
        $ret .= "<input type=\"hidden\" name=\"comment\" value=\"".FormLib::get_form_value('comment')."\" />";
        $ret .= "<button type=\"submit\" name=\"submit2\" value=\"Confirm\" 
                    class=\"btn btn-default\">Confirm</button>";
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $ret .= "<button type=\"buton\" class=\"btn btn-default\" onclick=\"back(); return false;\">Back</button>";
        $ret .= "</form>";
        
        return $ret;
    }

    function finish_content()
    {
        if (!empty($this->errors)) {
            return $this->errors;
        }

        $ret = '';
        
        $trans_no = DTrans::getTransNo($this->connection, $this->CORRECTION_CASHIER, $this->CORRECTION_LANE);
        $params = array(
            'card_no' => $this->cn,
            'register_no' => $this->CORRECTION_LANE,
            'emp_no' => $this->CORRECTION_CASHIER,
        );
        DTrans::addOpenRing($this->connection, $this->dept1, -1*$this->amount, $trans_no, $params);
        DTrans::addOpenRing($this->connection, $this->dept2, $this->amount, $trans_no, $params);

        $comment = FormLib::get_form_value('comment');
        if (!empty($comment)) {
            $params = array(
                'description' => $comment,
                'trans_type' => 'C',
                'trans_subtype' => 'CM',
                'card_no' => $this->cn,
                'register_no' => $this->CORRECTION_LANE,
                'emp_no' => $this->CORRECTION_CASHIER,
            );
            DTrans::addItem($this->connection, $trans_no, $params);
        }

        $ret .= sprintf("Receipt #1: %s",$this->CORRECTION_CASHIER.'-'.$this->CORRECTION_LANE.'-'.$trans_no);

        return $ret;
    }

    function form_content()
    {
        if (!empty($this->errors)) return $this->errors;

        ob_start();
        ?>
        <form action="MemArEquityDumpTool.php" method="post">
        <div class="container">
        <div class="row form-group form-inline">
            <label>Remove</label>
            <div class="input-group">
                <span class="input-group-addon">$</span>
                <input type="text" name="amount" class="form-control"
                    required />
            </div>
            <label>From</label>
            <select name="deptFrom" class="form-control">
            <?php
                foreach($this->depts as $k=>$v)
                    echo "<option value=\"$k\">$v</option>";
            ?>
            </select>
            <label>And add to department #</label>
            <input type="number" name="deptTo" class="form-control" 
                value="<?php echo $this->DEFAULT_DEPT; ?>" required />
        </div>
        <div class="row form-group form-inline">
            <label>Member #</label>
            <input type="number" name="card_no" class="form-control" required />
        </div>
        <div class="row form-group form-inline">
            <label>Comment</label>
            <?php $memNum = FormLib::get_form_value('memIN'); ?>
            <input type="text" name="comment" class="form-control" required
                value="<?php echo $memNum; ?>" />
        </div>
        <p>
            <button type="submit" name="submit1" value="Submit"
                class="btn btn-default">Submit</button>
        </p>
        </div>
        </form>
        <?php

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

?>

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

class MemEquityTransferTool extends FanniePage {

    protected $title='Fannie - Member Management Module';
    protected $header='Transfer Member Equity';

    protected $must_authenticate = true;
    protected $auth_classes =  array('editmembers');

    private $errors = '';
    private $mode = 'init';
    private $depts = array();

    public $description = '[Transfer Equity] moves an Equity payment from one member to another.';
    public $themed = true;

    private $CORRECTION_CASHIER = 1001;
    private $CORRECTION_LANE = 30;
    private $CORRECTION_DEPT = 800;

    private $dept;
    private $amount;
    private $cn1;
    private $cn2;
    private $name1;
    private $name2;

    function preprocess(){
        global $FANNIE_EQUITY_DEPARTMENTS;
        global $FANNIE_OP_DB;
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

        if (empty($FANNIE_EQUITY_DEPARTMENTS)){
            $this->errors .= '<div class="alert alert-danger">Error: no equity departments found</div>';
            return True;
        }

        $ret = preg_match_all("/[0-9]+/",$FANNIE_EQUITY_DEPARTMENTS,$depts);
        if ($ret == 0){
            $this->errors .= '<div class="alert alert-danger">Error: can\'t read equity department definitions</div>';
            return True;
        }
        $temp_depts = array_pop($depts);

        $dlist = "(";
        $dArgs = array();
        foreach ($temp_depts as $d){
            $dlist .= "?,"; 
            $dArgs[] = $d;
        }
        $dlist = substr($dlist,0,strlen($dlist)-1).")";

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $q = $dbc->prepare("SELECT dept_no,dept_name FROM departments WHERE dept_no IN $dlist");
        $r = $dbc->execute($q,$dArgs);
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

            $this->dept = FormLib::get_form_value('dept');
            $this->amount = FormLib::get_form_value('amount');
            $this->cn1 = FormLib::get_form_value('memFrom');
            $this->cn2 = FormLib::get_form_value('memTo');

            if (!isset($this->depts[$this->dept])){
                $this->errors .= "<div class=\"alert alert-danger\">Error: equity department doesn't exist</div>"
                    ."<br /><br />"
                    ."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
                return True;
            }
            if (!is_numeric($this->amount)){
                $this->errors .= "<div class=\"alert alert-danger\">Error: amount given (".$this->amount.") isn't a number</div>"
                    ."<br /><br />"
                    ."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
                return True;
            }
            if (!is_numeric($this->cn1)){
                $this->errors .= "<div class=\"alert alert-danger\">Error: member given (".$this->cn1.") isn't a number</div>"
                    ."<br /><br />"
                    ."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
                return True;
            }
            if (!is_numeric($this->cn2)){
                $this->errors .= "<div class=\"alert alert-danger\">Error: member given (".$this->cn2.") isn't a number</div>"
                    ."<br /><br />"
                    ."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
                return True;
            }

            $account = \COREPOS\Fannie\API\member\MemberREST::get($this->cn1);
            if ($account == false) {
                $this->errors .= "<div class=\"alert alert-success\">Error: no such member: ".$this->cn1."</div>"
                    ."<br /><br />"
                    ."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
                return True;
            }
            foreach ($account['customers'] as $c) {
                if ($c['accountHolder']) {
                    $this->name1 = $c['firstName'] . ' ' . $c['lastName'];
                    break;
                }
            }

            $account = \COREPOS\Fannie\API\member\MemberREST::get($this->cn2);
            if ($account == false) {
                $this->errors .= "<div class=\"alert alert-success\">Error: no such member: ".$this->cn2."</div>"
                    ."<br /><br />"
                    ."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
                return True;
            }
            foreach ($account['customers'] as $c) {
                if ($c['accountHolder']) {
                    $this->name2 = $c['firstName'] . ' ' . $c['lastName'];
                    break;
                }
            }
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

        $ret = "<form action=\"MemEquityTransferTool.php\" method=\"post\">";
        $ret .= "<b>Confirm transfer</b>";
        $ret .= "<div class=\"alert alert-info\">";
        $ret .= sprintf("\$%.2f %s will be moved from %d (%s) to %d (%s)",
            $this->amount,$this->depts[$this->dept],
            $this->cn1,$this->name1,$this->cn2,$this->name2);
        $ret .= "</div><p>";
        $ret .= sprintf('<div class="form-group">
            <label>Comment</label>
            <input type="text" class="form-control" 
                name="correction-comment" value="EQ XFER %d TO %d" />
            </div>',
            $this->cn1, $this->cn2);
        $ret .= "<input type=\"hidden\" name=\"dept\" value=\"{$this->dept}\" />";
        $ret .= "<input type=\"hidden\" name=\"amount\" value=\"{$this->amount}\" />";
        $ret .= "<input type=\"hidden\" name=\"memFrom\" value=\"{$this->cn1}\" />";
        $ret .= "<input type=\"hidden\" name=\"memTo\" value=\"{$this->cn2}\" />";
        $ret .= "<button type=\"submit\" name=\"submit2\" value=\"Confirm\" 
                    class=\"btn btn-default\">Confirm</button>";
        $ret .= "</form>";
        
        return $ret;
    }

    function finish_content(){

        if (!empty($this->errors)) return $this->errors;

        $ret = '';

        $trans_no = DTrans::getTransNo($this->connection, $this->CORRECTION_CASHIER, $this->CORRECTION_LANE);
        $params = array(
            'card_no' => $this->cn1,
            'register_no' => $this->CORRECTION_LANE,
            'emp_no' => $this->CORRECTION_CASHIER,
        );
        DTrans::addOpenRing($this->connection, $this->CORRECTION_DEPT, $this->amount, $trans_no, $params);
        DTrans::addOpenRing($this->connection, $this->dept, -1*$this->amount, $trans_no, $params);
        
        $comment = FormLib::get('correction-comment');
        if (!empty($comment)) {
            $params = array(
                'description' => $comment,
                'trans_type' => 'C',
                'trans_subtype' => 'CM',
                'card_no' => $this->cn1,
                'register_no' => $this->CORRECTION_LANE,
                'emp_no' => $this->CORRECTION_CASHIER,
            );
            DTrans::addItem($this->connection, $trans_no, $params);
        }

        $ret .= sprintf("Receipt #1: %s",$this->CORRECTION_CASHIER.'-'.$this->CORRECTION_LANE.'-'.$trans_no);

        $trans_no = DTrans::getTransNo($this->connection, $this->CORRECTION_CASHIER, $this->CORRECTION_LANE);
        $params = array(
            'card_no' => $this->cn2,
            'register_no' => $this->CORRECTION_LANE,
            'emp_no' => $this->CORRECTION_CASHIER,
        );
        DTrans::addOpenRing($this->connection, $this->dept, $this->amount, $trans_no, $params);
        DTrans::addOpenRing($this->connection, $this->CORRECTION_DEPT, -1*$this->amount, $trans_no, $params);

        if (!empty($comment)) {
            $params = array(
                'description' => $comment,
                'trans_type' => 'C',
                'trans_subtype' => 'CM',
                'card_no' => $this->cn2,
                'register_no' => $this->CORRECTION_LANE,
                'emp_no' => $this->CORRECTION_CASHIER,
            );
            DTrans::addItem($this->connection, $trans_no, $params);
        }

        $ret .= "<br /><br />";
        $ret .= sprintf("Receipt #2: %s",$this->CORRECTION_CASHIER.'-'.$this->CORRECTION_LANE.'-'.$trans_no);

        return $ret;
    }

    function form_content(){

        if (!empty($this->errors)) return $this->errors;

        ob_start();
        ?>
        <form action="MemEquityTransferTool.php" method="post">
        <div class="container">
        <div class="row form-group form-inline">
            <label>Transfer</label>
            <div class="input-group">
                <span class="input-group-addon">$</span>
                <input type="text" name="amount" class="form-control"
                    required />
            </div>
            <select name="dept" class="form-control">
            <?php
            foreach($this->depts as $k=>$v)
                echo "<option value=\"$k\">$v</option>";
            ?>
            </select>
        </div>
        <p>If adjusting to remove an amount from the account, prefix it with '-'</p>
        <?php $memNum = FormLib::get_form_value('memIN') ?>
        <div class="row form-group form-inline">
            <label>From member #</label>
            <input type="number" name="memFrom" class="form-control" required
                value="<?php echo $memNum; ?>" />
            <label>To member #</label>
            <input type="number" name="memTo" class="form-control" required />
        </div>
        <input type="hidden" name="type" value="equity_transfer" />
        <p>
            <button type="submit" name="submit1" value="Submit"
                class="btn btn-default">Submit</button>
        </p>
        </div>
        </form>
        <?php

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>
            Transfer an equity payment from one member account
            to another. Since an equity payment <em>increases</em>
            a member\'s balance, moving $20 from Alice to Bob
            will <em>decrease</em> Alice\'s balance by $20 and
            <em>increase</em> Bob\'s balance by $20.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $this->errors = 'foo';
        $this->mode = 'init';
        $phpunit->assertEquals('foo', $this->body_content());
        $this->errors = '';
        $this->depts = array(1 => 'Dept', 2 => 'Other Dept');
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
        $this->errors = 'foo';
        $this->mode = 'confirm';
        $phpunit->assertEquals('foo', $this->body_content());
        $this->errors = '';
        $this->amount = 1;
        $this->dept = 1;
        $this->cn1 = 1;
        $this->cn2 = 2;
        $this->name1 = 'JoeyJoeJoe';
        $this->name2 = 'JoeyJoeJoe';
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }
}

FannieDispatch::conditionalExec();


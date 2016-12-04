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

class MemArTransferTool extends FannieRESTfulPage 
{

    protected $title='Fannie - Member Management Module';
    protected $header='Transfer A/R';

    public $description = '[Transfer AR] moves an AR payment from one member to another.';

    protected $must_authenticate = true;
    protected $auth_classes =  array('editmembers');

    private $errors = '';
    private $depts = array();

    protected $dept;
    protected $amount;

    protected function getDepartments($ar_depts)
    {
        if (empty($ar_depts)){
            $this->errors .= '<div class="alert alert-danger">Error: no AR departments found</div>';
            return array();
        }

        $ret = preg_match_all("/[0-9]+/",$ar_depts,$depts);
        if ($ret == 0){
            $this->errors .= '<div class="alert alert-danger">Error: can\'t read AR department definitions</div>';
            return array();
        }
        $temp_depts = array_pop($depts);

        $dbc = FannieDB::get($this->config->get('OP_DB'));
        list($dlist, $dArgs) = $dbc->safeInClause($temp_depts);
        $prep = $dbc->prepare("SELECT dept_no,dept_name FROM departments WHERE dept_no IN ($dlist)");
        $res = $dbc->execute($prep,$dArgs);
        if ($dbc->numRows($res) == 0){
            $this->errors .= '<div class="alert alert-danger">Error: department(s) don\'t exist.</div>';
        }
        $ret = array();
        while ($row = $dbc->fetchRow($res)) {
            $ret[$row[0]] = $row[1];
        }

        return $ret;
    }

    public function preprocess()
    {
        $this->addRoute('post<dept><amount><memFrom><memTo>','post<dept><amount><memFrom><memTo><confirm>');

        $ar_depts = $this->config->get('AR_DEPARTMENTS');
        $this->depts = $this->getDepartments($ar_depts);

        // error check inputs
        $this->dept = FormLib::get_form_value('dept');
        $this->amount = FormLib::get_form_value('amount');

        if ($this->dept !== '' && !isset($this->depts[$this->dept])){
            $this->errors .= "<div class=\"alert alert-danger\">Error: AR department doesn't exist</div>"
                ."<br /><br />"
                ."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
        }
        if ($this->amount !== '' && !is_numeric($this->amount)){
            $this->errors .= "<div class=\"alert alert-danger\">Error: amount given (".$this->amount.") isn't a number</div>"
                ."<br /><br />"
                ."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
        }

        return parent::preprocess();
    }

    protected function getName($num)
    {
        if (!is_numeric($num)) {
            $this->errors .= "<div class=\"alert alert-danger\">Error: value given (".$num.") isn't a number</div>"
                ."<br /><br />"
                ."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
            return '';
        } else {
            $account = \COREPOS\Fannie\API\member\MemberREST::get($num);
            if ($account == false) {
                $this->errors .= "<div class=\"alert alert-success\">Error: no such member: ".$num."</div>"
                    ."<br /><br />"
                    ."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
                return '';
            }
            foreach ($account['customers'] as $c) {
                if ($c['accountHolder']) {
                    return $c['firstName'] . ' ' . $c['lastName'];
                }
            }
        }

        return '';
    }
    
    protected function post_dept_amount_memFrom_memTo_view()
    {
        if (!empty($this->errors)) return $this->errors;
        $name1 = $this->getName($this->memFrom);
        $name2 = $this->getName($this->memTo);

        $ret = "<form action=\"MemArTransferTool.php\" method=\"post\">";
        $ret .= "<b>Confirm transfer</b>";
        $ret .= "<div class=\"alert alert-info\">";
        $ret .= sprintf("\$%.2f %s will be moved from %d (%s) to %d (%s)",
            $this->amount,$this->depts[$this->dept],
            $this->memFrom,$name1,$this->memTo,$name2);
        $ret .= "</div><p>";
        $ret .= sprintf('<div class="form-group">
            <label>Comment</label>
            <input type="text" class="form-control" 
                name="correction-comment" value="AR XFER %d TO %d" />
            </div>',
            $this->memFrom, $this->memTo);
        $ret .= "<input type=\"hidden\" name=\"dept\" value=\"{$this->dept}\" />";
        $ret .= "<input type=\"hidden\" name=\"amount\" value=\"{$this->amount}\" />";
        $ret .= "<input type=\"hidden\" name=\"memFrom\" value=\"{$this->memFrom}\" />";
        $ret .= "<input type=\"hidden\" name=\"memTo\" value=\"{$this->memTo}\" />";
        $ret .= "<button type=\"submit\" name=\"confirm\" value=\"Confirm\" 
                    class=\"btn btn-default\">Confirm</button>";
        $ret .= "</form>";
        
        return $ret;
    }

    protected function post_dept_amount_memFrom_memTo_confirm_view()
    {
        if (!empty($this->errors)) return $this->errors;

        $ret = '';
        $emp_no = $this->config->get('EMP_NO', 1001);
        $reg_no = $this->config->get('REGISTER_NO', 30);
        $xfer_dept = $this->config->get('CORRECTION_DEPT', 800);

        $trans_no = DTrans::getTransNo($this->connection, $emp_no, $reg_no);
        $params = array(
            'card_no' => $this->memFrom,
            'register_no' => $reg_no,
            'emp_no' => $emp_no,
        );
        DTrans::addOpenRing($this->connection, $xfer_dept, $this->amount, $trans_no, $params);
        DTrans::addOpenRing($this->connection, $this->dept, -1*$this->amount, $trans_no, $params);
        
        $comment = FormLib::get('correction-comment');
        if (!empty($comment)) {
            $params = array(
                'description' => $comment,
                'trans_type' => 'C',
                'trans_subtype' => 'CM',
                'card_no' => $this->memFrom,
                'register_no' => $reg_no,
                'emp_no' => $emp_no,
            );
            DTrans::addItem($this->connection, $trans_no, $params);
        }

        $ret .= sprintf("Receipt #1: %s",$emp_no.'-'.$reg_no.'-'.$trans_no);

        $trans_no = DTrans::getTransNo($this->connection, $emp_no, $reg_no);
        $params = array(
            'card_no' => $this->memTo,
            'register_no' => $reg_no,
            'emp_no' => $emp_no,
        );
        DTrans::addOpenRing($this->connection, $this->dept, $this->amount, $trans_no, $params);
        DTrans::addOpenRing($this->connection, $xfer_dept, -1*$this->amount, $trans_no, $params);

        if (!empty($comment)) {
            $params = array(
                'description' => $comment,
                'trans_type' => 'C',
                'trans_subtype' => 'CM',
                'card_no' => $this->memTo,
                'register_no' => $reg_no,
                'emp_no' => $emp_no,
            );
            DTrans::addItem($this->connection, $trans_no, $params);
        }

        $ret .= "<br /><br />";
        $ret .= sprintf("Receipt #2: %s",$emp_no.'-'.$reg_no.'-'.$trans_no);

        return $ret;
    }

    protected function get_view()
    {
        if (!empty($this->errors)) return $this->errors;

        ob_start();
        ?>
        <form action="MemArTransferTool.php" method="post">
        <div class="container">
        <div class="row form-group form-inline">
            <label>Transfer</label>
            <div class="input-group">
                <span class="input-group-addon">$</span>
                <input type="number" min="-9999" max="9999" step="0.01" 
                    name="amount" class="form-control" required />
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
            Transfer an AR payment from one member account
            to another. Since an AR payment <em>reduces</em>
            a member\'s balance, moving $20 from Alice to Bob
            will <em>increase</em> Alice\'s balance by $20 and
            <em>decrease</em> Bob\'s balance by $20.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $this->errors = 'foo';
        $phpunit->assertEquals('foo', $this->get_view());
        $this->errors = '';
        $this->depts = array(1 => 'Dept', 2 => 'Other Dept');
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->errors = 'foo';
        $phpunit->assertNotEquals(0, strlen($this->post_dept_amount_memFrom_memTo_view()));
        $this->errors = '';
        $this->amount = 1;
        $this->dept = 1;
        $this->memFrom = 1;
        $this->memTo = 1;
        $phpunit->assertNotEquals(0, strlen($this->post_dept_amount_memFrom_memTo_view()));
    }
}

FannieDispatch::conditionalExec();


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

class PatronageTransferTool extends FanniePage {

    protected $title='Fannie - Member Management Module';
    protected $header='Transfer Patronage';

    protected $must_authenticate = true;
    protected $auth_classes =  array('editmembers');

    public $description = '[Transfer Patronage] shifts an entire transaction from one member
    to another.';
    public $themed = true;

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

    function preprocess()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $FANNIE_EMP_NO = $this->config->get('EMP_NO');
        $FANNIE_REGISTER_NO = $this->config->get('REGISTER_NO');
        $FANNIE_CORRECTION_DEPT = $this->config->get('PATRONAGE_DEPT');
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

        if (FormLib::get_form_value('submit1',False) !== false) {
            $this->mode = 'confirm';
        } elseif (FormLib::get_form_value('submit2',False) !== False) {
            $this->mode = 'finish';
        }

        // error check inputs
        if ($this->mode != 'init'){

            $this->date = FormLib::get_form_value('date');
            $this->tn = FormLib::get_form_value('trans_num');
            $this->cn2 = FormLib::get_form_value('memTo');

            if (!is_numeric($this->cn2)){
                $this->errors .= "<div class=\"alert alert-danger\">Error: member given (".$this->cn2.") isn't a number</div>"
                    ."<br /><br />"
                    ."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
                return True;
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

            $dlog = DTransactionsModel::selectDlog($this->date);
            $q = $dbc->prepare("SELECT card_no FROM $dlog WHERE trans_num=? AND
                tdate BETWEEN ? AND ?
                ORDER BY card_no DESC");
            $r = $dbc->execute($q,array($this->tn,$this->date.' 00:00:00',$this->date.' 23:59:59'));
            if ($dbc->num_rows($r) == 0){
                $this->errors .= "<div class=\"alert alert-error\">Error: receipt not found: " . $this->tn . "</div>"
                    ."<br /><br />"
                    ."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
                return True;
            }
            $w = $dbc->fetchRow($r);
            $this->cn1 = is_array($w) ? $w[0] : 0;

            $q = $dbc->prepare("SELECT SUM(CASE WHEN trans_type in ('I','M','D') then total else 0 END)
                FROM $dlog WHERE trans_num=? AND tdate BETWEEN ? AND ?");
            $r = $dbc->execute($q,array($this->tn,$this->date.' 00:00:00',$this->date.' 23:59:59'));
            $w = $dbc->fetchRow($r);
            $this->amt = is_array($w) ? $w[0] : 0;
        }

        return True;
    }
    
    function body_content()
    {
        if ($this->mode == 'init') {
            return $this->form_content();
        } elseif ($this->mode == 'confirm') {
            return $this->confirm_content();
        } elseif ($this->mode == 'finish') {
            return $this->finish_content();
        }
    }

    function confirm_content()
    {
        if (!empty($this->errors)) return $this->errors;

        $ret = "<form action=\"PatronageTransferTool.php\" method=\"post\">";
        $ret .= "<b>Confirm transfer</b>";
        $ret .= "<div class=\"alert alert-info\">";
        $ret .= sprintf("\$%.2f will be moved from %d to %d (%s)",
            $this->amt,$this->cn1,$this->cn2,$this->name2);
        $ret .= "</div><p>";
        $ret .= sprintf('<div class="form-group">
            <label>Comment</label>
            <input type="text" class="form-control" 
                name="correction-comment" value="PAT XFER %d TO %d" />
            </div>',
            $this->cn1, $this->cn2);
        $ret .= "<input type=\"hidden\" name=\"date\" value=\"{$this->date}\" />";
        $ret .= "<input type=\"hidden\" name=\"trans_num\" value=\"{$this->tn}\" />";
        $ret .= "<input type=\"hidden\" name=\"memTo\" value=\"{$this->cn2}\" />";
        $ret .= "<button type=\"submit\" name=\"submit2\" value=\"Confirm\" 
                    class=\"btn btn-default\">Confirm</button>";
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $ret .= "<button type=\"buton\" class=\"btn btn-default\" onclick=\"back(); return false;\">Back</button>";
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
        DTrans::addOpenRing($this->connection, $this->CORRECTION_DEPT, -1*$this->amt, $trans_no, $params);
        
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
        DTrans::addOpenRing($this->connection, $this->CORRECTION_DEPT, $this->amt, $trans_no, $params);

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

        $ret .= '<hr />';

        $ret .= '<a href="../MemCorrectionIndex.php">Home</a>';

        return $ret;
    }

    function form_content(){

        if (!empty($this->errors)) return $this->errors;

        ob_start();
        ?>
        <form action="PatronageTransferTool.php" method="post">
        <div class="container">
        <div class="form-group">
            <label>Date</label>
            <input type="text" id="date" name="date" class="form-control date-field" required />
        </div>
        <div class="form-group">
            <label>Receipt #</label>
            <input type="text" name="trans_num" class="form-control" required />
        </div>
        <div class="form-group">
            <label>To Member #</label>
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
            Transfer patronage from one member to another. This
            is for corrections where the wrong member number was
            applied to a given transaction. The transfer happens
            as a lump sum rather than shifting invidual receipt lines
            from one member to another. Generally this is fine
            but transactions including equity or AR activity need
            additional corrections to account for those specific
            receipt lines.
            </p>
            <p>
            The amount transferred does not include taxes or discounts;
            just the total of the products on the receipt.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $this->errors = 'foo';
        $this->mode = 'init';
        $phpunit->assertEquals('foo', $this->body_content());
        $this->errors = '';
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
        $this->errors = 'foo';
        $this->mode = 'confirm';
        $phpunit->assertEquals('foo', $this->body_content());
        $this->errors = '';
        $this->amt = 1;
        $this->cn1 = 1;
        $this->cn2 = 2;
        $this->name2 = 'JoeyJoeJoe';
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }
}

FannieDispatch::conditionalExec();


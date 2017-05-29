<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

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
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class NewMemberTool extends FanniePage 
{
    public $description = '[New Members] creates a block of new member accounts.';
    protected $title = "Fannie :: Create Members";
    protected $header = "Create Members";
    protected $must_authenticate = True;
    protected $auth_classes = array('memgen');

    private $errors;
    private $mode = 'form';

    function preprocess()
    {
        if (FormLib::get_form_value('createMems',False) !== false) {
            if (!is_numeric(FormLib::get_form_value('memtype'))) {
                $this->errors = "<i>Error: member type wasn't set correctly</i>";   
            } elseif (!is_numeric(FormLib::get_form_value('num'))) {
                $this->errors = "<i>'How Many' needs to be a number</i>";
            } elseif (FormLib::get_form_value('num') <= 0) {
                $this->errors = "<i>'How Many' needs to be positive</i>";
            } else {
                $this->mode = 'results';
            }
        }

        return true;
    }

    function body_content()
    {
        if ($this->mode == 'form') {
            return $this->form_content();
        } elseif ($this->mode == 'results') {
            return $this->results_content();
        }
    }

    function form_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        // inner join so that only types
        // with defaults set up are shown
        $q = $dbc->prepare("SELECT m.memtype,m.memDesc 
            FROM memtype AS m ORDER BY m.memtype");
        $r = $dbc->execute($q);
        $opts = "";
        while($w = $dbc->fetch_row($r)) {
            $opts .= sprintf("<option %s value=%d>%s</option>",
                ($w['memtype'] == 1 ? 'selected' : ''),
                $w['memtype'],$w['memDesc']);
        }

        $ret = '';
        if (!empty($this->errors)) {
            $ret .= '<div class="alert alert-danger well">';
            $ret .= $this->errors;
            $ret .= '</div><br />';
        }

        ob_start();
        ?>
        <form action="NewMemberTool.php" method="get" class="form-horizontal">
        <div class="form-group">
            <label class="col-sm-2">Type</label>
            <div class="col-sm-4">
                <select name="memtype" class="form-control">
                <?php echo $opts; ?>
                </select>
            </div> 
        </div> 
        <div class="form-group">
            <label class="col-sm-2">How Many</label>
            <div class="col-sm-4">
                <input type="number" name="num" value="40" class="form-control" required />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2">Name</label>
            <div class="col-sm-4">
                <input type="text" name="name" value="NEW MEMBER" class="form-control" required />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-5">
                <input type="checkbox" onclick="$('#sdiv').toggle();$('#start').val('')" />
                Specify First Number
            </label>
        </div>
        <div id="sdiv" class="collapse row">
            <label class="col-sm-2">First Number</label>
            <div class="col-sm-4">
                <input type="number" name="start" id="start" class="form-control" value="" />
            </div>
        </div>
        <p>
            <button type="submit" name="createMems" value="Create Members"
                class="btn btn-default">Create Members</button>
        </p>
        </form>
        <?php
        $ret .= ob_get_clean();
    
        return $ret;
    }

    function results_content()
    {
        global $FANNIE_OP_DB, $FANNIE_SERVER_DBMS;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $mtype = FormLib::get_form_value('memtype',0);
        $num = FormLib::get_form_value('num',0);
        $name = FormLib::get_form_value('name','NEW MEMBER');
        $manual_start = FormLib::get_form_value('start', false);
        if (!is_numeric($manual_start)) {
            $manual_start = false;
        }

        $mt = $dbc->tableDefinition('memtype');
        $defaultsQ = $dbc->prepare("SELECT custdataType,discount,staff,ssi from memtype WHERE memtype=?");
        if ($dbc->tableExists('memdefaults') && (!isset($mt['custdataType']) || !isset($mt['discount']) || !isset($mt['staff']) || !isset($mt['ssi']))) {
            $defaultsQ = $dbc->prepare("SELECT cd_type as custdataType,discount,staff,SSI as ssi
                    FROM memdefaults WHERE memtype=?");
        }
        $defaultsR = $dbc->execute($defaultsQ,array($mtype));
        $defaults = $dbc->fetch_row($defaultsR);

        /**
          1Jul2015
          Use FannieREST API calls to create new members
          Not tested yet.
        $json = array(
            'customerTypeID' => $mtype,
            'memberStatus' => $mt['custdataType'],
            'addressFirstLine' => '',
            'addressSecondLine' => '',
            'city' => '',
            'state' => '',
            'zip' => '',
            'contactAllowed' => 1,
            'contactMethod' => 'mail',
            'customers' => array(
                array(
                    'firstName' => '',
                    'lastName' => $name,
                    'phone' => '',
                    'altPhone' => '',
                    'email' => 1,
                    'discount' => $mt['discount'],
                    'staff' => $mt['staff'],
                    'lowIncomeBenefits' => $mt['ssi'],
                ),
            ),
        );

        $start = PHP_INT_MAX;
        $end = 0;
        for ($i=0; $i<$num; $i++) {
            if ($manual_start) {
                $resp = \COREPOS\Fannie\API\member\MemberREST::post($manual_start+$i, $json);
            } else {
                $resp = \COREPOS\Fannie\API\member\MemberREST::post(0, $json);
            }

            if (isset($resp['account']) && $resp['account']['cardNo'] > $end) {
                $end = $resp['account']['cardNo'];
            }
            if (isset($resp['account']) && $resp['account']['cardNo'] < $start) {
                $start = $resp['account']['cardNo'];
            }
        }
        */

        /* going to create memberships
           part of the insert arrays can
           be prepopulated */
        $meminfo = array(
            'last_name'=>"''",
            'first_name'=>"''",
            'othlast_name'=>"''",
            'othfirst_name'=>"''",
            'street'=>"''",
            'city'=>"''",
            'state'=>"''",
            'zip'=>"''",
            'phone'=>"''",
            'email_1'=>"''",
            'email_2'=>"''",
            'ads_OK'=>1
        );

        /* everything's set but the actual member #s */
        $numQ = $dbc->prepare("SELECT MAX(CardNo) FROM custdata");
        if ($FANNIE_SERVER_DBMS == 'MSSQL') {
            $numQ = $dbc->prepare("SELECT MAX(CAST(CardNo AS int)) FROM custdata");
        }
        $numR = $dbc->execute($numQ);
        $start = 1;
        if ($dbc->num_rows($numR) > 0) {
            $numW = $dbc->fetch_row($numR);
            if (!empty($numW[0])) {
                $start = $numW[0]+1;
            }
        }

        if ($manual_start) {
            $start = (int)$manual_start;
        }

        $end = $start + $num - 1;

        $ret = "<b>Starting number</b>: $start<br />";
        $ret .= "<b>Ending number</b>: $end<br />";

        $model = new CustdataModel($dbc);
        $model->personNum(1);
        $model->LastName($name);
        $model->FirstName('');
        $model->CashBack(999.99);
        $model->Balance(0);
        $model->memCoupons(0);
        $model->Discount($defaults['discount']);
        $model->Type($defaults['custdataType']);
        $model->staff($defaults['staff']);
        $model->SSI($defaults['ssi']);
        $model->memType($mtype);
        $meminfo = new MeminfoModel($dbc);

        $chkP = $dbc->prepare('SELECT CardNo FROM custdata WHERE CardNo=?');
        $mdP = $dbc->prepare("INSERT INTO memDates VALUES (?,NULL,NULL)");
        $mcP = $dbc->prepare("INSERT INTO memContact (card_no,pref) VALUES (?,1)");
        $dbc->startTransaction();
        for($i=$start; $i<=$end; $i++) {
            // skip if record already exists
            $chkR = $dbc->execute($chkP,array($i));
            if ($dbc->num_rows($chkR) > 0) {
                continue;
            }

            $model->CardNo($i);
            $model->blueLine($i.' '.$name);
            $model->save();
            $meminfo->card_no($i);
            $meminfo->save();

            $dbc->execute($mdP, array($i));
            $dbc->execute($mcP, array($i));
        }
        $dbc->commitTransaction();

        return $ret;
    }

    public function helpContent()
    {
        return '<p>Create a set of new member accounts. Typically
            accounts are created ahead of time so there are always
            several available, un-assigned accounts. When a person
            signs up for a membership, they are given one of the 
            available account numbers. This approach ensures that 
            first transaction is assigned to the correct membership.</p>
            ';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }
}

FannieDispatch::conditionalExec();


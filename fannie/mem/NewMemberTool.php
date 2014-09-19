<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

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
        $q = $dbc->prepare_statement("SELECT m.memtype,m.memDesc 
            FROM memtype AS m ORDER BY m.memtype");
        $r = $dbc->exec_statement($q);
        $opts = "";
        while($w = $dbc->fetch_row($r)) {
            $opts .= sprintf("<option value=%d>%s</option>",
                $w['memtype'],$w['memDesc']);
        }

        $ret = '';
        if (!empty($this->errors)) {
            $ret .= '<blockquote style="border: solid 1px red; padding: 5px;
                    margin: 5px;">';
            $ret .= $this->errors;
            $ret .= '</blockquote><br />';
        }

        $ret .= "<b>Create New Members</b><br />";
        $ret .= '<form action="NewMemberTool.php" method="get">';
        $ret .= '<b>Type</b>: <select name="memtype">'.$opts.'</select>';
        $ret .= '<br /><br />';
        $ret .= '<b>How Many</b>: <input size="4" type="text" name="num" value="40" />';
        $ret .= '<br /><br />';
        $ret .= '<b>Name</b>: <input type="text" name="name" value="NEW MEMBER" />';
        $ret .= '<br /><br />';
        $ret .= '<input type="checkbox" onclick="$(\'#sdiv\').toggle();$(\'#start\').val(\'\');" /> Specify first number';
        $ret .= '<div id="sdiv" style="display:none;">';
        $ret .= '<b>Start</b>: <input type="text" size="5" name="start" id="start" />';
        $ret .= '</div>';
        $ret .= '<br /><br />';
        $ret .= '<input type="submit" name="createMems" value="Create Members" />';
        $ret .= '</form>';
    
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

        $mt = $dbc->tableDefinition('memtype');
        $defaultsQ = $dbc->prepare_statement("SELECT custdataType,discount,staff,ssi from memtype WHERE memtype=?");
        if ($dbc->tableExists('memdefaults') && (!isset($mt['custdataType']) || !isset($mt['discount']) || !isset($mt['staff']) || !isset($mt['ssi']))) {
            $defaultsQ = $dbc->prepare_statement("SELECT cd_type as custdataType,discount,staff,SSI as ssi
                    FROM memdefaults WHERE memtype=?");
        }
        $defaultsR = $dbc->exec_statement($defaultsQ,array($mtype));
        $defaults = $dbc->fetch_row($defaultsR);

        /* everything's set but the actual member #s */
        $numQ = $dbc->prepare_statement("SELECT MAX(CardNo) FROM custdata");
        if ($FANNIE_SERVER_DBMS == 'MSSQL') {
            $numQ = $dbc->prepare_statement("SELECT MAX(CAST(CardNo AS int)) FROM custdata");
        }
        $numR = $dbc->exec_statement($numQ);
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

        $chkP = $dbc->prepare_statement('SELECT CardNo FROM custdata WHERE CardNo=?');
        $mdP = $dbc->prepare_statement("INSERT INTO memDates VALUES (?,NULL,NULL)");
        $mcP = $dbc->prepare_statement("INSERT INTO memContact (card_no,pref) VALUES (?,1)");
        for($i=$start; $i<=$end; $i++) {
            // skip if record already exists
            $chkR = $dbc->exec_statement($chkP,array($i));
            if ($dbc->num_rows($chkR) > 0) {
                continue;
            }

            $model->CardNo($i);
            $model->blueLine($i.' '.$name);
            $model->save();
            MeminfoModel::update($i, array());
            $dbc->exec_statement($mdP, array($i));
            $dbc->exec_statement($mcP, array($i));
        }

        return $ret;
    }
}

FannieDispatch::conditionalExec(false);


<?php 
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op, Duluth, MN

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

class MemStatusEditor extends FanniePage {
    protected $header = "Customer Status";
    protected $title = "Fannie :: Customer Status";
    protected $must_authenticate = True;
    public $description = '[Member Status] alters an account\'s active status.';
    protected $auth_classes =  array('editmembers');

    private $cardno;

    function preprocess()
    {
        $this->cardno = FormLib::get_form_value('memID',False);

        if (FormLib::get_form_value('savebtn',False) !== False) {
            $reason = 0;
            $codes = FormLib::get_form_value('rcode',array());
            $type = FormLib::get_form_value('type','INACT');
            if (is_array($codes)) {
                foreach($codes as $r)
                    $reason = $reason | $r;
            }

            if ($reason == 0) {
                $this->reactivate_account($this->cardno);
            } else {
                $this->deactivate_account($this->cardno, $reason, $type);
            }
        
            header("Location: MemberEditor.php?memNum=".$this->cardno);

            return false;
        }

        return true;
    }

    function body_content()
    {
        global $FANNIE_OP_DB;

        if ($this->cardno === false) {
            return '<i>Error - no member specified</i>';
        }

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $ret = sprintf('<h3>Account #%d</h3>',$this->cardno);
        $ret .=  '<form action="MemStatusEditor.php" method="post">';
        $ret .= sprintf('<input type="hidden" value="%d" name="memID" />',$this->cardno);

        $model = new CustdataModel($dbc);
        $model->CardNo($this->cardno);
        $model->personNum(1);
        $model->load();
        $status_string = $model->Type();

        $reasonQ = $dbc->prepare_statement("SELECT textStr,mask,
            CASE WHEN cardno IS NULL THEN 0 ELSE 1 END as checked
            FROM reasoncodes AS r LEFT JOIN suspensions AS s
            ON s.cardno=? AND r.mask & s.reasoncode <> 0
            ORDER BY mask");
        $reasonR = $dbc->exec_statement($reasonQ,array($this->cardno));
        $ret .= '<table cellpadding="4" cellspacing="0" border="1">';
        $ret .= '<tr><td colspan="2">Mode <select name="type">';
        $ret .= '<option value="INACT">Inactive</option>';
        $ret .= '<option value="TERM" '.($status_string=='TERM'?'selected':'').'>Terminated</option>';
        $ret .= '</select></td></tr>';
        while($reasonW = $dbc->fetch_row($reasonR)) {
            $ret .= sprintf('<tr><td><input type="checkbox" name="rcode[]" value="%d" %s</td>
                <td>%s</td></tr>',
                $reasonW['mask'],
                ($reasonW['checked']==1?'checked':''),
                $reasonW['textStr']
            );
        }
        $ret .= '</table><br />';
        $ret .= '<input type="submit" value="Save" name="savebtn" />';
        $ret .= '</form>';

        return $ret;
    }

    protected function reactivate_account($cardno)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        // fetch stored values
        $valQ = $dbc->prepare_statement("SELECT memtype1,memtype2,mailflag,discount,chargelimit
            FROM suspensions WHERE cardno=?");
        $valR = $dbc->exec_statement($valQ,array($cardno));
        $valW = $dbc->fetch_row($valR);

        // restore stored values
        $model = new CustdataModel($dbc);
        $model->CardNo($cardno);
        foreach($model->find() as $obj) {
            $obj->Type($valW['memtype2']);
            $obj->memType($valW['memtype1']);
            $obj->Discount($valW['discount']);
            $obj->MemDiscountLimit($valW['chargelimit']);
            $obj->ChargeLimit($valW['chargelimit']);
            $obj->save();
        }

        $mailQ = $dbc->prepare_statement("UPDATE meminfo SET ads_OK=? WHERE card_no=?");
        $mailR = $dbc->exec_statement($mailQ,array($valW['mailflag'],$cardno));

        // remove suspension and log action to history
        $delQ = $dbc->prepare_statement("DELETE FROM suspensions WHERE cardno=?");
        $delR = $dbc->exec_statement($delQ,$cardno);

        $username = $this->current_user;
        $now = date('Y-m-d h:i:s');
        $histQ = $dbc->prepare_statement("INSERT INTO suspension_history (username, postdate,
            post, cardno, reasoncode) VALUES (?,".$dbc->now().",'Account reactivated',?,-1)");
        $histR = $dbc->exec_statement($histQ,array($username,$cardno));
    }

    protected function deactivate_account($cardno, $reason, $type)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $cas_model = new CustomerAccountSuspensionsModel($dbc);
        $cas_model->card_no($cardno);
        $current_id = 0;

        $chkQ = $dbc->prepare_statement("SELECT cardno FROM suspensions WHERE cardno=?");
        $chkR = $dbc->exec_statement($chkQ,array($cardno));
        if ($dbc->num_rows($chkR)>0) {
            // if account is already suspended, just update the reason
            $upQ = $dbc->prepare_statement("UPDATE suspensions SET reasoncode=?, type=?
                WHERE cardno=?");
            $upR = $dbc->exec_statement($upQ,array($reason,substr($type,0,1),$cardno));

            $m_status = 0;
            if (substr($type, 0, 1) == 'T') {
                $m_status = 2;
            } else {
                $m_status = 1;
            }
            $cas_model->active(1);
            $changed = false;
            // find most recent active record
            $current = $cas_model->find('tdate', true);
            foreach($current as $obj) {
                if ($obj->reasonCode() != $reason || $obj->suspensionTypeID() != $m_status) {
                    $changed = true;
                }
                $cas_model->savedType($obj->savedType());
                $cas_model->savedMemType($obj->savedMemType());
                $cas_model->savedDiscount($obj->savedDiscount());
                $cas_model->savedChargeLimit($obj->savedChargeLimit());
                $cas_model->savedMailFlag($obj->savedMailFlag());
                // copy "saved" values from current active
                // suspension record. should only be one
                break;
            }

            // only add a record if something changed.
            // count($current) of zero means there is no
            // record. once the migration to the new data
            // structure is complete, that check won't
            // be necessary
            if ($changed || count($current) == 0) {
                $cas_model->reasonCode($reason);
                $cas_model->username($this->current_user);
                $cas_model->tdate(date('Y-m-d H:i:s'));
                $model->suspensionTypeID($m_status);

                $current_id = $model->save();
            }

        } else {
            // new suspension
            // get current values and save them in suspensions table

            $model = new CustdataModel($dbc);
            $model->CardNo($cardno);
            $model->personNum(1);
            $model->load();
            $limit = $model->ChargeLimit();
            if ($limit == 0) {
                $limit = $model->MemDiscountLimit();
            }

            $meminfo = new MeminfoModel($dbc);
            $meminfo->card_no($cardno);
            $meminfo->load();

            $now = date('Y-m-d H:i:s');
            $insQ = $dbc->prepare_statement("INSERT INTO suspensions (cardno, type, memtype1,
                memtype2, reason, suspDate, mailflag, discount, chargelimit,
                reasoncode) VALUES (?,?,?,?,'',".$dbc->now().",?,?,?,?)");
            $insR = $dbc->exec_statement($insQ,array($cardno, substr($type,0,1), 
                    $model->memType(),$model->Type(), $meminfo->ads_OK(),
                    $model->Discount(),$limit,$reason));

            // log action
            $username = $this->current_user;
            $histQ = $dbc->prepare_statement("INSERT INTO suspension_history (username, postdate,
                post, cardno, reasoncode) VALUES (?,".$dbc->now().",'',?,?)");
            $histR = $dbc->exec_statement($histQ,array($username,$cardno,$reason));

            $cas_model->savedType($model->Type());
            $cas_model->savedMemType($model->memType());
            $cas_model->savedDiscount($model->Discount());
            $cas_model->savedChargeLimit($model->ChargeLimit());
            $cas_model->savedMailFlag($meminfo->ads_OK());
            $cas_model->suspensionTypeID( substr($type, 0, 1) == 'T' ? 2 : 1 );
            $cas_model->tdate(date('Y-m-d H:i:s'));
            $cas_model->username($this->current_user);
            $cas_mode->reasonCode($reason);
            $cas_model->active(1);
            $current_id = $cas_model->save();
        }

        // remove account privileges in custdata
        $model = new CustdataModel($dbc);
        $model->CardNo($cardno);
        foreach($model->find() as $obj) {
            $obj->Type($type);
            $obj->memType(0);
            $obj->Discount(0);
            $obj->MemDiscountLimit(0);
            $obj->ChargeLimit(0);
            $obj->save();
        }

        $model = new MeminfoModel($dbc);
        $model->card_no($cardno);
        $model->ads_OK(0);
        $model->save();

        // only one CustomerAccountSuspensions record should be active
        if ($current_id != 0) {
            $cas_model->reset();
            $cas_model->card_no($cardno);
            $cas_model->active(1);
            foreach($cas_model->find() as $obj) {
                if ($obj->customerAccountSuspensionID() != $current_id) {
                    $obj->active(0);
                    $obj->save();
                }
            }
        }
    }
}

FannieDispatch::conditionalExec(false);

?>

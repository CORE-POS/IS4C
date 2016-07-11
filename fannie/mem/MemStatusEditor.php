<?php 
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op, Duluth, MN

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

class MemStatusEditor extends FanniePage {
    protected $header = "Customer Status";
    protected $title = "Fannie :: Customer Status";
    public $description = '[Member Status] alters an account\'s active status.';
    public $themed = true;
    protected $must_authenticate = true;
    protected $auth_classes =  array('editmembers');

    private $cardno = false;

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
            return '<div class="alert alert-danger">Error - no member specified</div>';
        }

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $ret = sprintf('<h3>Account #%d</h3>',$this->cardno);
        $ret .=  '<form action="MemStatusEditor.php" method="post">';
        $ret .= sprintf('<input type="hidden" value="%d" name="memID" />',$this->cardno);

        $account = \COREPOS\Fannie\API\member\MemberREST::get($this->cardno);
        $status_string = $account['activeStatus'];

        $reasonQ = $dbc->prepare("SELECT textStr,mask,
            CASE WHEN cardno IS NULL THEN 0 ELSE 1 END as checked
            FROM reasoncodes AS r LEFT JOIN suspensions AS s
            ON s.cardno=? AND r.mask & s.reasoncode <> 0
            ORDER BY mask");
        $reasonR = $dbc->execute($reasonQ,array($this->cardno));
        $ret .= '<div class="form-group form-inline"><label>Mode</label> <select name="type" class="form-control">';
        $ret .= '<option value="INACT">Inactive</option>';
        $ret .= '<option value="TERM" '.($status_string=='TERM'?'selected':'').'>Terminated</option>';
        $ret .= '</select></div>';
        $ret .= '<div class="panel panel-default">';
        $ret .= '<div class="panel-heading">Reasons(s)</div>
                <div class="panel-body">';
        while($reasonW = $dbc->fetch_row($reasonR)) {
            $ret .= sprintf('
                <div class="form-group">
                    <label><input type="checkbox" name="rcode[]" value="%d" %s />
                        %s</label>
                </div>',
                $reasonW['mask'],
                ($reasonW['checked']==1?'checked':''),
                $reasonW['textStr']
            );
        }
        $ret .= '</div>'; // end panel-body
        $ret .= '</div>'; // end panel
        $ret .= '<p><button type="submit" value="1" name="savebtn"
                    class="btn btn-default">Save</button></p>';
        $ret .= '</form>';

        return $ret;
    }

    protected function reactivate_account($cardno)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $account = \COREPOS\Fannie\API\member\MemberREST::get($cardno);

        // fetch stored values
        $valQ = $dbc->prepare("SELECT memtype1,memtype2,mailflag,discount,chargelimit
            FROM suspensions WHERE cardno=?");
        $valR = $dbc->execute($valQ,array($cardno));
        $valW = $dbc->fetch_row($valR);

        $account['activeStatus'] = '';
        $account['memberStatus'] = $valW['memtype2'];
        $account['customerTypeID'] = $valW['memtype1'];
        $account['chargeLimit'] = $valW['chargelimit'];
        $account['contactAllowed'] = $valW['mailflag'];
        for ($i=0; $i<count($account['customers']); $i++) {
            $account['customers'][$i]['discount'] = $valW['discount'];
        }

        \COREPOS\Fannie\API\member\MemberREST::post($cardno, $account);

        // remove suspension and log action to history
        $delQ = $dbc->prepare("DELETE FROM suspensions WHERE cardno=?");
        $delR = $dbc->execute($delQ,$cardno);

        $username = $this->current_user;
        $now = date('Y-m-d h:i:s');
        $histQ = $dbc->prepare("INSERT INTO suspension_history (username, postdate,
            post, cardno, reasoncode) VALUES (?,".$dbc->now().",'Account reactivated',?,-1)");
        $histR = $dbc->execute($histQ,array($username,$cardno));
    }

    protected function deactivate_account($cardno, $reason, $type)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $account = \COREPOS\Fannie\API\member\MemberREST::get($cardno);

        $cas_model = new CustomerAccountSuspensionsModel($dbc);
        $cas_model->card_no($cardno);
        $current_id = 0;

        $chkQ = $dbc->prepare("SELECT cardno FROM suspensions WHERE cardno=?");
        $chkR = $dbc->execute($chkQ,array($cardno));
        if ($dbc->num_rows($chkR)>0) {
            // if account is already suspended, just update the reason
            $upQ = $dbc->prepare("UPDATE suspensions SET reasoncode=?, type=?
                WHERE cardno=?");
            $upR = $dbc->execute($upQ,array($reason,substr($type,0,1),$cardno));

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
                $cas_model->suspensionTypeID($m_status);

                $current_id = $cas_model->save();
            }

        } else {
            // new suspension
            // get current values and save them in suspensions table

            $discount = 0;
            foreach ($account['customers'] as $c) {
                if ($c['accountHolder']) {
                    $discount = $c['discount'];
                    break;
                }
            }

            $now = date('Y-m-d H:i:s');
            $insQ = $dbc->prepare(
                "INSERT INTO suspensions 
                (cardno, type, memtype1, memtype2, reason, suspDate, mailflag, discount, chargelimit, reasoncode) 
                VALUES (?,?,?,?,'',".$dbc->now().",?,?,?,?)"
            );
            $insArgs = array(
                $cardno,
                substr($type, 0, 1),
                $account['customerTypeID'],
                $account['memberStatus'],
                $account['contactAllowed'],
                $discount,
                $account['chargeLimit'],
                $reason
            );
            $insR = $dbc->execute($insQ, $insArgs);

            // log action
            $username = $this->current_user;
            $histQ = $dbc->prepare("INSERT INTO suspension_history (username, postdate,
                post, cardno, reasoncode) VALUES (?,".$dbc->now().",'',?,?)");
            $histR = $dbc->execute($histQ,array($username,$cardno,$reason));

            $cas_model->savedType($account['memberStatus']);
            $cas_model->savedMemType($account['customerTypeID']);
            $cas_model->savedDiscount($discount);
            $cas_model->savedChargeLimit($account['chargeLimit']);
            $cas_model->savedMailFlag($account['contactAllowed']);
            $cas_model->suspensionTypeID( substr($type, 0, 1) == 'T' ? 2 : 1 );
            $cas_model->tdate(date('Y-m-d H:i:s'));
            $cas_model->username($this->current_user);
            $cas_model->reasonCode($reason);
            $cas_model->active(1);
            $current_id = $cas_model->save();
        }

        /**
          Clear privileges and save the account
        */
        $account['activeStatus'] = $type;
        $account['customerTypeID'] = 0;
        $account['chargeLimit'] = 0;
        $account['contactAllowed'] = 0;
        for ($i=0; $i<count($account['customers']); $i++) {
            $account['customers'][$i]['discount'] = 0;
        }
        \COREPOS\Fannie\API\member\MemberREST::post($cardno, $account);

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

    public function helpContent()
    {
        return '<p>
            Change a member\'s status to inactive or
            terminated. Inactive accounts are considered
            temporarily suspended but may be reactivated later.
            Terminated accounts are permanently closed.
            </p>
            <p>
            To deactivate a member, choose Inactive/Termed
            and check one or more reasons. To reactivate an account,
            simply clear all the reason checkboxes.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
        $this->cardno = 1;
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }
}

FannieDispatch::conditionalExec();


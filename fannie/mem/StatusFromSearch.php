<?php

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class StatusFromSearch extends FannieRESTfulPage 
{
    protected $title = 'Change Account Status';
    protected $header = 'Change Account Status';
    public $discoverable = false;
    protected $must_authenticate = true;
    protected $auth_classes = array('editmembers');

    protected function post_view()
    {
        $codes = FormLib::get('rc');
        $code = 0;
        foreach ($codes as $c) {
            $code = $code | ((int)$c);
        }
        $accts = FormLib::get('update');
        $stat = FormLib::get('status');
        foreach ($accts as $id) {
            $this->connection->startTransaction();
            $cas_model = new CustomerAccountSuspensionsModel($this->connection);
            $cas_model->card_no($id);
            $current_id = 0;
            $account = \COREPOS\Fannie\API\member\MemberREST::get($id);
            $susp = new SuspensionsModel($this->connection);
            $susp->cardno($id);
            $susp = $susp->load() ? $susp : false;
            if ($code == 0) {
                // reactivate account
                $this->activateAccount($id, $account, $cas_model, $susp);
            } elseif ($susp !== false) {
                // account already suspended
                $current_id = $this->reSuspendAccount($id, $account, $cas_model, $susp, $code, $stat);
            } else {
                // account not currently suspended
                $current_id = $this->suspendAccount($id, $account, $cas_model, $code, $stat);
            }
            // only one CustomerAccountSuspensions record should be active
            if ($current_id != 0) {
                $cas_model->reset();
                $cas_model->card_no($id);
                $cas_model->active(1);
                foreach($cas_model->find() as $obj) {
                    if ($obj->customerAccountSuspensionID() != $current_id) {
                        $obj->active(0);
                        $obj->save();
                    }
                }
            }
            $this->connection->commitTransaction();
        }

        return <<<HTML
<div class="alert alert-success">Accounts' status updated</div>
HTML;
    }

    private function activateAccount($id, $account, $cas_model)
    {
        // log activation into history
        $history = new SuspensionHistoryModel($this->connection);
        $history->username($this->current_user);
        $history->cardno($id);
        $history->reasoncode(-1);
        $history->post('Account reactivated');
        $history->postdate(date('Y-m-d H:i:s'));
        $history->save();

        // update CAS record
        $cas_model->reasonCode(0);
        $cas_model->suspensionTypeID(0);
        $cas_model->active(0);
        $cas_model->username($this->current_user);
        $cas_model->tdate(date('Y-m-d H:i:s'));
        $cas_model->save();

        // if suspended, restore settings via suspensions record
        // then delete it
        if ($susp !== false) {

            $json = array(
                'cardNo' => $this->id,
                'activeStatus' => '',
                'memberStatus' => $susp->memtype2(),
                'customerTypeID' => $susp->memtype1(),
                'chargeLimit' => $susp->chargelimit(),
                'contactAllowed' => $susp->mailflag(),
                'customers' => array()
            );
            foreach ($account['customers'] as $c) {
                $c['discount'] = $susp->discount();
                $c['chargeAllowed'] = 1;
                $json['customers'][] = $c;
            }
            \COREPOS\Fannie\API\member\MemberREST::post($id, $json);

            $susp->delete();
        }
    }

    private function reSuspendAccount($id, $account, $cas_model, $susp, $code, $status)
    {
        $m_status = 0;
        if ($status == 'TERM') {
            $susp->type('T');
            $m_status = 2;
        } else {
            $susp->type('I');
            $m_status = 1;
        }
        $susp->reasoncode($code);
        $susp->suspDate(date('Y-m-d H:i:s'));
        $susp->save();

        $history = new SuspensionHistoryModel($this->connection);
        $history->username($this->current_user);
        $history->cardno($id);
        $history->reasoncode($code);
        $history->postdate(date('Y-m-d H:i:s'));
        $history->save();

        $changed = false;
        $cas_model->active(1);
        // find most recent active record
        $current = $cas_model->find('tdate', true);
        foreach($current as $obj) {
            if ($obj->reasonCode() != $code || $obj->suspensionTypeID() != $m_status) {
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
        $current_id = 0;
        if ($changed || count($current) == 0) {
            $cas_model->reasonCode($code);
            $cas_model->username($this->current_user);
            $cas_model->tdate(date('Y-m-d H:i:s'));
            $cas_model->suspensionTypeID($m_status);

            $current_id = $cas_model->save();
        }

        $json = array(
            'cardNo' => $id,
            'activeStatus' => $status,
        );
        \COREPOS\Fannie\API\member\MemberREST::post($id, $json);

        return $current_id;
    }

    private function suspendAccount($id, $account, $cas_model, $code, $status)
    {
        $discount = 0;
        foreach ($account['customers'] as $c) {
            if ($c['accountHolder']) {
                $discount = $c['discount'];
                break;
            }
        }
        
        $susp = new SuspensionsModel($this->connection);
        $susp->cardno($id);
        $susp->type( $status == 'TERM' ? 'T' : 'I' );           
        $susp->memtype1($account['customerTypeID']);
        $susp->memtype2($account['memberStatus']);
        $susp->suspDate(date('Y-m-d H:i:s'));
        $susp->reason('');
        $susp->mailflag($account['contactAllowed']);
        $susp->discount($discount);
        $susp->chargelimit($account['chargeLimit']);
        $susp->reasoncode($code);
        $susp->save();

        $cas_model->savedType($account['memberStatus']);
        $cas_model->savedMemType($account['customerTypeID']);
        $cas_model->savedDiscount($discount);
        $cas_model->savedChargeLimit($account['chargeLimit']);
        $cas_model->savedMailFlag($account['contactAllowed']);
        $cas_model->suspensionTypeID( $status == 'TERM' ? 2 : 1 );
        $cas_model->tdate(date('Y-m-d H:i:s'));
        $cas_model->username($this->current_user);
        $cas_model->reasonCode($code);
        $cas_model->active(1);
        $current_id = $cas_model->save();

        $history = new SuspensionHistoryModel($this->connection);
        $history->username($this->current_user);
        $history->cardno($id);
        $history->reasoncode($code);
        $history->postdate(date('Y-m-d H:i:s'));
        $history->save();

        $json = array(
            'cardNo' => $id,
            'chargeLimit' => 0,
            'activeStatus' => $status,
            'customerTypeID' => 0,
            'contactAllowed' => 0,
            'customers' => array(),
        );
        foreach ($account['customers'] as $c) {
            $c['discount'] = 0;
            $json['customers'][] = $c;
        }
        \COREPOS\Fannie\API\member\MemberREST::post($id, $json);

        return $current_id;
    }

    protected function post_id_view()
    {
        $acctList = implode(", ", $this->id);

        $ids = "";
        foreach ($this->id as $id) {
            $ids .= sprintf('<input type="hidden" name="update[]" value="%d" />', $id);
        }

        $stats = array('INACT'=>'Inactive','TERM'=>'Termed','INACT2'=>'Term pending');
        $statOpts = '';
        foreach ($stats as $k => $v) {
            $statOpts .= sprintf('<option value="%s">%s</option>', $k, $v);
        }

        $reasons = new ReasoncodesModel($this->connection);
        $reasonChecks = '';
        foreach ($reasons->find('mask') as $r) {
            $reasonChecks .= sprintf('<label><input type="checkbox" name="rc[]" value="%d" /> %s</label><br />',
                $r->mask(), $r->textStr());
        }

    return <<<HTML
<form method="post">
    {$ids}
    <p><b>Update Status for Accounts</b>: {$acctList}</p>
    <div class="form-group">
        <label>Status</label>
        <select name="status" class="form-control">
            {$statOpts}
        </select>
    </div>
    <div class="form-group">
        <label>Reason(s)</label>
        <fieldset>
        {$reasonChecks}
        </fieldset>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Update</button>
    </div>
</form>
HTML;

    }
}

FannieDispatch::conditionalExec();

